<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('document', function (Blueprint $table) {
            $table->id();
            $table->string('docx_path');
            $table->string('name');
            $table->string('type'); // Plano, Protocolo
            $table->boolean('is_active')->default(true);
        });

        Schema::create('document_type_course', function (Blueprint $table) {
            $table->foreignId('document_id')->constrained('document')->onDelete('cascade');
            $table->foreignId('type_course_id')->constrained('type_course')->onDelete('cascade');
            $table->primary(['document_id', 'type_course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_type_course');
        Schema::dropIfExists('document');
    }
};
