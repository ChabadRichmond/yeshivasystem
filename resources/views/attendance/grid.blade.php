<x-app-layout>
    <style>
        /* Hebrew font support */
        @import url('https://fonts.googleapis.com/css2?family=Heebo:wght@400;500;600&display=swap');

        .hebrew-text {
            font-family: 'Heebo', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
    </style>

    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Attendance Overview</h2>
            <div class="flex gap-2">
                <a href="{{ route('attendance.import') }}" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm flex items-center gap-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    Import
                </a>
                <a href="{{ route('reports.attendance') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm">
                    View Report
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Date Selector -->
            <div class="bg-white rounded-xl shadow-md p-6 mb-6">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">Select Date</h3>
                    <div class="flex items-center gap-4">
                        <a href="{{ route('attendance.index', ['date' => $previousDate]) }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <form method="GET" action="{{ route('attendance.index') }}" class="flex items-center gap-2">
                            <input type="date" name="date" value="{{ $date }}" onchange="this.form.submit()"
                                   class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </form>
                        <a href="{{ route('attendance.index', ['date' => $nextDate]) }}" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <div class="mt-2 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ \Carbon\Carbon::parse($date)->format('l, F j, Y') }}</p>
                    <p class="text-sm text-gray-500 mt-1 hebrew-text" dir="rtl">{{ $hebrewDate }}</p>
                </div>
            </div>

            <!-- Color Legend -->
            <div class="bg-white rounded-xl shadow-sm p-4 mb-6">
                <div class="flex flex-wrap items-center justify-center gap-6 text-sm">
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-green-100 border-2 border-green-500"></div>
                        <span class="text-gray-700">Completed (All Marked)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-blue-100 border-2 border-blue-500"></div>
                        <span class="text-gray-700">Current Class</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-orange-100 border-2 border-orange-500"></div>
                        <span class="text-gray-700">Partial (Some Marked)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-red-100 border-2 border-red-500"></div>
                        <span class="text-gray-700">Not Started</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-gray-100 border-2 border-gray-300"></div>
                        <span class="text-gray-700">Future Class</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <div class="w-4 h-4 rounded bg-gray-800 border-2 border-gray-900"></div>
                        <span class="text-gray-700">Cancelled</span>
                    </div>
                </div>
            </div>

            <!-- Classes Grid -->
            @if($classStats->isEmpty())
                <div class="bg-white rounded-xl shadow-sm p-12 text-center">
                    <div class="text-4xl mb-3">📚</div>
                    <p class="text-gray-500 mb-2">No classes scheduled for this date</p>
                </div>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                    @foreach($classStats as $stat)
                        @php
                            $colorClass = match($stat['status']) {
                                'completed' => 'bg-green-50 border-green-300 hover:border-green-500',
                                'current' => 'bg-blue-50 border-blue-300 hover:border-blue-500',
                                'partial' => 'bg-orange-50 border-orange-300 hover:border-orange-500',
                                'not_started' => 'bg-red-50 border-red-300 hover:border-red-500',
                                'cancelled' => 'bg-gray-200 border-gray-400 hover:border-gray-500 opacity-75',
                                default => 'bg-gray-50 border-gray-300 hover:border-gray-400'
                            };

                            $iconColor = match($stat['status']) {
                                'completed' => 'text-green-600',
                                'current' => 'text-blue-600',
                                'partial' => 'text-orange-600',
                                'not_started' => 'text-red-600',
                                'cancelled' => 'text-gray-700',
                                default => 'text-gray-400'
                            };
                        @endphp

                        <a href="{{ route('attendance.mark', ['class_id' => $stat['class']->id, 'date' => $date]) }}"
                           class="block border-2 rounded-xl p-5 transition-all {{ $colorClass }} hover:shadow-lg cursor-pointer">

                            <!-- Class Name -->
                            <h3 class="font-bold text-gray-800 text-lg mb-2 flex items-start justify-between">
                                <span class="{{ $stat['status'] === 'cancelled' ? 'line-through text-gray-500' : '' }}">{{ $stat['class']->name }}</span>
                                <span class="{{ $iconColor }}">
                                    @if($stat['status'] === 'completed')
                                        ✓
                                    @elseif($stat['status'] === 'current')
                                        ●
                                    @elseif($stat['status'] === 'partial')
                                        ◐
                                    @elseif($stat['status'] === 'not_started')
                                        ○
                                    @elseif($stat['status'] === 'cancelled')
                                        <span class="font-bold">C</span>
                                    @endif
                                </span>
                            </h3>

                            @if($stat['status'] === 'cancelled')
                                <p class="text-sm text-gray-600 mb-2 font-medium">CANCELLED</p>
                                @if($stat['cancellation_reason'])
                                    <p class="text-xs text-gray-500 mb-3 italic">{{ $stat['cancellation_reason'] }}</p>
                                @endif
                            @endif

                            <!-- Schedule Time -->
                            @if($stat['schedule_time'])
                                <p class="text-sm text-gray-600 mb-3">
                                    ⏰ {{ $stat['schedule_time'] }}
                                </p>
                            @endif

                            <!-- Stats -->
                            <div class="space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Total Students:</span>
                                    <span class="font-semibold text-gray-800">{{ $stat['total_students'] }}</span>
                                </div>
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">Marked:</span>
                                    <span class="font-semibold {{ $stat['marked'] > 0 ? 'text-indigo-600' : 'text-gray-400' }}">
                                        {{ $stat['marked'] }}
                                    </span>
                                </div>
                                @if($stat['marked'] > 0)
                                    <div class="pt-2 border-t border-gray-200">
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="text-green-600">✓ {{ $stat['present'] }}</span>
                                            <span class="text-orange-600">⏰ {{ $stat['late'] }}</span>
                                            <span class="text-red-600">✗ {{ $stat['absent'] }}</span>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Progress Bar -->
                            @if($stat['total_students'] > 0)
                                <div class="mt-3">
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="h-2 rounded-full transition-all {{ $stat['status'] === 'completed' ? 'bg-green-500' : ($stat['status'] === 'partial' ? 'bg-orange-500' : 'bg-gray-300') }}"
                                             style="width: {{ ($stat['marked'] / $stat['total_students']) * 100 }}%"></div>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-1 text-center">
                                        {{ round(($stat['marked'] / $stat['total_students']) * 100) }}% Complete
                                    </p>
                                </div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
