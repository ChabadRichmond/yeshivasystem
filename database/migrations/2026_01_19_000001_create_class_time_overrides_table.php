<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Class time overrides - allow temporary start/end time changes for specific dates
        Schema::create('class_time_overrides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->date('override_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Each class can only have one time override per date
            $table->unique(['school_class_id', 'override_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_time_overrides');
    }
};
