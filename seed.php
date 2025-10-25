<?php
// One-time script to create admin (save as seed.php and run once)
require_once 'includes/db.php';
$hash = password_hash('misbrgy4thyear', PASSWORD_DEFAULT);
echo $hash . "\n";
$conn->query("INSERT INTO users (username, name, role, password) VALUES ('admin', 'Ersel Magbanua','admin','$hash')");
echo "Admin created!";

//$2y$10$qVF1aQqguiec6vBZ8JIxbe6RyLgEurxB1jJo/xqaTCGA9hvJeouoO