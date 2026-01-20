<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('test_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained()->nullOnDelete();
            $table->string('test_name');
            $table->date('test_date');
            $table->decimal('score', 8, 2);
            $table->decimal('max_score', 8, 2)->default(100);
            $table->decimal('percentage', 5, 2)->nullable();
            $table->string('letter_grade', 5)->nullable();
            $table->decimal('weight', 3, 2)->default(1.00);
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['student_id', 'subject_id']);
            $table->index(['test_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('test_scores');
    }
};
