<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\AcademicGrade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Carbon\Carbon;

class StudentImportController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage students'),
        ];
    }

    public function index()
    {
        $grades = AcademicGrade::active()->ordered()->get();
        return view('students.import', compact('grades'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:2048',
            'default_grade_id' => 'nullable|exists:academic_grades,id',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        // Read CSV with error handling
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return redirect()->route('students.import')
                ->with('error', 'Could not open the uploaded file. Please try again.');
        }

        $header = fgetcsv($handle);
        if ($header === false) {
            fclose($handle);
            return redirect()->route('students.import')
                ->with('error', 'The file appears to be empty or in an invalid format.');
        }
        
        // Normalize headers
        $header = array_map(function($col) {
            return strtolower(trim(str_replace([' ', '-'], '_', $col)));
        }, $header);

        $imported = 0;
        $errors = [];
        $rowNum = 1;

        while (($row = fgetcsv($handle)) !== false) {
            $rowNum++;
            
            if (count($row) !== count($header)) {
                $errors[] = "Row {$rowNum}: Column count mismatch";
                continue;
            }

            $data = array_combine($header, $row);
            
            // Map columns
            $studentData = [
                'first_name' => $data['first_name'] ?? $data['firstname'] ?? null,
                'last_name' => $data['last_name'] ?? $data['lastname'] ?? null,
                'email' => $data['email'] ?? null,
                'date_of_birth' => $this->parseDate($data['date_of_birth'] ?? $data['dob'] ?? $data['birthday'] ?? null),
                'gender' => strtolower($data['gender'] ?? $data['sex'] ?? '') ?: null,
                'academic_grade_id' => $this->findGradeId($data['grade'] ?? $data['grade_level'] ?? null) ?? $request->default_grade_id,
                'phone' => $data['phone'] ?? $data['telephone'] ?? null,
                'address' => $data['address'] ?? null,
                'city' => $data['city'] ?? null,
                'province' => $data['province'] ?? $data['state'] ?? null,
                'postal_code' => $data['postal_code'] ?? $data['zip'] ?? null,
                'enrollment_status' => 'active',
                'enrollment_date' => now(),
            ];

            // Validate required fields
            $validator = Validator::make($studentData, [
                'first_name' => 'required|string|max:255',
                'last_name' => 'required|string|max:255',
            ]);

            if ($validator->fails()) {
                $errors[] = "Row {$rowNum}: " . implode(', ', $validator->errors()->all());
                continue;
            }

            // Check for duplicates
            $exists = Student::where('first_name', $studentData['first_name'])
                ->where('last_name', $studentData['last_name'])
                ->when($studentData['email'], fn($q) => $q->orWhere('email', $studentData['email']))
                ->exists();

            if ($exists) {
                $errors[] = "Row {$rowNum}: Student '{$studentData['first_name']} {$studentData['last_name']}' already exists";
                continue;
            }

            Student::create($studentData);
            $imported++;
        }

        fclose($handle);

        $message = "Successfully imported {$imported} students.";
        if (count($errors) > 0) {
            $message .= " " . count($errors) . " rows had errors.";
        }

        return redirect()->route('students.import')
            ->with('success', $message)
            ->with('import_errors', $errors);
    }

    public function template()
    {
        $headers = ['first_name', 'last_name', 'email', 'date_of_birth', 'gender', 'grade', 'phone', 'address', 'city', 'province', 'postal_code'];
        $sample = ['John', 'Doe', 'john@example.com', '2015-05-20', 'male', 'Aleph', '555-1234', '123 Main St', 'Toronto', 'ON', 'M1A 2B3'];

        $callback = function() use ($headers, $sample) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            fputcsv($handle, $sample);
            fclose($handle);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="student_import_template.csv"',
        ]);
    }

    private function parseDate($value)
    {
        if (!$value) return null;
        
        try {
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function findGradeId($gradeName)
    {
        if (!$gradeName) return null;
        
        $grade = AcademicGrade::where('name', $gradeName)
            ->orWhere('code', $gradeName)
            ->first();
        
        return $grade?->id;
    }
}
