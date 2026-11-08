<?php

namespace Tests\Feature\Api;

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
 * Bulk import of public holidays from CSV / Excel, and the downloadable template.
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

    public function test_csv_preview_checks_rows_without_saving(): void
    {
        $this->upload($this->csv("date,name,recurring\n2027-01-01,New Year's Day,yes\n2027-06-15,Anniversary,no\n"), dryRun: true)
            ->assertOk()
            ->assertJsonPath('data.summary.new', 2)
            ->assertJsonPath('data.rows.0.row', 2)
            ->assertJsonPath('data.rows.0.recurring', true)
            ->assertJsonPath('data.rows.1.recurring', false);

        $this->assertSame(0, Holiday::count());
    }

    public function test_csv_import_adds_the_holidays(): void
    {
        $this->upload($this->csv("date,name,recurring\n2027-01-01,New Year's Day,yes\n2027-06-15,Anniversary,\n"))
            ->assertOk()
            ->assertJsonPath('data.summary.imported', 2)
            ->assertJsonPath('message', 'Imported 2 holidays.');

        $this->assertTrue(Holiday::where('name', "New Year's Day")->first()->recurring);
        $this->assertFalse(Holiday::where('name', 'Anniversary')->first()->recurring);
    }

    public function test_excel_file_with_real_date_cells_is_imported(): void
    {
        $file = $this->xlsx([
            ['Date', 'Name', 'Recurring'], // any capitalisation
            [new DateTimeImmutable('2027-12-25'), 'Christmas Day', 'Yes'],
            ['2027-04-13', 'Songkran', 'yes'],
        ]);

        $this->upload($file)->assertOk()->assertJsonPath('data.summary.imported', 2);

        $this->assertTrue(Holiday::where('name', 'Christmas Day')->first()->date->isSameDay('2027-12-25'));
    }

    public function test_day_month_year_and_semicolon_csv_are_understood(): void
    {
        $this->upload($this->csv("date;name;recurring\n25/12/2027;Christmas Day;yes\n5/1/2027;Holiday;no\n"), dryRun: true)
            ->assertJsonPath('data.rows.0.date', '2027-12-25')
            ->assertJsonPath('data.rows.1.date', '2027-01-05')
            ->assertJsonPath('data.summary.new', 2);
    }

    public function test_existing_duplicate_and_bad_rows_are_reported_and_skipped(): void
    {
        Holiday::create(['date' => '2027-01-01', 'name' => 'Already there']);

        $response = $this->upload($this->csv(implode("\n", [
            'date,name,recurring',
            '2027-01-01,New Year,yes',       // exists
            '2027-03-01,Good one,no',        // new
            '2027-03-01,Same date again,no', // duplicate in file
            '31/02/2027,Bad date,no',        // not a real date
            '2027-05-01,,no',                // no name
            '2027-05-02,Odd flag,maybe',     // bad recurring
        ])))->assertOk();

        $response->assertJsonPath('data.summary.imported', 1)
            ->assertJsonPath('data.summary.exists', 1)
            ->assertJsonPath('data.summary.duplicate', 1)
            ->assertJsonPath('data.summary.error', 3)
            ->assertJsonPath('data.rows.2.message', 'Same date as row 3 in this file.')
            ->assertJsonPath('data.rows.4.message', 'Name is missing.');

        $this->assertSame(2, Holiday::count()); // the existing one + "Good one"
    }

    public function test_missing_headings_are_explained(): void
    {
        $this->upload($this->csv("2027-01-01,New Year,yes\n2027-02-01,Other,no\n"))
            ->assertStatus(422)->assertJsonPath('code', 'missing_headings');
    }

    public function test_empty_file_and_wrong_file_type_are_refused(): void
    {
        $this->upload($this->csv("date,name,recurring\n"))->assertStatus(422)->assertJsonPath('code', 'empty_file');

        $this->upload(UploadedFile::fake()->create('holidays.pdf', 10, 'application/pdf'))
            ->assertStatus(422)->assertJsonValidationErrors('file');
    }

    public function test_too_many_rows_are_refused(): void
    {
        $lines = ['date,name,recurring'];
        foreach (range(1, 501) as $i) {
            $lines[] = now()->addDays($i)->format('Y-m-d') . ",Day {$i},no";
        }

        $this->upload($this->csv(implode("\n", $lines)))->assertStatus(422)->assertJsonPath('code', 'too_many_rows');
    }

    public function test_regular_employee_cannot_import(): void
    {
        Sanctum::actingAs($this->makeEmployee()->user);

        $this->upload($this->csv("date,name,recurring\n2027-01-01,New Year,yes\n"))->assertForbidden();
        $this->assertSame(0, Holiday::count());
    }

    public function test_templates_download_and_can_be_imported_as_they_are(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'hr']));

        $csv = $this->get('/templates/holidays.csv')->assertOk()->assertDownload('crewly-holidays-template.csv');
        $this->assertStringContainsString('date,name,recurring', file_get_contents($csv->getFile()->getPathname()));

        $xlsx = $this->get('/templates/holidays.xlsx')->assertOk()->assertDownload('crewly-holidays-template.xlsx');
        $path = $xlsx->getFile()->getPathname();
        $copy = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        copy($path, $copy);

        // The template's example rows import cleanly.
        $reader = new XlsxReader();
        $reader->open($copy);
        foreach ($reader->getSheetIterator() as $sheet) {
            $this->assertSame('Holidays', $sheet->getName());
            break;
        }
        $reader->close();

        Sanctum::actingAs(User::factory()->create(['role' => 'hr']));
        $this->upload(new UploadedFile($copy, 'template.xlsx', null, null, true))
            ->assertOk()->assertJsonPath('data.summary.imported', 4);
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
