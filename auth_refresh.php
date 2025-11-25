<?php
function refreshUserSession($conn) {
    if (!isset($_SESSION['user'])) return;

    $uid = $_SESSION['user']['id'];

    $q = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $q->bind_param("i", $uid);
    $q->execute();
    $fresh = $q->get_result()->fetch_assoc();

    if ($fresh) {
        $_SESSION['user'] = $fresh;
    }
}
?>