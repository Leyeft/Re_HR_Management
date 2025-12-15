<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800">
            Dashboard
        </h2>
    </x-slot>

    <div class="py-8 max-w-7xl mx-auto space-y-6">

        {{-- Stats --}}
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white p-6 rounded shadow">
                <h3 class="text-sm text-gray-500">Total Staff</h3>
                <p class="text-3xl font-bold">{{ $totalStaff }}</p>
            </div>

            <div class="bg-white p-6 rounded shadow">
                <h3 class="text-sm text-gray-500">Total Departments</h3>
                <p class="text-3xl font-bold">{{ $totalDepartments }}</p>
            </div>
        </div>

        {{-- Pie Chart --}}
        <div class="bg-white p-6 rounded shadow">
            <h3 class="text-lg font-semibold mb-4">
                Staff by Department
            </h3>

            <div class="max-w-md mx-auto">
                <canvas id="departmentChart"></canvas>
            </div>
        </div>

        {{-- Bar Chart --}}
        <<div class="mt-8 bg-white p-6 rounded shadow">
            <h3 class="text-lg font-semibold mb-4">
                Leave Request Status
            </h3>

            <div class="max-w-md mx-auto">
                <canvas id="leaveStatusChart"></canvas>
        </div>
</div>


    </div>

    {{-- Chart.js --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        // PIE CHART
        const deptCtx = document.getElementById('departmentChart');

        new Chart(deptCtx, {
            type: 'pie',
            data: {
                labels: @json($labels),
                datasets: [{
                    data: @json($data),
                    backgroundColor: [
                        '#4f46e5',
                        '#22c55e',
                        '#f97316',
                        '#ef4444',
                        '#14b8a6',
                        '#eab308',
                    ],
                }]
            }
        });

        // BAR CHART
        const leaveCtx = document.getElementById('leaveStatusChart');

        new Chart(leaveCtx, {
            type: 'bar',
            data: {
                labels: ['Pending', 'Approved', 'Rejected'],
                datasets: [{
                    label: 'Leave Requests',
                    data: [
                        {{ $leaveStats['pending'] }},
                        {{ $leaveStats['approved'] }},
                        {{ $leaveStats['rejected'] }}
                    ],
                    backgroundColor: [
                        '#FACC15',
                        '#22C55E',
                        '#EF4444'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { stepSize: 1 }
                    }
                }
            }
        });
    </script>
</x-app-layout>
