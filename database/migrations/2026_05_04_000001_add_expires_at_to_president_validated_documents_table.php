<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('president_validated_documents', function (Blueprint $table) {
            $table->timestamp('expires_at')->nullable()->after('is_validated');
        });

        DB::table('president_validated_documents')
            ->whereNull('expires_at')
            ->update(['expires_at' => now()->addDays(7)]);

    }

    public function down(): void
    {
        Schema::table('president_validated_documents', function (Blueprint $table) {
            $table->dropColumn('expires_at');
        });
    }
};
