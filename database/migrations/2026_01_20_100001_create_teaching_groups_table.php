<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Teaching groups represent sub-groups within a class
        // e.g., "Shiur Aleph", "Shiur Beis" within "Chassidus Boker"
        // Each teaching group has ONE primary teacher
        Schema::create('teaching_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_class_id')->constrained()->onDelete('cascade');
            $table->string('name'); // e.g., "Shiur Aleph", "Shiur Beis"
            $table->foreignId('primary_teacher_id')->nullable()->constrained('users')->onDelete('set null');
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Each teaching group name should be unique within a class
            $table->unique(['school_class_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('teaching_groups');
    }
};
