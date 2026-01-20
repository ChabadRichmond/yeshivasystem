<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Grades & Sessions</h2>
            <div class="flex gap-2">
                <button onclick="document.getElementById('add-grade-modal').classList.remove('hidden'); document.getElementById('add-grade-modal').classList.add('flex');" 
                        class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                    + New Grade
                </button>
                <a href="{{ route('classes.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                    + New Session
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="mb-4 p-4 bg-red-100 text-red-700 rounded-md">{{ session('error') }}</div>
            @endif

            <!-- Grades Management Section -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-800 mb-2">📚 Grades</h3>
                <p class="text-sm text-gray-500 mb-4">Define academic grade levels. Students are assigned to grades.</p>
                
                <div class="flex flex-wrap gap-3">
                    @forelse($grades ?? [] as $grade)
                        <div class="flex items-center gap-2 px-4 py-2 bg-gradient-to-r from-indigo-50 to-purple-50 border border-indigo-200 rounded-lg">
                            <span class="font-medium text-indigo-700">{{ $grade->name }}</span>
                            @if($grade->code)
                                <span class="text-xs text-gray-500">({{ $grade->code }})</span>
                            @endif
                            <span class="text-xs text-gray-400">• {{ $grade->students()->count() }} students</span>
                            <button onclick="editGrade({{ $grade->id }}, '{{ addslashes($grade->name) }}', '{{ addslashes($grade->code) }}')" 
                                    class="ml-2 text-gray-400 hover:text-indigo-600" title="Edit">
                                ✏️
                            </button>
                            <form method="POST" action="{{ route('grades.destroy', $grade) }}" style="display:inline;" 
                                  onsubmit="return confirm('Delete this grade?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-gray-400 hover:text-red-600" title="Delete">🗑️</button>
                            </form>
                        </div>
                    @empty
                        <div class="text-gray-500">
                            No grades defined yet. 
                            <button onclick="document.getElementById('add-grade-modal').classList.remove('hidden'); document.getElementById('add-grade-modal').classList.add('flex');" 
                                    class="text-indigo-600 hover:underline">Add your first grade →</button>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Sessions Section (flat grid, no grouping by old grade_level) -->
            <div class="bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Sessions</h3>
                
                @if($classes->isEmpty())
                    <div class="text-center py-8">
                        <div class="text-4xl mb-3">📋</div>
                        <p class="text-gray-500 mb-4">No sessions created yet.</p>
                        <a href="{{ route('classes.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                            Create First Session
                        </a>
                    </div>
                @else
                    <p class="text-sm text-gray-500 mb-3">💡 Drag and drop to reorder sessions</p>
                    <div id="sessions-grid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach($classes as $class)
                            <div class="session-card border rounded-lg p-4 hover:shadow-md transition-shadow bg-gray-50" data-id="{{ $class->id }}">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex items-start gap-2">
                                        <span class="drag-handle cursor-move text-gray-400 hover:text-gray-600 mt-1 select-none" title="Drag to reorder">⋮⋮</span>
                                        <div>
                                            <h4 class="font-semibold text-gray-800">{{ $class->name }}</h4>
                                            @php
                                                $days = ['S', 'M', 'T', 'W', 'T', 'F', 'S'];
                                                $activeSchedules = $class->schedules->where('is_active', true)->sortBy('day_of_week');
                                            @endphp
                                            @if($activeSchedules->count() > 0)
                                                <p class="text-sm text-gray-500">
                                                    ⏰ {{ $activeSchedules->map(fn($s) => $days[$s->day_of_week])->implode(', ') }}
                                                    @if($activeSchedules->first()->start_time)
                                                        • {{ \Carbon\Carbon::parse($activeSchedules->first()->start_time)->format('g:i A') }}
                                                    @endif
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                    <span class="px-2 py-1 bg-indigo-100 text-indigo-700 text-xs rounded-full">
                                        {{ $class->students->count() }} students
                                    </span>
                                </div>

                                @if($class->teacher)
                                    <p class="text-sm text-gray-600 mb-3">
                                        👤 {{ $class->teacher->name }}
                                    </p>
                                @endif

                                <!-- Student avatars preview -->
                                @if($class->students->count() > 0)
                                    <div class="flex -space-x-2 mb-3">
                                        @foreach($class->students->take(5) as $student)
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold border-2 border-white" title="{{ $student->first_name }} {{ $student->last_name }}">
                                                {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                            </div>
                                        @endforeach
                                        @if($class->students->count() > 5)
                                            <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center text-gray-600 text-xs font-bold border-2 border-white">
                                                +{{ $class->students->count() - 5 }}
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                <!-- Action buttons -->
                                <div class="flex gap-2">
                                    <a href="{{ route('attendance.index', ['class_id' => $class->id]) }}"
                                       class="flex-1 px-3 py-2 bg-green-600 text-white text-sm rounded-md text-center hover:bg-green-700">
                                        📋 Attendance
                                    </a>
                                    <a href="{{ route('classes.show', $class) }}"
                                       class="px-3 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300" title="View">
                                        👁
                                    </a>
                                    <a href="{{ route('classes.edit', $class) }}"
                                       class="px-3 py-2 bg-gray-200 text-gray-700 text-sm rounded-md hover:bg-gray-300" title="Edit">
                                        ✏️
                                    </a>
                                    <button onclick="openDeleteModal({{ $class->id }}, '{{ addslashes($class->name) }}')"
                                       class="px-3 py-2 bg-red-100 text-red-600 text-sm rounded-md hover:bg-red-200" title="Delete">
                                        🗑️
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        /* Ensure buttons work on mobile/touch devices */
        .session-card {
            touch-action: pan-y; /* Allow vertical scrolling but not horizontal */
        }
        .session-card a,
        .session-card button {
            touch-action: manipulation; /* Prevent double-tap zoom on buttons */
            pointer-events: auto;
        }
        .drag-handle {
            touch-action: none; /* Drag handle should capture all touch events */
        }
    </style>

    <!-- Add/Edit Grade Modal -->
    <div id="add-grade-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closeGradeModal(event)">
        <div class="bg-white rounded-xl shadow-xl p-6 w-96 max-w-full" onclick="event.stopPropagation()">
            <h3 id="grade-modal-title" class="text-lg font-bold text-gray-800 mb-4">Add New Grade</h3>
            
            <form id="grade-form" method="POST" action="{{ route('grades.store') }}">
                @csrf
                <div id="grade-method-container"></div>
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Grade Name *</label>
                        <input type="text" name="name" id="grade-name" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="e.g., Shiur Aleph, Grade 1">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Short Code (optional)</label>
                        <input type="text" name="code" id="grade-code"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="e.g., 1A, SA">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Display Order</label>
                        <input type="number" name="display_order" id="grade-order" value="0" min="0"
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Save Grade
                    </button>
                    <button type="button" onclick="closeGradeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editGrade(id, name, code) {
            document.getElementById('grade-modal-title').textContent = 'Edit Grade';
            document.getElementById('grade-name').value = name;
            document.getElementById('grade-code').value = code || '';
            document.getElementById('grade-form').action = '/grades/' + id;
            document.getElementById('grade-method-container').innerHTML = '@method("PUT")';
            document.getElementById('add-grade-modal').classList.remove('hidden');
            document.getElementById('add-grade-modal').classList.add('flex');
        }

        function closeGradeModal(event) {
            if (!event || event.target === document.getElementById('add-grade-modal')) {
                document.getElementById('add-grade-modal').classList.add('hidden');
                document.getElementById('add-grade-modal').classList.remove('flex');
                // Reset form
                document.getElementById('grade-modal-title').textContent = 'Add New Grade';
                document.getElementById('grade-form').action = '{{ route("grades.store") }}';
                document.getElementById('grade-method-container').innerHTML = '';
                document.getElementById('grade-name').value = '';
                document.getElementById('grade-code').value = '';
            }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        // Sortable for drag and drop reordering (must run after SortableJS loads)
        document.addEventListener('DOMContentLoaded', function() {
            if (document.getElementById('sessions-grid')) {
                new Sortable(document.getElementById('sessions-grid'), {
                    animation: 150,
                    handle: '.drag-handle', // Only allow dragging from the handle
                    ghostClass: 'bg-indigo-100',
                    touchStartThreshold: 5, // Better touch support
                    forceFallback: false, // Use native HTML5 drag on supported browsers
                    onEnd: function(evt) {
                        const order = Array.from(document.querySelectorAll('.session-card')).map(el => el.dataset.id);
                        fetch('{{ route("classes.reorder") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({ order: order })
                        }).then(res => res.json()).then(data => {
                            if (data.success) {
                                // Show subtle feedback
                                evt.item.classList.add('ring-2', 'ring-green-500');
                                setTimeout(() => evt.item.classList.remove('ring-2', 'ring-green-500'), 500);
                            }
                        });
                    }
                });
            }
        });

        // Delete modal functions
        let currentDeleteClassId = null;

        function openDeleteModal(classId, className) {
            currentDeleteClassId = classId;
            document.getElementById('delete-class-name').textContent = className;
            document.getElementById('delete-modal').classList.remove('hidden');
            document.getElementById('delete-modal').classList.add('flex');
            document.getElementById('hard-delete-checkbox').checked = false;
        }

        function closeDeleteModal() {
            document.getElementById('delete-modal').classList.add('hidden');
            document.getElementById('delete-modal').classList.remove('flex');
            currentDeleteClassId = null;
        }

        function confirmDelete() {
            if (!currentDeleteClassId) return;

            const hardDelete = document.getElementById('hard-delete-checkbox').checked;
            const form = document.getElementById('delete-form');
            form.action = `/classes/${currentDeleteClassId}`;
            document.getElementById('hard-delete-input').value = hardDelete ? '1' : '0';
            form.submit();
        }
    </script>

    <!-- Delete Confirmation Modal -->
    <div id="delete-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="if(event.target === this) closeDeleteModal()">
        <div class="bg-white rounded-xl shadow-xl p-6 w-96 max-w-full" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Delete Class</h3>
            <p class="text-gray-600 mb-4">
                Are you sure you want to delete "<span id="delete-class-name" class="font-semibold"></span>"?
            </p>

            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-3 mb-4">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            By default, classes are soft deleted (can be restored). Check the box below for permanent deletion.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2 mb-4 p-3 bg-gray-50 rounded">
                <input type="checkbox" id="hard-delete-checkbox" class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                <label for="hard-delete-checkbox" class="text-sm text-gray-700 cursor-pointer select-none">
                    <span class="font-medium text-red-600">Permanently delete</span> (cannot be undone)
                </label>
            </div>

            <form id="delete-form" method="POST" action="" style="display:none;">
                @csrf
                @method('DELETE')
                <input type="hidden" name="hard_delete" id="hard-delete-input" value="0">
            </form>

            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300">
                    Cancel
                </button>
                <button onclick="confirmDelete()" class="flex-1 px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">
                    Delete
                </button>
            </div>
        </div>
    </div>
</x-app-layout>
