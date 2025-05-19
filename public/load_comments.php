<?php
require_once __DIR__ . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

$host = $_ENV['DB_HOST'];  
$port = $_ENV['DB_PORT'];  
$db = $_ENV['DB_NAME'];
$user = $_ENV['DB_USER'];
$pass = $_ENV['DB_PASS'];

$conn_string = "host=$host port=$port dbname=$db user=$user password=$pass sslmode=require";
$conn = pg_connect($conn_string);

if (!$conn) {
    die("Connection failed: " . pg_last_error());
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
            echo "Comment saved successfully!";
        } else {
            echo "Error saving comment: " . pg_last_error($conn);
        }
    } else {
        echo "Username and comment are required!";
    }

}
function fetch_comments($conn, $parent_id = 0)
{
    $sql = "SELECT * FROM comments WHERE parent_id = $1 ORDER BY created_at DESC";
    $result = pg_query_params($conn, $sql, array($parent_id));

    if (!$result) {
        die("Error executing query: " . pg_last_error($conn));
    }

    $comments = [];
    while ($row = pg_fetch_assoc($result)) {
        $comments[] = $row;
    }
    return $comments;
}

// Užklausa visų komentarų kiekio
$sql_total = "SELECT COUNT(*) as total FROM comments";
$result_total = pg_query($conn, $sql_total);

if (!$result_total) {
    die("Error querying total comments: " . pg_last_error($conn));
}

$total_comments = pg_fetch_assoc($result_total)['total'];

echo "<h5 id='total-comments'>Comments: {$total_comments}</h5>";

$comments = fetch_comments($conn);
render_comments($comments, $conn);

function render_comments($comments, $conn)
{
    foreach ($comments as $comment) {
        echo "<div class='card mb-3' id='comment-{$comment['id']}'>";
        echo "<div class='card-body'>";
        echo "<div class='d-flex flex-column flex-sm-row'>";

        $avatar_url = !empty($comment['avatar_url']) ? $comment['avatar_url'] : 'https://www.gravatar.com/avatar/00000000000000000000000000000000?d=mp&f=y';  
        echo "<img class='rounded-circle shadow-1-strong me-3 mb-3 mb-sm-0' src='{$avatar_url}' alt='avatar' width='50' height='50' />";

        echo "<div class='flex-grow-1'>";
        echo "<p><strong>" . htmlspecialchars($comment['username']) . "</strong>: " . htmlspecialchars($comment['comment']) . "</p>";
        echo "<div class='d-flex justify-content-between align-items-center'>";
        echo "<small class='text-muted'>Posted on: " . htmlspecialchars($comment['created_at']) . "</small>";
        echo "<button class='btn m-3 reply-btn mt-2' data-id='" . $comment['id'] . "'>Reply</button>";
        echo "</div>";

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

pg_close($conn);
?>