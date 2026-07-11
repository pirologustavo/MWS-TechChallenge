<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('os_status', function (Blueprint $table) {
            $table->id('osstatid');
            $table->foreignId('osid')->constrained('os', 'osid')->onDelete('cascade');
            $table->foreignId('statid')->constrained('status', 'statid');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('os_status');
    }
};
