<?php
/*
 * Test mysql connection
*/

error_reporting(E_ALL);

$base_datos = filter_input(\INPUT_POST, 'db');
$server = filter_input(\INPUT_POST, 'server');
$usuario = filter_input(\INPUT_POST, 'user');
$password = filter_input(\INPUT_POST, 'pass');

try {
    $pdo = new PDO("mysql:host=$server;dbname=".$base_datos, $usuario, $password);
    echo "1;".$pdo->getAttribute(PDO::ATTR_CONNECTION_STATUS);
} catch (PDOException $e) {
    echo "0;".$e->getMessage();
}
