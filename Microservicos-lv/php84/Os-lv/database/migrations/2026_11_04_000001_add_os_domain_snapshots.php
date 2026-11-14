<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('os', function (Blueprint $table) {
            $table->string('cliente_nome_snapshot')->nullable();
            $table->string('cliente_email_snapshot')->nullable();
            $table->string('veiculo_modelo_snapshot')->nullable();
            $table->string('veiculo_placa_snapshot')->nullable();
            $table->string('funcionario_nome_snapshot')->nullable();
        });

        Schema::table('os_itens', function (Blueprint $table) {
            $table->string('descricao_snapshot')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('os_itens', fn (Blueprint $table) => $table->dropColumn('descricao_snapshot'));
        Schema::table('os', function (Blueprint $table) {
            $table->dropColumn(['cliente_nome_snapshot', 'cliente_email_snapshot', 'veiculo_modelo_snapshot', 'veiculo_placa_snapshot', 'funcionario_nome_snapshot']);
        });
    }
};
