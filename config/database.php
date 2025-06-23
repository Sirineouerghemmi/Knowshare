<?php
$db_server = "127.0.0.1";
$db_username = "root";
$db_pwd = "";
$db_name = "knowshare";

try {
    $cnx = new PDO("mysql:host=$db_server;dbname=$db_name", $db_username, $db_pwd);
    $cnx->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>