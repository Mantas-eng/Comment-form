<?php
header('Content-Type: application/json');

$host = 'https://pacific-springs-15861-df7e1201a420.herokuapp.com/';
$db = 'mano_baze';
$user = 'root';
$pass = '';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Connection failed: ' . $conn->connect_error]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
    $comment = trim($_POST['comment']);
    $parent_id = isset($_POST['parent_id']) ? intval($_POST['parent_id']) : 0;

    if (!$username || !$email || !$comment) {
        echo json_encode(['error' => 'All fields are required.']);
        exit;
    }

    $sql = "INSERT INTO comments (parent_id, username, email, comment) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isss", $parent_id, $username, $email, $comment);

    if ($stmt->execute()) {
        $comment_id = $stmt->insert_id;
        $created_at = date('Y-m-d H:i:s');

        echo json_encode([
            'message' => 'Comment added successfully.',
            'comment' => [
                'id' => $comment_id,
                'username' => htmlspecialchars($username),
                'comment' => htmlspecialchars($comment),
                'created_at' => $created_at,
                'parent_id' => $parent_id,
            ],
        ]);
    } else {
        echo json_encode(['error' => 'Failed to save comment.']);
    }

    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid request method.']);
}

$conn->close();
?>