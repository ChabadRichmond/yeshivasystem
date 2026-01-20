<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('school_calendar', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->enum('type', ['holiday', 'half_day', 'special', 'vacation']);
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('affects_all_classes')->default(true);
            $table->foreignId('school_class_id')->nullable()->constrained()->onDelete('cascade');
            $table->timestamps();
            
            $table->unique(['date', 'school_class_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('school_calendar');
    }
};
