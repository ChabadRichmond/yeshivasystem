<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Attendance takers are assigned per-student, per-class
        // This is SEPARATE from primary teacher assignments (which go through teaching groups)
        // A student can only have ONE attendance taker per class
        Schema::create('class_attendance_takers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('attendance_taker_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // One attendance taker per student per class
            $table->unique(['school_class_id', 'student_id'], 'class_student_attendance_unique');
        });

        // Migrate existing attendance_taker records from class_teacher_student
        if (Schema::hasTable('class_teacher_student')) {
            $existingRecords = DB::table('class_teacher_student')
                ->where('role', 'attendance_taker')
                ->get();

            foreach ($existingRecords as $record) {
                DB::table('class_attendance_takers')->insert([
                    'school_class_id' => $record->school_class_id,
                    'student_id' => $record->student_id,
                    'attendance_taker_id' => $record->teacher_user_id,
                    'created_at' => $record->created_at ?? now(),
                    'updated_at' => $record->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('class_attendance_takers');
    }
};
