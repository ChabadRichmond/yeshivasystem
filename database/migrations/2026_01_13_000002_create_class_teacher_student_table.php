<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pivot table for teacher-student assignments within a class
        // This allows: "In Class X, Teacher Y is responsible for Students [A, B, C]"
        Schema::create('class_teacher_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->timestamps();

            // Each student can only be assigned to one teacher per class
            $table->unique(['school_class_id', 'student_id'], 'class_student_teacher_unique');
            
            // Index for quick lookups by teacher
            $table->index(['school_class_id', 'teacher_user_id'], 'class_teacher_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_teacher_student');
    }
};
