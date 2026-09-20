<?php

$banco = "metalurgica_oliveira";

$configs = [
    ["host" => "localhost", "user" => "root", "pass" => ""],
    ["host" => "127.0.0.1", "user" => "root", "pass" => ""],
    ["host" => "192.168.56.20", "user" => "admin", "pass" => "12345"]
];

$pdo = null;
$ultimoErro = null;

foreach ($configs as $cfg) {
    try {
        $pdo = new PDO(
            "mysql:host={$cfg['host']};dbname={$banco};charset=utf8mb4",
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

?>