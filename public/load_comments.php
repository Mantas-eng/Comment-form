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

// Funkcija, kuri užklausia komentarus iš duomenų bazės
function fetch_comments($conn, $parent_id = 0)
{
    $sql = "SELECT * FROM comments WHERE parent_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        die("Error preparing statement: " . $conn->error);
    }
    $stmt->bind_param("i", $parent_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $comments = [];
    while ($row = $result->fetch_assoc()) {
        $comments[] = $row;
    }
    $stmt->close();
    return $comments;
}

// Užklausa visų komentarų kiekio
$sql_total = "SELECT COUNT(*) as total FROM comments";
$result_total = $conn->query($sql_total);
if ($result_total === false) {
    die("Error querying total comments: " . $conn->error);
}
$total_comments = $result_total->fetch_assoc()['total'];

// Rodyti bendrą komentarų kiekį
echo "<h5 id='total-comments'>Comments: {$total_comments}</h5>";

// Gauti ir rodyti visus pagrindinius komentarus
$comments = fetch_comments($conn);
render_comments($comments, $conn);

// Funkcija, kuri atvaizduoja komentarus ir jų atsakymus
function render_comments($comments, $conn)
{
    foreach ($comments as $comment) {
        echo "<div class='card mb-3' id='comment-{$comment['id']}'>";
        echo "<div class='card-body'>";
        echo "<div class='d-flex flex-column flex-sm-row'>";

        // Jei nėra nuotraukos, naudojamas numatytasis Gravatar avataras
        $avatar_url = !empty($comment['avatar_url']) ? $comment['avatar_url'] : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';  
        echo "<img class='rounded-circle shadow-1-strong me-3 mb-3 mb-sm-0' src='{$avatar_url}' alt='avatar' width='50' height='50' />";

        echo "<div class='flex-grow-1'>";
        echo "<p><strong>" . htmlspecialchars($comment['username']) . "</strong>: " . htmlspecialchars($comment['comment']) . "</p>";
        echo "<div class='d-flex justify-content-between align-items-center'>";
        echo "<small class='text-muted'>Posted on: " . htmlspecialchars($comment['created_at']) . "</small>";
        echo "<button class='btn m-3 reply-btn mt-2' data-id='" . $comment['id'] . "'>Reply</button>";
        echo "</div>";

        // Rodyti atsakymus, jei jie yra
        $replies = fetch_comments($conn, $comment['id']);
        if (!empty($replies)) {
            echo "<div class='comment-reply ms-4'>";
            render_comments($replies, $conn);
            echo "</div>";
        }

        echo "</div>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }
}

// Uždarome prisijungimą prie duomenų bazės
$conn->close();
?>