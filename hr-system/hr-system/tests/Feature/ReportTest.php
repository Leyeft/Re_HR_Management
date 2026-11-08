<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    private function hr(): User
    {
        return User::factory()->create(['role' => 'hr']);
    }

    private function leave($employee, array $attrs = []): LeaveRequest
    {
        return LeaveRequest::create($attrs + [
            'employee_id' => $employee->id,
            'department_id' => $employee->department_id,
            'type' => 'annual',
            'start_date' => '2026-11-02',
            'end_date' => '2026-11-04',
            'reason' => 'Holiday',
            'status' => 'approved',
        ]);
    }

    // Parse a streamed CSV download into rows (header first), without the Excel BOM.
    private function csvRows($response): array
    {
        $body = preg_replace('/^\xEF\xBB\xBF/', '', $response->streamedContent());

        return array_map('str_getcsv', array_values(array_filter(explode("\n", trim($body)))));
    }

    /* ===== Access ===== */

    public function test_regular_employee_cannot_export(): void
    {
        $employee = $this->makeEmployee();

        $this->actingAs($employee->user)->get(route('reports.leave', ['format' => 'csv']))->assertForbidden();
        $this->actingAs($employee->user)->get(route('reports.employees', ['format' => 'pdf']))->assertForbidden();
        $this->actingAs($employee->user)->get(route('reports.employee', $employee))->assertForbidden();
    }

    public function test_guest_is_sent_to_login(): void
    {
        $this->get(route('reports.leave', ['format' => 'csv']))->assertRedirect(route('login'));
    }

    public function test_unknown_format_is_not_found(): void
    {
        $this->actingAs($this->hr())->get('/reports/leave.xlsx')->assertNotFound();
    }

    /* ===== Leave report ===== */

    public function test_leave_csv_has_one_row_per_request_with_working_days(): void
    {
        $employee = $this->makeEmployee();
        $this->leave($employee, ['start_date' => '2026-11-06', 'end_date' => '2026-11-09']); // Fri–Mon

        $response = $this->actingAs($this->hr())->get(route('reports.leave', ['format' => 'csv']));

        $response->assertOk()->assertDownload('crewly-leave-report-' . now()->format('Y-m-d') . '.csv');
        $rows = $this->csvRows($response);

        $this->assertSame('Employee ID', $rows[0][0]);
        $this->assertCount(2, $rows); // header + 1
        $this->assertSame($employee->user->name, $rows[1][1]);
        $this->assertSame('2', $rows[1][6]); // working days: weekend skipped
        $this->assertSame('Approved', $rows[1][7]);
    }

    public function test_leave_export_uses_the_page_filters(): void
    {
        $finance = $this->makeEmployee();
        $it = $this->makeEmployee(['department' => Department::create(['name' => 'IT'])]);
        $this->leave($finance, ['status' => 'approved']);
        $this->leave($finance, ['status' => 'rejected', 'start_date' => '2026-12-01', 'end_date' => '2026-12-01']);
        $this->leave($it, ['status' => 'approved']);

        $rows = $this->csvRows($this->actingAs($this->hr())->get(route('reports.leave', [
            'format' => 'csv',
            'department' => $finance->department_id,
            'status' => 'approved',
        ])));

        $this->assertCount(2, $rows); // header + Finance's approved one only
        $this->assertSame($finance->user->name, $rows[1][1]);
    }

    public function test_date_range_keeps_leave_that_overlaps_it(): void
    {
        $employee = $this->makeEmployee();
        $this->leave($employee, ['start_date' => '2026-10-28', 'end_date' => '2026-11-03']); // overlaps
        $this->leave($employee, ['start_date' => '2026-12-07', 'end_date' => '2026-12-08']); // outside

        $rows = $this->csvRows($this->actingAs($this->hr())->get(route('reports.leave', [
            'format' => 'csv', 'from' => '2026-11-01', 'to' => '2026-11-30',
        ])));

        $this->assertCount(2, $rows);
        $this->assertSame('2026-10-28', $rows[1][4]);
    }

    public function test_all_leave_page_applies_the_same_filters(): void
    {
        $employee = $this->makeEmployee();
        $this->leave($employee, ['status' => 'approved']);
        $this->leave($employee, ['status' => 'rejected', 'start_date' => '2026-12-01', 'end_date' => '2026-12-01']);

        // The All Leave page's API uses the same filter as the export.
        $this->actingAs($this->hr())
            ->getJson('/api/hr/leave-requests?status=rejected')
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.status', 'rejected');
    }

    public function test_end_date_before_start_date_is_rejected(): void
    {
        $this->actingAs($this->hr())
            ->get(route('reports.leave', ['format' => 'csv', 'from' => '2026-11-10', 'to' => '2026-11-01']))
            ->assertSessionHasErrors('to');
    }

    public function test_formula_text_is_neutralised_in_csv(): void
    {
        // A reason typed as a spreadsheet formula must not run when opened in Excel.
        $this->leave($this->makeEmployee(), ['reason' => '=HYPERLINK("http://evil.test")']);

        $rows = $this->csvRows($this->actingAs($this->hr())->get(route('reports.leave', ['format' => 'csv'])));

        $this->assertSame("'=HYPERLINK(\"http://evil.test\")", $rows[1][8]);
    }

    public function test_leave_pdf_downloads(): void
    {
        $this->leave($this->makeEmployee());

        $response = $this->actingAs($this->hr())->get(route('reports.leave', ['format' => 'pdf']));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /* ===== Employee reports ===== */

    public function test_employees_csv_uses_the_page_filters(): void
    {
        $head = $this->makeEmployee(['rank' => 'head']);
        $this->makeEmployee(); // regular employee, filtered out

        $rows = $this->csvRows($this->actingAs($this->hr())->get(route('reports.employees', [
            'format' => 'csv', 'rank' => 'head',
        ])));

        $this->assertCount(2, $rows);
        $this->assertSame((string) $head->employee_code, $rows[1][0]);
        $this->assertSame('Head of Department', $rows[1][5]);
    }

    public function test_employees_pdf_downloads(): void
    {
        $this->makeEmployee();

        $response = $this->actingAs($this->hr())->get(route('reports.employees', ['format' => 'pdf']));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_single_employee_leave_record_pdf_downloads(): void
    {
        $employee = $this->makeEmployee();
        $this->leave($employee);

        $response = $this->actingAs($this->hr())->get(route('reports.employee', $employee));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringContainsString(
            'crewly-leave-record-' . $employee->employee_code,
            $response->headers->get('content-disposition'),
        );
    }
}
