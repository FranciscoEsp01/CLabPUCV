<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->onDelete('cascade');
            $table->string('type'); // theoretical, logical
            $table->text('content');
            $table->json('options')->nullable(); // Para teóricos
            $table->string('correct_answer')->nullable(); // Para teóricos
            $table->text('boilerplate_code')->nullable(); // Para lógicos
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
