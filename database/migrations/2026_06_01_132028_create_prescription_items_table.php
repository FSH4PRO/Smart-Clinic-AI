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
        Schema::create('prescription_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('prescription_id')->constrained('prescriptions')->onDelete('cascade');
            $table->string('drug_name', 120);
            $table->string('dosage', 80);
            $table->string('frequency', 80);
            $table->tinyInteger('duration_days')->nullable();
            $table->text('instructions')->nullable();
            $table->boolean('ai_interaction_flag')->default(false);
            $table->text('ai_interaction_detail')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prescription_items');
    }
};
