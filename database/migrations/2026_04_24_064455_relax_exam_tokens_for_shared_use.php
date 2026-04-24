<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('exam_tokens', function (Blueprint $table) {
            $table->string('status')->default('active')->change();
        });

        DB::table('exam_tokens')
            ->whereIn('status', ['unused', 'in_progress', 'used'])
            ->update(['status' => 'active']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('exam_tokens')
            ->where('status', 'active')
            ->update(['status' => 'unused']);

        Schema::table('exam_tokens', function (Blueprint $table) {
            $table->string('status')->default('unused')->change();
        });
    }
};
