<?php
require_once('../../vendor/inc/checklogin.php');
check_login();
if (!is_admin()) {
    header("Location: ../../index.php");
    exit();
}
include('../../admin/vendor/inc/config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('../../vendor/inc/head.php');?>
    <title>Admin Dashboard</title>
</head>
<body>
    <!-- Navigation -->
    <?php include('../../vendor/inc/nav.php');?>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <h1>Welcome Admin, <?php echo $_SESSION['a_name']; ?></h1>
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Admin Dashboard</h5>
                        <p class="card-text">You're logged in as an administrator.</p>
                        <a href="../logout.php" class="btn btn-danger">Logout</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include('../../vendor/inc/footer.php');?>
    <script src="../../vendor/jquery/jquery.min.js"></script>
    <script src="../../vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>