<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];  
$port = $_ENV['DB_PORT'];  
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

$conn = pg_connect("host=$host port=$port dbname=$db user=$user password=$pass");

if (!$conn) {
    die(json_encode(["error" => "Connection failed: " . pg_last_error()]));
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
    $comment = isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : '';
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;  

    if (!empty($username) && !empty($comment)) {
        $sql = "INSERT INTO comments (username, comment, parent_id, created_at) VALUES ($1, $2, $3, NOW())";
        
        // Sukuriame paruoštą užklausą
        $result = pg_query_params($conn, $sql, array($username, $comment, $parent_id));

        if ($result) {
            // Sėkmingai įrašytas komentaras, grąžiname sėkmės atsakymą
            echo json_encode(["success" => true, "message" => "Comment saved successfully!"]);
        } else {
            // Klaidos atveju grąžiname klaidos pranešimą
            echo json_encode(["error" => "Error saving comment: " . pg_last_error($conn)]);
        }
    } else {
        // Jei trūksta būtino lauko
        echo json_encode(["error" => "Username and comment are required!"]);
    }
} else {
    // Jei užklausa nėra POST tipo
    echo json_encode(["error" => "Invalid request method!"]);
}

// Uždarome prisijungimą prie duomenų bazės
pg_close($conn);
?>