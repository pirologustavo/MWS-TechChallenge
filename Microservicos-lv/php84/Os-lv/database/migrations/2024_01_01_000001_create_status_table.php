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
        Schema::create('status', function (Blueprint $table) {
            $table->id('statid');
            $table->string('statusdesc', 50)->unique();
        });

        // Inserindo os status iniciais logo após criar a tabela
        DB::table('status')->insert([
            ['statusdesc' => 'Recebida'],
            ['statusdesc' => 'Em diagnóstico'],
            ['statusdesc' => 'Aguardando Aprovação'],
            ['statusdesc' => 'Em Execução'],
            ['statusdesc' => 'Finalizado'],
            ['statusdesc' => 'Entregue'],
            ['statusdesc' => 'Cancelado'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('statuses');
    }
};
