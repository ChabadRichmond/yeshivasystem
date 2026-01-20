<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Bulk Import Attendance</h2>
            <a href="{{ route('attendance.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 text-sm">
                &larr; Back to Attendance
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8">
            <!-- Alerts -->
            @if(session('success'))
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-lg">
                    {{ session('warning') }}
                    @if(session('import_errors'))
                        <ul class="mt-2 text-sm list-disc list-inside">
                            @foreach(session('import_errors') as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Instructions Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">📋 Bulk Week Import</h3>

                <div class="space-y-4 text-sm text-gray-600">
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">1</span>
                        <p>Configure your template: select which classes to include and the date range (up to 7 days)</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">2</span>
                        <p>Download the template CSV with students pre-filled and date columns for each class</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">3</span>
                        <p>Fill in attendance using these codes: <code class="bg-gray-100 px-1 rounded">*</code> = present, <code class="bg-gray-100 px-1 rounded">r</code> = absent excused, <code class="bg-gray-100 px-1 rounded">a</code> = absent, <code class="bg-gray-100 px-1 rounded">[number]</code> = late minutes</p>
                    </div>
                    <div class="flex items-start gap-3">
                        <span class="flex-shrink-0 w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">4</span>
                        <p>Upload your completed CSV to import attendance for the entire week and all selected classes at once</p>
                    </div>
                </div>
            </div>

            <!-- Download Template Card -->
            <div class="bg-white rounded-xl shadow-sm p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Step 1: Configure & Download Template</h3>

                <form action="{{ route('attendance.import.bulk-template') }}" method="GET" class="space-y-4">
                    <!-- Date Range -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                            <input type="date" name="start_date" value="{{ now()->startOfWeek(\Carbon\Carbon::SUNDAY)->format('Y-m-d') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">End Date (max 7 days)</label>
                            <input type="date" name="end_date" value="{{ now()->endOfWeek(\Carbon\Carbon::SATURDAY)->format('Y-m-d') }}"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>

                    <!-- Class Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select Classes to Include</label>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-2 max-h-64 overflow-y-auto border rounded-lg p-3 bg-gray-50">
                            @foreach($classes as $class)
                                <label class="flex items-center gap-2 p-2 hover:bg-white rounded cursor-pointer">
                                    <input type="checkbox" name="class_ids[]" value="{{ $class->id }}"
                                           class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                    <span class="text-sm text-gray-700">{{ $class->name }}</span>
                                </label>
                            @endforeach
                        </div>
                        <p class="mt-1 text-xs text-gray-500">Select up to 10 classes for bulk import</p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Download Template
                        </button>
                    </div>
                </form>
            </div>

            <!-- Upload CSV Card -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Step 2: Upload Completed CSV</h3>

                <form action="{{ route('attendance.import.bulk-process') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                    @csrf

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Select CSV File</label>
                        <div class="flex items-center justify-center w-full">
                            <label class="flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100">
                                <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                    <svg class="w-8 h-8 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <p class="mb-2 text-sm text-gray-500"><span class="font-semibold">Click to upload</span> or drag and drop</p>
                                    <p class="text-xs text-gray-500">CSV file only (max 5MB)</p>
                                </div>
                                <input type="file" name="file" class="hidden" accept=".csv,.txt" required onchange="updateFileName(this)">
                            </label>
                        </div>
                        <p id="file-name" class="mt-2 text-sm text-gray-600 hidden"></p>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 flex items-center gap-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            Import Attendance
                        </button>
                    </div>
                </form>
            </div>

            <!-- Format Reference -->
            <div class="mt-6 bg-gradient-to-r from-indigo-50 to-purple-50 rounded-xl p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">💡 Attendance Code Reference</h4>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <div class="bg-white rounded-lg p-3">
                        <span class="font-medium text-green-600">Present</span>
                        <p class="text-gray-500 mt-1"><code class="bg-gray-100 px-1 rounded">*</code> or <code class="bg-gray-100 px-1 rounded">1</code></p>
                    </div>
                    <div class="bg-white rounded-lg p-3">
                        <span class="font-medium text-red-600">Absent</span>
                        <p class="text-gray-500 mt-1"><code class="bg-gray-100 px-1 rounded">a</code> or <code class="bg-gray-100 px-1 rounded">0</code></p>
                    </div>
                    <div class="bg-white rounded-lg p-3">
                        <span class="font-medium text-yellow-600">Absent Excused</span>
                        <p class="text-gray-500 mt-1"><code class="bg-gray-100 px-1 rounded">r</code></p>
                    </div>
                    <div class="bg-white rounded-lg p-3">
                        <span class="font-medium text-orange-600">Late</span>
                        <p class="text-gray-500 mt-1">Enter minutes (e.g. <code class="bg-gray-100 px-1 rounded">5</code>, <code class="bg-gray-100 px-1 rounded">10</code>)</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateFileName(input) {
            const fileName = document.getElementById('file-name');
            if (input.files && input.files[0]) {
                fileName.textContent = 'Selected: ' + input.files[0].name;
                fileName.classList.remove('hidden');
            }
        }
    </script>
</x-app-layout>
