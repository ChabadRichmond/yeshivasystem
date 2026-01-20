<?php

namespace App\Http\Controllers;

use App\Models\AcademicGrade;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class GradeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage students'),
        ];
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
        ]);

        AcademicGrade::create([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'display_order' => $validated['display_order'] ?? 0,
            'is_active' => true,
        ]);

        return redirect()->route('classes.index')
            ->with('success', 'Grade created successfully.');
    }

    public function update(Request $request, AcademicGrade $grade)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'display_order' => 'nullable|integer|min:0',
        ]);

        $grade->update([
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'display_order' => $validated['display_order'] ?? $grade->display_order,
        ]);

        return redirect()->route('classes.index')
            ->with('success', 'Grade updated successfully.');
    }

    public function destroy(AcademicGrade $grade)
    {
        // Check if students are assigned to this grade
        if ($grade->students()->count() > 0) {
            return redirect()->route('classes.index')
                ->with('error', 'Cannot delete grade with assigned students. Please reassign students first.');
        }

        $grade->delete();

        return redirect()->route('classes.index')
            ->with('success', 'Grade deleted successfully.');
    }
}
