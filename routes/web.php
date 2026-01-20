<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentImportController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportCardController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\SchoolClassController;
use App\Http\Controllers\SchoolCalendarController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\TestScoreController;
use Illuminate\Support\Facades\Route;

Route::get("/", function () {
    return view("welcome");
});

Route::get("/dashboard", function () {
    return view("dashboard");
})->middleware(["auth", "verified"])->name("dashboard");

Route::middleware("auth")->group(function () {
    Route::get("/profile", [ProfileController::class, "edit"])->name("profile.edit");
    Route::patch("/profile", [ProfileController::class, "update"])->name("profile.update");
    Route::delete("/profile", [ProfileController::class, "destroy"])->name("profile.destroy");

    // Student import - MUST be before resource route (rate limited)
    Route::get("students/import", [StudentImportController::class, "index"])->name("students.import");
    Route::post("students/import", [StudentImportController::class, "store"])
        ->middleware("throttle:10,1")
        ->name("students.import.store");
    Route::get("students/import/template", [StudentImportController::class, "template"])->name("students.import.template");

    // Student photo management - MUST be before resource route
    Route::delete("students/{student}/photo", [StudentController::class, "deletePhoto"])->name("students.photo.destroy");

    // Student permission/leave management - MUST be before resource route
    Route::post("students/{student}/permissions", [StudentController::class, "addPermission"])->name("students.permissions.store");
    Route::delete("students/{student}/permissions/{permission}", [StudentController::class, "deletePermission"])->name("students.permissions.destroy");

    // Student management
    Route::resource("students", StudentController::class);

    // Classes management
    Route::resource("classes", SchoolClassController::class);

    // School Calendar
    Route::get("calendar", [SchoolCalendarController::class, "index"])->name("calendar.index");
    Route::post("calendar", [SchoolCalendarController::class, "store"])->name("calendar.store");
    Route::put("calendar/{calendar}", [SchoolCalendarController::class, "update"])->name("calendar.update");
    Route::delete("calendar/{calendar}", [SchoolCalendarController::class, "destroy"])->name("calendar.destroy");
    Route::get("api/calendar/check", [SchoolCalendarController::class, "checkDate"])->name("calendar.check");
    Route::post("classes/reorder", [SchoolClassController::class, "reorder"])->name("classes.reorder");

    // Academic Grades (Grade levels like "Grade 1", "Shiur Aleph")
    Route::post("grades", [App\Http\Controllers\GradeController::class, "store"])->name("grades.store");
    Route::put("grades/{grade}", [App\Http\Controllers\GradeController::class, "update"])->name("grades.update");
    Route::delete("grades/{grade}", [App\Http\Controllers\GradeController::class, "destroy"])->name("grades.destroy");

    // Subjects (Admin)
    Route::resource("subjects", SubjectController::class)->except(["show", "create", "edit"]);

    // Grades Dashboard & Test Scores
    Route::get("grades", [TestScoreController::class, "index"])->name("grades.index");
    Route::get("grades/tests/class/{class}", [TestScoreController::class, "classScores"])->name("grades.tests.class");
    Route::get("grades/tests/student/{student}", [TestScoreController::class, "studentScores"])->name("grades.tests.student");
    Route::post("grades/tests", [TestScoreController::class, "store"])->name("grades.tests.store");
    Route::put("grades/tests/{testScore}", [TestScoreController::class, "update"])->name("grades.tests.update");
    Route::delete("grades/tests/{testScore}", [TestScoreController::class, "destroy"])->name("grades.tests.destroy");

    // Reports
    Route::get("reports", [ReportController::class, "index"])->name("reports.index");
    Route::get("reports/attendance", [ReportController::class, "attendance"])->name("reports.attendance");
    Route::get("reports/attendance/stats", [ReportController::class, "attendanceStats"])->name("reports.attendance.stats");
    Route::get("reports/attendance/export", [ReportController::class, "exportAttendance"])->name("reports.attendance.export");

    // Report Cards (under Reports)
    Route::get("reports/report-cards", [ReportCardController::class, "index"])->name("reports.report-cards.index");
    Route::get("reports/report-cards/create", [ReportCardController::class, "create"])->name("reports.report-cards.create");
    Route::post("reports/report-cards", [ReportCardController::class, "store"])->name("reports.report-cards.store");
    Route::get("reports/report-cards/{reportCard}", [ReportCardController::class, "show"])->name("reports.report-cards.show");
    Route::get("reports/report-cards/{reportCard}/edit", [ReportCardController::class, "edit"])->name("reports.report-cards.edit");
    Route::put("reports/report-cards/{reportCard}", [ReportCardController::class, "update"])->name("reports.report-cards.update");
    Route::delete("reports/report-cards/{reportCard}", [ReportCardController::class, "destroy"])->name("reports.report-cards.destroy");

    // Attendance
    Route::get("attendance/mark", [AttendanceController::class, "mark"])->name("attendance.mark");
    Route::get("attendance/old", [AttendanceController::class, "index"])->name("attendance.index.old");
    Route::get("attendance", [AttendanceController::class, "grid"])->name("attendance.index");
    Route::get("attendance/grid", [AttendanceController::class, "grid"])->name("attendance.grid"); // Keep for backwards compatibility
    Route::post("attendance", [AttendanceController::class, "store"])->name("attendance.store");
    Route::post("attendance/cancel-class", [AttendanceController::class, "cancelClass"])->name("attendance.cancel");
    Route::post("attendance/restore-class", [AttendanceController::class, "restoreClass"])->name("attendance.restore");
    Route::delete("attendance", [AttendanceController::class, "destroy"])->name("attendance.destroy");
    Route::post("attendance/clear-class", [AttendanceController::class, "clearClassAttendance"])->name("attendance.clear-class");
    Route::post("attendance/clear-day", [AttendanceController::class, "clearDayAttendance"])->name("attendance.clear-day");
    Route::get("attendance/import", [AttendanceController::class, "import"])->name("attendance.import");
    Route::get("attendance/import/template", [AttendanceController::class, "importTemplate"])->name("attendance.import.template");
    Route::post("attendance/import", [AttendanceController::class, "processImport"])
        ->middleware("throttle:10,1")
        ->name("attendance.import.process");
    Route::get("attendance/import/bulk-template", [AttendanceController::class, "bulkImportTemplate"])->name("attendance.import.bulk-template");
    Route::post("attendance/import/bulk-process", [AttendanceController::class, "processBulkImport"])
        ->middleware("throttle:10,1")
        ->name("attendance.import.bulk-process");
    Route::post("attendance/time-override", [AttendanceController::class, "saveTimeOverride"])->name("attendance.time-override");

    // Messaging
    Route::resource("messages", MessageController::class);
    // User Management
    Route::resource("users", App\Http\Controllers\UserController::class);
});

require __DIR__."/auth.php";
