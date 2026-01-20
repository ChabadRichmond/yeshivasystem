<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Attendance Report') }}</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
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
                                <span class="font-medium">Filtered View:</span> You're viewing attendance records for
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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <!-- Filter Form with Session Dropdown -->
                    <form method="GET" action="{{ route('reports.attendance') }}" class="flex flex-wrap items-end gap-4 mb-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Session</label>
                            <select name="class_id" class="mt-1 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">All Sessions</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->id }}" {{ (int)$classId === $class->id ? 'selected' : '' }}>
                                        {{ $class->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="start_date" value="{{ $startDate }}" class="mt-1 rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Date</label>
                            <input type="date" name="end_date" value="{{ $endDate }}" class="mt-1 rounded-md border-gray-300 shadow-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Sort By</label>
                            <select name="sort" class="mt-1 rounded-md border-gray-300 shadow-sm">
                                <option value="name" {{ ($sort ?? 'name') === 'name' ? 'selected' : '' }}>Name (A-Z)</option>
                                <option value="name_desc" {{ ($sort ?? '') === 'name_desc' ? 'selected' : '' }}>Name (Z-A)</option>
                                <option value="percentage_desc" {{ ($sort ?? '') === 'percentage_desc' ? 'selected' : '' }}>Best Attendance (High to Low)</option>
                                <option value="percentage_asc" {{ ($sort ?? '') === 'percentage_asc' ? 'selected' : '' }}>Worst Attendance (Low to High)</option>
                            </select>
                        </div>
                        <div>
                            <button type="submit" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Filter</button>
                        </div>
                        <div class="ml-auto flex gap-2">
                            <a href="{{ route('reports.attendance.export', ['start_date' => $startDate, 'end_date' => $endDate, 'class_id' => $classId]) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Export CSV</a>
                            <a href="{{ route('attendance.index') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Mark Attendance</a>
                        </div>
                    </form>

                    <!-- Stats Summary -->
                    <div class="grid grid-cols-3 gap-4 mb-6">
                        <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                            <p class="text-3xl font-bold text-green-600">{{ $stats['present'] }}</p>
                            <p class="text-sm text-green-700">Present</p>
                        </div>
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 text-center">
                            <p class="text-3xl font-bold text-yellow-600">{{ $stats['late'] }}</p>
                            <p class="text-sm text-yellow-700">Late</p>
                        </div>
                        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                            <p class="text-3xl font-bold text-red-600">{{ $stats['absent'] }}</p>
                            <p class="text-sm text-red-700">Absent</p>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-green-600 uppercase">Present</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-yellow-600 uppercase">Late</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-red-600 uppercase">Absent</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-blue-600 uppercase">Excused</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-orange-600 uppercase">Left Early</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-purple-600 uppercase">Min Missed</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Rate</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($students as $student)
                                @php
                                    // Use pre-calculated stats from controller (performance optimization)
                                    $stats = $studentStats[$student->id] ?? [
                                        'present' => 0,
                                        'late' => 0,
                                        'absent' => 0,
                                        'excused' => 0,
                                        'left_early' => 0,
                                        'rate' => 0,
                                    ];
                                    $present = $stats['present'];
                                    $late = $stats['late'];
                                    $absent = $stats['absent'];
                                    $excused = $stats['excused'];
                                    $leftEarly = $stats['left_early'];
                                    $rate = $stats['rate'];
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold">
                                                {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                            </div>
                                            <div class="ml-4">
                                                <a href="{{ route('students.show', $student) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                                    {{ $student->first_name }} {{ $student->last_name }}
                                                </a>
                                                <p class="text-sm text-gray-500">{{ $student->academicGrade?->name ?? '-' }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center text-sm text-green-600 font-medium">{{ $present }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-yellow-600 font-medium">{{ $late }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-red-600 font-medium">{{ $absent }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-blue-600">{{ $excused }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-orange-600 font-medium">{{ $leftEarly ?? 0 }}</td>
                                    <td class="px-6 py-4 text-center text-sm text-purple-600 font-medium">{{ $studentMinutesMissed[$student->id] ?? 0 }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $rate >= 90 ? 'bg-green-100 text-green-700' : ($rate >= 70 ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                            {{ $rate }}%
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-12 text-center text-gray-500">No students found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
