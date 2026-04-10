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
        Schema::create('student_answers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('exam_attempt_id');
            $table->unsignedBigInteger('question_id');
            $table->unsignedBigInteger('question_option_id')->nullable();
            $table->longText('answer_text')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->boolean('is_correct')->nullable();
            $table->unsignedInteger('score')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_answers');
    }
};
