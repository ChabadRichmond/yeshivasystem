<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Report Card - {{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}
            </h2>
            <div class="flex items-center gap-4">
                <a href="{{ route('reports.report-cards.edit', $reportCard) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                <a href="{{ route('reports.report-cards.index') }}" class="text-gray-600 hover:text-gray-800">&larr; Back to List</a>
            </div>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">{{ session('success') }}</div>
            @endif

            <!-- Report Card Header -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                            {{ substr($reportCard->student->first_name, 0, 1) }}{{ substr($reportCard->student->last_name, 0, 1) }}
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}</h3>
                            <p class="text-sm text-gray-500">{{ $reportCard->term }} - {{ $reportCard->academic_year }}</p>
                        </div>
                    </div>
                    <div class="text-right">
                        @php
                            $statusClass = match($reportCard->status) {
                                'draft' => 'bg-gray-100 text-gray-800',
                                'pending_approval' => 'bg-yellow-100 text-yellow-800',
                                'approved' => 'bg-blue-100 text-blue-800',
                                'published' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-800',
                            };
                        @endphp
                        <span class="px-3 py-1 text-sm font-medium rounded-full {{ $statusClass }}">
                            {{ ucfirst(str_replace('_', ' ', $reportCard->status)) }}
                        </span>
                        @if($reportCard->published_at)
                            <p class="text-xs text-gray-500 mt-1">Published {{ $reportCard->published_at->format('M d, Y') }}</p>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Grades Table -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Subject Grades</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Grade</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Percentage</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comments</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($reportCard->subjectGrades as $subjectGrade)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-medium text-gray-900">
                                        {{ $subjectGrade->subject?->name ?? $subjectGrade->subject }}
                                    </span>
                                    @if($subjectGrade->calculated_from_tests)
                                        <span class="ml-2 text-xs text-gray-400">(from tests)</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($subjectGrade->grade)
                                        @php
                                            $gradeClass = match(true) {
                                                str_starts_with($subjectGrade->grade, 'A') => 'bg-green-100 text-green-800',
                                                str_starts_with($subjectGrade->grade, 'B') => 'bg-blue-100 text-blue-800',
                                                str_starts_with($subjectGrade->grade, 'C') => 'bg-yellow-100 text-yellow-800',
                                                str_starts_with($subjectGrade->grade, 'D') => 'bg-orange-100 text-orange-800',
                                                default => 'bg-red-100 text-red-800',
                                            };
                                        @endphp
                                        <span class="px-2 py-1 text-sm font-medium rounded-full {{ $gradeClass }}">
                                            {{ $subjectGrade->grade }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-600">
                                    {{ $subjectGrade->percentage ? $subjectGrade->percentage . '%' : '-' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500">
                                    {{ $subjectGrade->comments ?? '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500">No grades recorded.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Comments Section -->
            @if($reportCard->teacher_comments || $reportCard->admin_comments)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Comments</h3>
                    @if($reportCard->teacher_comments)
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Teacher Comments</label>
                            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">{{ $reportCard->teacher_comments }}</p>
                        </div>
                    @endif
                    @if($reportCard->admin_comments)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Admin Comments</label>
                            <p class="text-sm text-gray-600 bg-gray-50 p-3 rounded-md">{{ $reportCard->admin_comments }}</p>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Metadata -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Details</h3>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500">Created By</dt>
                        <dd class="text-gray-900">{{ $reportCard->creator?->name ?? 'Unknown' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500">Created On</dt>
                        <dd class="text-gray-900">{{ $reportCard->created_at->format('M d, Y g:i A') }}</dd>
                    </div>
                    @if($reportCard->approver)
                        <div>
                            <dt class="text-gray-500">Approved By</dt>
                            <dd class="text-gray-900">{{ $reportCard->approver->name }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500">Approved On</dt>
                            <dd class="text-gray-900">{{ $reportCard->approved_at?->format('M d, Y g:i A') }}</dd>
                        </div>
                    @endif
                </dl>
            </div>

            <!-- Actions -->
            <div class="mt-6 flex justify-between">
                <form action="{{ route('reports.report-cards.destroy', $reportCard) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this report card?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="px-4 py-2 border border-red-300 text-red-600 rounded-md hover:bg-red-50">Delete Report Card</button>
                </form>
                <a href="{{ route('reports.report-cards.edit', $reportCard) }}" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Edit Report Card</a>
            </div>
        </div>
    </div>
</x-app-layout>
