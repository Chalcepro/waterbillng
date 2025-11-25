// ...existing code...

function validateUserSession() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit;
    }
}

// Call this function at the start of user-related pages
validateUserSession();
