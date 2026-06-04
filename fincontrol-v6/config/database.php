<?php

$host = "sql113.infinityfree.com";
$dbname = "if0_42092187_fincontrol";
$user = "if0_42092187";
$pass = "38858669Wii";

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
} catch (PDOException $e) {
    die("Erro de conexão com o banco de dados.");
}