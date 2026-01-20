<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Import Students</h2>
            <a href="{{ route('students.index') }}" class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">
                Back to Students
            </a>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 text-green-700 rounded-md">{{ session('success') }}</div>
            @endif

            @if(session('import_errors') && count(session('import_errors')) > 0)
                <div class="mb-4 p-4 bg-yellow-100 text-yellow-800 rounded-md">
                    <p class="font-medium mb-2">Import Errors:</p>
                    <ul class="list-disc list-inside text-sm max-h-40 overflow-y-auto">
                        @foreach(session('import_errors') as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Upload CSV File</h3>
                    <p class="text-sm text-gray-600 mb-4">
                        Upload a CSV file with student information. 
                        <a href="{{ route('students.import.template') }}" class="text-indigo-600 hover:underline">Download template</a>
                    </p>
                </div>

                <form method="POST" action="{{ route('students.import.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">CSV File *</label>
                            <input type="file" name="file" accept=".csv,.txt" required
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @error('file')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Default Grade (optional)</label>
                            <select name="default_grade_id" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- No default grade --</option>
                                @foreach($grades as $grade)
                                    <option value="{{ $grade->id }}">{{ $grade->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-gray-500 mt-1">Applied to students without a grade in the CSV</p>
                        </div>
                    </div>

                    <div class="mt-6">
                        <button type="submit" class="w-full px-6 py-3 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-medium">
                            Upload & Import Students
                        </button>
                    </div>
                </form>
            </div>

            <!-- Expected Format -->
            <div class="mt-6 bg-white rounded-xl shadow-md p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Expected CSV Format</h3>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Column</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Required</th>
                                <th class="px-3 py-2 text-left font-medium text-gray-600">Example</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr><td class="px-3 py-2">first_name</td><td class="px-3 py-2 text-green-600">Yes</td><td class="px-3 py-2 text-gray-500">John</td></tr>
                            <tr><td class="px-3 py-2">last_name</td><td class="px-3 py-2 text-green-600">Yes</td><td class="px-3 py-2 text-gray-500">Doe</td></tr>
                            <tr><td class="px-3 py-2">email</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">john@example.com</td></tr>
                            <tr><td class="px-3 py-2">date_of_birth</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">2015-05-20</td></tr>
                            <tr><td class="px-3 py-2">gender</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">male / female</td></tr>
                            <tr><td class="px-3 py-2">grade</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Aleph</td></tr>
                            <tr><td class="px-3 py-2">phone</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">555-1234</td></tr>
                            <tr><td class="px-3 py-2">address</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">123 Main St</td></tr>
                            <tr><td class="px-3 py-2">city</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">Toronto</td></tr>
                            <tr><td class="px-3 py-2">province</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">ON</td></tr>
                            <tr><td class="px-3 py-2">postal_code</td><td class="px-3 py-2">No</td><td class="px-3 py-2 text-gray-500">M1A 2B3</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
