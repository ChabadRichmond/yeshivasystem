<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">{{ __('Grades') }}</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <!-- Enter Test Scores -->
                <a href="#class-select" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-indigo-100 rounded-lg p-3">
                                <svg class="h-8 w-8 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Enter Test Scores</h3>
                                <p class="text-sm text-gray-500">Record test/quiz scores for a class</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Report Cards -->
                <a href="{{ route('reports.report-cards.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-green-100 rounded-lg p-3">
                                <svg class="h-8 w-8 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Report Cards</h3>
                                <p class="text-sm text-gray-500">Create and manage report cards</p>
                            </div>
                        </div>
                    </div>
                </a>

                <!-- Manage Subjects -->
                <a href="{{ route('subjects.index') }}" class="block bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                    <div class="p-6">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 bg-purple-100 rounded-lg p-3">
                                <svg class="h-8 w-8 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                                </svg>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-lg font-medium text-gray-900">Manage Subjects</h3>
                                <p class="text-sm text-gray-500">Add, edit, or remove subjects</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Select Class for Test Entry -->
            <div id="class-select" class="bg-white shadow-sm sm:rounded-lg p-6 mb-8">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Enter Test Scores by Class</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    @forelse($classes as $class)
                        <a href="{{ route('grades.tests.class', $class) }}" class="block p-4 border border-gray-200 rounded-lg hover:border-indigo-500 hover:bg-indigo-50 transition text-center">
                            <div class="font-medium text-gray-900">{{ $class->name }}</div>
                            <div class="text-sm text-gray-500">{{ $class->students_count }} students</div>
                        </a>
                    @empty
                        <p class="col-span-full text-gray-500">No classes available.</p>
                    @endforelse
                </div>
            </div>

            <!-- Recent Test Scores -->
            <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Recent Test Scores</h3>
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Student</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Subject</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Score</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Grade</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($recentScores as $score)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $score->test_date->format('M d, Y') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <a href="{{ route('grades.tests.student', $score->student) }}" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                        {{ $score->student->first_name }} {{ $score->student->last_name }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $score->subject->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $score->test_name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                    {{ $score->score }}/{{ $score->max_score }}
                                    <span class="text-gray-400">({{ $score->percentage }}%)</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @php
                                        $gradeClass = match(true) {
                                            $score->percentage >= 90 => 'bg-green-100 text-green-800',
                                            $score->percentage >= 80 => 'bg-blue-100 text-blue-800',
                                            $score->percentage >= 70 => 'bg-yellow-100 text-yellow-800',
                                            $score->percentage >= 60 => 'bg-orange-100 text-orange-800',
                                            default => 'bg-red-100 text-red-800',
                                        };
                                    @endphp
                                    <span class="px-2 py-1 text-xs font-medium rounded-full {{ $gradeClass }}">{{ $score->letter_grade }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center text-gray-500">No test scores recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
