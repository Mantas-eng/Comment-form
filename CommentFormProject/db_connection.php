<?php
$parse = parse_url(getenv("JAWSDB_URL"));

$servername = $parse['host'];
$username = $parse['user'];
$password = $parse['pass'];
$dbname = substr($parse['path'], 1);

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>