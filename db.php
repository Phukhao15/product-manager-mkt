<?php
session_start(); // เริ่มต้น Session ทุกครั้งที่เรียกใช้ไฟล์นี้

$host = "localhost";
$username = "root";
$password = "";
$dbname = "product_db";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>