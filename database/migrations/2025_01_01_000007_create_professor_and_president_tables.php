<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('professor', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::create('professor_course', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professor_id')->constrained('professor')->onDelete('cascade');
            $table->foreignId('course_id')->constrained('course')->onDelete('cascade');
            $table->foreignId('intern_id')->constrained('user')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('president_emails', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
        });

        Schema::create('president_validated_documents', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->unique();
            $table->foreignId('final_document_id')->constrained('final_document')->onDelete('cascade');
            $table->boolean('is_verified')->default(false);
            $table->boolean('is_validated')->default(false);
        });

        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->string('action');
            $table->string('name');
            $table->foreignId('final_document_id')->nullable()->constrained('final_document')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logs');
        Schema::dropIfExists('president_validated_documents');
        Schema::dropIfExists('president_emails');
        Schema::dropIfExists('professor_course');
        Schema::dropIfExists('professor');
    }
};
