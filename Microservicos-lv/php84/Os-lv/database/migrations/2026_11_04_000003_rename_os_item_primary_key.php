<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('os_itens', 'itemid') && !Schema::hasColumn('os_itens', 'id')) {
            Schema::table('os_itens', function (Blueprint $table) {
                $table->renameColumn('itemid', 'id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('os_itens', 'id') && !Schema::hasColumn('os_itens', 'itemid')) {
            Schema::table('os_itens', function (Blueprint $table) {
                $table->renameColumn('id', 'itemid');
            });
        }
    }
};
