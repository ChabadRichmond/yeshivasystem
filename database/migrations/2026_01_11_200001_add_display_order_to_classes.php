<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table("school_classes", function (Blueprint $table) {
            if (!Schema::hasColumn("school_classes", "display_order")) {
                $table->integer("display_order")->default(0)->after("name");
            }
        });
        // Set initial order based on id
        DB::statement("UPDATE school_classes SET display_order = id WHERE display_order = 0");
    }

    public function down(): void
    {
        Schema::table("school_classes", function (Blueprint $table) {
            $table->dropColumn("display_order");
        });
    }
};
