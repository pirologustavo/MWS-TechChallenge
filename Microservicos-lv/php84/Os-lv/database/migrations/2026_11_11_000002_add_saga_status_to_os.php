<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os', function (Blueprint $table): void {
            $table->string('saga_status', 32)->default('CONCLUIDA')->index();
        });
    }

    public function down(): void
    {
        Schema::table('os', function (Blueprint $table): void {
            $table->dropColumn('saga_status');
        });
    }
};
