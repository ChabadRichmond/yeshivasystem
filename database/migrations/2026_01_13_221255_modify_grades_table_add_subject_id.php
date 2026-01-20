<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create grades table if it doesn't exist (recreate with proper structure)
        if (!Schema::hasTable('grades')) {
            Schema::create('grades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('report_card_id')->constrained()->cascadeOnDelete();
                $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
                $table->string('subject')->nullable(); // Keep for backward compatibility
                $table->string('grade')->nullable(); // Letter grade (A, B+, etc.)
                $table->decimal('percentage', 5, 2)->nullable();
                $table->text('comments')->nullable();
                $table->boolean('calculated_from_tests')->default(false);
                $table->timestamps();
            });
        } else {
            Schema::table('grades', function (Blueprint $table) {
                if (!Schema::hasColumn('grades', 'subject_id')) {
                    $table->foreignId('subject_id')->nullable()->after('report_card_id')->constrained()->nullOnDelete();
                }
                if (!Schema::hasColumn('grades', 'calculated_from_tests')) {
                    $table->boolean('calculated_from_tests')->default(false)->after('comments');
                }
            });
        }
    }

    public function down(): void
    {
        // Only drop if we created it
        Schema::dropIfExists('grades');
    }
};
