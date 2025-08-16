<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = isset($_SESSION['u_id']) ? (int) $_SESSION['u_id'] : 0;
// Defaults
$client_name = "No appointment";
$client_contact = "";
$pickup_location = "";
$dropoff_location = "";
$booking_id = null;
if ($aid > 0 && isset($mysqli)) {
    $sql = "
        SELECT b.booking_id,
               CONCAT(u.u_fname, ' ', u.u_lname) AS client_name,
               u.u_phone AS client_phone,
               b.pickup_point, b.dropoff_point, b.scheduled_at, b.status
        FROM tms_bookings b
        JOIN tms_user u ON u.u_id = b.client_id
        WHERE b.driver_id = ?
          AND b.status = 'pending'
          AND (b.scheduled_at IS NOT NULL AND b.scheduled_at >= NOW())
        ORDER BY b.scheduled_at ASC
        LIMIT 1
    ";
    if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $aid);
        $stmt->execute();
        if (method_exists($stmt, 'get_result')) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $booking_id = $row['booking_id'];
                $client_name = $row['client_name'] ?? $client_name;
                $client_contact = $row['client_phone'] ?? $client_contact;
                $pickup_location = $row['pickup_point'] ?? $pickup_location;
                $dropoff_location = $row['dropoff_point'] ?? $dropoff_location;
            }
        } else {
            // fallback without get_result()
            $stmt->bind_result($booking_id_res, $client_name_res, $client_phone_res, $pickup_res, $dropoff_res, $scheduled_res, $status_res);
            if ($stmt->fetch()) {
                $booking_id = $booking_id_res;
                $client_name = $client_name_res ?: $client_name;
                $client_contact = $client_phone_res ?: $client_contact;
                $pickup_location = $pickup_res ?: $pickup_location;
                $dropoff_location = $dropoff_res ?: $dropoff_location;
            }
        }
        $stmt->close();
    } else {
        error_log('Prepare failed: '.$mysqli->error);
    }
}
?>




<!DOCTYPE html>
<html lang="en">

<!-- Head -->
<?php include('vendor/inc/head.php'); ?>
<!-- End Head -->

<body id="page-top">
    <div id="wrapper">
        <div id="content-wrapper">
            <div class="container-fluid fas">
                <ol class="breadcrumb">
                    <h6 class="mb-4 breadcrumb-title">New Appointment</h6>
                </ol>
                <style>
                    .breadcrumb-title {
                        margin: 0 auto;           /* center horizontally */
                        text-align: center;       /* center text */
                        font-weight: 600;         /* make it semi-bold */
                        font-size: 1.25rem;        /* adjust size */
                        height: 5%;               /* set height to 5% of container */
                        line-height: 2rem;         /* vertical alignment */
                        width: auto;               /* let width fit text */
                        display: block;            /* ensure block-level for centering */
                    }
                    .breadcrumb {
                        justify-content: center;   /* center items in breadcrumb */
                        background: #f8f9fa;       /* light gray background */
                        padding: 0.5rem 1rem;
                        border-radius: 8px;
                    }
                </style>                    
                 <div class="card text-white" style="background-color: #000047; border-color: navy; color:antiquewhite; border-radius: 12px;">
                    <div class="card-body">
                        <h5 class="text-center mb-3">New Appointment</h5>
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($client_name); ?></p>
                        <p><strong>Contact:</strong> <?php echo htmlspecialchars($client_contact); ?></p>
                        <p class="text-center"><strong>Pickup Point:</strong> <?php echo htmlspecialchars($pickup_location); ?></p>
                        <p class="text-center"><strong>Drop-off Point:</strong> <?php echo htmlspecialchars($dropoff_location); ?></p>
                    </div>
                    <div class="card-footer d-flex justify-content-around">
                        <?php if ($booking_id): ?>
                        <a href="accept_appointment.php?id=<?php echo $booking_id; ?>" class="btn btn-success btn-sm" style="background: #E4ECF5;">Accept</a>
                        <a href="decline_appointment.php?id=<?php echo $booking_id; ?>" class="btn btn-danger btn-sm" style="background: #8B0000;">Decline</a>
                        <?php else: ?>
                        <button class="btn btn-secondary btn-sm" disabled>No appointment</button>
                        <?php endif; ?>
                    </div>
                    </div>
                <div style="background: #1B1212;">
                </div> 
                <!-- Bottom Navbar -->
                <nav class="navbar navbar-dark bg-white navbar-expand fixed-bottom">
                    <ul class="navbar-nav nav-justified w-100">
                        <li class="nav-item text-center">
                            <a class="nav-link" href="user-dashboard.php" style="color: black;">
                                <img src="../assets/icons/home3.png" alt="Home" style="width:45px; height:45px;"><br>
                                Home
                            </a>
                        </li>
                        <li class="nav-item text-center">
                            <a class="nav-link" href="appointments.php" style="color: black;">
                                <img src="../assets/icons/appo3.png" alt="Appointment" style="width:45px; height:45px;"><br>
                                Appointment
                            </a>
                        </li>
                        <li class="nav-item text-center">
                            <a class="nav-link" href="logs.php" style="color: black;">
                                <img src="../assets/icons/log3.png" alt="Log" style="width:45px; height:45px;"><br>
                                Log
                            </a>
                        </li>
                        
                    </ul>
                </nav>

            </div>
            <!-- /.container-fluid -->
        </div>
        <!-- /.content-wrapper -->
    </div>
    <!-- /#wrapper -->

    <!-- Scroll to Top Button-->
    <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
    </a>

    <!-- JavaScript -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Page plugins -->
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>

    <!-- Custom scripts -->
    <script src="vendor/js/sb-admin.min.js"></script>
    <script src="vendor/js/demo/datatables-demo.js"></script>
    <script src="vendor/js/demo/chart-area-demo.js"></script>
</body>

</html>
