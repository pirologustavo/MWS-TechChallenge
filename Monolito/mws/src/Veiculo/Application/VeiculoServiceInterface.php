<?php

namespace App\Veiculo\Application;

interface VeiculoServiceInterface
{
    const MARCAS = [
        "Audi",
        "BMW",
        "BYD",
        "Caoa Chery",
        "Citroen",
        "Chevrolet",
        "Fiat",
        "Ford",
        "GWM",
        "Honda",
        "Hyundai",
        "Jeep",
        "Land Rover",
        "Mercedes-Benz",
        "Mitsubishi",
        "Nissan",
        "Peugeot",
        "Porsche",
        "Renault",
        "Suzuki",
        "Toyota",
        "Volkswagen",
        "Volvo",
    ];

    public function validarVeiculo(array $veiculo);

    public function atualizarVeiculo($id, $veiculo);

    public function listarTodos();
}