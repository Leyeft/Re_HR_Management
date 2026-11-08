{{-- PDF: Leave Requests Report (All Leave page export). Data from ReportController@leave. --}}
@php use App\Http\Controllers\ReportController as R; @endphp
@extends('reports.layout')

@section('title', 'Leave Requests Report')

@section('content')
    <h1>Leave Requests Report</h1>
    <div class="filters">{{ $filterSummary }}</div>

    <table class="totals">
        <tr>
            <td><div class="num">{{ $totals['requests'] }}</div>requests</td>
            <td><div class="num">{{ $totals['approvedDays'] }}</div>approved working days</td>
            <td><div class="num">{{ $totals['pending'] }}</div>awaiting approval</td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th>ID</th>
                <th>Employee</th>
                <th>Department</th>
                <th>Type</th>
                <th>Dates</th>
                <th class="num-col">Days</th>
                <th>Status</th>
                <th>Reason</th>
                <th>Signed off by</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($leaves as $leave)
                <tr>
                    <td>{{ $leave->employee?->employee_code }}</td>
                    <td>{{ $leave->employee?->user?->name }}</td>
                    <td>{{ $leave->department?->name }}</td>
                    <td>{{ R::TYPE_LABELS[$leave->type] ?? $leave->type }}</td>
                    <td>{{ $leave->start_date->format('d M Y') }} – {{ $leave->end_date->format('d M Y') }}</td>
                    <td class="num-col">{{ $leave->days() }}</td>
                    <td class="status status-{{ $leave->status }}">
                        {{ R::STATUS_LABELS[$leave->status] ?? $leave->status }}
                        @if ($leave->rejection_reason || $leave->cancellation_reason)
                            <div class="muted" style="font-weight: normal">{{ $leave->rejection_reason ?? $leave->cancellation_reason }}</div>
                        @endif
                    </td>
                    <td>{{ $leave->reason }}</td>
                    <td class="muted">
                        @if ($leave->headApprover) Head: {{ $leave->headApprover->name }}<br>@endif
                        @if ($leave->hrApprover) HR: {{ $leave->hrApprover->name }}@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="empty">No leave requests match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
