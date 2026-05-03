<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('os', function (Blueprint $table) {
            $table->id('osid');
            $table->integer('clientid');
            $table->integer('carid');
            $table->integer('funcid');
            $table->text('sintomas')->nullable();
            $table->text('analise')->nullable();
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->string('status_atual')->default('Recebida');
            $table->foreignId('statid_atual')->default(1)->constrained('status', 'statid');
            $table->integer('entregue')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('os');
    }
};
