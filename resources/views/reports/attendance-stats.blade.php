<x-app-layout>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Attendance Statistics</h1>
        <a href="{{ route('reports.index') }}" class="text-indigo-600 hover:underline">&larr; Back to Reports</a>
    </div>

    <!-- Teacher Filtering Notice -->
    @if(auth()->user()->hasRole('Teacher'))
        <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6 rounded-r-lg">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-blue-700">
                        <span class="font-medium">Filtered View:</span> You're viewing statistics for
                        @php
                            $primaryCount = auth()->user()->getPrimaryStudentIds();
                        @endphp
                        <span class="font-semibold">{{ count($primaryCount) }} primary {{ Str::plural('student', count($primaryCount)) }}</span>
                        assigned to you. Students where you only have attendance permission are not included in this report.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Filters Card -->
    <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
        <form method="GET" action="{{ route('reports.attendance.stats') }}" class="grid grid-cols-1 md:grid-cols-6 gap-4 items-end">
            <!-- Period Selector -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Period</label>
                <select name="period" id="period-select" class="w-full rounded-md border-gray-300 shadow-sm text-sm" onchange="toggleDateFields()">
                    <option value="day" {{ $period === 'day' ? 'selected' : '' }}>Today</option>
                    <option value="week" {{ $period === 'week' ? 'selected' : '' }}>This Week</option>
                    <option value="month" {{ $period === 'month' ? 'selected' : '' }}>This Month</option>
                    <option value="custom" {{ $period === 'custom' ? 'selected' : '' }}>Custom Range</option>
                </select>
            </div>

            <!-- Start Date -->
            <div id="start-date-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <!-- End Date -->
            <div id="end-date-field">
                <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}"
                       class="w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <!-- Student Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Student</label>
                <select name="student_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Students</option>
                    @foreach($students as $student)
                        <option value="{{ $student->id }}" {{ $studentId == $student->id ? 'selected' : '' }}>
                            {{ $student->last_name }}, {{ $student->first_name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Class Filter -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Class</label>
                <select name="class_id" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="">All Classes</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" {{ (int)$classId === $class->id ? 'selected' : '' }}>
                            {{ $class->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Sort Option -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Sort By</label>
                <select name="sort" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                    <option value="name" {{ ($sort ?? 'name') === 'name' ? 'selected' : '' }}>Name (A-Z)</option>
                    <option value="percentage_desc" {{ ($sort ?? '') === 'percentage_desc' ? 'selected' : '' }}>Best Attendance (High to Low)</option>
                    <option value="percentage_asc" {{ ($sort ?? '') === 'percentage_asc' ? 'selected' : '' }}>Worst Attendance (Low to High)</option>
                </select>
            </div>

            <!-- Submit Button -->
            <div class="md:col-span-6">
                <button type="submit" class="w-full md:w-auto px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Generate Report
                </button>
            </div>
        </form>
    </div>

    <!-- Date Range Display -->
    <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-3 mb-6 text-center">
        <span class="text-indigo-800 font-medium">
            {{ $startDate->format('M d, Y') }} &mdash; {{ $endDate->format('M d, Y') }}
        </span>
    </div>

    <!-- Results -->
    @if($studentId && isset($statsData['student']))
        <!-- Single Student Report -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden mb-6">
            <div class="p-4 border-b bg-gray-50 flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-800">
                    {{ $statsData['student']->first_name }} {{ $statsData['student']->last_name }}
                </h2>
                @php
                    // Use pre-calculated aggregate percentage from controller
                    // This matches the calculation method used in all-students view
                    $overallPct = $statsData['overall_percentage'] ?? 0;
                @endphp
                <span class="px-3 py-1 rounded-full text-lg font-bold {{ $overallPct >= 90 ? 'bg-green-100 text-green-800' : ($overallPct >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                    {{ $overallPct }}% Overall
                </span>
            </div>

            <!-- Daily History Grid (like the image) -->
            @if($historyData && count($dates) > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-3 py-2 text-left font-medium text-gray-700 sticky left-0 bg-gray-50 min-w-[160px]">Class</th>
                            <th class="px-2 py-2 text-center font-medium text-gray-500 text-xs">Stats</th>
                            @foreach($dates as $date)
                                <th class="px-1 py-2 text-center font-medium text-gray-500 text-xs whitespace-nowrap" style="writing-mode: vertical-lr; height: 70px;">
                                    {{ $date->format('M d') }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($historyData as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-3 py-2 font-medium text-gray-900 sticky left-0 bg-white">
                                    {{ $row['class']->name }}
                                </td>
                                <td class="px-2 py-2">
                                    <div class="flex gap-1 text-xs">
                                        <span class="px-1.5 py-0.5 rounded bg-green-100 text-green-700">{{ $row['totals']['present'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-orange-100 text-orange-700">{{ $row['totals']['late'] }}</span>
                                        <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-700">{{ $row['totals']['absent'] }}</span>
                                    </div>
                                </td>
                                @foreach($dates as $date)
                                    @php
                                        $dateKey = $date->format('Y-m-d');
                                        $att = $row['attendances'][$dateKey] ?? null;
                                        $cancelledKey = $row['class']->id . '-' . $dateKey;
                                        $isCancelled = isset($cancelledSessionKeys) && isset($cancelledSessionKeys[$cancelledKey]);

                                        // Check if student is on permission for this class (class-based)
                                        $isOnPermission = false;
                                        if (isset($studentPermissions) && $studentPermissions->isNotEmpty()) {
                                            // Build ordered class IDs for this day (sorted by start time)
                                            $dayOfWeek = $date->dayOfWeek;
                                            $orderedClassIds = collect($historyData)
                                                ->pluck('class')
                                                ->filter(fn($c) => $c->schedules->contains(fn($s) => $s->day_of_week === $dayOfWeek))
                                                ->sortBy(fn($c) => $c->schedules->first(fn($s) => $s->day_of_week === $dayOfWeek)?->start_time ?? '23:59')
                                                ->pluck('id')
                                                ->map(fn($id) => (int) $id)
                                                ->values()
                                                ->toArray();

                                            // Check if any permission covers this class
                                            foreach ($studentPermissions as $perm) {
                                                if ($perm->coversClass($date, (int) $row['class']->id, $orderedClassIds)) {
                                                    $isOnPermission = true;
                                                    break;
                                                }
                                            }
                                        }
                                    @endphp
                                    <td class="px-1 py-2 text-center">
                                        @if($isCancelled)
                                            <span class="text-gray-900 font-bold text-sm" title="Class Cancelled (not counted)">C</span>
                                        @elseif($isOnPermission)
                                            <span class="text-gray-900 font-bold text-sm" title="On Permission (not counted)">C</span>
                                        @else
                                        <span class="relative inline-block">
                                            <button type="button"
                                                    onclick="openEditModal({{ $statsData['student']->id }}, {{ $row['class']->id }}, '{{ $dateKey }}', '{{ $att?->status ?? 'unmarked' }}', {{ $att?->minutes_late ?? 0 }}, '{{ $row['class']->name }}', '{{ $date->format('M d') }}')"
                                                    class="hover:bg-gray-100 rounded p-1 transition cursor-pointer"
                                                    title="Click to edit">
                                            @if($att)
                                                @if($att->status === 'absent_excused')
                                                    <span class="inline-block w-4 h-4 rounded-full bg-yellow-400 border-2 border-yellow-500" title="Absent Excused (not counted)"></span>
                                                @elseif($att->status === 'late_excused')
                                                    <span class="relative inline-block">
                                                        <span class="text-orange-500 text-xs font-medium">{{ $att->minutes_late ?: 'L' }}</span>
                                                        <span class="absolute -top-1 -right-1 w-2 h-2 rounded-full bg-yellow-400 border border-yellow-500" title="Late Excused (adjusted)"></span>
                                                    </span>
                                                @elseif(str_starts_with($att->status, 'present'))
                                                    <span class="text-green-600 text-lg">&#10003;</span>
                                                @elseif(str_starts_with($att->status, 'late'))
                                                    <span class="text-orange-500 text-xs font-medium">{{ $att->minutes_late ?: 'L' }}</span>
                                                @elseif(str_starts_with($att->status, 'absent'))
                                                    <span class="text-red-500 text-lg">&#10007;</span>
                                                @endif
                                            @else
                                                <span class="text-gray-300">-</span>
                                            @endif
                                            </button>
                                            @if($att?->notes)
                                                <button type="button"
                                                        onclick="event.stopPropagation(); showAttendanceNote('{{ addslashes($row['class']->name) }}', '{{ $date->format('M d') }}', '{{ addslashes($att->notes) }}')"
                                                        class="absolute -top-1 -right-1 w-3 h-3 bg-blue-500 text-white rounded-full text-[8px] flex items-center justify-center hover:bg-blue-600"
                                                        title="Has note - click to view">
                                                    📝
                                                </button>
                                            @endif
                                        </span>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif

            <!-- Summary Table -->
            <div class="border-t">
                <div class="p-3 bg-gray-50 text-sm font-medium text-gray-600">Summary by Class</div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sessions</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-green-600 uppercase">Present</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-orange-600 uppercase">Late</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-red-600 uppercase">Absent</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Min Missed</th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-indigo-600 uppercase">%</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @php
                            $totalMinutesMissed = collect($statsData['classes'])->sum(function($classStats) {
                                return $classStats['total_minutes_missed'] ?? $classStats['total_minutes_late'] ?? 0;
                            });
                            $totalSessions = collect($statsData['classes'])->sum('total_sessions');
                            $totalPresent = collect($statsData['classes'])->sum('present');
                            $totalLate = collect($statsData['classes'])->sum('late');
                            $totalAbsent = collect($statsData['classes'])->sum('absent');
                            @endphp
                            @forelse($statsData['classes'] as $classStats)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $classStats['class_name'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center">{{ $classStats['total_sessions'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-green-600 font-medium">{{ $classStats['present'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-orange-600 font-medium">{{ $classStats['late'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-red-600 font-medium">{{ $classStats['absent'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $classStats['total_minutes_missed'] ?? $classStats['total_minutes_late'] }}</td>
                                    <td class="px-4 py-3 text-sm text-center">
                                        <span class="px-2 py-1 rounded-full text-sm font-bold
                                            {{ $classStats['percentage'] >= 90 ? 'bg-green-100 text-green-800' :
                                               ($classStats['percentage'] >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                            {{ $classStats['percentage'] }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No attendance data for this period</td></tr>
                            @endforelse

                            @if(!empty($statsData['classes']))
                            <!-- Total Row -->
                            <tr class="bg-gray-100 font-bold border-t-2 border-gray-300">
                                <td class="px-4 py-3 text-sm">TOTAL</td>
                                <td class="px-4 py-3 text-sm text-center">{{ $totalSessions }}</td>
                                <td class="px-4 py-3 text-sm text-center text-green-600">{{ $totalPresent }}</td>
                                <td class="px-4 py-3 text-sm text-center text-orange-600">{{ $totalLate }}</td>
                                <td class="px-4 py-3 text-sm text-center text-red-600">{{ $totalAbsent }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $totalMinutesMissed }}</td>
                                <td class="px-4 py-3 text-sm text-center">-</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($classId)
        <!-- Single Class Report - All Students -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h2 class="text-lg font-bold text-gray-800">{{ $classes->firstWhere('id', $classId)?->name ?? 'Class' }} - All Students</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sessions</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-green-600 uppercase">Present</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-orange-600 uppercase">Late</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-red-600 uppercase">Absent</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Min Missed</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-indigo-600 uppercase">Attendance %</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($statsData as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $item['student']->last_name }}, {{ $item['student']->first_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">{{ $item['stats']['total_sessions'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-green-600 font-medium">{{ $item['stats']['present'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-orange-600 font-medium">{{ $item['stats']['late'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-red-600 font-medium">{{ $item['stats']['absent'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $item['stats']['total_minutes_missed'] ?? $item['stats']['total_minutes_late'] }}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 rounded-full text-sm font-bold
                                        {{ $item['stats']['percentage'] >= 90 ? 'bg-green-100 text-green-800' :
                                           ($item['stats']['percentage'] >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $item['stats']['percentage'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No attendance data for this period</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @else
        <!-- All Students Summary -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="p-4 border-b bg-gray-50">
                <h2 class="text-lg font-bold text-gray-800">All Students - Overall Attendance</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Sessions</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-green-600 uppercase">Present</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-orange-600 uppercase">Late</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-red-600 uppercase">Absent</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase">Min Missed</th>
                            <th class="px-4 py-3 text-center text-xs font-medium text-indigo-600 uppercase">Attendance %</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($statsData as $item)
                            <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('reports.attendance.stats', ['student_id' => $item['student']->id, 'period' => $period, 'start_date' => $startDate->format('Y-m-d'), 'end_date' => $endDate->format('Y-m-d')]) }}'">
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                    {{ $item['student']->last_name }}, {{ $item['student']->first_name }}
                                </td>
                                <td class="px-4 py-3 text-sm text-center">{{ $item['total_sessions'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-green-600 font-medium">{{ $item['present'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-orange-600 font-medium">{{ $item['late'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-red-600 font-medium">{{ $item['absent'] }}</td>
                                <td class="px-4 py-3 text-sm text-center text-gray-600">{{ $item['total_minutes_missed'] ?? 0 }}</td>
                                <td class="px-4 py-3 text-sm text-center">
                                    <span class="px-2 py-1 rounded-full text-sm font-bold
                                        {{ $item['percentage'] >= 90 ? 'bg-green-100 text-green-800' :
                                           ($item['percentage'] >= 75 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                                        {{ $item['percentage'] }}%
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-4 py-8 text-center text-gray-500">No attendance data for this period</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Edit Attendance Modal -->
<div id="edit-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closeEditModal(event)">
    <div class="bg-white rounded-xl shadow-xl p-5 w-80 max-w-full" onclick="event.stopPropagation()">
        <div class="text-center mb-4">
            <div class="text-lg font-bold text-indigo-600">Edit Attendance</div>
            <div id="edit-context" class="text-sm text-gray-600"></div>
        </div>

        <input type="hidden" id="edit-student-id">
        <input type="hidden" id="edit-class-id">
        <input type="hidden" id="edit-date">

        <!-- Status Selection -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
            <div class="grid grid-cols-2 gap-2">
                <button type="button" onclick="selectStatus('present')" id="btn-present"
                        class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition flex items-center justify-center gap-2 border-gray-200 hover:border-green-500">
                    <span class="text-green-600">&#10003;</span> Present
                </button>
                <button type="button" onclick="selectStatus('late')" id="btn-late"
                        class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition flex items-center justify-center gap-2 border-gray-200 hover:border-orange-500">
                    <span class="text-orange-500">&#9201;</span> Late
                </button>
                <button type="button" onclick="selectStatus('absent')" id="btn-absent"
                        class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition flex items-center justify-center gap-2 border-gray-200 hover:border-red-500">
                    <span class="text-red-500">&#10007;</span> Absent
                </button>
                <button type="button" onclick="selectStatus('unmarked')" id="btn-unmarked"
                        class="px-3 py-2 rounded-lg border-2 text-sm font-medium transition flex items-center justify-center gap-2 border-gray-200 hover:border-gray-500">
                    <span class="text-gray-400">-</span> Unmark
                </button>
            </div>
        </div>

        <!-- Minutes Late (shown only for late) -->
        <div id="minutes-late-section" class="mb-4 hidden">
            <label class="block text-sm font-medium text-gray-700 mb-1">Minutes Late</label>
            <input type="number" id="edit-minutes-late" min="0" max="120" value="0"
                   class="w-full rounded-md border-gray-300 shadow-sm text-sm focus:ring-indigo-500 focus:border-indigo-500">
        </div>

        <!-- Excused Toggle -->
        <div id="excused-section" class="mb-4 hidden">
            <label class="flex items-center justify-between cursor-pointer">
                <span class="text-sm font-medium text-gray-700">Excused?</span>
                <input type="checkbox" id="edit-excused" class="w-5 h-5 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
            </label>
        </div>

        <!-- Buttons -->
        <div class="flex gap-2">
            <button onclick="closeEditModal()" class="flex-1 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
            <button onclick="saveAttendanceEdit()" class="flex-1 px-3 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">Save</button>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg hidden z-50">Saved!</div>

<script>
const csrfToken = '{{ csrf_token() }}';
let currentStatus = 'unmarked';

function toggleDateFields() {
    const period = document.getElementById('period-select').value;
    const startField = document.getElementById('start-date-field');
    const endField = document.getElementById('end-date-field');

    if (period === 'custom') {
        startField.classList.remove('hidden');
        endField.classList.remove('hidden');
    } else if (period === 'day') {
        startField.classList.remove('hidden');
        endField.classList.add('hidden');
    } else {
        startField.classList.add('hidden');
        endField.classList.add('hidden');
    }
}

// Show attendance note in a popup modal
function showAttendanceNote(className, dateLabel, note) {
    const existingModal = document.getElementById('note-view-modal');
    if (existingModal) existingModal.remove();

    const modal = document.createElement('div');
    modal.id = 'note-view-modal';
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
    modal.innerHTML = `
        <div class="bg-white rounded-xl shadow-xl p-5 w-96 max-w-full mx-4" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-800">📝 Note</h3>
                <button onclick="document.getElementById('note-view-modal').remove()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            <div class="text-sm text-gray-500 mb-2">${className} - ${dateLabel}</div>
            <p class="text-gray-700 whitespace-pre-wrap">${note}</p>
            <div class="mt-4 flex justify-end">
                <button onclick="document.getElementById('note-view-modal').remove()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Close</button>
            </div>
        </div>
    `;
    document.body.appendChild(modal);
}

function openEditModal(studentId, classId, date, status, minutesLate, className, dateLabel) {
    document.getElementById('edit-student-id').value = studentId;
    document.getElementById('edit-class-id').value = classId;
    document.getElementById('edit-date').value = date;
    document.getElementById('edit-context').textContent = `${className} - ${dateLabel}`;
    document.getElementById('edit-minutes-late').value = minutesLate || 0;
    document.getElementById('edit-excused').checked = status.includes('excused');

    // Parse base status
    if (status.startsWith('present')) {
        selectStatus('present');
    } else if (status.startsWith('late')) {
        selectStatus('late');
    } else if (status.startsWith('absent')) {
        selectStatus('absent');
    } else {
        selectStatus('unmarked');
    }

    document.getElementById('edit-modal').classList.remove('hidden');
    document.getElementById('edit-modal').classList.add('flex');
}

function closeEditModal(event) {
    if (!event || event.target === document.getElementById('edit-modal')) {
        document.getElementById('edit-modal').classList.add('hidden');
        document.getElementById('edit-modal').classList.remove('flex');
    }
}

function selectStatus(status) {
    currentStatus = status;

    // Reset all button styles
    ['present', 'late', 'absent', 'unmarked'].forEach(s => {
        const btn = document.getElementById('btn-' + s);
        btn.classList.remove('border-green-500', 'border-orange-500', 'border-red-500', 'border-gray-500', 'bg-green-50', 'bg-orange-50', 'bg-red-50', 'bg-gray-100');
        btn.classList.add('border-gray-200');
    });

    // Highlight selected button
    const selectedBtn = document.getElementById('btn-' + status);
    const colors = {
        present: ['border-green-500', 'bg-green-50'],
        late: ['border-orange-500', 'bg-orange-50'],
        absent: ['border-red-500', 'bg-red-50'],
        unmarked: ['border-gray-500', 'bg-gray-100']
    };
    selectedBtn.classList.remove('border-gray-200');
    selectedBtn.classList.add(...colors[status]);

    // Show/hide minutes late section
    const minutesSection = document.getElementById('minutes-late-section');
    if (status === 'late') {
        minutesSection.classList.remove('hidden');
    } else {
        minutesSection.classList.add('hidden');
    }

    // Show/hide excused section
    const excusedSection = document.getElementById('excused-section');
    if (status === 'late' || status === 'absent') {
        excusedSection.classList.remove('hidden');
    } else {
        excusedSection.classList.add('hidden');
    }
}

async function saveAttendanceEdit() {
    const studentId = document.getElementById('edit-student-id').value;
    const classId = document.getElementById('edit-class-id').value;
    const date = document.getElementById('edit-date').value;
    const minutesLate = parseInt(document.getElementById('edit-minutes-late').value) || 0;
    const excused = document.getElementById('edit-excused').checked;

    // Build final status
    let finalStatus = currentStatus;
    if (excused && (currentStatus === 'late' || currentStatus === 'absent')) {
        finalStatus = currentStatus + '_excused';
    }

    try {
        if (currentStatus === 'unmarked') {
            // Delete the attendance record
            const response = await fetch('{{ route("attendance.destroy") }}', {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    school_class_id: classId,
                    date: date
                })
            });

            if (response.ok) {
                showToast('Attendance unmarked');
                closeEditModal();
                // Reload to reflect changes
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast('Error unmarking attendance', true);
            }
        } else {
            // Update or create attendance record
            const response = await fetch('{{ route("attendance.store") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    student_id: studentId,
                    school_class_id: classId,
                    date: date,
                    status: finalStatus,
                    minutes_late: currentStatus === 'late' ? minutesLate : null
                })
            });

            if (response.ok) {
                showToast('Attendance updated');
                closeEditModal();
                // Reload to reflect changes
                setTimeout(() => window.location.reload(), 500);
            } else {
                showToast('Error updating attendance', true);
            }
        }
    } catch (e) {
        console.error('Save error:', e);
        showToast('Error saving', true);
    }
}

function showToast(msg, isError = false) {
    const toast = document.getElementById('toast');
    toast.textContent = msg;
    toast.className = 'fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 ' + (isError ? 'bg-red-600 text-white' : 'bg-green-600 text-white');
    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 2000);
}

// Initial state
toggleDateFields();
</script>
</x-app-layout>
