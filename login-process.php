<?php
session_start();
include('admin/vendor/inc/config.php');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $user_type = $_POST['user_type'];
    if (empty($email) || empty($password) || empty($user_type)) {
        $_SESSION['error'] = "All fields are required.";
        header("Location: index.php");
        exit();
    }
    try {
        if ($user_type === 'admin') {
            // Admin login
            $stmt = $mysqli->prepare("SELECT a_id, a_name, a_email, a_pwd FROM tms_admin WHERE a_email = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $mysqli->error);
            }
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($a_id, $a_name, $a_email, $a_pwd);
                $stmt->fetch();
                // If passwords are stored as MD5 hash
                if (md5($password) === $a_pwd) {
                    $_SESSION['a_id'] = $a_id;
                    $_SESSION['a_name'] = $a_name;
                    $_SESSION['a_email'] = $a_email;
                    $_SESSION['user_type'] = 'admin';
                    // Redirect to admin dashboard
                    header("Location: admin/admin-dashboard.php");
                    exit();
                } else {
                    throw new Exception("Invalid password");
                }
            } else {
                throw new Exception("Admin not found");
            }
        } elseif ($user_type === 'user') {
            // Normal user login
            $stmt = $mysqli->prepare("SELECT u_id, CONCAT(u_fname, ' ', u_lname) AS u_name, u_email, u_pwd FROM tms_user WHERE u_email = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $mysqli->error);
            }
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $stmt->bind_result($u_id, $u_name, $u_email, $u_pwd);
                $stmt->fetch();
                // If passwords are stored as MD5 hash
                if (md5($password) === $u_pwd) {
                    $_SESSION['u_id'] = $u_id;
                    $_SESSION['u_name'] = $u_name;
                    $_SESSION['u_email'] = $u_email;
                    $_SESSION['user_type'] = 'user';
                    // Redirect to user dashboard
                    header("Location: usr/user-dashboard.php");
                    exit();
                } else {
                    throw new Exception("Invalid password");
                }
            } else {
                throw new Exception("User not found");
            }
        } else {
            throw new Exception("Invalid user type");
        }
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
        header("Location: index.php");
        exit();
    }
} else {
    $_SESSION['error'] = "Invalid request.";
    header("Location: index.php");
    exit();
}
