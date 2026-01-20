<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Create pivot table for many-to-many relationship
        Schema::create('calendar_class', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_calendar_id')->constrained('school_calendar')->onDelete('cascade');
            $table->foreignId('school_class_id')->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['school_calendar_id', 'school_class_id']);
        });

        // Remove the single class FK from school_calendar (we'll use the pivot table now)
        Schema::table('school_calendar', function (Blueprint $table) {
            $table->dropForeign(['school_class_id']);
            $table->dropColumn('school_class_id');
        });
    }

    public function down(): void
    {
        Schema::table('school_calendar', function (Blueprint $table) {
            $table->foreignId('school_class_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::dropIfExists('calendar_class');
    }
};
