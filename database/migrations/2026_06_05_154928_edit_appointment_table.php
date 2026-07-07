<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->text('chief_complaint')->nullable()->change(); // Filled only after App Triage
            $table->tinyInteger('triage_score')->nullable()->change(); // 1-5 urgency, calculated post-triage
            $table->decimal('no_show_risk', 4, 2)->nullable()->change(); // 0.00-1.00, calculated by daily Cron job
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointments', function (Blueprint $table) {
            $table->text('chief_complaint')->change(); // Make it required again
            $table->tinyInteger('triage_score')->change(); // Make it required again
            $table->decimal('no_show_risk', 4, 2)->change(); // Make it required again
        });
    }
};
