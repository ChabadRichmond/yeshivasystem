<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Students') }}</h2>
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
                                <span class="font-medium">Filtered View:</span> You're viewing
                                @php
                                    $primaryCount = auth()->user()->getPrimaryStudentIds();
                                @endphp
                                <span class="font-semibold">{{ count($primaryCount) }} primary {{ Str::plural('student', count($primaryCount)) }}</span>
                                assigned to you. These are students you have full access to (grades, reports, profile).
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 bg-white border-b border-gray-200">
                    <div class="flex flex-wrap items-center justify-between gap-4">
                        <form method="GET" action="{{ route('students.index') }}" class="flex gap-2 flex-wrap">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search..." class="rounded-md border-gray-300 shadow-sm">
                            <select name="status" class="rounded-md border-gray-300 shadow-sm">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        @unless($isReadOnly ?? false)
                            <a href="{{ route('students.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">+ Add Student</a>
                        @endunless
                    </div>
                </div>

                @if(session('success'))
                    <div class="p-4 bg-green-100 border-l-4 border-green-500 text-green-700">{{ session('success') }}</div>
                @endif

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Grade</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($students as $student)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            @if($student->photo)
                                                <img src="{{ asset('storage/' . $student->photo) }}" alt="{{ $student->first_name }}" class="h-10 w-10 rounded-full object-cover flex-shrink-0">
                                            @else
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <span class="text-indigo-600 font-medium text-sm">{{ strtoupper(substr($student->first_name, 0, 1) . substr($student->last_name, 0, 1)) }}</span>
                                                </div>
                                            @endif
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">{{ $student->first_name }} {{ $student->last_name }}</div>
                                                <div class="text-sm text-gray-500">{{ $student->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->student_id ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $student->grade_level ?? '-' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            {{ $student->enrollment_status == 'active' ? 'bg-green-100 text-green-800' : '' }}
                                            {{ $student->enrollment_status == 'inactive' ? 'bg-red-100 text-red-800' : '' }}
                                            {{ $student->enrollment_status == 'pending' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                                            {{ ucfirst($student->enrollment_status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('students.show', $student) }}" class="text-indigo-600 hover:text-indigo-900">View</a>
                                        @unless($isReadOnly ?? false)
                                            <a href="{{ route('students.edit', $student) }}" class="ml-4 text-gray-600 hover:text-gray-900">Edit</a>
                                        @endunless
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-gray-500">
                                        @if($isReadOnly ?? false)
                                            No students found.
                                        @else
                                            No students found. <a href="{{ route('students.create') }}" class="text-indigo-600">Add your first student</a>
                                        @endif
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-4">{{ $students->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
