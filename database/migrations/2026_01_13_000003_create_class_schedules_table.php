<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Class weekly schedules - different times for different days
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->tinyInteger('day_of_week'); // 0=Sunday, 1=Monday, ... 6=Saturday
            $table->time('start_time');
            $table->time('end_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Each class can have one schedule per day
            $table->unique(['school_class_id', 'day_of_week']);
        });

        // Class cancellations - mark specific dates as cancelled
        Schema::create('class_cancellations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->date('cancelled_date');
            $table->string('reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            // Each class can only be cancelled once per date
            $table->unique(['school_class_id', 'cancelled_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_cancellations');
        Schema::dropIfExists('class_schedules');
    }
};
