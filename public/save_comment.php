<?php
// Įtraukti autoloader'į, jei naudojate Composer
require_once __DIR__ . '/vendor/autoload.php';

// Naudoti Dotenv, kad nuskaitytumėte .env failą
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Gauti duomenų bazės prisijungimo informaciją iš .env failo
$host = $_ENV['DB_HOST'];  // Naudojame duomenis iš .env failo
$port = $_ENV['DB_PORT'];  // PostgreSQL portas
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

// Sukuriame PostgreSQL prisijungimą
$conn = pg_connect("host=$host port=$port dbname=$db user=$user password=$pass");

if (!$conn) {
    die("Connection failed: " . pg_last_error());
}

// Tikriname, ar gauti reikalingi duomenys iš POST užklausos
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Išvalome duomenis prieš įrašant į duomenų bazę (apsaugos nuo XSS ir SQL injekcijų)
    $username = isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '';
    $comment = isset($_POST['comment']) ? htmlspecialchars($_POST['comment']) : '';
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;  // Jei nėra parent_id, priskiriame 0

    // Patikriname, ar visi būtini laukai yra užpildyti
    if (!empty($username) && !empty($comment)) {
        // SQL užklausa komentarų įrašymui į duomenų bazę
        $sql = "INSERT INTO comments (username, comment, parent_id, created_at) VALUES ($1, $2, $3, NOW())";
        
        // Sukuriame paruoštą užklausą
        $result = pg_query_params($conn, $sql, array($username, $comment, $parent_id));

        if ($result) {
            echo "Comment saved successfully!";
        } else {
            echo "Error saving comment: " . pg_last_error($conn);
        }
    } else {
        echo "Username and comment are required!";
    }
} else {
    echo "Invalid request method!";
}

// Uždarome prisijungimą prie duomenų bazės
pg_close($conn);
?>