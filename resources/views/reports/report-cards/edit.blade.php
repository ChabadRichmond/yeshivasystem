<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit Report Card - {{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}
            </h2>
            <a href="{{ route('reports.report-cards.show', $reportCard) }}" class="text-indigo-600 hover:text-indigo-800">&larr; Back to Report Card</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8">
            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border-l-4 border-red-500 text-red-700 rounded">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('reports.report-cards.update', $reportCard) }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Report Card Information -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Report Card Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Student</label>
                            <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                {{ $reportCard->student->first_name }} {{ $reportCard->student->last_name }}
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Term</label>
                            <select name="term" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($terms as $term)
                                    <option value="{{ $term }}" {{ old('term', $reportCard->term) == $term ? 'selected' : '' }}>{{ $term }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Academic Year</label>
                            <select name="academic_year" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                @foreach($academicYears as $year)
                                    <option value="{{ $year }}" {{ old('academic_year', $reportCard->academic_year) == $year ? 'selected' : '' }}>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="draft" {{ old('status', $reportCard->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                                <option value="pending_approval" {{ old('status', $reportCard->status) == 'pending_approval' ? 'selected' : '' }}>Pending Approval</option>
                                <option value="approved" {{ old('status', $reportCard->status) == 'approved' ? 'selected' : '' }}>Approved</option>
                                <option value="published" {{ old('status', $reportCard->status) == 'published' ? 'selected' : '' }}>Published</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Subject Grades -->
                <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Subject Grades</h3>
                    </div>
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">Grade</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">%</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Comments</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($subjects as $index => $subject)
                                @php
                                    $existing = $existingGrades[$subject->id] ?? null;
                                @endphp
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <input type="hidden" name="grades[{{ $index }}][subject_id]" value="{{ $subject->id }}">
                                        <span class="text-sm font-medium text-gray-900">{{ $subject->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <select name="grades[{{ $index }}][grade]" class="w-20 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                                            <option value="">-</option>
                                            @foreach(['A+', 'A', 'A-', 'B+', 'B', 'B-', 'C+', 'C', 'C-', 'D', 'F'] as $grade)
                                                <option value="{{ $grade }}" {{ old("grades.{$index}.grade", $existing?->grade) == $grade ? 'selected' : '' }}>{{ $grade }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center">
                                        <input type="number"
                                               name="grades[{{ $index }}][percentage]"
                                               min="0"
                                               max="100"
                                               step="0.1"
                                               value="{{ old("grades.{$index}.percentage", $existing?->percentage) }}"
                                               class="w-20 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="-">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text"
                                               name="grades[{{ $index }}][comments]"
                                               value="{{ old("grades.{$index}.comments", $existing?->comments) }}"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                               placeholder="Optional comments">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Comments -->
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Comments</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Teacher Comments</label>
                            <textarea name="teacher_comments" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="General comments about student performance...">{{ old('teacher_comments', $reportCard->teacher_comments) }}</textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Admin Comments</label>
                            <textarea name="admin_comments" rows="2" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Administrative notes (optional)...">{{ old('admin_comments', $reportCard->admin_comments) }}</textarea>
                        </div>
                    </div>
                </div>

                <!-- Submit -->
                <div class="flex justify-end gap-4">
                    <a href="{{ route('reports.report-cards.show', $reportCard) }}" class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Cancel</a>
                    <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
