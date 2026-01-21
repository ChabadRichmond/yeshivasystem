<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div class="flex items-center gap-3">
                <a href="{{ route('attendance.index', ['date' => $date]) }}" class="text-gray-600 hover:text-gray-800">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Take Attendance</h2>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('reports.attendance') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                    View Report
                </a>
            </div>
        </div>
    </x-slot>

    <style>
        /* Pending mark state for two-tap confirmation on mobile */
        .mark-btn.pending-mark {
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.5) !important;
            transform: scale(1.1);
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.15); }
        }

        /* List view - toggled by user via view mode button */
        #students-grid.list-view {
            display: flex !important;
            flex-direction: column !important;
            gap: 0.5rem !important;
        }
        
        .list-view .student-card {
            display: flex !important;
            flex-direction: row !important;
            align-items: center !important;
            padding: 0.75rem 1rem !important;
            gap: 1rem !important;
            text-align: left !important;
        }
        
        .list-view .student-card .avatar-container {
            margin-bottom: 0 !important;
        }
        
        .list-view .student-card .avatar-container > div,
        .list-view .student-card .avatar-container > img {
            width: 48px !important;
            height: 48px !important;
            font-size: 1rem !important;
        }
        
        .list-view .student-card .name-container {
            flex: 1 !important;
            text-align: left !important;
            margin-bottom: 0 !important;
        }
        
        .list-view .student-card .status-badge {
            margin-right: 0.5rem !important;
        }
        
        .list-view .student-card .action-buttons {
            margin-bottom: 0 !important;
        }
        
        .list-view .student-card .action-buttons button {
            width: 36px !important;
            height: 36px !important;
        }
        
        /* Show options menu in list view - inline style */
        .list-view .student-card .options-menu-container {
            display: inline-block !important;
            margin-left: auto;
        }

        /* Filtering class - overrides both grid and list view display */
        .student-card.hidden-filtered,
        .list-view .student-card.hidden-filtered {
            display: none !important;
        }

        /* Session bar - sticky on desktop only */
        #session-bar-container {
            z-index: 40;
            background: #f3f4f6;
        }
        /* Filter bar - always sticky */
        #filter-bar-container {
            position: sticky;
            top: 0;
            z-index: 35;
            background: #f3f4f6;
        }
        /* Desktop: session bar is sticky, filter bar positioned below it */
        @media (min-width: 768px) {
            #session-bar-container {
                position: sticky;
                top: 0;
            }
            #filter-bar-container {
                position: sticky;
                top: 115px; /* Height of session bar */
            }
        }
    </style>


    <div class="py-4">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Session & Date Display - Read Only -->
            <div id="session-bar-container" class="-mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 pt-2">
            <div class="bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl shadow-md p-4 mb-2 border-2 border-indigo-200">
                <div class="flex flex-wrap gap-6 items-center justify-center">
                    <div class="text-center">
                        <label class="block text-xs font-medium text-gray-500 mb-1">📚 SESSION</label>
                        <p class="text-xl font-bold text-gray-800">{{ $selectedClass->name }}</p>
                    </div>
                    <div class="h-8 w-px bg-gray-300"></div>
                    <div class="text-center">
                        <label class="block text-xs font-medium text-gray-500 mb-1">📅 DATE</label>
                        <p class="text-xl font-bold text-gray-800">{{ \Carbon\Carbon::parse($date)->format('l, M j, Y') }}</p>
                        <div id="hebrew-date-display" class="text-xs text-indigo-600 mt-1 font-medium" dir="rtl"></div>
                    </div>
                    <input type="hidden" id="gregorian-date" value="{{ $date }}">
                    @if($selectedClass)
                    <div class="h-8 w-px bg-gray-300"></div>
                    <div class="text-center">
                        <label class="block text-xs font-medium text-gray-500 mb-1">⏰ START</label>
                        <p class="text-lg font-semibold text-gray-700 cursor-pointer hover:text-indigo-600 transition-colors" onclick="editStartTime()" title="Click to edit start time">
                            <span id="start-time-display">{{ $classStartTime ? \Carbon\Carbon::parse($classStartTime)->format('g:i A') : 'N/A' }}</span>
                            <svg class="w-3 h-3 inline-block ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </p>
                    </div>
                    <div class="text-center">
                        <label class="block text-xs font-medium text-gray-500 mb-1">🏁 END</label>
                        <p class="text-lg font-semibold text-gray-700 cursor-pointer hover:text-indigo-600 transition-colors" onclick="editEndTime()" title="Click to edit end time">
                            <span id="end-time-display">{{ $classEndTime ? \Carbon\Carbon::parse($classEndTime)->format('g:i A') : 'N/A' }}</span>
                            <svg class="w-3 h-3 inline-block ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                            </svg>
                        </p>
                    </div>
                    @endif
                </div>
            </div>
            </div><!-- End Session Bar Sticky Container -->

            @if(isset($isCancelled) && $isCancelled)
            <!-- Cancelled Session Banner -->
            <div class="bg-gray-800 text-white rounded-xl p-4 mb-4 shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🚫</span>
                        <div>
                            <p class="font-bold text-lg">This session is cancelled</p>
                            @if(isset($cancellationReason) && $cancellationReason)
                                <p class="text-gray-300 text-sm">Reason: {{ $cancellationReason }}</p>
                            @endif
                        </div>
                    </div>
                    <form method="POST" action="{{ route('attendance.restore') }}" onsubmit="return confirm('Restore this session?');">
                        @csrf
                        <input type="hidden" name="class_id" value="{{ $classId }}">
                        <input type="hidden" name="date" value="{{ $date }}">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 font-medium">
                            ✓ Restore Session
                        </button>
                    </form>
                </div>
            </div>
            @else
            <!-- Cancel Session Button -->
            <div class="flex justify-end mb-2">
                <button onclick="showCancelModal()" class="px-3 py-1.5 text-sm bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                    Cancel Session
                </button>
            </div>
            @endif

            @if($selectedClass)
            <!-- Filter Buttons - Always sticky -->
            <div id="filter-bar-container" class="-mx-4 px-4 sm:-mx-6 sm:px-6 lg:-mx-8 lg:px-8 py-2">
                <div class="flex flex-wrap gap-2">
                    <button onclick="filterStudents('all')" class="filter-btn px-4 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300" data-filter="all">All</button>
                    <button onclick="filterStudents('unmarked')" class="filter-btn px-4 py-2 rounded-lg bg-blue-100 text-blue-700 hover:bg-blue-200" data-filter="unmarked">⬜ Unmarked</button>
                    <button onclick="filterStudents('present')" class="filter-btn px-4 py-2 rounded-lg bg-green-100 text-green-700 hover:bg-green-200" data-filter="present">✓ Present</button>
                    <button onclick="filterStudents('late')" class="filter-btn px-4 py-2 rounded-lg bg-orange-100 text-orange-700 hover:bg-orange-200" data-filter="late">⏰ Late</button>
                    <button onclick="filterStudents('absent')" class="filter-btn px-4 py-2 rounded-lg bg-red-100 text-red-700 hover:bg-red-200" data-filter="absent">✗ Absent</button>
                    <button onclick="filterStudents('left_early')" class="filter-btn px-4 py-2 rounded-lg bg-purple-100 text-purple-700 hover:bg-purple-200" data-filter="left_early">🚪 Left Early</button>
                    
                    <!-- Bulk Actions Dropdown -->
                    <div class="relative">
                        <button onclick="toggleBulkMenu()" id="bulk-btn" class="px-4 py-2 rounded-lg bg-indigo-100 text-indigo-700 hover:bg-indigo-200 flex items-center gap-2">
                            <span id="selected-count-display">0</span> Selected ▾
                        </button>
                        <div id="bulk-menu" class="hidden absolute right-0 top-full mt-1 w-48 bg-white rounded-lg shadow-lg border z-50">
                            <button onclick="bulkMark('present')" class="w-full text-left px-4 py-2 text-sm hover:bg-green-50 text-green-700 flex items-center gap-2 rounded-t-lg">✓ Mark All Present</button>
                            <button onclick="openBulkLatePopup()" class="w-full text-left px-4 py-2 text-sm hover:bg-orange-50 text-orange-700 flex items-center gap-2">⏰ Mark All Late</button>
                            <button onclick="bulkMark('absent')" class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 text-red-700 flex items-center gap-2">✗ Mark All Absent</button>
                            <hr class="my-1">
                            <button onclick="bulkUnmark()" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-gray-600 flex items-center gap-2">🔄 Unmark All</button>
                            <button onclick="clearAllSelections()" class="w-full text-left px-4 py-2 text-sm hover:bg-gray-100 text-gray-600 rounded-b-lg">🧹 Clear Selection</button>
                        </div>
                    </div>
                    
                    <!-- Clear Attendance Dropdown -->
                    <div class="relative">
                        <button onclick="toggleClearMenu()" id="clear-btn" class="px-4 py-2 rounded-lg bg-red-100 text-red-700 hover:bg-red-200 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Clear ▾
                        </button>
                        <div id="clear-menu" class="hidden absolute right-0 top-full mt-1 w-56 bg-white rounded-lg shadow-lg border z-50">
                            <button onclick="clearClassAttendance()" class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 text-red-700 flex items-center gap-2 rounded-t-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Clear This Session
                            </button>
                            <button onclick="clearDayAttendance()" class="w-full text-left px-4 py-2 text-sm hover:bg-red-50 text-red-700 flex items-center gap-2 rounded-b-lg">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                Clear Entire Day
                            </button>
                        </div>
                    </div>

                    <!-- View Toggle -->
                    <div class="ml-auto flex gap-1 bg-gray-100 rounded-lg p-1">
                        <button onclick="setViewMode('grid')" id="view-grid-btn" class="px-3 py-1.5 rounded-md bg-white shadow-sm text-gray-700" title="Grid View">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M5 3a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2V5a2 2 0 00-2-2H5zM5 11a2 2 0 00-2 2v2a2 2 0 002 2h2a2 2 0 002-2v-2a2 2 0 00-2-2H5zM11 5a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V5zM11 13a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        </button>
                        <button onclick="setViewMode('list')" id="view-list-btn" class="px-3 py-1.5 rounded-md text-gray-500 hover:bg-gray-200" title="List View">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z" clip-rule="evenodd"/></svg>
                        </button>
                    </div>
                </div>
            </div><!-- End Filter Bar Sticky Container -->


                <!-- Student Cards Grid -->
                @php
                    $showedSeparator = false;
                    $hasAttendanceTakerStudents = !empty($attendanceTakerStudentIds ?? []) && $students->contains(fn($s) => in_array($s->id, $attendanceTakerStudentIds));
                @endphp

                @if($hasAttendanceTakerStudents)
                    <div class="mb-4">
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-500 font-medium px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full">Assigned Students for This Class</span>
                            <div class="flex-grow h-px bg-gray-200"></div>
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4" id="students-grid">
                    @foreach($students as $student)
                        @php
                            $isAttendanceTakerStudent = in_array($student->id, $attendanceTakerStudentIds ?? []);
                            $isPrimaryStudent = in_array($student->id, $primaryStudentIds ?? []);

                            // Show separator when transitioning from attendance taker students to primary students
                            $needsSeparator = !$showedSeparator && $hasAttendanceTakerStudents && !$isAttendanceTakerStudent && $isPrimaryStudent;
                            if ($needsSeparator) $showedSeparator = true;

                            $attendance = $attendances[$student->id] ?? null;
                            $status = $attendance?->status ?? 'unmarked';
                            // Ring colors for photo border
                            $ringColor = match(true) {
                                str_starts_with($status, 'present') => 'ring-green-500',
                                str_starts_with($status, 'late') => 'ring-orange-400',
                                str_starts_with($status, 'absent') => 'ring-red-500',
                                str_starts_with($status, 'left_early') => 'ring-purple-500',
                                default => 'ring-gray-300'
                            };
                            // Status label colors
                            $statusLabelClass = match(true) {
                                str_starts_with($status, 'present') => 'text-green-600',
                                str_starts_with($status, 'late') => 'text-orange-500',
                                str_starts_with($status, 'absent') => 'text-red-500',
                                str_starts_with($status, 'left_early') => 'text-purple-600',
                                default => 'text-gray-400'
                            };
                        @endphp

                        @if($needsSeparator)
                            <div class="col-span-full my-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex-grow h-px bg-gray-300"></div>
                                    <span class="text-sm text-gray-500 font-medium px-3 py-1 bg-gray-100 rounded-full">Your Primary Students</span>
                                    <div class="flex-grow h-px bg-gray-300"></div>
                                </div>
                            </div>
                        @endif

                        <div class="student-card bg-white rounded-xl shadow-sm border border-gray-100 p-4 text-center transition-all hover:shadow-md"
                             data-student-id="{{ $student->id }}"
                             data-status="{{ $status }}"
                             data-left-early="{{ $attendance?->left_early ? 'true' : 'false' }}">
                            
                            <!-- Photo with Ring Border -->
                            <div class="avatar-container flex justify-center mb-3 relative cursor-pointer" onclick="toggleSelection({{ $student->id }})">
                                <!-- Selection overlay -->
                                <div class="selection-overlay hidden absolute inset-0 bg-indigo-500 bg-opacity-80 rounded-full flex items-center justify-center z-10 w-20 h-20 mx-auto" data-selection-overlay="{{ $student->id }}">
                                    <svg class="w-10 h-10 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </div>
                                @if($student->photo)
                                    <img src="{{ asset('storage/' . $student->photo) }}" 
                                         alt="{{ $student->first_name }}" 
                                         class="w-20 h-20 rounded-full object-cover ring-[6px] {{ $ringColor }}">
                                @else
                                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-gray-100 to-gray-200 flex items-center justify-center text-gray-500 text-xl font-bold ring-[6px] {{ $ringColor }}">
                                        {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                    </div>
                                @endif
                                @if($attendance?->left_early)
                                    <span class="absolute -top-1 -right-1 w-6 h-6 bg-purple-500 text-white text-xs rounded-full flex items-center justify-center" title="Left Early">🚪</span>
                                @endif
                            </div>
                            
                            <!-- Name with Role Badge -->
                            <div class="name-container mb-3">
                                <div class="text-sm font-semibold text-gray-800 truncate">{{ $student->last_name }}, {{ $student->first_name }}</div>
                                @if(auth()->user()->hasRole('Teacher') && isset($student->is_primary))
                                    @if($student->is_primary)
                                        <div class="flex items-center justify-center mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-amber-100 text-amber-800">
                                                ⭐ Your Student
                                            </span>
                                        </div>
                                    @else
                                        <div class="flex items-center justify-center mt-1">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                                                📋 Attendance Only
                                            </span>
                                        </div>
                                    @endif
                                @endif
                            </div>

                            <!-- Action Buttons - Larger -->
                            <div class="action-buttons flex items-center justify-center gap-2 mb-2">
                                <button onclick="handleMarkClick({{ $student->id }}, 'present', this)"
                                        data-student-id="{{ $student->id }}"
                                        data-status="present"
                                        class="mark-btn w-10 h-10 rounded-full flex items-center justify-center transition-all shadow-sm {{ str_starts_with($status, 'present') ? 'bg-green-500 text-white ring-2 ring-green-300' : 'bg-gray-50 text-green-600 hover:bg-green-100 border border-gray-200' }}" title="Present">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                </button>
                                <button onmousedown="startLongPress({{ $student->id }})"
                                        onmouseup="endLongPress({{ $student->id }})"
                                        onmouseleave="cancelLongPress()"
                                        ontouchstart="startLongPress({{ $student->id }})"
                                        ontouchend="endLongPress({{ $student->id }})"
                                        data-student-id="{{ $student->id }}"
                                        data-status="late"
                                        class="mark-btn w-10 h-10 rounded-full flex items-center justify-center transition-all shadow-sm {{ str_starts_with($status, 'late') ? 'bg-orange-500 text-white ring-2 ring-orange-300' : 'bg-gray-50 text-orange-500 hover:bg-orange-100 border border-gray-200' }}" title="Late (hold for options)">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                </button>
                                <button onclick="handleMarkClick({{ $student->id }}, 'absent', this)"
                                        data-student-id="{{ $student->id }}"
                                        data-status="absent"
                                        class="mark-btn w-10 h-10 rounded-full flex items-center justify-center transition-all shadow-sm {{ str_starts_with($status, 'absent') ? 'bg-red-500 text-white ring-2 ring-red-300' : 'bg-gray-50 text-red-500 hover:bg-red-100 border border-gray-200' }}" title="Absent">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                                </button>
                            </div>

                            <!-- Status Label - Clear and Prominent -->
                            <div class="status-badge">
                                <span class="status-label text-xs font-medium {{ $statusLabelClass }}">
                                    @if($status === 'unmarked')
                                        —
                                    @elseif(str_starts_with($status, 'present'))
                                        present
                                    @elseif(str_starts_with($status, 'late'))
                                        {{ str_contains($status, 'excused') ? 'late excused' : 'late' }}{{ $attendance?->minutes_late ? ' - '.$attendance->minutes_late : '' }}
                                    @elseif(str_starts_with($status, 'absent'))
                                        {{ str_contains($status, 'excused') ? 'absent excused' : 'absent' }}
                                    @elseif(str_starts_with($status, 'left_early'))
                                        left early
                                    @endif
                                </span>
                                @if($attendance?->absenceReason)
                                    <div class="text-xs text-gray-500 mt-0.5" title="Reason: {{ $attendance->absenceReason->name }}">{{ $attendance->absenceReason->name }}</div>
                                @endif
                                @if($attendance?->excusedByUser)
                                    <div class="text-xs text-gray-400 mt-0.5" title="Excused by {{ $attendance->excusedByUser->name }}">by {{ Str::limit($attendance->excusedByUser->name, 10) }}</div>
                                @endif
                            </div>

                            <!-- Note Indicator - shows when student has a note -->
                            @if($attendance?->notes)
                            <div class="mt-1">
                                <button onclick="showNotePopup('{{ addslashes($student->first_name) }} {{ addslashes($student->last_name) }}', '{{ addslashes($attendance->notes) }}')"
                                        class="inline-flex items-center gap-1 px-2 py-1 text-xs bg-blue-50 text-blue-600 rounded-full hover:bg-blue-100 transition"
                                        title="Click to view note">
                                    <span>📝</span>
                                    <span class="max-w-[80px] truncate">{{ Str::limit($attendance->notes, 15) }}</span>
                                </button>
                            </div>
                            @endif

                            <!-- 3-dot menu for extra options -->
                            <div class="options-menu-container relative mt-1">
                                <button onclick="toggleMenu({{ $student->id }})" class="text-gray-400 hover:text-gray-600 px-2 py-1" title="More options">
                                    •••
                                </button>
                                <div id="menu-{{ $student->id }}" class="hidden absolute left-1/2 -translate-x-1/2 top-full mt-1 w-44 bg-white rounded-lg shadow-lg border z-50">
                                    <button onclick="unmarkAttendance({{ $student->id }})" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded-t-lg">Unmark</button>
                                    <a href="{{ route('students.show', $student) }}" class="block w-full text-left px-3 py-2 text-sm hover:bg-gray-100">View Details</a>
                                    <button onclick="openExcusedPopup({{ $student->id }}, '{{ $student->first_name }} {{ $student->last_name }}')" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100">Mark Excused</button>
                                    <button onclick="openLatePopup({{ $student->id }}, '{{ $student->first_name }} {{ $student->last_name }}')" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100">Edit Late Time</button>
                                    <button onclick="openLeftEarlyPopup({{ $student->id }}, '{{ $student->first_name }} {{ $student->last_name }}')" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100">Left Early</button>
                                    <button onclick="openNotePopup({{ $student->id }}, '{{ $student->first_name }} {{ $student->last_name }}')" class="w-full text-left px-3 py-2 text-sm hover:bg-gray-100 rounded-b-lg">Add Note</button>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if($students->isEmpty())
                    <div class="text-center py-12 bg-white rounded-xl">
                        <p class="text-gray-500">No students assigned to this session.</p>
                        <a href="{{ route('classes.edit', $selectedClass) }}" class="text-indigo-600 hover:underline">Assign students →</a>
                    </div>
                @endif

                <!-- Select All Button -->
                @if(!$students->isEmpty())
                <div class="mt-4 flex justify-center">
                    <button onclick="selectAllStudents()" class="px-6 py-3 bg-indigo-100 text-indigo-700 rounded-lg hover:bg-indigo-200 font-medium flex items-center gap-2">
                        ☑️ Select All Students
                    </button>
                </div>
                @endif
            @else
                <div class="text-center py-12 bg-white rounded-xl shadow-md">
                    <div class="text-4xl mb-3">📚</div>
                    <p class="text-gray-500 mb-2">Select a session to take attendance</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Late/Left Early Popup Modal -->
    <div id="time-popup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closePopup(event)">
        <div class="bg-white rounded-xl shadow-xl p-5 w-80 max-w-full" onclick="event.stopPropagation()">
            <div id="popup-header" class="text-center mb-4">
                <div id="popup-title" class="text-lg font-bold text-orange-600">Late</div>
                <div id="popup-student" class="text-sm text-gray-600"></div>
            </div>
            
            <input type="hidden" id="popup-student-id">
            <input type="hidden" id="popup-type" value="late">
            
            <!-- Minutes Display -->
            <div class="text-center mb-3">
                <span id="selected-minutes" class="text-3xl font-bold text-orange-600">0</span>
                <span class="text-gray-500 ml-1">Minutes</span>
            </div>
            
            <!-- Minutes Grid -->
            <div style="display: grid; grid-template-columns: repeat(6, 1fr); gap: 4px; margin-bottom: 8px; text-align: center;">
                <button type="button" onclick="setMinutes(50)" style="padding: 8px; border-radius: 4px; background: #fee2e2; color: #b91c1c; border: none; cursor: pointer;">50</button>
                <button type="button" onclick="setMinutes(55)" style="padding: 8px; border-radius: 4px; background: #fee2e2; color: #b91c1c; border: none; cursor: pointer;">55</button>
                <button type="button" onclick="setMinutes(60)" style="padding: 8px; border-radius: 4px; background: #fee2e2; color: #b91c1c; border: none; cursor: pointer;">60</button>
                <button type="button" onclick="setMinutes(7)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">7</button>
                <button type="button" onclick="setMinutes(8)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">8</button>
                <button type="button" onclick="setMinutes(9)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">9</button>
                <button type="button" onclick="setMinutes(35)" style="padding: 8px; border-radius: 4px; background: #ffedd5; color: #c2410c; border: none; cursor: pointer;">35</button>
                <button type="button" onclick="setMinutes(40)" style="padding: 8px; border-radius: 4px; background: #ffedd5; color: #c2410c; border: none; cursor: pointer;">40</button>
                <button type="button" onclick="setMinutes(45)" style="padding: 8px; border-radius: 4px; background: #ffedd5; color: #c2410c; border: none; cursor: pointer;">45</button>
                <button type="button" onclick="setMinutes(4)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">4</button>
                <button type="button" onclick="setMinutes(5)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">5</button>
                <button type="button" onclick="setMinutes(6)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">6</button>
                <button type="button" onclick="setMinutes(20)" style="padding: 8px; border-radius: 4px; background: #fef3c7; color: #a16207; border: none; cursor: pointer;">20</button>
                <button type="button" onclick="setMinutes(25)" style="padding: 8px; border-radius: 4px; background: #fef3c7; color: #a16207; border: none; cursor: pointer;">25</button>
                <button type="button" onclick="setMinutes(30)" style="padding: 8px; border-radius: 4px; background: #fef3c7; color: #a16207; border: none; cursor: pointer;">30</button>
                <button type="button" onclick="setMinutes(1)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">1</button>
                <button type="button" onclick="setMinutes(2)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">2</button>
                <button type="button" onclick="setMinutes(3)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">3</button>
                <button type="button" onclick="setMinutes(5)" style="padding: 8px; border-radius: 4px; background: #dcfce7; color: #15803d; border: none; cursor: pointer;">5</button>
                <button type="button" onclick="setMinutes(10)" style="padding: 8px; border-radius: 4px; background: #dcfce7; color: #15803d; border: none; cursor: pointer;">10</button>
                <button type="button" onclick="setMinutes(15)" style="padding: 8px; border-radius: 4px; background: #dcfce7; color: #15803d; border: none; cursor: pointer;">15</button>
                <button type="button" onclick="setMinutes(0)" style="padding: 8px; border-radius: 4px; background: #f3f4f6; color: #374151; border: none; cursor: pointer;">0</button>
                <button type="button" onclick="setMinutes(0)" style="padding: 8px; border-radius: 4px; background: #e5e7eb; color: #374151; border: none; cursor: pointer;">C</button>
                <div></div>
            </div>
            
            <!-- Custom Minutes Input -->
            <div class="flex items-center gap-2 mb-4">
                <label class="text-sm text-gray-600 whitespace-nowrap">Or type:</label>
                <input type="number" id="custom-minutes-input" min="0" max="999" placeholder="Minutes" 
                       onchange="setMinutes(parseInt(this.value) || 0)"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-center text-lg font-bold focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
            </div>

            <!-- Excused Toggle -->
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding: 0 8px;">
                <span style="font-size: 14px; color: #374151;">Excused?</span>
                <div style="position: relative; display: inline-flex; align-items: center; cursor: pointer;" onclick="toggleExcused()">
                    <input type="checkbox" id="popup-excused" style="position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0;">
                    <div id="toggle-track" style="width: 44px; height: 24px; background: #d1d5db; border-radius: 12px; position: relative; transition: background 0.2s;">
                        <div id="toggle-thumb" style="width: 20px; height: 20px; background: white; border-radius: 50%; position: absolute; top: 2px; left: 2px; transition: transform 0.2s; box-shadow: 0 1px 3px rgba(0,0,0,0.2);"></div>
                    </div>
                </div>
            </div>

            <!-- Note -->
            <div class="mb-4">
                <input type="text" id="popup-note" placeholder="Note..." class="w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <!-- Buttons -->
            <div class="flex gap-2">
                <button onclick="clearPopupAndClose()" class="flex-1 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Clear</button>
                <button onclick="closePopup()" class="flex-1 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
                <button onclick="savePopup()" class="flex-1 px-3 py-2 text-sm bg-orange-500 text-white rounded hover:bg-orange-600">Save</button>
            </div>
        </div>
    </div>

    <!-- Excused Popup Modal -->
    <div id="reason-popup" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closeReasonPopup(event)">
        <div class="bg-white rounded-xl shadow-xl p-5 w-80 max-w-full" onclick="event.stopPropagation()">
            <div class="text-center mb-4">
                <div class="text-lg font-bold text-indigo-600">Mark as Excused</div>
                <div id="reason-popup-student" class="text-sm text-gray-600"></div>
                <div id="reason-popup-status-display" class="text-xs text-gray-500 mt-1"></div>
            </div>
            
            <input type="hidden" id="reason-popup-student-id">
            <input type="hidden" id="reason-status" value="absent_excused">
            
            <!-- Minutes Late (shown only for late) -->
            <div id="minutes-late-section" class="mb-4 hidden">
                <label class="block text-sm font-medium text-gray-700 mb-1">Minutes Late</label>
                <input type="number" id="excuse-minutes-late" min="0" max="120" value="0" class="w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <!-- Reason Dropdown -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Reason</label>
                <select id="reason-select" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                    <option value="">-- Select Reason --</option>
                    @php
                        $absenceReasons = \App\Models\AbsenceReason::active()->ordered()->get();
                    @endphp
                    @foreach($absenceReasons as $reason)
                        <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Note -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Note (optional)</label>
                <input type="text" id="reason-note" placeholder="Additional details..." class="w-full rounded-md border-gray-300 shadow-sm text-sm">
            </div>

            <!-- Buttons -->
            <div class="flex gap-2">
                <button onclick="closeReasonPopup()" class="flex-1 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Cancel</button>
                <button onclick="saveExcused()" class="flex-1 px-3 py-2 text-sm bg-indigo-600 text-white rounded hover:bg-indigo-700">Save as Excused</button>
            </div>
        </div>
    </div>

    <!-- Cancel Session Modal -->
    <div id="cancel-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" onclick="closeCancelModal(event)">
        <div class="bg-white rounded-xl shadow-xl p-5 w-96 max-w-full" onclick="event.stopPropagation()">
            <div class="text-center mb-4">
                <div class="text-xl font-bold text-gray-800">Cancel Session</div>
                <div class="text-sm text-gray-600 mt-1">{{ $selectedClass->name ?? 'Class' }} - {{ \Carbon\Carbon::parse($date)->format('M j, Y') }}</div>
            </div>

            <form method="POST" action="{{ route('attendance.cancel') }}" id="cancel-form">
                @csrf
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <input type="hidden" name="date" value="{{ $date }}">

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Reason (optional)</label>
                    <input type="text" name="reason" placeholder="e.g., Snow day, Holiday..." class="w-full rounded-md border-gray-300 shadow-sm text-sm">
                </div>

                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3 mb-4 text-sm text-yellow-800">
                    <strong>Note:</strong> Cancelling a session will:
                    <ul class="list-disc ml-4 mt-1">
                        <li>Remove any existing attendance marks</li>
                        <li>Exclude this session from reports</li>
                        <li>Show "C" in student history</li>
                    </ul>
                </div>

                <div class="flex gap-2">
                    <button type="button" onclick="closeCancelModal()" class="flex-1 px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded">Back</button>
                    <button type="submit" class="flex-1 px-3 py-2 text-sm bg-red-600 text-white rounded hover:bg-red-700">Cancel Session</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="fixed bottom-4 right-4 bg-green-600 text-white px-4 py-2 rounded-lg shadow-lg hidden z-50">Saved!</div>

    <script>
        const csrfToken = '{{ csrf_token() }}';
        const classId = {{ $selectedClass?->id ?? 'null' }};
        const attendanceDate = '{{ $date }}';
        let sessionStartTime = '{{ $classStartTime ?? "" }}';
        let sessionEndTime = '{{ $classEndTime ?? "" }}';
        
        // Update start time when custom time is set
        function updateStartTime(newTime) {
            sessionStartTime = newTime;
            saveTimeOverride();
            showToast('Start time: ' + newTime);
        }

        // Update end time when custom time is set
        function updateEndTime(newTime) {
            sessionEndTime = newTime;
            saveTimeOverride();
            showToast('End time: ' + newTime);
        }

        // Save time override to server so it persists across page loads
        async function saveTimeOverride() {
            try {
                const response = await fetch('{{ route("attendance.time-override") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        school_class_id: classId,
                        date: attendanceDate,
                        start_time: sessionStartTime || null,
                        end_time: sessionEndTime || null
                    })
                });
                if (!response.ok) {
                    console.error('Failed to save time override');
                }
            } catch (error) {
                console.error('Error saving time override:', error);
            }
        }

        // Edit start time - prompt user for new time
        function editStartTime() {
            const currentTime = sessionStartTime || '';
            const newTime = prompt('Enter new start time (HH:MM format, 24-hour):', currentTime);

            if (newTime !== null && newTime.trim() !== '') {
                // Validate time format
                const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
                if (timeRegex.test(newTime.trim())) {
                    updateStartTime(newTime.trim());
                    // Update display
                    const displayTime = formatTimeForDisplay(newTime.trim());
                    document.getElementById('start-time-display').textContent = displayTime;
                } else {
                    alert('Invalid time format. Please use HH:MM format (e.g., 09:30 or 14:45)');
                }
            }
        }

        // Edit end time - prompt user for new time
        function editEndTime() {
            const currentTime = sessionEndTime || '';
            const newTime = prompt('Enter new end time (HH:MM format, 24-hour):', currentTime);

            if (newTime !== null && newTime.trim() !== '') {
                // Validate time format
                const timeRegex = /^([0-1]?[0-9]|2[0-3]):[0-5][0-9]$/;
                if (timeRegex.test(newTime.trim())) {
                    updateEndTime(newTime.trim());
                    // Update display
                    const displayTime = formatTimeForDisplay(newTime.trim());
                    document.getElementById('end-time-display').textContent = displayTime;
                } else {
                    alert('Invalid time format. Please use HH:MM format (e.g., 09:30 or 14:45)');
                }
            }
        }

        // Format time for display (convert 24h to 12h with AM/PM)
        function formatTimeForDisplay(time24) {
            const [hours, minutes] = time24.split(':');
            let h = parseInt(hours);
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12 || 12; // Convert 0 to 12
            return `${h}:${minutes} ${ampm}`;
        }

        let longPressTimer = null;
        let longPressStudentId = null;
        const LONG_PRESS_DURATION = 700; // 700ms
        
        // Debug: log classId on page load
        console.log('Attendance page loaded - classId:', classId, 'date:', attendanceDate);

        // Hebrew month name mapping
        const hebrewMonths = {
            'Nisan': 1, 'Iyyar': 2, 'Sivan': 3, 'Tamuz': 4, 'Av': 5, 'Elul': 6,
            'Tishrei': 7, 'Cheshvan': 8, 'Kislev': 9, 'Tevet': 10, 'Shvat': 11, 'Adar': 12,
            'Adar I': 12, 'Adar II': 13
        };
        const hebrewMonthNames = {
            1: 'Nisan', 2: 'Iyyar', 3: 'Sivan', 4: 'Tamuz', 5: 'Av', 6: 'Elul',
            7: 'Tishrei', 8: 'Cheshvan', 9: 'Kislev', 10: 'Tevet', 11: 'Shvat', 12: 'Adar'
        };

        // Convert Hebrew date to Gregorian and submit form
        async function hebrewDateChanged() {
            const month = document.getElementById('hebrew-month').value;
            const day = document.getElementById('hebrew-day').value;
            const year = document.getElementById('hebrew-year').value;
            
            try {
                // Hebcal API converts Hebrew to Gregorian
                const url = `https://www.hebcal.com/converter?cfg=json&hy=${year}&hm=${month}&hd=${day}&h2g=1`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.gy && data.gm && data.gd) {
                    // Format as YYYY-MM-DD
                    const gregorianDate = `${data.gy}-${String(data.gm).padStart(2, '0')}-${String(data.gd).padStart(2, '0')}`;
                    document.getElementById('gregorian-date').value = gregorianDate;
                    document.getElementById('gregorian-date').form.submit();
                } else {
                    console.error('Invalid Hebrew date response:', data);
                }
            } catch (e) {
                console.error('Hebrew date conversion error:', e);
            }
        }

        // Initialize Hebrew date pickers from current Gregorian date
        async function initHebrewDate() {
            const gregorianDate = document.getElementById('gregorian-date').value;
            if (!gregorianDate) return;
            
            try {
                const [year, month, day] = gregorianDate.split('-');
                const url = `https://www.hebcal.com/converter?cfg=json&gy=${year}&gm=${parseInt(month)}&gd=${parseInt(day)}&g2h=1`;
                const response = await fetch(url);
                const data = await response.json();
                
                if (data.hd && data.hm && data.hy) {
                    // Update Hebrew date display
                    document.getElementById('hebrew-date-display').textContent = data.hebrew || '';
                    
                    // Update the Hebrew date picker dropdowns
                    document.getElementById('hebrew-day').value = data.hd;
                    document.getElementById('hebrew-year').value = data.hy;
                    
                    // Find and select the correct month option
                    const monthSelect = document.getElementById('hebrew-month');
                    for (const opt of monthSelect.options) {
                        if (opt.value === data.hm) {
                            opt.selected = true;
                            break;
                        }
                    }
                }
            } catch (e) {
                console.error('Hebrew date init error:', e);
            }
        }

        // Initialize Hebrew date on page load
        initHebrewDate();

        // Filter students
        function filterStudents(filter) {
            document.querySelectorAll('.student-card').forEach(card => {
                const status = card.dataset.status;
                const leftEarly = card.dataset.leftEarly === 'true';
                let show = false;
                if (filter === 'all') show = true;
                else if (filter === 'unmarked') show = status === 'unmarked';
                else if (filter === 'present') show = status.startsWith('present');
                else if (filter === 'late') show = status.startsWith('late');
                else if (filter === 'absent') show = status.startsWith('absent');
                else if (filter === 'left_early') show = leftEarly;
                // Use class toggle instead of inline style to work with list view
                card.classList.toggle('hidden-filtered', !show);
            });
            document.querySelectorAll('.filter-btn').forEach(btn => {
                btn.classList.toggle('ring-2', btn.dataset.filter === filter);
                btn.classList.toggle('ring-offset-2', btn.dataset.filter === filter);
            });
        }

        // Two-tap confirmation for mobile devices (prevent accidental marks while scrolling)
        let pendingMarkButton = null;
        let pendingMarkTimeout = null;
        const isTouchDevice = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
        const TWO_TAP_TIMEOUT = 3000; // 3 seconds before pending state expires

        function handleMarkClick(studentId, status, button) {
            // Desktop or non-touch device: execute immediately (single click)
            if (!isTouchDevice) {
                markAttendance(studentId, status);
                return;
            }

            // Mobile/Touch device: require two taps
            const buttonKey = `${studentId}-${status}`;

            // Check if this is the second tap on the same button
            if (pendingMarkButton === buttonKey) {
                // Second tap - confirm and execute
                clearTimeout(pendingMarkTimeout);
                clearPendingMarkState();
                markAttendance(studentId, status);
            } else {
                // First tap - show pending state
                clearPendingMarkState(); // Clear any existing pending state
                pendingMarkButton = buttonKey;
                button.classList.add('pending-mark');

                // Show visual feedback
                const originalTitle = button.getAttribute('title');
                button.setAttribute('title', 'Tap again to confirm');
                button.style.animation = 'pulse 0.5s ease-in-out';

                // Auto-clear pending state after timeout
                pendingMarkTimeout = setTimeout(() => {
                    clearPendingMarkState();
                    button.setAttribute('title', originalTitle);
                    button.style.animation = '';
                }, TWO_TAP_TIMEOUT);
            }
        }

        function clearPendingMarkState() {
            if (pendingMarkButton) {
                // Remove pending class from all buttons
                document.querySelectorAll('.mark-btn.pending-mark').forEach(btn => {
                    btn.classList.remove('pending-mark');
                    btn.style.animation = '';
                });
                pendingMarkButton = null;
            }
            if (pendingMarkTimeout) {
                clearTimeout(pendingMarkTimeout);
                pendingMarkTimeout = null;
            }
        }

        // Clear pending state when scrolling (user is navigating, not marking)
        let scrollTimeout;
        window.addEventListener('scroll', () => {
            if (isTouchDevice && pendingMarkButton) {
                clearTimeout(scrollTimeout);
                scrollTimeout = setTimeout(() => {
                    clearPendingMarkState();
                }, 100);
            }
        }, { passive: true });

        // Mark attendance (quick click)
        async function markAttendance(studentId, status, minutesLate = null, minutesEarly = null, notes = null, excused = false) {
            showToast('Saving...');
            
            // Calculate minutes late if not provided and marking as late
            if (status === 'late' && minutesLate === null && sessionStartTime) {
                const now = new Date();
                const [h, m] = sessionStartTime.split(':');
                const start = new Date();
                start.setHours(parseInt(h), parseInt(m), 0);
                minutesLate = Math.max(0, Math.round((now - start) / 60000));
                console.log('Auto-calculated minutes late:', minutesLate, 'from start time:', sessionStartTime);
            }
            
            // Adjust status for excused
            if (excused && (status === 'late' || status === 'absent')) {
                status = status + '_excused';
            }
            
            console.log('Saving attendance:', { studentId, classId, attendanceDate, status, minutesLate });
            
            try {
                const response = await fetch('{{ route("attendance.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        student_id: studentId,
                        school_class_id: classId,
                        date: attendanceDate,
                        status: status,
                        minutes_late: minutesLate,
                        minutes_early: minutesEarly,
                        notes: notes
                    })
                });
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Save response:', data);
                    showToast('Saved!');
                    updateCardUI(studentId, status, minutesLate);
                } else {
                    const errorText = await response.text();
                    console.error('Save failed:', response.status, errorText);
                    showToast('Error: ' + response.status, true);
                }
            } catch (e) {
                console.error('Save exception:', e);
                showToast('Error saving', true);
            }
        }

        // New function: Mark left early - adds left_early flag without changing status
        async function markLeftEarly(studentId, minutesEarly = 0, notes = null, excused = false) {
            console.log('Marking left early:', { studentId, minutesEarly, excused });
            
            try {
                const response = await fetch('{{ route("attendance.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        student_id: studentId,
                        school_class_id: classId,
                        date: attendanceDate,
                        left_early: true,
                        left_early_excused: excused,
                        minutes_early: minutesEarly,
                        notes: notes
                    })
                });
                
                if (response.ok) {
                    showToast('Left Early marked!');
                    // Add door icon to show left early
                    const card = document.querySelector(`[data-student-id="${studentId}"]`);
                    if (card) {
                        let indicator = card.querySelector('.left-early-indicator');
                        if (!indicator) {
                            indicator = document.createElement('span');
                            indicator.className = 'left-early-indicator absolute top-1 right-1 text-sm';
                            indicator.textContent = '🚪';
                            indicator.title = 'Left Early' + (excused ? ' (Excused)' : '');
                            card.style.position = 'relative';
                            card.appendChild(indicator);
                        }
                    }
                } else {
                    const errorText = await response.text();
                    console.error('Left early save failed:', response.status, errorText);
                    showToast('Error: ' + response.status, true);
                }
            } catch (e) {
                console.error('Left early exception:', e);
                showToast('Error saving', true);
            }
        }
        function updateCardUI(studentId, status, minutesLate) {
            const card = document.querySelector(`[data-student-id="${studentId}"]`);
            if (!card) return;
            
            card.dataset.status = status;
            
            // New design: white cards with ring colors on avatar
            let ringColor = 'ring-gray-300';
            let labelClass = 'text-gray-400';
            let labelText = '—';
            
            if (status.startsWith('present')) {
                ringColor = 'ring-green-500';
                labelClass = 'text-green-600';
                labelText = 'present';
            } else if (status.startsWith('late')) {
                ringColor = 'ring-orange-400';
                labelClass = 'text-orange-500';
                labelText = (status.includes('excused') ? 'late excused' : 'late') + (minutesLate ? ' - ' + minutesLate : '');
            } else if (status.startsWith('absent')) {
                ringColor = 'ring-red-500';
                labelClass = 'text-red-500';
                labelText = status.includes('excused') ? 'absent excused' : 'absent';
            } else if (status.startsWith('left_early')) {
                ringColor = 'ring-purple-500';
                labelClass = 'text-purple-600';
                labelText = 'left early';
            }
            
            // Update avatar ring color (now uses ring-[6px] class)
            const avatar = card.querySelector('[class*="ring-"]');
            if (avatar) {
                avatar.className = avatar.className.replace(/ring-(green|orange|red|purple|gray)-\d+/g, ringColor);
            }
            
            // Update status label
            const label = card.querySelector('.status-label');
            if (label) {
                label.className = 'status-label text-xs font-medium ' + labelClass;
                label.textContent = labelText;
            }
            
            // Update action buttons
            updateButtonStates(card, status);
        }
        
        function updateButtonStates(card, status) {
            const buttons = card.querySelectorAll('.action-buttons button');
            if (buttons.length < 3) return;
            
            const [presentBtn, lateBtn, absentBtn] = buttons;
            
            // Reset all buttons to default state
            const defaultClass = 'bg-gray-50 border border-gray-200';
            const activeClasses = {
                present: 'bg-green-500 text-white ring-2 ring-green-300',
                late: 'bg-orange-500 text-white ring-2 ring-orange-300',
                absent: 'bg-red-500 text-white ring-2 ring-red-300'
            };
            
            // Present button
            if (status.startsWith('present')) {
                presentBtn.className = presentBtn.className.replace(/bg-\S+/g, '').replace(/text-\S+/g, '').replace(/ring-\S+/g, '').replace(/border-\S+/g, '') + ' ' + activeClasses.present;
            } else {
                presentBtn.className = presentBtn.className.replace(/bg-\S+/g, '').replace(/ring-\d+/g, '').replace(/border-\S+/g, '') + ' ' + defaultClass + ' text-green-600 hover:bg-green-100';
            }
            
            // Late button  
            if (status.startsWith('late')) {
                lateBtn.className = lateBtn.className.replace(/bg-\S+/g, '').replace(/text-\S+/g, '').replace(/ring-\S+/g, '').replace(/border-\S+/g, '') + ' ' + activeClasses.late;
            } else {
                lateBtn.className = lateBtn.className.replace(/bg-\S+/g, '').replace(/ring-\d+/g, '').replace(/border-\S+/g, '') + ' ' + defaultClass + ' text-orange-500 hover:bg-orange-100';
            }
            
            // Absent button
            if (status.startsWith('absent')) {
                absentBtn.className = absentBtn.className.replace(/bg-\S+/g, '').replace(/text-\S+/g, '').replace(/ring-\S+/g, '').replace(/border-\S+/g, '') + ' ' + activeClasses.absent;
            } else {
                absentBtn.className = absentBtn.className.replace(/bg-\S+/g, '').replace(/ring-\d+/g, '').replace(/border-\S+/g, '') + ' ' + defaultClass + ' text-red-500 hover:bg-red-100';
            }
        }

        // Long press for late - with mobile two-tap confirmation and auto-calculated minutes
        let pendingLateButton = null;
        let pendingLateTimeout = null;
        const LATE_TWO_TAP_TIMEOUT = 3000;

        function startLongPress(studentId, event) {
            longPressStudentId = studentId;
            longPressTimer = setTimeout(() => {
                const card = document.querySelector(`[data-student-id="${studentId}"]`);
                const nameEl = card.querySelector('.name-container .text-sm');
                const name = nameEl ? nameEl.textContent.trim() : 'Student';
                openLatePopup(studentId, name);
                longPressTimer = null; // Prevent endLongPress from also firing
            }, LONG_PRESS_DURATION);
        }

        function endLongPress(studentId, event) {
            // If long press already triggered popup, don't do anything
            if (!longPressTimer) {
                longPressStudentId = null;
                return;
            }

            clearTimeout(longPressTimer);
            longPressTimer = null;

            // Get the button element
            const card = document.querySelector(`[data-student-id="${studentId}"]`);
            const button = card?.querySelector('[data-status="late"]');
            const buttonKey = `late-${studentId}`;

            // Mobile/Touch device: require two taps with confirmation
            if (isTouchDevice) {
                // Check if this is the second tap on the same button
                if (pendingLateButton === buttonKey) {
                    // Second tap - show confirmation with calculated minutes
                    clearTimeout(pendingLateTimeout);
                    clearPendingLateState();
                    showLateConfirmation(studentId);
                } else {
                    // First tap - show pending state
                    clearPendingLateState();
                    pendingLateButton = buttonKey;
                    if (button) {
                        button.classList.add('pending-mark');
                        button.style.animation = 'pulse 0.5s ease-in-out';
                    }

                    // Auto-clear pending state after timeout
                    pendingLateTimeout = setTimeout(() => {
                        clearPendingLateState();
                    }, LATE_TWO_TAP_TIMEOUT);
                }
            } else {
                // Desktop: show confirmation directly
                showLateConfirmation(studentId);
            }
        }

        function clearPendingLateState() {
            if (pendingLateButton) {
                document.querySelectorAll('.mark-btn[data-status="late"].pending-mark').forEach(btn => {
                    btn.classList.remove('pending-mark');
                    btn.style.animation = '';
                });
                pendingLateButton = null;
            }
            if (pendingLateTimeout) {
                clearTimeout(pendingLateTimeout);
                pendingLateTimeout = null;
            }
        }

        function showLateConfirmation(studentId) {
            const card = document.querySelector(`[data-student-id="${studentId}"]`);
            const nameEl = card?.querySelector('.name-container .text-sm');
            const studentName = nameEl ? nameEl.textContent.trim() : 'Student';

            // Calculate minutes late
            let calculatedMinutes = 0;
            let startTimeDisplay = 'N/A';

            if (sessionStartTime) {
                const now = new Date();
                const [h, m] = sessionStartTime.split(':');
                const start = new Date();
                start.setHours(parseInt(h), parseInt(m), 0);
                calculatedMinutes = Math.max(0, Math.round((now - start) / 60000));
                startTimeDisplay = formatTimeForDisplay(sessionStartTime);
            }

            // If no start time, open the full popup for manual entry
            if (!sessionStartTime) {
                openLatePopup(studentId, studentName);
                return;
            }

            // Show confirmation dialog with calculated minutes
            const confirmMessage = `Mark ${studentName} as late?\n\nCalculated: ${calculatedMinutes} minutes late\nClass started at: ${startTimeDisplay}\n\nClick OK to confirm, or Cancel to enter different minutes.`;

            if (confirm(confirmMessage)) {
                // User confirmed - save with calculated minutes
                markAttendance(studentId, 'late', calculatedMinutes);
            } else {
                // User cancelled - open popup for manual entry
                openLatePopup(studentId, studentName);
                // Pre-fill with calculated minutes
                document.getElementById('selected-minutes').textContent = calculatedMinutes;
                document.getElementById('custom-minutes-input').value = calculatedMinutes;
            }
        }

        function cancelLongPress() {
            if (longPressTimer) {
                clearTimeout(longPressTimer);
                longPressTimer = null;
            }
        }

        // Toggle menu
        function toggleMenu(studentId) {
            document.querySelectorAll('[id^="menu-"]').forEach(m => {
                if (m.id !== 'menu-' + studentId) m.classList.add('hidden');
            });
            document.getElementById('menu-' + studentId).classList.toggle('hidden');
        }
        
        // Close menus on outside click
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[id^="menu-"]') && !e.target.closest('button')) {
                document.querySelectorAll('[id^="menu-"]').forEach(m => m.classList.add('hidden'));
            }
        });

        // Popup functions
        function openLatePopup(studentId, studentName, preExcused = false) {
            document.getElementById('popup-student-id').value = studentId;
            document.getElementById('popup-student').textContent = studentName;
            document.getElementById('popup-title').textContent = 'Late';
            document.getElementById('popup-title').className = 'text-lg font-bold text-orange-600';
            document.getElementById('popup-type').value = 'late';
            document.getElementById('selected-minutes').textContent = '0';
            document.getElementById('selected-minutes').className = 'text-3xl font-bold text-orange-600';
            document.getElementById('popup-excused').checked = preExcused;
            if (preExcused) {
                document.getElementById('toggle-track').style.background = '#22c55e';
                document.getElementById('toggle-thumb').style.transform = 'translateX(20px)';
            } else {
                document.getElementById('toggle-track').style.background = '#d1d5db';
                document.getElementById('toggle-thumb').style.transform = 'translateX(0)';
            }
            document.getElementById('popup-note').value = '';
            document.getElementById('time-popup').classList.remove('hidden');
            document.getElementById('time-popup').classList.add('flex');
            closeAllMenus();
        }
        
        function openLeftEarlyPopup(studentId, studentName) {
            document.getElementById('popup-student-id').value = studentId;
            document.getElementById('popup-student').textContent = studentName;
            document.getElementById('popup-title').textContent = 'Left Early';
            document.getElementById('popup-title').className = 'text-lg font-bold text-purple-600';
            document.getElementById('popup-type').value = 'left_early';
            document.getElementById('selected-minutes').textContent = '0';
            document.getElementById('selected-minutes').className = 'text-3xl font-bold text-purple-600';
            document.getElementById('popup-excused').checked = false;
            document.getElementById('toggle-track').style.background = '#d1d5db';
            document.getElementById('toggle-thumb').style.transform = 'translateX(0)';
            document.getElementById('popup-note').value = '';
            document.getElementById('time-popup').classList.remove('hidden');
            document.getElementById('time-popup').classList.add('flex');
            closeAllMenus();
        }
        
        function openNotePopup(studentId, studentName) {
            const note = prompt('Add note for ' + studentName + ':');
            if (note !== null) {
                // Save note via API
                fetch('{{ route("attendance.store") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        student_id: studentId,
                        school_class_id: classId,
                        date: attendanceDate,
                        notes: note,
                        update_note_only: true
                    })
                }).then(() => showToast('Note saved!'));
            }
            closeAllMenus();
        }

        // Show note in a popup modal
        function showNotePopup(studentName, note) {
            const existingModal = document.getElementById('note-view-modal');
            if (existingModal) existingModal.remove();

            const modal = document.createElement('div');
            modal.id = 'note-view-modal';
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.onclick = (e) => { if (e.target === modal) modal.remove(); };
            modal.innerHTML = `
                <div class="bg-white rounded-xl shadow-xl p-5 w-96 max-w-full mx-4" onclick="event.stopPropagation()">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-bold text-gray-800">📝 Note for ${studentName}</h3>
                        <button onclick="document.getElementById('note-view-modal').remove()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <p class="text-gray-700 whitespace-pre-wrap">${note}</p>
                    <div class="mt-4 flex justify-end">
                        <button onclick="document.getElementById('note-view-modal').remove()" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200">Close</button>
                    </div>
                </div>
            `;
            document.body.appendChild(modal);
        }
        
        function setMinutes(mins) {
            document.getElementById('selected-minutes').textContent = mins;
            const input = document.getElementById('custom-minutes-input');
            if (input) input.value = mins;
        }

        // === UNMARK ATTENDANCE ===
        function unmarkAttendance(studentId) {
            if (!confirm('Remove attendance record for this student?')) {
                closeAllMenus();
                return;
            }
            
            fetch('{{ route("attendance.destroy") }}', {
                method: 'DELETE',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    student_id: studentId,
                    school_class_id: classId,
                    date: attendanceDate
                })
            }).then(res => {
                if (res.ok) {
                    const card = document.querySelector(`[data-student-id="${studentId}"]`);
                    if (card) {
                        // Reset to unmarked state
                        card.setAttribute('data-status', 'unmarked');
                        card.querySelector('.status-label').textContent = '—';
                        card.querySelector('.status-label').className = 'status-label text-xs font-medium text-gray-400';
                        // Clear reason and excused_by display (remove all such divs)
                        card.querySelectorAll('.status-badge div.text-gray-500, .status-badge div.text-gray-400').forEach(el => el.remove());
                        const buttons = card.querySelectorAll('.action-buttons button');
                        buttons.forEach(btn => {
                            btn.classList.remove('bg-green-500', 'bg-orange-500', 'bg-red-500', 'text-white', 'ring-2', 'ring-green-300', 'ring-orange-300', 'ring-red-300');
                            btn.classList.add('bg-gray-50', 'border', 'border-gray-200');
                        });
                    }
                    showToast('Unmarked!');
                }
            });
            closeAllMenus();
        }

        // === EXCUSED POPUP ===
        function openExcusedPopup(studentId, studentName) {
            const card = document.querySelector(`[data-student-id="${studentId}"]`);
            const currentStatus = card ? card.getAttribute('data-status') : 'absent';
            
            // Determine if late or absent based on current status
            let isLate = currentStatus.startsWith('late');
            let status = isLate ? 'late_excused' : 'absent_excused';
            
            document.getElementById('reason-popup-student-id').value = studentId;
            document.getElementById('reason-popup-student').textContent = studentName;
            document.getElementById('reason-status').value = status;
            document.getElementById('reason-select').value = '';
            document.getElementById('reason-note').value = '';
            
            // Show status info
            const statusDisplay = document.getElementById('reason-popup-status-display');
            statusDisplay.textContent = isLate ? '(Currently marked as Late)' : '(Currently marked as Absent)';
            statusDisplay.className = isLate ? 'text-xs text-orange-600 mt-1' : 'text-xs text-red-600 mt-1';
            
            // Show/hide minutes late section
            const minutesSection = document.getElementById('minutes-late-section');
            if (isLate) {
                minutesSection.classList.remove('hidden');
                // Pre-fill with existing minutes if any
                const minutesEl = card?.querySelector('.status-label');
                const minutesMatch = minutesEl?.textContent.match(/(\d+)/);
                document.getElementById('excuse-minutes-late').value = minutesMatch ? minutesMatch[1] : '0';
            } else {
                minutesSection.classList.add('hidden');
            }
            
            document.getElementById('reason-popup').classList.remove('hidden');
            document.getElementById('reason-popup').classList.add('flex');
            closeAllMenus();
        }

        function closeReasonPopup(event) {
            if (!event || event.target === document.getElementById('reason-popup')) {
                document.getElementById('reason-popup').classList.add('hidden');
                document.getElementById('reason-popup').classList.remove('flex');
            }
        }

        function saveExcused() {
            const studentId = document.getElementById('reason-popup-student-id').value;
            const status = document.getElementById('reason-status').value;
            const reasonSelect = document.getElementById('reason-select');
            const reasonId = reasonSelect.value;
            const reasonText = reasonSelect.options[reasonSelect.selectedIndex]?.text || '';
            const note = document.getElementById('reason-note').value;
            const minutesLate = status === 'late_excused' ? 
                parseInt(document.getElementById('excuse-minutes-late').value) || 0 : null;

            fetch('{{ route("attendance.store") }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({
                    student_id: studentId,
                    school_class_id: classId,
                    date: attendanceDate,
                    status: status,
                    minutes_late: minutesLate,
                    absence_reason_id: reasonId || null,
                    notes: note || null,
                    class_start_time: sessionStartTime,
                    class_end_time: sessionEndTime
                })
            }).then(res => {
                if (!res.ok) throw new Error('Save failed');
                return res.json();
            }).then(data => {
                updateCardUI(studentId, status, minutesLate);
                
                // Immediately inject reason and excused_by into the card
                const card = document.querySelector(`[data-student-id="${studentId}"]`);
                if (card) {
                    const statusBadge = card.querySelector('.status-badge');
                    if (statusBadge) {
                        // Remove existing reason/excused elements if any
                        statusBadge.querySelectorAll('div.text-gray-500, div.text-gray-400').forEach(el => el.remove());
                        
                        // Add reason display if selected
                        if (reasonId && reasonText && reasonText !== '-- Select Reason --') {
                            const reasonDiv = document.createElement('div');
                            reasonDiv.className = 'text-xs text-gray-500 mt-0.5';
                            reasonDiv.textContent = '📋 ' + reasonText;
                            reasonDiv.title = 'Reason: ' + reasonText;
                            statusBadge.appendChild(reasonDiv);
                        }
                        
                        // Add excused_by display
                        const excusedByDiv = document.createElement('div');
                        excusedByDiv.className = 'text-xs text-gray-400 mt-0.5';
                        excusedByDiv.textContent = 'by {{ Auth::user()->name }}';
                        excusedByDiv.title = 'Excused by {{ Auth::user()->name }}';
                        statusBadge.appendChild(excusedByDiv);
                    }
                }
                
                showToast('Marked as Excused!');
                closeReasonPopup();
            }).catch(err => {
                console.error('Error saving excused:', err);
                showToast('Error saving', true);
            });
        }
        
        // === VIEW MODE TOGGLE ===
        let currentViewMode = localStorage.getItem('attendanceViewMode') || 'grid';
        
        function setViewMode(mode) {
            currentViewMode = mode;
            localStorage.setItem('attendanceViewMode', mode);
            
            const grid = document.getElementById('students-grid');
            const gridBtn = document.getElementById('view-grid-btn');
            const listBtn = document.getElementById('view-list-btn');
            
            if (mode === 'list') {
                grid.classList.add('list-view');
                gridBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
                gridBtn.classList.add('text-gray-500', 'hover:bg-gray-200');
                listBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
                listBtn.classList.remove('text-gray-500', 'hover:bg-gray-200');
            } else {
                grid.classList.remove('list-view');
                listBtn.classList.remove('bg-white', 'shadow-sm', 'text-gray-700');
                listBtn.classList.add('text-gray-500', 'hover:bg-gray-200');
                gridBtn.classList.add('bg-white', 'shadow-sm', 'text-gray-700');
                gridBtn.classList.remove('text-gray-500', 'hover:bg-gray-200');
            }
        }
        
        // Apply saved view mode on load
        document.addEventListener('DOMContentLoaded', () => {
            if (currentViewMode) setViewMode(currentViewMode);
        });
        
        function toggleExcused() {
            const checkbox = document.getElementById('popup-excused');
            const track = document.getElementById('toggle-track');
            const thumb = document.getElementById('toggle-thumb');
            checkbox.checked = !checkbox.checked;
            if (checkbox.checked) {
                track.style.background = '#22c55e';
                thumb.style.transform = 'translateX(20px)';
            } else {
                track.style.background = '#d1d5db';
                thumb.style.transform = 'translateX(0)';
            }
        }
        
        function closePopup(event) {
            if (!event || event.target === document.getElementById('time-popup')) {
                document.getElementById('time-popup').classList.add('hidden');
                document.getElementById('time-popup').classList.remove('flex');
            }
        }
        
        function clearPopupAndClose() {
            const studentId = document.getElementById('popup-student-id').value;
            markAttendance(studentId, 'unmarked');
            closePopup();
        }
        
        function savePopup() {
            const studentId = document.getElementById('popup-student-id').value;
            const type = document.getElementById('popup-type').value;
            const minutes = parseInt(document.getElementById('selected-minutes').textContent);
            const excused = document.getElementById('popup-excused').checked;
            const note = document.getElementById('popup-note').value;
            
            if (type === 'late') {
                markAttendance(studentId, 'late', minutes, null, note, excused);
            } else if (type === 'left_early') {
                markLeftEarly(studentId, minutes, note, excused);
            }
            closePopup();
        }
        
        function closeAllMenus() {
            document.querySelectorAll('[id^="menu-"]').forEach(m => m.classList.add('hidden'));
        }

        function showToast(msg, isError = false) {
            const toast = document.getElementById('toast');
            toast.textContent = msg;
            toast.className = 'fixed bottom-4 right-4 px-4 py-2 rounded-lg shadow-lg z-50 ' + (isError ? 'bg-red-600 text-white' : 'bg-green-600 text-white');
            toast.classList.remove('hidden');
            setTimeout(() => toast.classList.add('hidden'), 2000);
        }
        
        // === MULTI-SELECT BULK MARKING ===
        let selectedStudents = new Set();
        
        function toggleSelection(studentId) {
            const overlay = document.querySelector(`[data-selection-overlay="${studentId}"]`);
            if (selectedStudents.has(studentId)) {
                selectedStudents.delete(studentId);
                overlay?.classList.add('hidden');
            } else {
                selectedStudents.add(studentId);
                overlay?.classList.remove('hidden');
            }
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            document.getElementById('selected-count-display').textContent = selectedStudents.size;
        }
        
        function toggleBulkMenu() {
            const menu = document.getElementById('bulk-menu');
            menu.classList.toggle('hidden');
        }
        
        function clearAllSelections() {
            selectedStudents.forEach(id => {
                const overlay = document.querySelector(`[data-selection-overlay="${id}"]`);
                overlay?.classList.add('hidden');
            });
            selectedStudents.clear();
            updateSelectedCount();
            document.getElementById('bulk-menu').classList.add('hidden');
            showToast('Selection cleared');
        }
        
        function selectAllStudents() {
            document.querySelectorAll('.student-card:not(.hidden-filtered)').forEach(card => {
                const studentId = parseInt(card.dataset.studentId);
                if (!selectedStudents.has(studentId)) {
                    selectedStudents.add(studentId);
                    const overlay = document.querySelector(`[data-selection-overlay="${studentId}"]`);
                    overlay?.classList.remove('hidden');
                }
            });
            updateSelectedCount();
            showToast(`Selected ${selectedStudents.size} students`);
        }
        
        async function bulkMark(status) {
            if (selectedStudents.size === 0) {
                showToast('No students selected', true);
                return;
            }

            document.getElementById('bulk-menu').classList.add('hidden');
            const count = selectedStudents.size;

            for (const studentId of selectedStudents) {
                await markAttendance(studentId, status);
                const overlay = document.querySelector(`[data-selection-overlay="${studentId}"]`);
                overlay?.classList.add('hidden');
            }

            selectedStudents.clear();
            updateSelectedCount();
            showToast(`Marked ${count} students as ${status}`);
        }

        async function bulkUnmark() {
            if (selectedStudents.size === 0) {
                showToast('No students selected', true);
                return;
            }

            if (!confirm(`Unmark attendance for ${selectedStudents.size} selected students?`)) {
                return;
            }

            document.getElementById('bulk-menu').classList.add('hidden');
            const count = selectedStudents.size;

            for (const studentId of selectedStudents) {
                await fetch('{{ route("attendance.destroy") }}', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                    body: JSON.stringify({
                        student_id: studentId,
                        school_class_id: classId,
                        date: attendanceDate
                    })
                });

                // Update UI
                const card = document.querySelector(`[data-student-id="${studentId}"]`);
                if (card) {
                    card.setAttribute('data-status', 'unmarked');
                    card.querySelector('.status-label').textContent = '—';
                    card.querySelector('.status-label').className = 'status-label text-xs font-medium text-gray-400';
                    card.querySelectorAll('.status-badge div.text-gray-500, .status-badge div.text-gray-400').forEach(el => el.remove());
                    const buttons = card.querySelectorAll('.action-buttons button');
                    buttons.forEach(btn => {
                        btn.classList.remove('bg-green-500', 'bg-orange-500', 'bg-red-500', 'text-white', 'ring-2', 'ring-green-300', 'ring-orange-300', 'ring-red-300');
                        btn.classList.add('bg-gray-50', 'border', 'border-gray-200');
                    });
                }

                const overlay = document.querySelector(`[data-selection-overlay="${studentId}"]`);
                overlay?.classList.add('hidden');
            }

            selectedStudents.clear();
            updateSelectedCount();
            showToast(`Unmarked ${count} students`);
        }
        
        function openBulkLatePopup() {
            // Open late popup for bulk - will mark all selected with custom minutes
            if (selectedStudents.size === 0) {
                showToast('No students selected', true);
                return;
            }
            document.getElementById('bulk-menu').classList.add('hidden');
            
            // Use existing popup but with bulk mode
            document.getElementById('popup-student-id').value = 'bulk';
            document.getElementById('popup-student').textContent = `${selectedStudents.size} selected students`;
            document.getElementById('popup-title').textContent = 'Bulk Late';
            document.getElementById('popup-title').className = 'text-lg font-bold text-orange-600';
            document.getElementById('popup-type').value = 'bulk_late';
            document.getElementById('selected-minutes').textContent = '0';
            document.getElementById('popup-excused').checked = false;
            document.getElementById('toggle-track').style.background = '#d1d5db';
            document.getElementById('toggle-thumb').style.transform = 'translateX(0)';
            document.getElementById('popup-note').value = '';
            document.getElementById('time-popup').classList.remove('hidden');
            document.getElementById('time-popup').classList.add('flex');
        }
        
        // Update savePopup to handle bulk mode
        const originalSavePopup = savePopup;
        savePopup = async function() {
            const studentId = document.getElementById('popup-student-id').value;
            const type = document.getElementById('popup-type').value;
            
            if (type === 'bulk_late') {
                const minutes = parseInt(document.getElementById('selected-minutes').textContent);
                const excused = document.getElementById('popup-excused').checked;
                const note = document.getElementById('popup-note').value;
                
                for (const sid of selectedStudents) {
                    await markAttendance(sid, 'late', minutes, null, note, excused);
                    const overlay = document.querySelector(`[data-selection-overlay="${sid}"]`);
                    overlay?.classList.add('hidden');
                }
                
                const count = selectedStudents.size;
                selectedStudents.clear();
                updateSelectedCount();
                showToast(`Marked ${count} students as late`);
                closePopup();
            } else {
                originalSavePopup();
            }
        };
        
        // Close bulk menu when clicking outside
        document.addEventListener('click', (e) => {
            if (!e.target.closest('#bulk-btn') && !e.target.closest('#bulk-menu')) {
                document.getElementById('bulk-menu')?.classList.add('hidden');
            }
            if (!e.target.closest('#clear-btn') && !e.target.closest('#clear-menu')) {
                document.getElementById('clear-menu')?.classList.add('hidden');
            }
        });

        // === CLEAR ATTENDANCE FUNCTIONS ===
        function toggleClearMenu() {
            const menu = document.getElementById('clear-menu');
            menu.classList.toggle('hidden');
        }

        async function clearClassAttendance() {
            if (!classId) {
                showToast('No session selected', true);
                return;
            }

            const count = document.querySelectorAll('.student-card[data-status]:not([data-status="unmarked"])').length;
            if (!confirm(`Clear all attendance for this session on ${attendanceDate}? This will remove ${count} records.`)) {
                document.getElementById('clear-menu').classList.add('hidden');
                return;
            }

            try {
                const response = await fetch('{{ route("attendance.clear-class") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        class_id: classId,
                        date: attendanceDate
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    showToast(`Cleared ${data.count} attendance records`);
                    // Reload page to reflect changes
                    window.location.reload();
                } else {
                    showToast('Error clearing attendance: ' + response.status, true);
                }
            } catch (e) {
                console.error('Clear attendance error:', e);
                showToast('Error clearing attendance: ' + e.message, true);
            }

            document.getElementById('clear-menu').classList.add('hidden');
        }

        async function clearDayAttendance() {
            if (!confirm(`Clear ALL attendance for ALL sessions on ${attendanceDate}? This cannot be undone.`)) {
                document.getElementById('clear-menu').classList.add('hidden');
                return;
            }

            // Double confirm for day-wide clear
            if (!confirm('Are you SURE? This will clear attendance for ALL classes on this date.')) {
                document.getElementById('clear-menu').classList.add('hidden');
                return;
            }

            try {
                const response = await fetch('{{ route("attendance.clear-day") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: JSON.stringify({
                        date: attendanceDate
                    })
                });

                if (response.ok) {
                    const data = await response.json();
                    showToast(`Cleared ${data.count} attendance records for all classes`);
                    // Reload page to reflect changes
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    showToast('Error clearing attendance: ' + response.status, true);
                }
            } catch (e) {
                console.error('Clear day attendance error:', e);
                showToast('Error: ' + e.message, true);
            }

            document.getElementById('clear-menu').classList.add('hidden');
        }

        // === CANCEL SESSION MODAL ===
        function showCancelModal() {
            document.getElementById('cancel-modal').classList.remove('hidden');
            document.getElementById('cancel-modal').classList.add('flex');
        }

        function closeCancelModal(event) {
            if (!event || event.target === document.getElementById('cancel-modal')) {
                document.getElementById('cancel-modal').classList.add('hidden');
                document.getElementById('cancel-modal').classList.remove('flex');
            }
        }
    </script>
</x-app-layout>
