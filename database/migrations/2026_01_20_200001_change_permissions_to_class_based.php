<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_permissions', function (Blueprint $table) {
            // Drop the time-based columns
            $table->dropColumn(['start_time', 'end_time']);

            // Add class-based columns
            // first_excused_class_id = first class student is excused from (inclusive)
            // last_excused_class_id = last class student is excused from (inclusive)
            // If both are NULL, permission applies to ALL classes (full day)
            $table->foreignId('first_excused_class_id')->nullable()->after('end_date')
                ->constrained('school_classes')->onDelete('set null');
            $table->foreignId('last_excused_class_id')->nullable()->after('first_excused_class_id')
                ->constrained('school_classes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('student_permissions', function (Blueprint $table) {
            $table->dropForeign(['first_excused_class_id']);
            $table->dropForeign(['last_excused_class_id']);
            $table->dropColumn(['first_excused_class_id', 'last_excused_class_id']);

            // Restore time columns
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }
};
