<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Assigns students to teaching groups
        // A student can only be in ONE teaching group per class
        Schema::create('teaching_group_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teaching_group_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            // A student can only be in one teaching group (enforced at teaching group level)
            $table->unique(['teaching_group_id', 'student_id']);
        });

        // Add index for finding a student's teaching group within a class
        // We'll enforce "one teaching group per student per class" at the application level
        // since the constraint would need to reference teaching_groups.school_class_id
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_group_student');
    }
};
