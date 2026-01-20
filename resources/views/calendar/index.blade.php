<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">School Calendar</h2>
                <p class="text-sm text-gray-500">Manage holidays, half-days, and vacation periods</p>
            </div>
            <button onclick="document.getElementById('add-entry-modal').classList.remove('hidden'); document.getElementById('add-entry-modal').classList.add('flex');" 
                    class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                + Add Entry
            </button>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">{{ session('success') }}</div>
            @endif

            <!-- Year Navigation -->
            <div class="bg-white rounded-xl shadow-md p-4 mb-6">
                <div class="flex items-center justify-between">
                    <a href="{{ route('calendar.index', ['year' => $year - 1]) }}" 
                       class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">← {{ $year - 1 }}</a>
                    <h3 class="text-xl font-bold text-gray-800">{{ $year }}</h3>
                    <a href="{{ route('calendar.index', ['year' => $year + 1]) }}" 
                       class="px-4 py-2 bg-gray-200 rounded-md hover:bg-gray-300">{{ $year + 1 }} →</a>
                </div>
            </div>

            <!-- Calendar Entries -->
            @if($entries->isEmpty())
                <div class="bg-white rounded-xl shadow-md p-8 text-center">
                    <div class="text-4xl mb-3">📅</div>
                    <p class="text-gray-500 mb-4">No calendar entries for {{ $year }}.</p>
                    <p class="text-sm text-gray-400">Add holidays, half-days, and vacation periods to prevent attendance marking on those days.</p>
                </div>
            @else
                <div class="space-y-6">
                    @foreach($entries as $monthKey => $monthEntries)
                        @php
                            $monthDate = \Carbon\Carbon::parse($monthKey . '-01');
                        @endphp
                        <div class="bg-white rounded-xl shadow-md p-6">
                            <h3 class="text-lg font-bold text-gray-800 mb-4">{{ $monthDate->format('F Y') }}</h3>
                            <div class="space-y-3">
                                @foreach($monthEntries as $entry)
                                    <div class="flex items-center justify-between p-3 rounded-lg 
                                        @if($entry->type === 'holiday') bg-red-50 border border-red-200
                                        @elseif($entry->type === 'half_day') bg-yellow-50 border border-yellow-200
                                        @elseif($entry->type === 'vacation') bg-blue-50 border border-blue-200
                                        @else bg-purple-50 border border-purple-200
                                        @endif">
                                        <div class="flex items-center gap-4">
                                            <div class="text-center min-w-[60px]">
                                                <div class="text-2xl font-bold text-gray-800">{{ $entry->date->format('d') }}</div>
                                                <div class="text-xs text-gray-500">{{ $entry->date->format('D') }}</div>
                                            </div>
                                            <div>
                                                <div class="font-semibold text-gray-800">{{ $entry->name }}</div>
                                                <div class="flex items-center gap-2 text-sm">
                                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                        @if($entry->type === 'holiday') bg-red-200 text-red-800
                                                        @elseif($entry->type === 'half_day') bg-yellow-200 text-yellow-800
                                                        @elseif($entry->type === 'vacation') bg-blue-200 text-blue-800
                                                        @else bg-purple-200 text-purple-800
                                                        @endif">
                                                        {{ ucfirst(str_replace('_', ' ', $entry->type)) }}
                                                    </span>
                                                    @if(!$entry->affects_all_classes)
                                                        <span class="text-gray-500">• {{ $entry->getAffectedClassesText() }}</span>
                                                    @endif
                                                </div>
                                                @if($entry->description)
                                                    <p class="text-sm text-gray-500 mt-1">{{ $entry->description }}</p>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('calendar.destroy', $entry) }}" 
                                                  onsubmit="return confirm('Delete this entry?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="p-2 text-gray-400 hover:text-red-600">🗑️</button>
                                            </form>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Add Entry Modal -->
    <div id="add-entry-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 overflow-y-auto py-4" onclick="closeModal(event)">
        <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md max-h-[90vh] overflow-y-auto my-auto" onclick="event.stopPropagation()">
            <h3 class="text-lg font-bold text-gray-800 mb-4">Add Calendar Entry</h3>
            
            <form method="POST" action="{{ route('calendar.store') }}">
                @csrf
                
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date *</label>
                            <input type="date" name="date" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                            <input type="date" name="end_date"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <p class="text-xs text-gray-400 mt-1">For multi-day vacations</p>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Type *</label>
                        <select name="type" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="holiday">🔴 Holiday (No School)</option>
                            <option value="half_day">🟡 Half Day</option>
                            <option value="vacation">🔵 Vacation Period</option>
                            <option value="special">🟣 Special Event</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Name *</label>
                        <input type="text" name="name" required
                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                               placeholder="e.g., Martin Luther King Day, Purim">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="2"
                                  class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                  placeholder="Optional notes..."></textarea>
                    </div>

                    <div>
                        <label class="flex items-center gap-2">
                            <input type="checkbox" name="affects_all_classes" value="1" checked
                                   class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                   onchange="toggleClassSelect(this)">
                            <span class="text-sm text-gray-700">Affects all classes</span>
                        </label>
                    </div>

                    <div id="class-select-container" class="hidden">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Classes to Cancel:</label>
                        <div class="max-h-48 overflow-y-auto border border-gray-200 rounded-md p-3 space-y-2">
                            @foreach($classes as $class)
                                <label class="flex items-center gap-2 hover:bg-gray-50 p-1 rounded cursor-pointer">
                                    <input type="checkbox" name="class_ids[]" value="{{ $class->id }}"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">{{ $class->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-400 mt-1">Select which sessions are cancelled on this day</p>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                        Add Entry
                    </button>
                    <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function closeModal(event) {
            if (!event || event.target === document.getElementById('add-entry-modal')) {
                document.getElementById('add-entry-modal').classList.add('hidden');
                document.getElementById('add-entry-modal').classList.remove('flex');
            }
        }

        function toggleClassSelect(checkbox) {
            const container = document.getElementById('class-select-container');
            if (checkbox.checked) {
                container.classList.add('hidden');
            } else {
                container.classList.remove('hidden');
            }
        }
    </script>
</x-app-layout>
