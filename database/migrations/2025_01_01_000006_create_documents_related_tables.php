<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submitted_plans', function (Blueprint $table) {
            $table->id();
            $table->string('path');
            $table->boolean('verified')->default(false);
            $table->timestamps();
        });

        Schema::create('final_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->string('pdf_path')->nullable();
            $table->foreignId('document_id')->constrained('document')->onDelete('cascade');
            $table->string('status')->default('Pendente');
            $table->foreignId('plan_id')->nullable()->constrained('submitted_plans')->onDelete('set null');
            $table->timestamps();
        });

        Schema::create('field_value', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('document')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('user')->onDelete('cascade');
            $table->foreignId('field_id')->constrained('field')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->foreignId('final_document_id')->constrained('final_document')->onDelete('cascade');
        });

        Schema::create('addition', function (Blueprint $table) {
            $table->id();
            $table->foreignId('final_document_id')->constrained('final_document')->onDelete('cascade');
            $table->string('name');
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('addition');
        Schema::dropIfExists('field_value');
        Schema::dropIfExists('final_document');
        Schema::dropIfExists('submitted_plans');
    }
};
