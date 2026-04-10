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
        Schema::create('exam_results', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_attempt_id');
            $table->unsignedInteger('correct_count')->default(0);
            $table->unsignedInteger('wrong_count')->default(0);
            $table->unsignedInteger('blank_count')->default(0);
            $table->unsignedInteger('multiple_choice_score')->default(0);
            $table->unsignedInteger('essay_score')->default(0);
            $table->unsignedInteger('total_score')->default(0);
            $table->boolean('is_passed')->nullable();
            $table->string('essay_status')->default('not_needed');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_results');
    }
};
