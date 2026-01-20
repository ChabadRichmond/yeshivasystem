<?php

namespace App\Http\Controllers;

use App\Models\ReportCard;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectGrade;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class ReportCardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:view grades|manage grades', only: ['index', 'show']),
            new Middleware('permission:manage grades', only: ['create', 'store', 'edit', 'update', 'destroy']),
        ];
    }

    /**
     * Display a listing of report cards.
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', ReportCard::class);

        $query = ReportCard::with(['student', 'creator'])
            ->latest('created_at');

        // Filter by primary students for teachers
        $user = auth()->user();
        if ($user && $user->hasRole('Teacher')) {
            $primaryStudentIds = $user->getPrimaryStudentIds();
            if (!empty($primaryStudentIds)) {
                $query->whereIn('student_id', $primaryStudentIds);
            } else {
                $query->whereRaw('1 = 0'); // No students
            }
        }

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->student_id);
        }

        if ($request->filled('term')) {
            $query->where('term', $request->term);
        }

        if ($request->filled('academic_year')) {
            $query->where('academic_year', $request->academic_year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $reportCards = $query->paginate(20);

        // Filter students list by primary students for teachers
        $studentsQuery = Student::where('enrollment_status', 'active');
        if ($user && $user->hasRole('Teacher')) {
            $primaryStudentIds = $user->getPrimaryStudentIds();
            if (!empty($primaryStudentIds)) {
                $studentsQuery->whereIn('id', $primaryStudentIds);
            }
        }
        $students = $studentsQuery->orderBy('last_name')->get();

        return view('reports.report-cards.index', compact('reportCards', 'students'));
    }

    /**
     * Show the form for creating a new report card.
     */
    public function create(Request $request)
    {
        $student = null;
        if ($request->filled('student_id')) {
            $student = Student::findOrFail($request->student_id);
            // Authorization check for specific student
            $this->authorize('create', [ReportCard::class, $student->id]);
        }

        // Filter students list by primary students for teachers
        $studentsQuery = Student::where('enrollment_status', 'active');
        $user = auth()->user();
        if ($user && $user->hasRole('Teacher')) {
            $primaryStudentIds = $user->getPrimaryStudentIds();
            if (!empty($primaryStudentIds)) {
                $studentsQuery->whereIn('id', $primaryStudentIds);
            }
        }
        $students = $studentsQuery->orderBy('last_name')->get();

        $subjects = Subject::active()->ordered()->get();

        // Get term options
        $terms = ['Term 1', 'Term 2', 'Term 3', 'Semester 1', 'Semester 2', 'Full Year'];
        $currentYear = now()->year;
        $academicYears = [
            ($currentYear - 1) . '-' . $currentYear,
            $currentYear . '-' . ($currentYear + 1),
        ];

        // If student selected, calculate averages from test scores
        $calculatedGrades = [];
        if ($student) {
            foreach ($subjects as $subject) {
                $average = $student->getSubjectAverage($subject->id);
                if ($average !== null) {
                    $calculatedGrades[$subject->id] = [
                        'percentage' => $average,
                        'letter_grade' => SubjectGrade::percentageToLetterGrade($average),
                    ];
                }
            }
        }

        return view('reports.report-cards.create', compact(
            'student', 'students', 'subjects', 'terms', 'academicYears', 'calculatedGrades'
        ));
    }

    /**
     * Store a newly created report card.
     */
    public function store(Request $request)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id',
        ]);

        // Authorization check
        $this->authorize('create', [ReportCard::class, $request->student_id]);

        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'term' => 'required|string|max:50',
            'academic_year' => 'required|string|max:20',
            'status' => 'required|in:draft,pending_approval,approved,published',
            'teacher_comments' => 'nullable|string',
            'admin_comments' => 'nullable|string',
            'grades' => 'required|array',
            'grades.*.subject_id' => 'required|exists:subjects,id',
            'grades.*.grade' => 'nullable|string|max:10',
            'grades.*.percentage' => 'nullable|numeric|min:0|max:100',
            'grades.*.comments' => 'nullable|string',
            'grades.*.calculated_from_tests' => 'boolean',
        ]);

        $reportCard = ReportCard::create([
            'student_id' => $validated['student_id'],
            'term' => $validated['term'],
            'academic_year' => $validated['academic_year'],
            'status' => $validated['status'],
            'teacher_comments' => $validated['teacher_comments'] ?? null,
            'admin_comments' => $validated['admin_comments'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Create subject grades
        foreach ($validated['grades'] as $gradeData) {
            if (empty($gradeData['grade']) && empty($gradeData['percentage'])) {
                continue; // Skip empty grades
            }

            $subject = Subject::find($gradeData['subject_id']);

            SubjectGrade::create([
                'report_card_id' => $reportCard->id,
                'subject_id' => $gradeData['subject_id'],
                'subject' => $subject->name, // Legacy field
                'grade' => $gradeData['grade'] ?? null,
                'percentage' => $gradeData['percentage'] ?? null,
                'comments' => $gradeData['comments'] ?? null,
                'calculated_from_tests' => $gradeData['calculated_from_tests'] ?? false,
            ]);
        }

        return redirect()->route('reports.report-cards.show', $reportCard)
            ->with('success', 'Report card created successfully.');
    }

    /**
     * Display the specified report card.
     */
    public function show(ReportCard $reportCard)
    {
        $this->authorize('view', $reportCard);

        $reportCard->load(['student', 'subjectGrades.subject', 'creator', 'approver']);

        return view('reports.report-cards.show', compact('reportCard'));
    }

    /**
     * Show the form for editing the specified report card.
     */
    public function edit(ReportCard $reportCard)
    {
        $this->authorize('update', $reportCard);

        $reportCard->load(['student', 'subjectGrades']);

        $subjects = Subject::active()->ordered()->get();
        $terms = ['Term 1', 'Term 2', 'Term 3', 'Semester 1', 'Semester 2', 'Full Year'];
        $currentYear = now()->year;
        $academicYears = [
            ($currentYear - 1) . '-' . $currentYear,
            $currentYear . '-' . ($currentYear + 1),
        ];

        // Create a map of existing grades
        $existingGrades = $reportCard->subjectGrades->keyBy('subject_id');

        return view('reports.report-cards.edit', compact(
            'reportCard', 'subjects', 'terms', 'academicYears', 'existingGrades'
        ));
    }

    /**
     * Update the specified report card.
     */
    public function update(Request $request, ReportCard $reportCard)
    {
        $this->authorize('update', $reportCard);

        $validated = $request->validate([
            'term' => 'required|string|max:50',
            'academic_year' => 'required|string|max:20',
            'status' => 'required|in:draft,pending_approval,approved,published',
            'teacher_comments' => 'nullable|string',
            'admin_comments' => 'nullable|string',
            'grades' => 'required|array',
            'grades.*.subject_id' => 'required|exists:subjects,id',
            'grades.*.grade' => 'nullable|string|max:10',
            'grades.*.percentage' => 'nullable|numeric|min:0|max:100',
            'grades.*.comments' => 'nullable|string',
        ]);

        $reportCard->update([
            'term' => $validated['term'],
            'academic_year' => $validated['academic_year'],
            'status' => $validated['status'],
            'teacher_comments' => $validated['teacher_comments'] ?? null,
            'admin_comments' => $validated['admin_comments'] ?? null,
            'approved_by' => $validated['status'] === 'approved' ? auth()->id() : $reportCard->approved_by,
            'approved_at' => $validated['status'] === 'approved' && !$reportCard->approved_at ? now() : $reportCard->approved_at,
            'published_at' => $validated['status'] === 'published' && !$reportCard->published_at ? now() : $reportCard->published_at,
        ]);

        // Update or create subject grades
        foreach ($validated['grades'] as $gradeData) {
            $subject = Subject::find($gradeData['subject_id']);

            SubjectGrade::updateOrCreate(
                [
                    'report_card_id' => $reportCard->id,
                    'subject_id' => $gradeData['subject_id'],
                ],
                [
                    'subject' => $subject->name,
                    'grade' => $gradeData['grade'] ?? null,
                    'percentage' => $gradeData['percentage'] ?? null,
                    'comments' => $gradeData['comments'] ?? null,
                ]
            );
        }

        return redirect()->route('reports.report-cards.show', $reportCard)
            ->with('success', 'Report card updated successfully.');
    }

    /**
     * Remove the specified report card.
     */
    public function destroy(ReportCard $reportCard)
    {
        $this->authorize('delete', $reportCard);

        $reportCard->delete();

        return redirect()->route('reports.report-cards.index')
            ->with('success', 'Report card deleted successfully.');
    }
}
