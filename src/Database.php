<?php

namespace Zayncaleb\Lostandfoundsystem;

use MongoDB\Client;

class Database
{
    private Client $client;
    private $database;

    public function __construct()
    {
        $this->client = new Client("mongodb://localhost:27017");

        $this->database = $this->client->selectDatabase("lost_and_found");
    }

    public function getDatabase()
    {
        return $this->database;
    }
}