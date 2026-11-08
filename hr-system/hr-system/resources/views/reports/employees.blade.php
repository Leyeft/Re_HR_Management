@php use App\Http\Controllers\ReportController as R; @endphp
@extends('reports.layout')

@section('title', 'Employee Report')

@section('content')
    <h1>Employee Report</h1>
    <div class="filters">{{ $filterSummary }} · {{ $employees->count() }} {{ Str::plural('employee', $employees->count()) }} · leave figures for {{ now()->year }}</div>

    <table class="data">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Department</th>
                <th>Role</th>
                <th>Rank</th>
                <th>Hired</th>
                <th class="num-col">Years</th>
                <th class="num-col">Annual allowance</th>
                <th class="num-col">Used</th>
                <th class="num-col">Pending</th>
                <th class="num-col">Left</th>
                <th class="num-col">Sick used</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $e)
                @php $ls = $e->leave_summary; @endphp
                <tr>
                    <td>{{ $e->employee_code }}</td>
                    <td>{{ $e->user?->name }}</td>
                    <td class="muted">{{ $e->user?->email }}</td>
                    <td>{{ $e->department?->name }}</td>
                    <td>{{ R::ROLE_LABELS[$e->user?->role] ?? $e->user?->role }}</td>
                    <td>{{ $e->rank === 'head' ? 'Head' : 'Employee' }}</td>
                    <td>{{ $e->date_hired?->format('d M Y') }}</td>
                    <td class="num-col">{{ $ls['years_of_service'] }}</td>
                    <td class="num-col">
                        {{ $ls['annual']['total'] }}
                        @if ($ls['annual']['carried_over'])<span class="muted">(+{{ $ls['annual']['carried_over'] }} carried)</span>@endif
                    </td>
                    <td class="num-col">{{ $ls['annual']['taken'] }}</td>
                    <td class="num-col">{{ $ls['annual']['pending'] }}</td>
                    <td class="num-col">{{ $ls['annual']['remaining'] }}</td>
                    <td class="num-col">{{ $ls['sick']['taken'] }}</td>
                </tr>
            @empty
                <tr><td colspan="13" class="empty">No employees match these filters.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
