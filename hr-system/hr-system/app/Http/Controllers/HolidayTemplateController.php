<?php

namespace App\Http\Controllers;

use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\CSV\Writer as CsvWriter;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * GET /templates/holidays.{xlsx|csv} — a ready-to-fill file for bulk-importing
 * public holidays (Leave settings → Import). Heading row + a few examples.
 */
class HolidayTemplateController extends Controller
{
    public function __invoke(string $format): BinaryFileResponse
    {
        $year = now()->addYear()->year;
        $examples = [
            ["{$year}-01-01", "New Year's Day"],
            ["{$year}-04-13", 'Songkran Festival'],
            ["{$year}-06-15", 'Company anniversary'],
            ["{$year}-12-25", 'Christmas Day'],
        ];

        $path = tempnam(sys_get_temp_dir(), 'holidays') . ".{$format}";

        if ($format === 'xlsx') {
            $options = new XlsxOptions();
            $options->setColumnWidth(14, 1);
            $options->setColumnWidth(32, 2);

            $writer = new XlsxWriter($options);
            $writer->openToFile($path);
            $writer->getCurrentSheet()->setName('Holidays');

            $heading = (new Style())->withFontBold(true)->withFontColor('FFFFFF')->withBackgroundColor('12305A');
            $writer->addRow(Row::fromValuesWithStyle(['date', 'name'], $heading));
            foreach ($examples as $row) {
                $writer->addRow(Row::fromValues($row));
            }

            // Second sheet: how to fill it in.
            $writer->addNewSheetAndMakeItCurrent()->setName('How to fill in');
            foreach ([
                ['How to fill in the Holidays sheet'],
                [''],
                ['date', 'The holiday date: 2026-12-25 or 25/12/2026 (day/month/year), or an Excel date.'],
                ['name', 'The holiday name, e.g. Christmas Day (max 100 characters).'],
                [''],
                ['Keep the heading row. Replace the example rows with your own. One holiday per row.'],
                ['Upload each year\'s list. A date that is already saved gets the name from your file;'],
                ['other saved dates are kept. You will see a preview before anything is saved.'],
                ['To start a fresh year, use "Clear all" on the Leave settings page first.'],
            ] as $line) {
                $writer->addRow(Row::fromValues($line));
            }
            $writer->close();
        } else {
            $writer = new CsvWriter();
            $writer->openToFile($path);
            $writer->addRow(Row::fromValues(['date', 'name']));
            foreach ($examples as $row) {
                $writer->addRow(Row::fromValues($row));
            }
            $writer->close();
        }

        return response()->download($path, "crewly-holidays-template.{$format}")->deleteFileAfterSend();
    }
}
