<?php

$conn = new PDO('mysql:host=127.0.0.1:4306', 'root', '');

$conn->exec("DROP DATABASE IF EXISTS midgar1");

$conn->exec("CREATE DATABASE midgar1");

echo "Database 'midgar1' dropped and recreated successfully.\n";