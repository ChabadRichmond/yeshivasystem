<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('school_class_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('period_type', ['daily', 'weekly', 'monthly', 'yearly']);
            $table->date('period_start');
            $table->date('period_end');
            $table->integer('total_days')->default(0);
            $table->integer('present_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('late_days')->default(0);
            $table->integer('excused_days')->default(0);
            $table->integer('left_early_days')->default(0);
            $table->integer('total_minutes_late')->default(0);
            $table->decimal('attendance_percentage', 5, 2)->default(0.00);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();
            
            $table->unique(['student_id', 'school_class_id', 'period_type', 'period_start'], 'attendance_stats_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_stats');
    }
};
