<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Student Profile</h2>
            @unless($isReadOnly ?? false)
                <a href="{{ route('students.edit', $student) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 text-sm">Edit Student</a>
            @endunless
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">{{ session('success') }}</div>
            @endif

            <!-- Hero Header Section -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                    <!-- Left: Avatar and Name -->
                    <div class="flex items-center gap-4">
                        @if($student->photo)
                            <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->first_name }}" class="h-16 w-16 rounded-full object-cover flex-shrink-0">
                        @else
                            <div class="h-16 w-16 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center flex-shrink-0">
                                <span class="text-white text-xl font-bold">{{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}</span>
                            </div>
                        @endif
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900">{{ $student->first_name }} {{ $student->last_name }}</h1>
                            <div class="flex items-center gap-2 mt-1">
                                <span class="px-2.5 py-0.5 text-xs font-medium rounded-full {{ $student->enrollment_status == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($student->enrollment_status) }}
                                </span>
                                @if($student->academicGrade)
                                    <span class="px-2.5 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-800">
                                        {{ $student->academicGrade->name }}
                                    </span>
                                @endif
                                @if($student->student_id)
                                    <span class="text-sm text-gray-500">{{ $student->student_id }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Right: Quick Stats -->
                    <div class="flex flex-wrap gap-4">
                        <div class="text-center px-4 py-2 bg-gray-50 rounded-lg">
                            <div class="text-2xl font-bold {{ $attendanceStats['percentage'] >= 90 ? 'text-green-600' : ($attendanceStats['percentage'] >= 75 ? 'text-yellow-600' : 'text-red-600') }}">
                                {{ $attendanceStats['percentage'] }}%
                            </div>
                            <div class="text-xs text-gray-500">Attendance</div>
                        </div>
                        <div class="text-center px-4 py-2 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $attendanceStats['present'] }}</div>
                            <div class="text-xs text-gray-500">Present</div>
                        </div>
                        <div class="text-center px-4 py-2 bg-yellow-50 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ $attendanceStats['late'] }}</div>
                            <div class="text-xs text-gray-500">Late</div>
                        </div>
                        <div class="text-center px-4 py-2 bg-red-50 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ $attendanceStats['absent'] }}</div>
                            <div class="text-xs text-gray-500">Absent</div>
                        </div>
                        <div class="text-center px-4 py-2 bg-indigo-50 rounded-lg">
                            <div class="text-2xl font-bold text-indigo-600">{{ $student->classes->count() }}</div>
                            <div class="text-xs text-gray-500">Classes</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Two Column Layout -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Left Column -->
                <div class="space-y-6">
                    <!-- Student Info Card -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Student Information</h3>
                        <dl class="grid grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Student ID</dt>
                                <dd class="text-sm text-gray-900 mt-1">{{ $student->student_id ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email</dt>
                                <dd class="text-sm text-gray-900 mt-1">{{ $student->email ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone</dt>
                                <dd class="text-sm text-gray-900 mt-1">{{ $student->phone ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Date of Birth</dt>
                                <dd class="text-sm text-gray-900 mt-1">
                                    @if($student->date_of_birth)
                                        {{ $student->date_of_birth->format('M d, Y') }}
                                        <span class="text-gray-500">({{ $student->date_of_birth->age }} years)</span>
                                    @else
                                        -
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Gender</dt>
                                <dd class="text-sm text-gray-900 mt-1">{{ ucfirst($student->gender ?? '-') }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Enrollment Date</dt>
                                <dd class="text-sm text-gray-900 mt-1">{{ $student->enrollment_date?->format('M d, Y') ?? '-' }}</dd>
                            </div>
                        </dl>

                        @if($student->address || $student->city || $student->province)
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <dt class="text-sm font-medium text-gray-500 mb-1">Address</dt>
                                <dd class="text-sm text-gray-900">
                                    @if($student->address){{ $student->address }}<br>@endif
                                    @if($student->city || $student->province || $student->postal_code)
                                        {{ collect([$student->city, $student->province, $student->postal_code])->filter()->join(', ') }}
                                    @endif
                                </dd>
                            </div>
                        @endif
                    </div>

                    <!-- Classes Enrolled Card -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Classes Enrolled</h3>
                        @forelse($student->classes as $class)
                            <div class="flex items-center justify-between py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                <div>
                                    <div class="font-medium text-gray-900">{{ $class->name }}</div>
                                    @php
                                        $todaySchedule = $class->schedules->where('day_of_week', now()->dayOfWeek)->first();
                                    @endphp
                                    @if($todaySchedule)
                                        <div class="text-sm text-gray-500">
                                            Today: {{ \Carbon\Carbon::parse($todaySchedule->start_time)->format('g:i A') }}
                                            @if($todaySchedule->end_time)
                                                - {{ \Carbon\Carbon::parse($todaySchedule->end_time)->format('g:i A') }}
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                <a href="{{ route('classes.show', $class) }}" class="text-indigo-600 hover:text-indigo-800 text-sm">View</a>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">Not enrolled in any classes</p>
                        @endforelse
                    </div>

                    <!-- Notes Section -->
                    @if($student->notes || $student->medical_notes)
                        <div class="bg-white shadow-sm sm:rounded-lg p-6">
                            @if($student->notes)
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-500 mb-2">Notes</h4>
                                    <p class="text-sm text-gray-900">{{ $student->notes }}</p>
                                </div>
                            @endif
                            @if($student->medical_notes)
                                <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                                    <h4 class="text-sm font-medium text-red-800 mb-2 flex items-center gap-2">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                        Medical Notes
                                    </h4>
                                    <p class="text-sm text-red-700">{{ $student->medical_notes }}</p>
                                </div>
                            @endif
                        </div>
                    @endif

                    <!-- Permissions/Leave Section -->
                    @can('update', $student)
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Permissions / Leave</h3>
                            <button onclick="document.getElementById('add-permission-modal').classList.remove('hidden')"
                                    class="px-3 py-1 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700">
                                Add Permission
                            </button>
                        </div>

                        @php
                            $permissions = $student->permissions()->orderBy('start_date', 'desc')->get();
                            $activePermission = $student->getPermissionForDate(now());
                        @endphp

                        @if($activePermission)
                            <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded-lg">
                                <div class="flex items-center gap-2">
                                    <span class="text-yellow-600 font-bold">Currently on Permission</span>
                                    @if($activePermission->class_range)
                                        <span class="text-xs text-yellow-600 bg-yellow-100 px-2 py-0.5 rounded">{{ $activePermission->class_range }}</span>
                                    @else
                                        <span class="text-xs text-yellow-600 bg-yellow-100 px-2 py-0.5 rounded">Full Day</span>
                                    @endif
                                </div>
                                <p class="text-sm text-yellow-700 mt-1">
                                    {{ $activePermission->start_date->format('M d, Y') }} - {{ $activePermission->end_date->format('M d, Y') }}
                                    @if($activePermission->reason)
                                        <br><span class="text-gray-600">Reason: {{ $activePermission->reason }}</span>
                                    @endif
                                </p>
                            </div>
                        @endif

                        @if($permissions->isEmpty())
                            <p class="text-gray-500 text-sm">No permission records</p>
                        @else
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                @foreach($permissions as $permission)
                                    <div class="flex items-center justify-between p-2 {{ $permission->coversDate(now()) ? 'bg-yellow-50' : 'bg-gray-50' }} rounded-lg">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900 flex items-center gap-2">
                                                {{ $permission->start_date->format('M d, Y') }} - {{ $permission->end_date->format('M d, Y') }}
                                                @if($permission->class_range)
                                                    <span class="text-xs text-gray-500 bg-gray-200 px-1.5 py-0.5 rounded">{{ $permission->class_range }}</span>
                                                @endif
                                            </div>
                                            @if($permission->reason)
                                                <div class="text-xs text-gray-500">{{ $permission->reason }}</div>
                                            @endif
                                        </div>
                                        <form action="{{ route('students.permissions.destroy', [$student, $permission]) }}" method="POST" onsubmit="return confirm('Delete this permission?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm">Delete</button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                    @endcan
                </div>

                <!-- Right Column -->
                <div class="space-y-6">
                    <!-- Recent Attendance Card -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Recent Attendance</h3>
                            <a href="{{ route('reports.attendance.stats', ['student_id' => $student->id]) }}" class="text-sm text-indigo-600 hover:text-indigo-800">View Full Report &rarr;</a>
                        </div>

                        @forelse($attendancesByDate as $date => $records)
                            <div class="mb-4 last:mb-0">
                                <div class="text-sm font-medium text-gray-700 mb-2 pb-1 border-b border-gray-100">
                                    {{ \Carbon\Carbon::parse($date)->format('l, M d, Y') }}
                                </div>
                                <div class="space-y-2">
                                    @foreach($records as $attendance)
                                        <div class="flex items-center justify-between py-1.5 px-3 rounded-lg bg-gray-50">
                                            <span class="text-sm text-gray-700">{{ $attendance->schoolClass->name ?? 'Unknown Class' }}</span>
                                            @php
                                                $status = $attendance->status;
                                                $statusClass = match(true) {
                                                    $status === 'present' => 'bg-green-100 text-green-800',
                                                    str_starts_with($status, 'late') => str_contains($status, 'excused') ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800',
                                                    str_starts_with($status, 'absent') => str_contains($status, 'excused') ? 'bg-blue-100 text-blue-800' : 'bg-red-100 text-red-800',
                                                    default => 'bg-gray-100 text-gray-800'
                                                };
                                                $statusLabel = match($status) {
                                                    'present' => 'Present',
                                                    'late_excused' => 'Late (Excused)',
                                                    'late_unexcused' => 'Late',
                                                    'absent_excused' => 'Absent (Excused)',
                                                    'absent_unexcused' => 'Absent',
                                                    default => ucfirst(str_replace('_', ' ', $status))
                                                };
                                            @endphp
                                            <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statusClass }}">
                                                {{ $statusLabel }}
                                                @if($attendance->minutes_late && $attendance->minutes_late > 0)
                                                    ({{ $attendance->minutes_late }}m)
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">No attendance records</p>
                        @endforelse
                    </div>

                    <!-- Grades Overview Card -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900">Grades Overview</h3>
                            <a href="{{ route('grades.tests.student', $student) }}" class="text-sm text-indigo-600 hover:text-indigo-800">View All &rarr;</a>
                        </div>

                        @if(count($subjectAverages) > 0)
                            <div class="grid grid-cols-2 gap-3 mb-4">
                                @foreach($subjectAverages as $subjectId => $data)
                                    @php
                                        $avg = $data['average'];
                                        $bgClass = match(true) {
                                            $avg >= 90 => 'bg-green-50 border-green-200',
                                            $avg >= 80 => 'bg-blue-50 border-blue-200',
                                            $avg >= 70 => 'bg-yellow-50 border-yellow-200',
                                            $avg >= 60 => 'bg-orange-50 border-orange-200',
                                            default => 'bg-red-50 border-red-200',
                                        };
                                        $textClass = match(true) {
                                            $avg >= 90 => 'text-green-700',
                                            $avg >= 80 => 'text-blue-700',
                                            $avg >= 70 => 'text-yellow-700',
                                            $avg >= 60 => 'text-orange-700',
                                            default => 'text-red-700',
                                        };
                                    @endphp
                                    <div class="p-3 rounded-lg border {{ $bgClass }}">
                                        <div class="text-xs font-medium text-gray-600">{{ $data['subject']->name }}</div>
                                        <div class="flex items-baseline gap-1">
                                            <span class="text-lg font-bold {{ $textClass }}">{{ number_format($avg, 1) }}%</span>
                                            <span class="text-xs text-gray-500">({{ $data['letter_grade'] }})</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        @if($recentTestScores->isNotEmpty())
                            <h4 class="text-sm font-medium text-gray-700 mb-2">Recent Tests</h4>
                            <div class="space-y-2">
                                @foreach($recentTestScores as $score)
                                    <div class="flex items-center justify-between py-2 px-3 bg-gray-50 rounded-lg">
                                        <div>
                                            <div class="text-sm font-medium text-gray-900">{{ $score->test_name }}</div>
                                            <div class="text-xs text-gray-500">{{ $score->subject?->name }} - {{ $score->test_date->format('M d') }}</div>
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-medium {{ $score->percentage >= 70 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $score->percentage }}%
                                            </div>
                                            <div class="text-xs text-gray-500">{{ $score->score }}/{{ $score->max_score }}</div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(count($subjectAverages) === 0)
                            <p class="text-gray-500 text-sm">No test scores recorded yet</p>
                        @endif
                    </div>

                    <!-- Guardians Card -->
                    <div class="bg-white shadow-sm sm:rounded-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Guardians</h3>
                        @forelse($student->guardians as $guardian)
                            <div class="py-3 {{ !$loop->last ? 'border-b border-gray-100' : '' }}">
                                <div class="flex items-start justify-between">
                                    <div>
                                        <div class="font-medium text-gray-900">
                                            {{ $guardian->user->name ?? ($guardian->first_name . ' ' . $guardian->last_name) }}
                                            @if($guardian->is_primary)
                                                <span class="ml-1 px-1.5 py-0.5 text-xs bg-indigo-100 text-indigo-700 rounded">Primary</span>
                                            @endif
                                        </div>
                                        <div class="text-sm text-gray-500 capitalize">{{ $guardian->relationship }}</div>
                                    </div>
                                </div>
                                <div class="mt-2 space-y-1">
                                    @if($guardian->phone)
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                                            <a href="tel:{{ $guardian->phone }}" class="hover:text-indigo-600">{{ $guardian->phone }}</a>
                                        </div>
                                    @endif
                                    @if($guardian->email ?? $guardian->user?->email)
                                        <div class="flex items-center gap-2 text-sm text-gray-600">
                                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                                            <a href="mailto:{{ $guardian->email ?? $guardian->user?->email }}" class="hover:text-indigo-600">{{ $guardian->email ?? $guardian->user?->email }}</a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-gray-500 text-sm">No guardians linked</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Permission Modal -->
    @can('update', $student)
    <div id="add-permission-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Add Permission / Leave</h3>
                <button onclick="document.getElementById('add-permission-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <form action="{{ route('students.permissions.store', $student) }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                        <input type="date" name="start_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ now()->format('Y-m-d') }}">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                        <input type="date" name="end_date" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" value="{{ now()->format('Y-m-d') }}">
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-md p-3">
                        <p class="text-xs text-blue-800 font-medium mb-2">Partial Day Permission (optional)</p>
                        <p class="text-xs text-blue-700 mb-3">Select which classes the student is excused from. Leave blank for full-day permission.</p>

                        <div class="space-y-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">First Excused Class</label>
                                <select name="first_excused_class_id" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- Start of day (all classes) --</option>
                                    @foreach($student->classes->sortBy(function($c) {
                                        $schedule = $c->schedules->where('is_active', true)->first();
                                        return $schedule ? $schedule->start_time : '99:99';
                                    }) as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 mb-1">Last Excused Class</label>
                                <select name="last_excused_class_id" class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">-- End of day (all remaining) --</option>
                                    @foreach($student->classes->sortBy(function($c) {
                                        $schedule = $c->schedules->where('is_active', true)->first();
                                        return $schedule ? $schedule->start_time : '99:99';
                                    }) as $class)
                                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                        <input type="text" name="reason" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., Family celebration, Medical appointment">
                    </div>
                    <p class="text-sm text-gray-500">Student will be excluded from attendance for selected classes and will show "C" in reports.</p>
                </div>
                <div class="mt-6 flex justify-end gap-3">
                    <button type="button" onclick="document.getElementById('add-permission-modal').classList.add('hidden')" class="px-4 py-2 text-gray-700 hover:text-gray-900">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Add Permission</button>
                </div>
            </form>
        </div>
    </div>
    @endcan
</x-app-layout>
