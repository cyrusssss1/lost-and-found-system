<?php

require_once __DIR__ . '/vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

$db = new Database();
$users = $db->getDatabase()->users;

$admin = $users->findOne([
    "role" => "admin"
]);

if (!$admin) {
    echo "No admin found.";
    exit;
}

$password = "09068222097r";

if (password_verify($password, $admin["password"])) {
    echo "<h2>Password is correct!</h2>";
} else {
    echo "<h2>Password does NOT match.</h2>";
}