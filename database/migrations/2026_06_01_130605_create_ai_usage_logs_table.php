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
        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->enum('feature', ['triage', 'soap_draft', 'drug_check', 'no_show_pred']);
            $table->string('model', 60);
            $table->integer('input_tokens');
            $table->integer('output_tokens');
            $table->decimal('cost_usd', 8, 6);
            $table->integer('duration_ms');
            $table->timestamp('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
    }
};
