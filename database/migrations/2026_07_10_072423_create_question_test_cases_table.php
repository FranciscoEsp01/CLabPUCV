<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_test_cases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_question_id')->constrained()->onDelete('cascade');
            $table->text('input');
            $table->text('expected_output');
            $table->boolean('is_hidden')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_test_cases');
    }
};
