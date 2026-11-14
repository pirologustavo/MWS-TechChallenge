<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os', function (Blueprint $table) {
            $table->timestamp('status_changed_at')->nullable()->after('statid_atual');
        });

        DB::table('os')->whereNull('status_changed_at')->update([
            'status_changed_at' => DB::raw('COALESCE(updated_at, created_at)'),
        ]);
    }

    public function down(): void
    {
        Schema::table('os', function (Blueprint $table) {
            $table->dropColumn('status_changed_at');
        });
    }
};
