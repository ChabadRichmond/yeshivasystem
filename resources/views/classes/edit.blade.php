<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Session: {{ $class->name }}</h2>
            <a href="{{ route('classes.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                Back to Sessions
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <form method="POST" action="{{ route('classes.update', $class) }}" id="classForm">
                @csrf
                @method('PUT')

                <div class="grid lg:grid-cols-3 gap-6">
                    <!-- Column 1: Class Details -->
                    <div class="bg-white rounded-xl shadow-md p-6 space-y-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2">Session Details</h3>

                        <!-- Session Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Session Name *</label>
                            <input type="text" name="name" value="{{ old('name', $class->name) }}" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                   placeholder="e.g., Chassidus Boker">
                            @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Display Order -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                                <input type="number" name="display_order" value="{{ old('display_order', $class->display_order ?? 0) }}" min="0"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>

                            <!-- Grade Level -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Grade Level</label>
                                <input type="text" name="grade_level" value="{{ old('grade_level', $class->grade_level) }}"
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" rows="2"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('description', $class->description) }}</textarea>
                        </div>

                        <!-- Weekly Schedule -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Weekly Schedule</label>
                            @php
                                $days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                                $existingSchedules = $class->schedules->keyBy('day_of_week');
                            @endphp
                            <div class="space-y-2">
                                @foreach($days as $index => $day)
                                    <div class="flex items-center gap-2 p-2 rounded {{ $index == 6 ? 'bg-gray-100' : 'bg-gray-50' }}">
                                        <input type="checkbox" name="schedules[{{ $index }}][enabled]" value="1"
                                               {{ isset($existingSchedules[$index]) && $existingSchedules[$index]->is_active ? 'checked' : '' }}
                                               class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500 schedule-day-toggle"
                                               data-day="{{ $index }}">
                                        <span class="text-sm w-10">{{ substr($day, 0, 3) }}</span>
                                        <input type="time" name="schedules[{{ $index }}][start_time]"
                                               value="{{ isset($existingSchedules[$index]) ? \Carbon\Carbon::parse($existingSchedules[$index]->start_time)->format('H:i') : '' }}"
                                               class="text-sm rounded-md border-gray-300 shadow-sm w-24 schedule-time-input"
                                               data-day="{{ $index }}"
                                               {{ isset($existingSchedules[$index]) && $existingSchedules[$index]->is_active ? '' : 'disabled' }}>
                                        <span class="text-xs text-gray-400">-</span>
                                        <input type="time" name="schedules[{{ $index }}][end_time]"
                                               value="{{ isset($existingSchedules[$index]) && $existingSchedules[$index]->end_time ? \Carbon\Carbon::parse($existingSchedules[$index]->end_time)->format('H:i') : '' }}"
                                               class="text-sm rounded-md border-gray-300 shadow-sm w-24 schedule-end-time-input"
                                               data-day="{{ $index }}"
                                               {{ isset($existingSchedules[$index]) && $existingSchedules[$index]->is_active ? '' : 'disabled' }}>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <!-- Enrolled Students -->
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <label class="block text-sm font-medium text-gray-700">Enrolled Students</label>
                                <div class="flex gap-2">
                                    <button type="button" onclick="selectAllStudents()" class="text-xs px-2 py-1 bg-indigo-100 text-indigo-700 rounded hover:bg-indigo-200">All</button>
                                    <button type="button" onclick="deselectAllStudents()" class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">None</button>
                                </div>
                            </div>
                            <div class="max-h-64 overflow-y-auto border rounded-md">
                                @php
                                    $studentsByGrade = $students->groupBy(fn($s) => $s->academicGrade?->name ?? 'No Grade');
                                    $enrolledStudentIds = old('student_ids', $class->students->pluck('id')->toArray());
                                @endphp
                                @forelse($studentsByGrade as $gradeName => $gradeStudents)
                                    <div class="border-b last:border-b-0">
                                        <div class="flex items-center justify-between p-2 bg-gray-50 cursor-pointer hover:bg-gray-100"
                                             onclick="toggleGradeGroup('{{ Str::slug($gradeName) }}')">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-500 transform transition-transform grade-chevron" id="chevron-{{ Str::slug($gradeName) }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                                </svg>
                                                <span class="font-medium text-sm text-gray-700">{{ $gradeName }}</span>
                                                <span class="text-xs text-gray-400">({{ $gradeStudents->count() }})</span>
                                            </div>
                                            <label class="flex items-center gap-1" onclick="event.stopPropagation()">
                                                <input type="checkbox" class="grade-select-all rounded border-gray-300 text-indigo-600 text-xs"
                                                       data-grade="{{ Str::slug($gradeName) }}"
                                                       onchange="toggleGradeSelection('{{ Str::slug($gradeName) }}', this.checked)">
                                            </label>
                                        </div>
                                        <div class="p-2 space-y-1 hidden" id="students-{{ Str::slug($gradeName) }}">
                                            @foreach($gradeStudents as $student)
                                                <label class="flex items-center gap-2 p-1 hover:bg-gray-50 rounded cursor-pointer">
                                                    <input type="checkbox" name="student_ids[]" value="{{ $student->id }}"
                                                           {{ in_array($student->id, $enrolledStudentIds) ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-indigo-600 student-checkbox"
                                                           data-student-id="{{ $student->id }}"
                                                           data-student-name="{{ $student->first_name }} {{ $student->last_name }}"
                                                           data-grade="{{ Str::slug($gradeName) }}"
                                                           onchange="updateGradeSelectAll('{{ Str::slug($gradeName) }}'); updateAvailableStudents()">
                                                    <span class="text-sm">{{ $student->first_name }} {{ $student->last_name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                @empty
                                    <p class="text-gray-500 text-sm p-4">No students available.</p>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <!-- Column 2: Teaching Groups -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <div class="flex justify-between items-center border-b pb-2 mb-4">
                            <h3 class="text-lg font-semibold text-gray-800">Teaching Groups</h3>
                            <button type="button" onclick="addTeachingGroup()" class="text-sm px-3 py-1 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                                + Add Group
                            </button>
                        </div>

                        <div class="bg-blue-50 border border-blue-200 rounded-md p-3 mb-4 text-sm">
                            <p class="text-blue-800">Teaching groups define which students belong to which primary teacher within this class.</p>
                            <p class="text-blue-700 text-xs mt-1">Example: "Shiur Aleph" with Rabbi Mann, "Shiur Beis" with Rabbi Cohen</p>
                        </div>

                        <div id="teachingGroupsContainer" class="space-y-4">
                            @php $groupIndex = 0; @endphp
                            @forelse($class->teachingGroups as $group)
                                <div class="teaching-group border rounded-lg p-4" data-group-id="{{ $group->id }}">
                                    <div class="flex gap-2 mb-3">
                                        <input type="text" name="teaching_groups[{{ $group->id }}][name]" value="{{ $group->name }}"
                                               placeholder="Group Name (e.g., Shiur Aleph)"
                                               class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <button type="button" onclick="removeTeachingGroup(this)" class="text-red-500 hover:text-red-700 px-2">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                    <div class="mb-3">
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Primary Teacher</label>
                                        <select name="teaching_groups[{{ $group->id }}][primary_teacher_id]"
                                                class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                            <option value="">-- Select Teacher --</option>
                                            @foreach($teachers as $teacher)
                                                <option value="{{ $teacher->id }}" {{ $group->primary_teacher_id == $teacher->id ? 'selected' : '' }}>
                                                    {{ $teacher->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-600 mb-1">Students in Group</label>
                                        <div class="max-h-32 overflow-y-auto border rounded-md p-2 space-y-1 group-students-container">
                                            @php $groupStudentIds = $group->students->pluck('id')->toArray(); @endphp
                                            @foreach($class->students as $student)
                                                <label class="flex items-center gap-2 p-1 hover:bg-gray-50 rounded cursor-pointer text-sm">
                                                    <input type="checkbox" name="teaching_groups[{{ $group->id }}][students][]" value="{{ $student->id }}"
                                                           {{ in_array($student->id, $groupStudentIds) ? 'checked' : '' }}
                                                           class="rounded border-gray-300 text-indigo-600 group-student-checkbox"
                                                           data-student-id="{{ $student->id }}">
                                                    <span>{{ $student->first_name }} {{ $student->last_name }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <input type="hidden" name="teaching_groups[{{ $group->id }}][display_order]" value="{{ $groupIndex }}">
                                </div>
                                @php $groupIndex++; @endphp
                            @empty
                                <p class="text-gray-500 text-sm text-center py-4" id="noGroupsMessage">
                                    No teaching groups yet. Click "Add Group" to create one.
                                </p>
                            @endforelse
                        </div>
                    </div>

                    <!-- Column 3: Attendance Takers -->
                    <div class="bg-white rounded-xl shadow-md p-6">
                        <h3 class="text-lg font-semibold text-gray-800 border-b pb-2 mb-4">Attendance Takers</h3>

                        <div class="bg-amber-50 border border-amber-200 rounded-md p-3 mb-4 text-sm">
                            <p class="text-amber-800">Optionally assign a different teacher to take attendance for specific students.</p>
                            <p class="text-amber-700 text-xs mt-1">Leave blank to use the primary teacher from the teaching group.</p>
                        </div>

                        <div id="attendanceTakersContainer" class="space-y-2 max-h-96 overflow-y-auto">
                            @php
                                $attendanceTakerMap = $class->attendanceTakerAssignments->keyBy('student_id');
                            @endphp
                            @forelse($class->students as $student)
                                @php
                                    $currentAttendanceTakerId = $attendanceTakerMap->get($student->id)?->attendance_taker_id;
                                    $primaryTeacher = $class->getPrimaryTeacherForStudent($student->id);
                                @endphp
                                <div class="flex items-center gap-2 p-2 bg-gray-50 rounded attendance-taker-row" data-student-id="{{ $student->id }}">
                                    <span class="text-sm flex-1 truncate" title="{{ $student->first_name }} {{ $student->last_name }}">
                                        {{ $student->first_name }} {{ $student->last_name }}
                                    </span>
                                    <select name="attendance_takers[{{ $student->id }}]"
                                            class="text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-40">
                                        <option value="">
                                            {{ $primaryTeacher ? '(Primary: ' . $primaryTeacher->name . ')' : '-- None --' }}
                                        </option>
                                        @foreach($teachers as $teacher)
                                            <option value="{{ $teacher->id }}" {{ $currentAttendanceTakerId == $teacher->id ? 'selected' : '' }}>
                                                {{ $teacher->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @empty
                                <p class="text-gray-500 text-sm text-center py-4">
                                    Enroll students first to assign attendance takers.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Update Session
                    </button>
                    <a href="{{ route('classes.index') }}" class="px-6 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>

    <script>
        let newGroupCounter = 0;
        const teachers = @json($teachers->map(fn($t) => ['id' => $t->id, 'name' => $t->name]));

        // === Grade Group Functions ===
        function toggleGradeGroup(gradeSlug) {
            const studentsDiv = document.getElementById('students-' + gradeSlug);
            const chevron = document.getElementById('chevron-' + gradeSlug);
            studentsDiv.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        }

        function toggleGradeSelection(gradeSlug, checked) {
            document.querySelectorAll('.student-checkbox[data-grade="' + gradeSlug + '"]').forEach(cb => cb.checked = checked);
            updateAvailableStudents();
        }

        function updateGradeSelectAll(gradeSlug) {
            const checkboxes = document.querySelectorAll('.student-checkbox[data-grade="' + gradeSlug + '"]');
            const selectAll = document.querySelector('.grade-select-all[data-grade="' + gradeSlug + '"]');
            if (selectAll) {
                selectAll.checked = Array.from(checkboxes).every(cb => cb.checked);
            }
        }

        function selectAllStudents() {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = true);
            document.querySelectorAll('.grade-select-all').forEach(cb => cb.checked = true);
            updateAvailableStudents();
        }

        function deselectAllStudents() {
            document.querySelectorAll('.student-checkbox').forEach(cb => cb.checked = false);
            document.querySelectorAll('.grade-select-all').forEach(cb => cb.checked = false);
            updateAvailableStudents();
        }

        // === Teaching Group Functions ===
        function addTeachingGroup() {
            const container = document.getElementById('teachingGroupsContainer');
            const noMessage = document.getElementById('noGroupsMessage');
            if (noMessage) noMessage.remove();

            newGroupCounter++;
            const groupId = 'new_' + newGroupCounter;
            const enrolledStudents = getEnrolledStudents();
            const displayOrder = container.querySelectorAll('.teaching-group').length;

            const html = `
                <div class="teaching-group border rounded-lg p-4" data-group-id="${groupId}">
                    <div class="flex gap-2 mb-3">
                        <input type="text" name="teaching_groups[${groupId}][name]" value=""
                               placeholder="Group Name (e.g., Shiur Aleph)"
                               class="flex-1 text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <button type="button" onclick="removeTeachingGroup(this)" class="text-red-500 hover:text-red-700 px-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    <div class="mb-3">
                        <label class="block text-xs font-medium text-gray-600 mb-1">Primary Teacher</label>
                        <select name="teaching_groups[${groupId}][primary_teacher_id]"
                                class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">-- Select Teacher --</option>
                            ${teachers.map(t => `<option value="${t.id}">${t.name}</option>`).join('')}
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Students in Group</label>
                        <div class="max-h-32 overflow-y-auto border rounded-md p-2 space-y-1 group-students-container">
                            ${enrolledStudents.length > 0 ? enrolledStudents.map(s => `
                                <label class="flex items-center gap-2 p-1 hover:bg-gray-50 rounded cursor-pointer text-sm">
                                    <input type="checkbox" name="teaching_groups[${groupId}][students][]" value="${s.id}"
                                           class="rounded border-gray-300 text-indigo-600 group-student-checkbox"
                                           data-student-id="${s.id}">
                                    <span>${s.name}</span>
                                </label>
                            `).join('') : '<p class="text-gray-400 text-xs text-center py-2">Enroll students first</p>'}
                        </div>
                    </div>
                    <input type="hidden" name="teaching_groups[${groupId}][display_order]" value="${displayOrder}">
                </div>
            `;
            container.insertAdjacentHTML('beforeend', html);
        }

        function removeTeachingGroup(btn) {
            btn.closest('.teaching-group').remove();
        }

        function getEnrolledStudents() {
            const students = [];
            document.querySelectorAll('.student-checkbox:checked').forEach(cb => {
                students.push({
                    id: cb.dataset.studentId,
                    name: cb.dataset.studentName
                });
            });
            return students;
        }

        function updateAvailableStudents() {
            const enrolledStudents = getEnrolledStudents();
            const enrolledIds = enrolledStudents.map(s => s.id);

            // Update teaching group student lists
            document.querySelectorAll('.teaching-group').forEach(group => {
                const container = group.querySelector('.group-students-container');
                const groupId = group.dataset.groupId;

                // Get currently checked students in this group
                const checkedIds = Array.from(container.querySelectorAll('.group-student-checkbox:checked')).map(cb => cb.dataset.studentId);

                // Rebuild the list
                if (enrolledStudents.length > 0) {
                    container.innerHTML = enrolledStudents.map(s => `
                        <label class="flex items-center gap-2 p-1 hover:bg-gray-50 rounded cursor-pointer text-sm">
                            <input type="checkbox" name="teaching_groups[${groupId}][students][]" value="${s.id}"
                                   ${checkedIds.includes(s.id) ? 'checked' : ''}
                                   class="rounded border-gray-300 text-indigo-600 group-student-checkbox"
                                   data-student-id="${s.id}">
                            <span>${s.name}</span>
                        </label>
                    `).join('');
                } else {
                    container.innerHTML = '<p class="text-gray-400 text-xs text-center py-2">Enroll students first</p>';
                }
            });

            // Update attendance takers list
            const atContainer = document.getElementById('attendanceTakersContainer');
            const currentSelections = {};
            atContainer.querySelectorAll('select').forEach(sel => {
                const studentId = sel.name.match(/\[(\d+)\]/)?.[1];
                if (studentId && sel.value) {
                    currentSelections[studentId] = sel.value;
                }
            });

            if (enrolledStudents.length > 0) {
                atContainer.innerHTML = enrolledStudents.map(s => `
                    <div class="flex items-center gap-2 p-2 bg-gray-50 rounded attendance-taker-row" data-student-id="${s.id}">
                        <span class="text-sm flex-1 truncate" title="${s.name}">${s.name}</span>
                        <select name="attendance_takers[${s.id}]"
                                class="text-xs rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 w-40">
                            <option value="">-- Primary --</option>
                            ${teachers.map(t => `<option value="${t.id}" ${currentSelections[s.id] == t.id ? 'selected' : ''}>${t.name}</option>`).join('')}
                        </select>
                    </div>
                `).join('');
            } else {
                atContainer.innerHTML = '<p class="text-gray-500 text-sm text-center py-4">Enroll students first to assign attendance takers.</p>';
            }
        }

        // === Schedule Functions ===
        document.querySelectorAll('.schedule-day-toggle').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const day = this.dataset.day;
                const row = this.closest('.flex');
                row.querySelectorAll('input[type="time"]').forEach(input => {
                    input.disabled = !this.checked;
                    if (!this.checked) input.value = '';
                });
            });
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.grade-select-all').forEach(selectAll => {
                updateGradeSelectAll(selectAll.dataset.grade);
            });
        });
    </script>
</x-app-layout>
