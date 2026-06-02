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
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignUuid('clinic_id')->constrained('clinics')->onDelete('cascade');
            $table->foreignUuid('branch_id')->constrained('clinic_branches')->onDelete('cascade');
            $table->string('specialty', 100);
            $table->text('bio')->nullable();
            $table->decimal('consultation_fee', 10, 2);
            $table->string('license_number', 80);
            $table->tinyInteger('years_experience')->unsigned();
            $table->boolean('ai_summary_enabled')->default(true);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('doctors');
    }
};
