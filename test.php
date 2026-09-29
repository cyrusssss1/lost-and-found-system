<?php

require __DIR__ . '/vendor/autoload.php';

use Zayncaleb\Lostandfoundsystem\Database;

try {
    $database = new Database();
    $db = $database->getDatabase();

    echo "<h1>Success!</h1>";
    echo "<p>PHP is connected to MongoDB.</p>";
    echo "<p>Database: lost_and_found</p>";

} catch (Exception $e) {
    echo "<h1>Connection Failed</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}