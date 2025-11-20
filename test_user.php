<?php
require_once 'includes/db.php';
$hash = password_hash('test123', PASSWORD_DEFAULT);
$conn->query("INSERT INTO users (username, name, role, password) VALUES ('admin', 'Test Admin','admin','$hash')");
echo "Test admin created with username: admin, password: test123\n";
