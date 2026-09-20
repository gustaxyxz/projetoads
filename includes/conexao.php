<?php

$hostHospedagem  = "";
$bancoHospedagem = "";
$userHospedagem  = "";
$passHospedagem  = "";

$configs = [];

if (!empty($hostHospedagem) && !empty($bancoHospedagem)) {
    $configs[] = [
        "host"  => $hostHospedagem,
        "banco" => $bancoHospedagem,
        "user"  => $userHospedagem,
        "pass"  => $passHospedagem
    ];
}

$configs[] = ["host" => "localhost", "banco" => "metalurgica_oliveira", "user" => "root", "pass" => ""];
$configs[] = ["host" => "127.0.0.1", "banco" => "metalurgica_oliveira", "user" => "root", "pass" => ""];

$pdo = null;
$ultimoErro = null;

foreach ($configs as $cfg) {
    try {
        $pdo = new PDO(
            "mysql:host={$cfg['host']};dbname={$cfg['banco']};charset=utf8mb4",
            $cfg['user'],
            $cfg['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        break;
    } catch (PDOException $e) {
        $ultimoErro = $e;
    }
}

if (!$pdo) {
    die("Erro ao conectar com o banco de dados: " . ($ultimoErro ? $ultimoErro->getMessage() : "Desconhecido"));
}