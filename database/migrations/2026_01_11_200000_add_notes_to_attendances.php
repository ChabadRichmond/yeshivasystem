<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("attendances", function (Blueprint $table) {
            if (!Schema::hasColumn("attendances", "notes")) {
                $table->text("notes")->nullable()->after("status");
            }
            if (!Schema::hasColumn("attendances", "minutes_early")) {
                $table->integer("minutes_early")->nullable()->after("minutes_late");
            }
        });
    }

    public function down(): void
    {
        Schema::table("attendances", function (Blueprint $table) {
            $table->dropColumn(["notes", "minutes_early"]);
        });
    }
};
