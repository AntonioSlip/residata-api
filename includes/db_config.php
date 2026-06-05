<?php
// Renomeie este arquivo para db_config.php e preencha com seus dados locais
$host = 'localhost';
$db   = 'residata_db';
$user = 'root';
$pass = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8", $user, $pass);
} catch (PDOException $e) {
    die("Erro ao conectar: " . $e->getMessage());
}