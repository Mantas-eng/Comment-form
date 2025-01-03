<?php
function get_db_connection() {
    $db_url = parse_url(getenv('mysql://d1h5t0yoywxh7daf:mocg4v8i04t8vh2y@gp96xszpzlqupw4k.cbetxkdyhwsb.us-east-1.rds.amazonaws.com:3306/ui2butiwnvviyfgw'));

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