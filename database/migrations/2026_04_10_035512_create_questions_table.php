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
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_id');
            $table->unsignedBigInteger('stimulus_id')->nullable();
            $table->unsignedInteger('order_number')->default(1);
            $table->string('type')->default('multiple_choice');
            $table->longText('question_text');
            $table->string('image_path')->nullable();
            $table->string('answer_key')->nullable();
            $table->unsignedInteger('score_weight')->default(1);
            $table->text('explanation')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('questions');
    }
};
