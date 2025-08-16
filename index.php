<?php
session_start();
include('admin/vendor/inc/config.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include("vendor/inc/head.php");?>
    <link rel="stylesheet" href="css/login.css">
    <style>
        .split-container {
            display: flex;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }
        .left-side {
            flex: 1;
            background-image: url('assets/images/login-bg.jpg');
            background-size: cover;
            background-position: center;
            position: relative;
        }
        .left-side::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
        }
        .left-content {
            position: relative;
            z-index: 1;
            color: white;
            padding: 2rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .right-side {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #f8f9fa;
        }
        .login-form-box {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-btn {
            width: 100%;
            padding: 10px;
            font-weight: 600;
        }
        @media (max-width: 768px) {
            .split-container {
                flex-direction: column;
            }
            .left-side {
                display: none;
            }
        }
    </style>
</head>
<body class="split-container">
    <div class="right-side fas" style="color:#000047;">
        <div class="login-form-box">
            <h2 class="text-center mb-4">Login</h2>
            <?php if(isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <form action="login-process.php" method="post">
                <div class="form-group">
                    <label for="email">Email address</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="user_type">Login as:</label>
                    <select class="form-control" id="user_type" name="user_type" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-primary login-btn">Login</button>
                </div>
                <div class="text-center mt-3">
                    <a href="register.php">Don't have an account? Register</a>
                </div>
            </form>
        </div>
    </div>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>