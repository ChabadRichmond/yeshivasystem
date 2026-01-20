<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ $class->name }}</h2>
                <p class="text-sm text-gray-500">{{ $class->grade_level }} • {{ $class->students->count() }} students</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('attendance.index', ['class_id' => $class->id]) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    Take Attendance
                </a>
                <a href="{{ route('classes.edit', $class) }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    Edit Class
                </a>
                <a href="{{ route('classes.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                    Back to Classes
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-3 gap-6">
                <div class="bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Class Details</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm text-gray-500">Teacher</dt>
                            <dd class="font-medium">{{ $class->teacher?->name ?? 'Not assigned' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Weekly Schedule</dt>
                            <dd class="font-medium">
                                @php
                                    $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
                                    $activeSchedules = $class->schedules->where('is_active', true)->sortBy('day_of_week');
                                @endphp
                                @if($activeSchedules->count() > 0)
                                    <div class="space-y-1">
                                        @foreach($activeSchedules as $schedule)
                                            <div class="text-sm">
                                                <span class="font-medium">{{ $days[$schedule->day_of_week] }}:</span>
                                                {{ \Carbon\Carbon::parse($schedule->start_time)->format('g:i A') }}
                                                @if($schedule->end_time)
                                                    - {{ \Carbon\Carbon::parse($schedule->end_time)->format('g:i A') }}
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400">No schedule set</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm text-gray-500">Academic Year</dt>
                            <dd class="font-medium">{{ $class->academic_year ?? date('Y') }}</dd>
                        </div>
                        @if($class->description)
                        <div>
                            <dt class="text-sm text-gray-500">Description</dt>
                            <dd class="text-gray-700">{{ $class->description }}</dd>
                        </div>
                        @endif
                    </dl>
                </div>

                <!-- Students List -->
                <div class="md:col-span-2 bg-white rounded-xl shadow-md p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Students ({{ $class->students->count() }})</h3>
                    
                    @if($class->students->count() > 0)
                        <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-3">
                            @foreach($class->students as $student)
                                <a href="{{ route('students.show', $student) }}" 
                                   class="flex items-center gap-3 p-3 rounded-lg border hover:bg-gray-50 transition-colors">
                                    <div class="w-10 h-10 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white font-bold text-sm">
                                        {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-800">{{ $student->first_name }} {{ $student->last_name }}</p>
                                        <p class="text-xs text-gray-500">{{ $student->grade_level }}</p>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-gray-500">No students assigned to this class yet.</p>
                        <a href="{{ route('classes.edit', $class) }}" class="inline-block mt-2 text-indigo-600 hover:underline">Add students →</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
