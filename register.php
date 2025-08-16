<?php
session_start();
include('admin/vendor/inc/config.php');
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fname = $mysqli->real_escape_string($_POST['fname']);
    $lname = $mysqli->real_escape_string($_POST['lname']);
    $email = $mysqli->real_escape_string($_POST['email']);
    $password = md5($_POST['password']); // Using md5 to match your existing data
    $phone = $mysqli->real_escape_string($_POST['phone']);
    $address = $mysqli->real_escape_string($_POST['address']);
    // Check if email already exists
    $check = $mysqli->prepare("SELECT u_id FROM tms_user WHERE u_email = ?");
    $check->bind_param('s', $email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
        $_SESSION['error'] = "Email already registered";
        header("Location: register.php");
        exit();
    }
    // Insert new user using your actual table structure
    $stmt = $mysqli->prepare("INSERT INTO tms_user (u_fname, u_lname, u_email, u_pwd, u_phone, u_addr, u_category) VALUES (?, ?, ?, ?, ?, ?, 'User')");
    $stmt->bind_param('ssssss', $fname, $lname, $email, $password, $phone, $address);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Registration successful! Please login.";
        header("Location: index.php");
        exit();
    } else {
        $_SESSION['error'] = "Registration failed: ".$mysqli->error;
        header("Location: register.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("vendor/inc/head.php");?>
    <title>User Registration</title>
</head>
<body>
    <div class="container">
        <h2>User Registration</h2>
        <?php if(isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>First Name</label>
                <input type="text" name="fname" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Last Name</label>
                <input type="text" name="lname" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Password</label>
                <input type="password" name="password" class="form-control" required>
            </div>
            <div class="form-group">
                <label>Phone</label>
                <input type="text" name="phone" class="form-control">
            </div>
            <div class="form-group">
                <label>Address</label>
                <textarea name="address" class="form-control"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Register</button>
            <a href="index.php" class="btn btn-link">Already have an account? Login</a>
        </form>
    </div>
</body>
</html>