<?php

namespace App\Http\Controllers;

use App\Models\SchoolCalendar;
use App\Models\SchoolClass;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Carbon\Carbon;

class SchoolCalendarController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('permission:manage students', only: ['store', 'update', 'destroy']),
        ];
    }

    public function index(Request $request)
    {
        $year = $request->get('year', now()->year);
        $month = $request->get('month');

        $query = SchoolCalendar::with('classes')
            ->whereYear('date', $year)
            ->orderBy('date');

        if ($month) {
            $query->whereMonth('date', $month);
        }

        $entries = $query->get()->groupBy(function ($entry) {
            return $entry->date->format('Y-m');
        });

        $classes = SchoolClass::active()->orderBy('display_order')->orderBy('name')->get();

        return view('calendar.index', compact('entries', 'year', 'month', 'classes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'end_date' => 'nullable|date|after_or_equal:date',
            'type' => 'required|in:holiday,half_day,special,vacation',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'affects_all_classes' => 'boolean',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:school_classes,id',
        ]);

        $startDate = Carbon::parse($validated['date']);
        $endDate = isset($validated['end_date']) ? Carbon::parse($validated['end_date']) : $startDate;
        $affectsAll = $validated['affects_all_classes'] ?? true;
        $classIds = $validated['class_ids'] ?? [];

        // Create entries for date range (for vacations)
        $created = 0;
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $entry = SchoolCalendar::firstOrCreate(
                ['date' => $date->format('Y-m-d'), 'name' => $validated['name']],
                [
                    'type' => $validated['type'],
                    'description' => $validated['description'] ?? null,
                    'affects_all_classes' => $affectsAll,
                ]
            );

            // Sync classes if not affecting all
            if (!$affectsAll && !empty($classIds)) {
                $entry->classes()->sync($classIds);
            } else {
                $entry->classes()->detach(); // Clear any previous class associations
            }

            $created++;
        }

        $message = $created > 1 
            ? "Created {$created} calendar entries for {$validated['name']}" 
            : "Calendar entry created successfully";

        return redirect()->route('calendar.index')
            ->with('success', $message);
    }

    public function update(Request $request, SchoolCalendar $calendar)
    {
        $validated = $request->validate([
            'type' => 'required|in:holiday,half_day,special,vacation',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'affects_all_classes' => 'boolean',
            'class_ids' => 'nullable|array',
            'class_ids.*' => 'exists:school_classes,id',
        ]);

        $affectsAll = $validated['affects_all_classes'] ?? true;
        $classIds = $validated['class_ids'] ?? [];

        $calendar->update([
            'type' => $validated['type'],
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'affects_all_classes' => $affectsAll,
        ]);

        // Sync classes
        if (!$affectsAll && !empty($classIds)) {
            $calendar->classes()->sync($classIds);
        } else {
            $calendar->classes()->detach();
        }

        return redirect()->route('calendar.index')
            ->with('success', 'Calendar entry updated successfully');
    }

    public function destroy(SchoolCalendar $calendar)
    {
        $calendar->delete();

        return redirect()->route('calendar.index')
            ->with('success', 'Calendar entry deleted successfully');
    }

    // API endpoint to check if a date is a holiday
    public function checkDate(Request $request)
    {
        $date = $request->get('date');
        $classId = $request->get('class_id');

        if (!$date) {
            return response()->json(['error' => 'Date required'], 400);
        }

        $entry = SchoolCalendar::getCalendarEntry($date, $classId);

        return response()->json([
            'is_holiday' => SchoolCalendar::isHoliday($date, $classId),
            'is_half_day' => SchoolCalendar::isHalfDay($date, $classId),
            'entry' => $entry ? [
                'type' => $entry->type,
                'name' => $entry->name,
                'description' => $entry->description,
            ] : null,
        ]);
    }
}
