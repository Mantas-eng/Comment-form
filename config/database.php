<?php
function get_db_connection() {
    // Prisijungimo duomenys
    $host = 'gp96xszpzlqupw4k.cbetxkdyhwsb.us-east-1.rds.amazonaws.com';
    $user = 'd1h5t0yoywxh7daf';
    $pass = 'mocg4v8i04t8vh2y';
    $dbname = 'ui2butiwnvviyfgw';

    // Sukurkite ryšį su duomenų baze
    $conn = new mysqli($host, $user, $pass, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    return $conn;
}

?>