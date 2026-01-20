<?php

namespace App\Http\Controllers;

use App\Models\TestScore;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class TestScoreController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view grades|manage grades', only: ['index', 'show', 'classScores', 'studentScores']),
            new Middleware('permission:manage grades', only: ['store', 'update', 'destroy']),
        ];
    }

    /**
     * Display grades dashboard.
     */
    public function index()
    {
        $this->authorize('viewAny', TestScore::class);

        $classes = SchoolClass::active()
            ->withCount('students')
            ->orderBy('display_order')
            ->get();

        $subjects = Subject::active()->ordered()->get();

        // Filter recent scores by primary students for teachers
        $recentScoresQuery = TestScore::with(['student', 'subject', 'schoolClass'])
            ->latest('test_date')
            ->limit(20);

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher')) {
            $primaryStudentIds = $user->getPrimaryStudentIds();
            if (!empty($primaryStudentIds)) {
                $recentScoresQuery->whereIn('student_id', $primaryStudentIds);
            }
        }

        $recentScores = $recentScoresQuery->get();

        return view('grades.index', compact('classes', 'subjects', 'recentScores'));
    }

    /**
     * Show test score entry form for a class.
     */
    public function classScores(Request $request, SchoolClass $class)
    {
        $subjects = Subject::active()->ordered()->get();

        // Filter students by permission for teachers
        $studentsQuery = $class->students()
            ->where('enrollment_status', 'active');

        $user = auth()->user();
        if ($user && $user->hasRole('Teacher')) {
            $primaryStudentIds = $user->getPrimaryStudentIds();
            if (!empty($primaryStudentIds)) {
                $studentsQuery->whereIn('students.id', $primaryStudentIds);
            }
        }

        $students = $studentsQuery->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $subjectId = $request->get('subject_id');
        $testDate = $request->get('test_date', now()->format('Y-m-d'));

        // Get existing scores for this class/subject/date
        $existingScores = [];
        if ($subjectId) {
            $existingScores = TestScore::where('school_class_id', $class->id)
                ->where('subject_id', $subjectId)
                ->where('test_date', $testDate)
                ->get()
                ->keyBy('student_id');
        }

        return view('grades.tests.class', compact('class', 'subjects', 'students', 'subjectId', 'testDate', 'existingScores'));
    }

    /**
     * Show test scores for a student.
     */
    public function studentScores(Student $student)
    {
        $subjects = Subject::active()->ordered()->get();

        $testScores = $student->testScores()
            ->with(['subject', 'schoolClass'])
            ->orderBy('test_date', 'desc')
            ->get()
            ->groupBy('subject_id');

        // Calculate averages per subject
        $averages = [];
        foreach ($testScores as $subjectId => $scores) {
            $averages[$subjectId] = [
                'average' => TestScore::calculateWeightedAverage($scores),
                'count' => $scores->count(),
            ];
        }

        return view('grades.tests.student', compact('student', 'subjects', 'testScores', 'averages'));
    }

    /**
     * Store test scores (bulk or single).
     */
    public function store(Request $request)
    {
        // Check if this is a bulk submission or single score
        if ($request->has('scores')) {
            return $this->storeBulk($request);
        }

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'subject_id' => 'required|exists:subjects,id',
            'school_class_id' => 'nullable|exists:school_classes,id',
            'test_name' => 'required|string|max:255',
            'test_date' => 'required|date',
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:0.01',
            'weight' => 'nullable|numeric|min:0|max:10',
            'notes' => 'nullable|string',
        ]);

        // Authorization check
        $this->authorize('create', [TestScore::class, $validated['student_id']]);

        $validated['recorded_by'] = auth()->id();
        $validated['weight'] = $validated['weight'] ?? 1.0;

        TestScore::create($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Score saved']);
        }

        return back()->with('success', 'Test score saved successfully.');
    }

    /**
     * Store bulk test scores for a class.
     */
    protected function storeBulk(Request $request)
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'school_class_id' => 'required|exists:school_classes,id',
            'test_name' => 'required|string|max:255',
            'test_date' => 'required|date',
            'max_score' => 'required|numeric|min:0.01',
            'scores' => 'required|array',
            'scores.*.student_id' => 'required|exists:students,id',
            'scores.*.score' => 'nullable|numeric|min:0',
        ]);

        $savedCount = 0;
        foreach ($validated['scores'] as $scoreData) {
            if ($scoreData['score'] === null || $scoreData['score'] === '') {
                continue;
            }

            // Authorization check: Skip students without permission
            if (!auth()->user()->hasRole(['Super Admin', 'Admin'])) {
                if (!auth()->user()->isPrimaryTeacherFor($scoreData['student_id'])) {
                    continue; // Skip unauthorized students silently
                }
            }

            TestScore::updateOrCreate(
                [
                    'student_id' => $scoreData['student_id'],
                    'subject_id' => $validated['subject_id'],
                    'school_class_id' => $validated['school_class_id'],
                    'test_date' => $validated['test_date'],
                    'test_name' => $validated['test_name'],
                ],
                [
                    'score' => $scoreData['score'],
                    'max_score' => $validated['max_score'],
                    'recorded_by' => auth()->id(),
                ]
            );
            $savedCount++;
        }

        return back()->with('success', "{$savedCount} test scores saved successfully.");
    }

    /**
     * Update a test score.
     */
    public function update(Request $request, TestScore $testScore)
    {
        $this->authorize('update', $testScore);

        $validated = $request->validate([
            'score' => 'required|numeric|min:0',
            'max_score' => 'required|numeric|min:0.01',
            'letter_grade' => 'nullable|string|max:5',
            'weight' => 'nullable|numeric|min:0|max:10',
            'notes' => 'nullable|string',
        ]);

        $testScore->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'testScore' => $testScore->fresh()]);
        }

        return back()->with('success', 'Test score updated successfully.');
    }

    /**
     * Delete a test score.
     */
    public function destroy(TestScore $testScore)
    {
        $this->authorize('delete', $testScore);

        $testScore->delete();

        if (request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Score deleted']);
        }

        return back()->with('success', 'Test score deleted successfully.');
    }
}
