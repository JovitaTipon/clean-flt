<?php
session_start(); include('../admin/vendor/inc/config.php'); include('../admin/vendor/inc/checklogin.php'); check_login();
$driverId = (int)($_SESSION['u_id'] ?? 0);
$rows = [];
$stmt = $mysqli->prepare("SELECT id, pickup_point, dropoff_point, pax, scheduled_start_at, status
                          FROM bookings
                          WHERE driver_id=? AND status IN ('awaiting_driver','accepted','in_progress')
                          ORDER BY scheduled_start_at DESC");
$stmt->bind_param('i',$driverId); $stmt->execute(); $res=$stmt->get_result();
while($r=$res->fetch_assoc()) $rows[]=$r;
$stmt->close();
?>
<!-- render table + buttons posting to driver-action.php -->
