<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('field', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('document')->onDelete('cascade');
            $table->string('name');
            $table->string('data_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field');
    }
};
