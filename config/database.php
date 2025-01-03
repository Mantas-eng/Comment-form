<?php
function get_db_connection() {
    $db_url = parse_url(getenv('JAWSDB_URL'));

    $host = $db_url['host'];
    $user = $db_url['user'];
    $pass = $db_url['pass'];
    $dbname = ltrim($db_url['path'], '/');

    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}
?>