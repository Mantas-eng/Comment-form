<?php
// Gauti ClearDB duomenų bazės prisijungimo informaciją iš aplinkos kintamųjų Heroku
$cleardb_url = parse_url(getenv("gp96xszpzlqupw4k.cbetxkdyhwsb.us-east-1.rds.amazonaws.com"));
$host = $cleardb_url["gp96xszpzlqupw4k.cbetxkdyhwsb.us-east-1.rds.amazonaws.com"];
$user = $cleardb_url["admin"];
$pass = $cleardb_url["pass"];
$db = substr($cleardb_url["path"], 1);

// Prisijungti prie duomenų bazės
$conn = new mysqli($host, $user, $pass, $db);

// Patikrinti klaidas
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Funkcija, skirta gauti komentarus
function fetch_comments($parent_id = 0, $conn)
{
    $sql = "SELECT * FROM comments WHERE parent_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($sql);
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

// Gauti visus komentarus
$comments = fetch_comments(0, $conn);

// Funkcija, kuri išveda komentarus
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
        echo "<small class='text-muted'>Posted on: " . date('F j, Y, g:i a', strtotime($comment['created_at'])) . "</small>";
        echo "<button class='btn m-3 reply-btn mt-2' data-id='" . $comment['id'] . "'>Reply</button>";
        echo "</div>";

        $replies = fetch_comments($comment['id'], $conn);
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

// Išvesti komentarus
render_comments($comments, $conn);

// Uždaryti prisijungimą prie duomenų bazės
$conn->close();
?>