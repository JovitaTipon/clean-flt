<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_log'])) {
    $trip_id = $mysqli->real_escape_string($_POST['trip_id']);
    $date = $mysqli->real_escape_string($_POST['date']);
    $pickup = $mysqli->real_escape_string($_POST['pickup']);
    $dropoff = $mysqli->real_escape_string($_POST['dropoff']);
    $odometer = $mysqli->real_escape_string($_POST['odometer']);

    // Example insert into tms_user fields you used before. Adjust columns as needed.
    $stmt = $mysqli->prepare("INSERT INTO tms_user (u_car_regno, u_car_bookdate, u_car_pickup, u_car_destination, u_car_book_status, u_car_pax) VALUES (?, ?, ?, ?, 'waiting', ?)");
    $stmt->bind_param("sssss", $trip_id, $date, $pickup, $dropoff, $odometer);
    if ($stmt->execute()) {
        // redirect to avoid form resubmission and to refresh table
        header("Location: logs.php");
        exit;
    } else {
        error_log("Insert failed: " . $stmt->error);
    }
    $stmt->close();
}
?>
<html lang="en">
<head>
    <?php include('vendor/inc/head.php'); ?>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <title>Logs</title>
    <style>
        /* Status badges for better visuals */
        .badge-completed {
            background-color: green;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .badge-ongoing {
            background-color: orange;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
        .badge-waiting {
            background-color: gray;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body id="page-top">
<div class="container mt-4 fas">
         <ol class="breadcrumb">
                    <h6 class="m-3 font-weight-bold fas" style="font-size: 30px; color:#000047;">Log's</h6>
                 </ol>
                <!-- Button to Open Modal -->
                <div class="d-flex justify-content-end mb-3">
                    <button type="button" class="btn btn-primary" style="background-color: navy; border-color: navy;" data-toggle="modal" data-target="#logTripModal">
                        + Log Trip
                    </button>
                </div>
                    <!-- Modal -->
                <div class="modal fade" id="logTripModal" tabindex="-1" role="dialog" aria-labelledby="logTripModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <div class="modal-content">

                            <!-- Modal Header -->
                            <div class="modal-header">
                                <h5 class="modal-title w-100 text-center" id="logTripModalLabel">
                                    PLEASE PROVIDE DETAILS OF THE TRIP
                                </h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <!-- Modal Body -->
                            <div class="modal-body">
                                <form method="POST" action="">
                                    <!-- Trip ID -->
                                    <div class="form-group text-center">
                                        <label for="trip_id">Trip ID</label>
                                        <input type="text" class="form-control w-50 mx-auto" id="trip_id" name="trip_id" required>
                                    </div>
                                    <!-- Date -->
                                    <div class="form-group text-center">
                                        <label for="date">Date</label>
                                        <input type="date" class="form-control w-50 mx-auto" id="date" name="date" required>
                                    </div>
                                    <!-- Pickup & Drop-off -->
                                    <div class="form-row">
                                        <div class="form-group col-md-6">
                                            <label for="pickup">Pick-Up Location</label>
                                            <input type="text" class="form-control" id="pickup" name="pickup" required>
                                        </div>
                                        <div class="form-group col-md-6">
                                            <label for="dropoff">Drop-Off Location</label>
                                            <input type="text" class="form-control" id="dropoff" name="dropoff" required>
                                        </div>
                                    </div>
                                    <!-- Odometer -->
                                    <div class="form-group text-center">
                                        <label for="odometer">Odometer Reading</label>
                                        <input type="number" class="form-control w-50 mx-auto" id="odometer" name="odometer" required>
                                    </div>
                                    <!-- Submit Button -->
                                    <div class="text-center">
                                        <button type="submit" name="add_log" class="btn btn-success">
                                            + Log Trip
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
    <!-- Responsive Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="text-white" style="background:#000047">
                                <tr>
                                    <th>Date</th>
                                    <th>Customer</th>
                                    <th>Pick Up Location</th>
                                    <th>Destination</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch from tms_user directly
                                $query = "
                                    SELECT 
                                        DATE_FORMAT(u.u_car_bookdate, '%Y-%m-%d') AS date,
                                        CONCAT(u.u_fname, ' ', u.u_lname) AS customer,
                                        u.u_car_pickup AS pickup,
                                        u.u_car_destination AS destination,
                                        u.u_car_book_status AS status
                                    FROM tms_user u
                                    WHERE u.u_category = 'client'
                                    ORDER BY u.u_car_bookdate DESC
                                ";
                                $result = $mysqli->query($query);
                                if ($result && $result->num_rows > 0) {
                                    while ($row = $result->fetch_assoc()) {
                                        // Assign badge classes
                                        $statusClass = '';
                                        if (strtolower($row['status']) === 'completed') {
                                            $statusClass = 'badge-completed';
                                        } elseif (strtolower($row['status']) === 'ongoing') {
                                            $statusClass = 'badge-ongoing';
                                        } else {
                                            $statusClass = 'badge-waiting';
                                        }
                                        echo "<tr>
                                                <td>{$row['date']}</td>
                                                <td>{$row['customer']}</td>
                                                <td>{$row['pickup']}</td>
                                                <td>{$row['destination']}</td>
                                                <td><span class='{$statusClass}'>" . ucfirst($row['status']) . "</span></td>
                                            </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center'>No logs found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <nav class="navbar navbar-dark bg-white navbar-expand fixed-bottom fas" style="color:#000047">
                    <ul class="navbar-nav nav-justified w-100">
                        <li class="nav-item text-center">
                            <a class="nav-link" href="user-dashboard.php" style="color:black;">
                                <img src="../assets/icons/home3.png" alt="Home" style="width:45px; height:45px;"><br>
                                Home
                            </a>
                        </li>
                        <li class="nav-item text-center">
                            <a class="nav-link" href="appointments.php" style="color:black;">
                                <img src="../assets/icons/appo3.png" alt="Appointment" style="width:45px; height:45px;"><br>
                                Appointment
                            </a>
                        </li>
                        <li class="nav-item text-center">
                            <a class="nav-link" href="logs.php" style="color:black;">
                                <img src="../assets/icons/log3.png" alt="Log" style="width:45px; height:45px;"><br>
                                Log
                            </a>
                        </li>
                    </ul>
                </nav>
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
