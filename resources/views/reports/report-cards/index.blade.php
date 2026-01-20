<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Report Cards') }}</h2>
            <a href="{{ route('reports.report-cards.create') }}" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">
                Create Report Card
            </a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">{{ session('success') }}</div>
            @endif

            <!-- Filters -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <form action="{{ route('reports.report-cards.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-5 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Student</label>
                        <select name="student_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Students</option>
                            @foreach($students as $student)
                                <option value="{{ $student->id }}" {{ request('student_id') == $student->id ? 'selected' : '' }}>
                                    {{ $student->last_name }}, {{ $student->first_name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Term</label>
                        <select name="term" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Terms</option>
                            @foreach(['Term 1', 'Term 2', 'Term 3', 'Semester 1', 'Semester 2', 'Full Year'] as $term)
                                <option value="{{ $term }}" {{ request('term') == $term ? 'selected' : '' }}>{{ $term }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                        <select name="academic_year" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Years</option>
                            @php
                                $currentYear = now()->year;
                                $years = [($currentYear - 1) . '-' . $currentYear, $currentYear . '-' . ($currentYear + 1)];
                            @endphp
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ request('academic_year') == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">All Statuses</option>
                            @foreach(['draft' => 'Draft', 'pending_approval' => 'Pending Approval', 'approved' => 'Approved', 'published' => 'Published'] as $key => $label)
                                <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Report Cards Table -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Term</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Year</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Created</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportCards as $reportCard)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-medium text-sm">
                                            {{ substr($reportCard->student->first_name, 0, 1) }}{{ substr($reportCard->student->last_name, 0, 1) }}
                                        </div>
                                        <div class="ml-3">
                                            <a href="{{ route('students.show', $reportCard->student) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                                {{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}
                                            </a>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $reportCard->term }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $reportCard->academic_year }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php
                                        $statusClass = match($reportCard->status) {
                                            'draft' => 'bg-gray-100 text-gray-800',
                                            'pending_approval' => 'bg-yellow-100 text-yellow-800',
                                            'approved' => 'bg-blue-100 text-blue-800',
                                            'published' => 'bg-green-100 text-green-800',
                                            default => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $statusClass }}">
                                        {{ ucfirst(str_replace('_', ' ', $reportCard->status)) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $reportCard->created_at->format('M d, Y') }}
                                    @if($reportCard->creator)
                                        <span class="text-gray-400">by {{ $reportCard->creator->name }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                                    <a href="{{ route('reports.report-cards.show', $reportCard) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">View</a>
                                    <a href="{{ route('reports.report-cards.edit', $reportCard) }}" class="text-gray-600 hover:text-gray-900 mr-3">Edit</a>
                                    <form action="{{ route('reports.report-cards.destroy', $reportCard) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this report card?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                    No report cards found. <a href="{{ route('reports.report-cards.create') }}" class="text-indigo-600 hover:text-indigo-900">Create one now</a>.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if($reportCards->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200">
                        {{ $reportCards->withQueryString()->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
