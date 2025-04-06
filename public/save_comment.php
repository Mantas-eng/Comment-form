<?php
// Įtraukti autoloader'į, jei naudojate Composer
require_once __DIR__ . '/vendor/autoload.php';

// Naudoti Dotenv, kad nuskaitytumėte .env failą
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Gauti duomenų bazės prisijungimo informaciją iš .env failo
$host = 'mysql';  // Naudojame 'mysql', nes Docker Compose tinklui suteikėme tokią pavadinimo reikšmę
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

// Prisijungimas prie duomenų bazės
$conn = new mysqli($host, $user, $pass, $db);

// Patikriname, ar prisijungimas pavyko
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
        $sql = "INSERT INTO comments (username, comment, parent_id, created_at) VALUES (?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);

        if ($stmt === false) {
            die("Error preparing statement: " . $conn->error);
        }

        // Susiejame parametrus su užklausa
        $stmt->bind_param("ssi", $username, $comment, $parent_id);

        // Atlikti užklausą
        if ($stmt->execute()) {
            echo "Comment saved successfully!";
        } else {
            echo "Error saving comment: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "Username and comment are required!";
    }
} else {
    echo "Invalid request method!";
}

// Uždarome prisijungimą prie duomenų bazės
$conn->close();
?>