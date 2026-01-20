<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_permissions', function (Blueprint $table) {
            // Add optional time columns for partial-day permissions
            // When NULL, permission applies to the entire day
            // When set, permission only applies during those hours
            $table->time('start_time')->nullable()->after('end_date');
            $table->time('end_time')->nullable()->after('start_time');
        });
    }

    public function down(): void
    {
        Schema::table('student_permissions', function (Blueprint $table) {
            $table->dropColumn(['start_time', 'end_time']);
        });
    }
};
