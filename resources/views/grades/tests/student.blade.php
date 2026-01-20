<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Test Scores - {{ $student->first_name }} {{ $student->last_name }}
            </h2>
            <a href="{{ route('students.show', $student) }}" class="text-indigo-600 hover:text-indigo-800">&larr; Back to Student</a>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Student Summary Card -->
            <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <div class="h-16 w-16 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl">
                            {{ substr($student->first_name, 0, 1) }}{{ substr($student->last_name, 0, 1) }}
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-medium text-gray-900">{{ $student->first_name }} {{ $student->last_name }}</h3>
                            <p class="text-sm text-gray-500">
                                @if($student->currentClass)
                                    {{ $student->currentClass->name }}
                                @else
                                    No class assigned
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-4">
                        <a href="{{ route('reports.report-cards.create', ['student_id' => $student->id]) }}" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">
                            Create Report Card
                        </a>
                    </div>
                </div>
            </div>

            <!-- Subject Averages Overview -->
            @if(count($averages) > 0)
                <div class="bg-white shadow-sm sm:rounded-lg p-6 mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Subject Averages</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                        @foreach($subjects as $subject)
                            @if(isset($averages[$subject->id]))
                                @php
                                    $avg = $averages[$subject->id]['average'];
                                    $bgClass = match(true) {
                                        $avg >= 90 => 'bg-green-50 border-green-200',
                                        $avg >= 80 => 'bg-blue-50 border-blue-200',
                                        $avg >= 70 => 'bg-yellow-50 border-yellow-200',
                                        $avg >= 60 => 'bg-orange-50 border-orange-200',
                                        default => 'bg-red-50 border-red-200',
                                    };
                                    $textClass = match(true) {
                                        $avg >= 90 => 'text-green-700',
                                        $avg >= 80 => 'text-blue-700',
                                        $avg >= 70 => 'text-yellow-700',
                                        $avg >= 60 => 'text-orange-700',
                                        default => 'text-red-700',
                                    };
                                @endphp
                                <div class="p-4 rounded-lg border {{ $bgClass }}">
                                    <div class="text-sm font-medium text-gray-600">{{ $subject->name }}</div>
                                    <div class="text-2xl font-bold {{ $textClass }}">{{ number_format($avg, 1) }}%</div>
                                    <div class="text-xs text-gray-500">{{ $averages[$subject->id]['count'] }} tests</div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- Test Scores by Subject -->
            @forelse($subjects as $subject)
                @if(isset($testScores[$subject->id]))
                    <div class="bg-white shadow-sm sm:rounded-lg overflow-hidden mb-6">
                        <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">{{ $subject->name }}</h3>
                                @if(isset($averages[$subject->id]))
                                    <span class="text-sm text-gray-500">
                                        Average: <span class="font-medium">{{ number_format($averages[$subject->id]['average'], 1) }}%</span>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Test Name</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Class</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Score</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">%</th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase">Grade</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($testScores[$subject->id] as $score)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $score->test_date->format('M d, Y') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            {{ $score->test_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $score->schoolClass?->name ?? '-' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            {{ $score->score }}/{{ $score->max_score }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm">
                                            {{ $score->percentage }}%
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
                                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $gradeClass }}">
                                                {{ $score->letter_grade }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @empty
                <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center text-gray-500">
                    No test scores recorded yet.
                </div>
            @endforelse

            @if(count($testScores) === 0)
                <div class="bg-white shadow-sm sm:rounded-lg p-12 text-center text-gray-500">
                    No test scores recorded for this student yet.
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
