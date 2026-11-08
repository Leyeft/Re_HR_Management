<?php

namespace Tests\Feature\Api;

use App\Models\ActivityLog;
use App\Models\Holiday;
use App\Models\User;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Laravel\Sanctum\Sanctum;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Tests\TestCase;

/**
 * Bulk import of public holidays from CSV / Excel (each year's list), the template,
 * and "clear all".
 */
class HolidayImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));
    }

    private function csv(string $content, string $name = 'holidays.csv'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($name, $content);
    }

    private function xlsx(array $rows): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'test') . '.xlsx';
        $writer = new XlsxWriter();
        $writer->openToFile($path);
        foreach ($rows as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        return new UploadedFile($path, 'holidays.xlsx', null, null, true);
    }

    private function upload(UploadedFile $file, bool $dryRun = false)
    {
        return $this->post('/api/holidays/import', ['file' => $file, 'dry_run' => $dryRun ? '1' : '0'], ['Accept' => 'application/json']);
    }

    private function names(): array
    {
        return Holiday::orderBy('date')->get()->mapWithKeys(fn ($h) => [$h->date->format('Y-m-d') => $h->name])->all();
    }

    /* ===== Reading the file ===== */

    public function test_csv_preview_checks_rows_without_saving(): void
    {
        $this->upload($this->csv("date,name\n2027-01-01,New Year's Day\n2027-06-15,Anniversary\n"), dryRun: true)
            ->assertOk()
            ->assertJsonPath('data.summary.new', 2)
            ->assertJsonPath('data.rows.0.row', 2)
            ->assertJsonPath('data.rows.0.status', 'new');

        $this->assertSame(0, Holiday::count());
    }

    public function test_csv_import_adds_the_holidays(): void
    {
        $this->upload($this->csv("date,name\n2027-01-01,New Year's Day\n2027-06-15,Anniversary\n"))
            ->assertOk()
            ->assertJsonPath('data.summary.added', 2)
            ->assertJsonPath('message', 'Holidays imported: added 2.');

        $this->assertSame(['2027-01-01' => "New Year's Day", '2027-06-15' => 'Anniversary'], $this->names());
    }

    public function test_excel_file_with_real_date_cells_is_imported(): void
    {
        $file = $this->xlsx([
            ['Date', 'Name'], // any capitalisation
            [new DateTimeImmutable('2027-12-25'), 'Christmas Day'],
            ['2027-04-13', 'Songkran'],
        ]);

        $this->upload($file)->assertOk()->assertJsonPath('data.summary.added', 2);

        $this->assertSame(['2027-04-13' => 'Songkran', '2027-12-25' => 'Christmas Day'], $this->names());
    }

    public function test_day_month_year_and_semicolon_csv_are_understood(): void
    {
        $this->upload($this->csv("date;name\n25/12/2027;Christmas Day\n5/1/2027;Holiday\n"), dryRun: true)
            ->assertJsonPath('data.rows.0.date', '2027-12-25')
            ->assertJsonPath('data.rows.1.date', '2027-01-05')
            ->assertJsonPath('data.summary.new', 2);
    }

    public function test_an_old_template_with_a_recurring_column_still_works(): void
    {
        // Extra columns are ignored.
        $this->upload($this->csv("date,name,recurring\n2027-01-01,New Year,yes\n"))
            ->assertOk()->assertJsonPath('data.summary.added', 1);
    }

    /* ===== Same date = replaced; other dates kept ===== */

    public function test_second_upload_replaces_same_dates_adds_new_ones_and_keeps_the_rest(): void
    {
        // 1st file: January to April.
        $this->upload($this->csv("date,name\n2027-01-01,New Year\n2027-04-06,Chakri Day\n2027-04-13,Songkran\n"))->assertOk();

        // 2nd file: April to May — 13 April renamed, 1 May new.
        $preview = $this->upload($this->csv("date,name\n2027-04-13,Songkran Festival\n2027-05-01,Labour Day\n"), dryRun: true)
            ->assertJsonPath('data.summary.new', 1)
            ->assertJsonPath('data.summary.replace', 1)
            ->assertJsonPath('data.rows.0.status', 'replace')
            ->assertJsonPath('data.rows.0.message', 'Replaces “Songkran”.');
        $this->assertSame('Songkran', $this->names()['2027-04-13']); // preview changes nothing

        $this->upload($this->csv("date,name\n2027-04-13,Songkran Festival\n2027-05-01,Labour Day\n"))
            ->assertOk()
            ->assertJsonPath('message', 'Holidays imported: added 1, replaced 1.');

        $this->assertSame([
            '2027-01-01' => 'New Year',          // kept (not in the 2nd file)
            '2027-04-06' => 'Chakri Day',        // kept (same month, different day)
            '2027-04-13' => 'Songkran Festival', // replaced (same day)
            '2027-05-01' => 'Labour Day',        // added
        ], $this->names());
    }

    public function test_uploading_the_same_file_twice_changes_nothing(): void
    {
        $file = "date,name\n2027-01-01,New Year\n";
        $this->upload($this->csv($file))->assertOk();

        $this->upload($this->csv($file))
            ->assertOk()
            ->assertJsonPath('data.summary.unchanged', 1)
            ->assertJsonPath('message', 'Nothing to change — every holiday in the file is already saved.');

        $this->assertSame(1, Holiday::count());
    }

    public function test_duplicate_and_bad_rows_are_reported_and_skipped(): void
    {
        $response = $this->upload($this->csv(implode("\n", [
            'date,name',
            '2027-03-01,Good one',
            '2027-03-01,Same date again', // duplicate in file
            '31/02/2027,Bad date',        // not a real date
            '2027-05-01,',                // no name
        ])))->assertOk();

        $response->assertJsonPath('data.summary.added', 1)
            ->assertJsonPath('data.summary.duplicate', 1)
            ->assertJsonPath('data.summary.error', 2)
            ->assertJsonPath('data.rows.1.message', 'Same date as row 2 in this file.')
            ->assertJsonPath('data.rows.3.message', 'Name is missing.');

        $this->assertSame(['2027-03-01' => 'Good one'], $this->names());
    }

    /* ===== Refused files ===== */

    public function test_missing_headings_are_explained(): void
    {
        $this->upload($this->csv("2027-01-01,New Year\n2027-02-01,Other\n"))
            ->assertStatus(422)->assertJsonPath('code', 'missing_headings');
    }

    public function test_empty_file_and_wrong_file_type_are_refused(): void
    {
        $this->upload($this->csv("date,name\n"))->assertStatus(422)->assertJsonPath('code', 'empty_file');

        $this->upload(UploadedFile::fake()->create('holidays.pdf', 10, 'application/pdf'))
            ->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_too_many_rows_are_refused(): void
    {
        $lines = ['date,name'];
        foreach (range(1, 501) as $i) {
            $lines[] = now()->addDays($i)->format('Y-m-d') . ",Day {$i}";
        }

        $this->upload($this->csv(implode("\n", $lines)))->assertStatus(422)->assertJsonPath('code', 'too_many_rows');
    }

    public function test_regular_employee_cannot_import(): void
    {
        Sanctum::actingAs($this->makeEmployee()->user);

        $this->upload($this->csv("date,name\n2027-01-01,New Year\n"))->assertForbidden();
        $this->assertSame(0, Holiday::count());
    }

    /* ===== Clear all ===== */

    public function test_clear_all_removes_every_holiday_with_one_log_line(): void
    {
        $this->upload($this->csv("date,name\n2026-01-01,New Year\n2027-01-01,New Year\n2027-04-13,Songkran\n"))->assertOk();

        $this->deleteJson('/api/holidays')
            ->assertOk()
            ->assertJsonPath('message', 'Removed all 3 holidays.')
            ->assertJsonPath('data.deleted', 3);

        $this->assertSame(0, Holiday::count());
        $this->assertDatabaseHas('activity_logs', ['action' => 'deleted', 'description' => 'Cleared all 3 public holidays']);
        $this->assertSame(0, ActivityLog::where('subject_type', 'Holiday')->where('action', 'deleted')->count());
    }

    public function test_cleared_holidays_count_as_working_days_again(): void
    {
        $monday = $this->nextWeekday(1);
        Holiday::create(['date' => $monday->toDateString(), 'name' => 'Holiday']);
        $this->assertSame(0, \App\Models\LeaveRequest::workingDaysBetween($monday, $monday));

        $this->deleteJson('/api/holidays')->assertOk();

        $this->assertSame(1, \App\Models\LeaveRequest::workingDaysBetween($monday, $monday));
    }

    public function test_regular_employee_cannot_clear_holidays(): void
    {
        Holiday::create(['date' => '2027-01-01', 'name' => 'New Year']);
        Sanctum::actingAs($this->makeEmployee()->user);

        $this->deleteJson('/api/holidays')->assertForbidden();
        $this->assertSame(1, Holiday::count());
    }

    /* ===== Template ===== */

    public function test_templates_download_and_can_be_imported_as_they_are(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'hr']));

        $csv = $this->get('/templates/holidays.csv')->assertOk()->assertDownload('crewly-holidays-template.csv');
        $content = file_get_contents($csv->getFile()->getPathname());
        $this->assertStringContainsString('date,name', $content);
        $this->assertStringNotContainsString('recurring', $content);

        $xlsx = $this->get('/templates/holidays.xlsx')->assertOk()->assertDownload('crewly-holidays-template.xlsx');
        $copy = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        copy($xlsx->getFile()->getPathname(), $copy);

        $reader = new XlsxReader();
        $reader->open($copy);
        foreach ($reader->getSheetIterator() as $sheet) {
            $this->assertSame('Holidays', $sheet->getName());
            break;
        }
        $reader->close();

        // The template's example rows import cleanly.
        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));
        $this->upload(new UploadedFile($copy, 'template.xlsx', null, null, true))
            ->assertOk()->assertJsonPath('data.summary.added', 4);
    }

    public function test_template_needs_admin_or_hr(): void
    {
        // Start signed out (setUp signed in an HR user for the API tests).
        $this->app['auth']->forgetGuards();
        $this->app['auth']->shouldUse('web');

        $this->get('/templates/holidays.xlsx')->assertRedirect(route('login'));

        $this->actingAs($this->makeEmployee()->user)->get('/templates/holidays.csv')->assertForbidden();
    }
}
