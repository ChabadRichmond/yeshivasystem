<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Add class relationship
            $table->foreignId('school_class_id')->nullable()->after('student_id')->constrained('school_classes')->nullOnDelete();
            
            // Enhanced status options
            // Existing 'status' column will be modified to include new values
            
            // Late tracking
            $table->integer('minutes_late')->nullable()->after('status');
            $table->time('class_start_time')->nullable()->after('minutes_late');
            
            // Index for performance
            $table->index(['school_class_id', 'date']);
        });

        // Update status enum - need to use raw SQL for MySQL enum modification
        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'late_excused', 'late_unexcused', 'absent_excused', 'absent_unexcused', 'late', 'absent', 'excused') DEFAULT 'present'");
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->dropColumn(['school_class_id', 'minutes_late', 'class_start_time']);
            $table->dropIndex(['school_class_id', 'date']);
        });

        DB::statement("ALTER TABLE attendances MODIFY COLUMN status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present'");
    }
};
