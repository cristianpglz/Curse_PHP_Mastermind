<?php

$dsn = 'mysql:host=localhost;dbname=contacts_app';
$user = 'root'; // o el nombre de usuario que corresponda
$password = ''; // o la contraseña que corresponda

try {
    $conn = new PDO($dsn, $user, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection failed: ' . $e->getMessage();
}
?>
