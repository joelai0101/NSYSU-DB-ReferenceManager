<?php
// Database credentials
$host = 'localhost'; // or IP address like '127.0.0.1'
$username = 'root'; // your database username
$password = ''; // your database password
$database = 'literature_db'; // the name of the database you are connecting to

// Create a new database connection
$link = new mysqli($host, $username, $password, $database);

// Check the connection
if($link->connect_error){
    die("ERROR: Could not connect. " . $link->connect_error);
}

// Set the character set to utf8mb4 for Unicode compatibility
$link->set_charset("utf8mb4");
