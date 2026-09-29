<?php

namespace Zayncaleb\Lostandfoundsystem;

use MongoDB\Client;

class Database
{
    private Client $client;
    private $database;

    public function __construct()
    {
        // Use MongoDB Atlas when MONGODB_URI is provided.
        // Otherwise, use the local MongoDB server.
        $connectionString = getenv('MONGODB_URI');

        if (!$connectionString) {
            $connectionString = "mongodb://localhost:27017";
        }

        $this->client = new Client($connectionString);

        $this->database = $this->client->selectDatabase("lost_and_found");
    }

    public function getDatabase()
    {
        return $this->database;
    }
}