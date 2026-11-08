{{-- PDF: one employee's leave record (profile → Download PDF). Data from ReportController@employee. --}}
@php use App\Http\Controllers\ReportController as R; @endphp
@extends('reports.layout')

@section('title', 'Leave Record')

@section('content')
    <h1>Leave Record — {{ $employee->user?->name }}</h1>

    <table class="totals" style="width: 100%">
        <tr>
            <td><span class="muted">Employee ID</span><br><b>{{ $employee->employee_code }}</b></td>
            <td><span class="muted">Department</span><br><b>{{ $employee->department?->name }}</b></td>
            <td><span class="muted">Rank</span><br><b>{{ $employee->rank === 'head' ? 'Head of Department' : 'Employee' }}</b></td>
            <td><span class="muted">Date hired</span><br><b>{{ $employee->date_hired?->format('d M Y') }}</b> ({{ $summary['years_of_service'] }} yrs)</td>
        </tr>
    </table>

    <h1 style="font-size: 12px">Balance for {{ $summary['year'] }}</h1>
    <table class="totals" style="width: 100%">
        <tr>
            <td>
                <div class="num">{{ $summary['annual']['remaining'] }} / {{ $summary['annual']['total'] }}</div>
                annual days left · {{ $summary['annual']['taken'] }} used · {{ $summary['annual']['pending'] }} pending
                @if ($summary['annual']['carried_over'])<br><span class="muted">includes {{ $summary['annual']['carried_over'] }} carried over</span>@endif
            </td>
            <td>
                <div class="num">{{ $summary['sick']['remaining'] }} / {{ $summary['sick']['cap'] }}</div>
                sick days left · {{ $summary['sick']['taken'] }} used
            </td>
            <td>
                <div class="num">{{ $summary['professional']['taken'] }}</div>
                professional days used (no limit)
            </td>
        </tr>
    </table>

    <h1 style="font-size: 12px">Full leave history</h1>
    <table class="data">
        <thead>
            <tr>
                <th>Type</th>
                <th>Dates</th>
                <th class="num-col">Days</th>
                <th>Requested</th>
                <th>Reason</th>
                <th>Status</th>
                <th>Approvals</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaves as $leave)
                <tr>
                    <td>{{ R::TYPE_LABELS[$leave->type] ?? $leave->type }}</td>
                    <td>{{ $leave->start_date->format('d M Y') }} – {{ $leave->end_date->format('d M Y') }}</td>
                    <td class="num-col">{{ $leave->days() }}</td>
                    <td>{{ optional($leave->created_at)->format('d M Y') }}</td>
                    <td>{{ $leave->reason }}</td>
                    <td class="status status-{{ $leave->status }}">
                        {{ R::STATUS_LABELS[$leave->status] ?? $leave->status }}
                        @if ($leave->rejection_reason || $leave->cancellation_reason)
                            <div class="muted" style="font-weight: normal">{{ $leave->rejection_reason ?? $leave->cancellation_reason }}</div>
                        @endif
                    </td>
                    <td class="muted">
                        @if ($leave->headApprover) Head: {{ $leave->headApprover->name }}, {{ $leave->head_approved_at?->format('d M Y') }}<br>@endif
                        @if ($leave->hrApprover) HR: {{ $leave->hrApprover->name }}, {{ $leave->hr_approved_at?->format('d M Y') }}@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="empty">No leave on record.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
