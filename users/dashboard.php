<?php
session_start();
include('../vendor/inc/checklogin.php');
check_login();
if($_SESSION['user_type'] != 'user') {
    header("Location: ../index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../vendor/inc/head.php');?>
    <title>User Dashboard</title>
</head>
<body>
    <?php include('../vendor/inc/nav.php');?>
    
    <div class="container mt-5">
        <h1>Welcome, <?php echo $_SESSION['u_name']; ?></h1>
        <!-- User dashboard content here -->
    </div>
    
    <?php include('../vendor/inc/footer.php');?>
    <script src="../vendor/jquery/jquery.min.js"></script>
    <script src="../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>