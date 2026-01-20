<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Enter Test Scores - {{ $class->name }}</h2>
            <a href="{{ route('grades.index') }}" class="text-indigo-600 hover:text-indigo-800">&larr; Back to Grades</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border-l-4 border-green-500 text-green-700 rounded">{{ session('success') }}</div>
            @endif

            <!-- Test Info Form -->
            <form action="{{ route('grades.tests.store') }}" method="POST" id="scores-form">
                @csrf
                <input type="hidden" name="school_class_id" value="{{ $class->id }}">

                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Test Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Subject</label>
                            <select name="subject_id" id="subject-select" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subject)
                                    <option value="{{ $subject->id }}" {{ $subjectId == $subject->id ? 'selected' : '' }}>{{ $subject->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Test Name</label>
                            <input type="text" name="test_name" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g., Chapter 5 Quiz" value="{{ old('test_name') }}">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Test Date</label>
                            <input type="date" name="test_date" required value="{{ $testDate }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Max Score</label>
                            <input type="number" name="max_score" required min="1" step="0.01" value="100" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                    </div>
                </div>

                @if($subjectId)
                    <!-- Student Scores Grid -->
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                            <h3 class="text-lg font-medium text-gray-900">Student Scores</h3>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save All Scores</button>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-32">Score</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-24">%</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase w-20">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($students as $student)
                                    @php
                                        $existing = $existingScores[$student->id] ?? null;
                                    @endphp
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="h-8 w-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-medium text-sm">
                                                    {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                                                </div>
                                                <div class="ml-3">
                                                    <a href="{{ route('grades.tests.student', $student) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                                        {{ $student->last_name }}, {{ $student->first_name }}
                                                    </a>
                                                </div>
                                            </div>
                                            <input type="hidden" name="scores[{{ $loop->index }}][student_id]" value="{{ $student->id }}">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <input type="number"
                                                   name="scores[{{ $loop->index }}][score]"
                                                   min="0"
                                                   step="0.01"
                                                   value="{{ $existing?->score }}"
                                                   class="w-24 text-center rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 score-input"
                                                   data-row="{{ $loop->index }}"
                                                   placeholder="-">
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="percentage-display text-sm text-gray-500" id="pct-{{ $loop->index }}">
                                                {{ $existing ? $existing->percentage . '%' : '-' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center">
                                            <span class="grade-display text-sm font-medium" id="grade-{{ $loop->index }}">
                                                @if($existing)
                                                    @php
                                                        $gradeClass = match(true) {
                                                            $existing->percentage >= 90 => 'text-green-600',
                                                            $existing->percentage >= 80 => 'text-blue-600',
                                                            $existing->percentage >= 70 => 'text-yellow-600',
                                                            default => 'text-red-600',
                                                        };
                                                    @endphp
                                                    <span class="{{ $gradeClass }}">{{ $existing->letter_grade }}</span>
                                                @else
                                                    -
                                                @endif
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-6 py-12 text-center text-gray-500">No students in this class.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        @if($students->isNotEmpty())
                            <div class="px-6 py-4 bg-gray-50 border-t">
                                <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Save All Scores</button>
                            </div>
                        @endif
                    </div>
                @else
                    <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center text-gray-500">
                        Select a subject above to enter test scores.
                    </div>
                @endif
            </form>
        </div>
    </div>

    <script>
        // Handle subject selection - redirect with query param instead of form submit
        document.getElementById('subject-select').addEventListener('change', function() {
            if (this.value) {
                window.location.href = '{{ route('grades.tests.class', $class) }}?subject_id=' + this.value;
            }
        });

        document.querySelectorAll('.score-input').forEach(input => {
            input.addEventListener('input', function() {
                const row = this.dataset.row;
                const score = parseFloat(this.value) || 0;
                const maxScore = parseFloat(document.querySelector('[name="max_score"]').value) || 100;
                const percentage = maxScore > 0 ? Math.round((score / maxScore) * 100 * 100) / 100 : 0;

                document.getElementById('pct-' + row).textContent = this.value ? percentage + '%' : '-';

                let grade = '-';
                let gradeClass = 'text-gray-500';
                if (this.value) {
                    if (percentage >= 97) { grade = 'A+'; gradeClass = 'text-green-600'; }
                    else if (percentage >= 93) { grade = 'A'; gradeClass = 'text-green-600'; }
                    else if (percentage >= 90) { grade = 'A-'; gradeClass = 'text-green-600'; }
                    else if (percentage >= 87) { grade = 'B+'; gradeClass = 'text-blue-600'; }
                    else if (percentage >= 83) { grade = 'B'; gradeClass = 'text-blue-600'; }
                    else if (percentage >= 80) { grade = 'B-'; gradeClass = 'text-blue-600'; }
                    else if (percentage >= 77) { grade = 'C+'; gradeClass = 'text-yellow-600'; }
                    else if (percentage >= 73) { grade = 'C'; gradeClass = 'text-yellow-600'; }
                    else if (percentage >= 70) { grade = 'C-'; gradeClass = 'text-yellow-600'; }
                    else if (percentage >= 60) { grade = 'D'; gradeClass = 'text-orange-600'; }
                    else { grade = 'F'; gradeClass = 'text-red-600'; }
                }

                const gradeEl = document.getElementById('grade-' + row);
                gradeEl.innerHTML = `<span class="${gradeClass}">${grade}</span>`;
            });
        });
    </script>
</x-app-layout>
