<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->foreignId('absence_reason_id')->nullable()->after('notes')->constrained()->onDelete('set null');
            $table->foreignId('excused_by')->nullable()->after('recorded_by')->constrained('users')->onDelete('set null');
            $table->boolean('notified_parent')->default(false)->after('excused_by');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropForeign(['absence_reason_id']);
            $table->dropForeign(['excused_by']);
            $table->dropColumn(['absence_reason_id', 'excused_by', 'notified_parent']);
        });
    }
};
