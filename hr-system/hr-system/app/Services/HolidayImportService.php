<?php

namespace App\Services;

use App\Exceptions\BusinessRuleException;
use App\Models\Holiday;
use DateTimeInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use OpenSpout\Reader\CSV\Options as CsvOptions;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use Throwable;

/**
 * Bulk-add public holidays from an Excel (.xlsx) or CSV file — HR uploads each year's list.
 *
 * File layout (see the downloadable template): a heading row, then one holiday per row.
 *   date   2026-12-25, 25/12/2026 (day/month/year) or an Excel date cell
 *   name   e.g. Christmas Day
 *
 * Each row is compared with the holidays already saved:
 *   new        no holiday on that date yet          → added
 *   replace    a holiday on that date, other name   → its name is replaced by the file's
 *   unchanged  same date and same name              → nothing to do
 *   duplicate  the date appears earlier in the file → skipped
 *   error      not a valid row                      → skipped (with the reason)
 * Holidays on dates that are not in the file are kept.
 *
 * preview() checks every row without saving; import() applies "new" and "replace".
 */
class HolidayImportService
{
    public const MAX_ROWS = 500;

    private const DATE_FORMATS = ['Y-m-d', 'd/m/Y', 'j/n/Y', 'd-m-Y', 'j-n-Y', 'Y/m/d', 'd.m.Y'];

    /**
     * @return array{rows: array<int, array>, summary: array<string, int>}
     */
    public function preview(UploadedFile $file): array
    {
        $rows = $this->checkRows($this->readRows($file));

        return ['rows' => $rows, 'summary' => $this->summary($rows)];
    }

    public function import(UploadedFile $file): array
    {
        $result = $this->preview($file);

        DB::transaction(function () use (&$result) {
            foreach ($result['rows'] as &$row) {
                if ($row['status'] === 'new') {
                    Holiday::create(['date' => $row['date'], 'name' => $row['name']]);
                    $row['status'] = 'added';
                } elseif ($row['status'] === 'replace') {
                    Holiday::whereDate('date', $row['date'])->first()?->update(['name' => $row['name']]);
                    $row['status'] = 'replaced';
                }
            }
        });

        $result['summary'] = $this->summary($result['rows']);

        return $result;
    }

    /* ---------- reading ---------- */

    // All non-empty rows as arrays of cell values (heading row first).
    private function readRows(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        try {
            $reader = $extension === 'xlsx'
                ? new XlsxReader()
                : new CsvReader(new CsvOptions(FIELD_DELIMITER: $this->guessDelimiter($file->getRealPath())));

            $reader->open($file->getRealPath());
            $rows = [];
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $values = $row->toArray();
                    $hasContent = collect($values)->contains(fn ($v) => $v instanceof DateTimeInterface || trim((string) $v) !== '');
                    if ($hasContent) {
                        $rows[] = $values;
                    }
                }
                break; // only the first sheet
            }
            $reader->close();
        } catch (Throwable) {
            throw new BusinessRuleException('This file could not be read. Please use the template and save it as .xlsx or .csv.', 'unreadable_file');
        }

        if (count($rows) < 2) {
            throw new BusinessRuleException('The file has no holidays in it. Add one holiday per row under the headings.', 'empty_file');
        }
        if (count($rows) - 1 > self::MAX_ROWS) {
            throw new BusinessRuleException('Please import at most ' . self::MAX_ROWS . ' holidays at a time.', 'too_many_rows');
        }

        return $rows;
    }

    // Excel in some countries saves CSV with ";" instead of ",".
    private function guessDelimiter(string $path): string
    {
        $handle = fopen($path, 'r');
        $firstLine = (string) fgets($handle);
        fclose($handle);

        return substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    }

    /* ---------- checking ---------- */

    private function checkRows(array $raw): array
    {
        $columns = $this->columns(array_shift($raw));

        // date => current name, for everything already saved
        $existing = Holiday::all()->mapWithKeys(fn ($h) => [$h->date->format('Y-m-d') => $h->name])->all();
        $seen = [];
        $out = [];

        foreach ($raw as $i => $cells) {
            $rowNumber = $i + 2; // the heading is row 1, as in Excel
            $rawDate = $cells[$columns['date']] ?? null;
            $name = trim((string) ($cells[$columns['name']] ?? ''));

            $date = $this->parseDate($rawDate);
            $shownDate = $date ?? ($rawDate instanceof DateTimeInterface ? $rawDate->format('Y-m-d') : trim((string) $rawDate));
            $current = $date !== null ? ($existing[$date] ?? null) : null;

            [$status, $message] = match (true) {
                $date === null => ['error', $shownDate === '' ? 'Date is missing.' : "“{$shownDate}” is not a date. Use 2026-12-25 or 25/12/2026."],
                $name === '' => ['error', 'Name is missing.'],
                mb_strlen($name) > 100 => ['error', 'Name is longer than 100 characters.'],
                isset($seen[$date]) => ['duplicate', "Same date as row {$seen[$date]} in this file."],
                $current === null => ['new', null],
                $current === $name => ['unchanged', 'Already saved with this name.'],
                default => ['replace', "Replaces “{$current}”."],
            };

            if ($date !== null && ! isset($seen[$date])) {
                $seen[$date] = $rowNumber;
            }

            $out[] = ['row' => $rowNumber, 'date' => $date ?? $shownDate, 'name' => $name, 'status' => $status, 'message' => $message];
        }

        return $out;
    }

    // Find the date and name columns in the heading row (any order, any case).
    private function columns(array $heading): array
    {
        $map = [];
        foreach ($heading as $index => $title) {
            $key = strtolower(trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $title))); // strip Excel's BOM
            if (in_array($key, ['date', 'name'], true)) {
                $map[$key] = $index;
            }
        }

        if (! isset($map['date'], $map['name'])) {
            throw new BusinessRuleException(
                'The first row must be the headings: date, name. Download the template to see the layout.',
                'missing_headings',
            );
        }

        return $map;
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        // An Excel date saved as a plain number (days since 1899-12-30).
        if (is_numeric($value) && $value > 20000 && $value < 80000) {
            return Carbon::create(1899, 12, 30)->addDays((int) $value)->format('Y-m-d');
        }

        $text = trim((string) $value);
        foreach (self::DATE_FORMATS as $format) {
            $date = \DateTime::createFromFormat('!' . $format, $text);
            $errors = \DateTime::getLastErrors();
            if ($date && (! $errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0)) && $date->format($format) === $text) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }

    private function summary(array $rows): array
    {
        $count = fn (string $status) => count(array_filter($rows, fn ($r) => $r['status'] === $status));

        return [
            'total' => count($rows),
            'new' => $count('new'),
            'replace' => $count('replace'),
            'unchanged' => $count('unchanged'),
            'duplicate' => $count('duplicate'),
            'error' => $count('error'),
            'added' => $count('added'),
            'replaced' => $count('replaced'),
        ];
    }
}
