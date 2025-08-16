<?php
function check_login() {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    // Check if user is logged in based on their type
    if (isset($_SESSION['user_type'])) {
        switch ($_SESSION['user_type']) {
            case 'admin':
                if (empty($_SESSION['a_id'])) {
                    redirect_to_login();
                }
                break;
                
            case 'user':
                if (empty($_SESSION['u_id'])) {
                    redirect_to_login();
                }
                break;
                
            default:
                // Invalid user type
                redirect_to_login();
        }
    } else {
        // No session data at all
        redirect_to_login();
    }
}

function redirect_to_login() {
    // Clear all session variables
    $_SESSION = array();
    
    // Destroy the session
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
    
    // Redirect to login page
    $host = $_SERVER['HTTP_HOST'];
    $uri = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
    $extra = "index.php";
    header("Location: http://$host$uri/$extra");
    exit();
}

// Additional helper function to check specific user types
function is_admin() {
    return (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin');
}

function is_user() {
    return (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'user');
}
?>