<?php
session_start();
$filename = "/loginpage.html";
if (isset($_SESSION['admin']) && $_SESSION["admin"] === true) {
    $filename = "/storage_explorer.php";
    require(__DIR__ . $filename);
    die();
}
if (isset($_POST['submit'])) { 
    $password = $_POST['password']; 
    if ($password === "131313") { 
        session_start();
        $_SESSION["admin"] = true;
        $filename = "/storage_explorer.php";
    }
}
require(__DIR__ . $filename);