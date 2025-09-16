<?php
session_start();
include('../vendor/inc/config.php');
include('../vendor/inc/checklogin.php');
check_login();
require_admin();


header('Content-Type: application/json');
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;


$rows = [];
$sql = "SELECT v_id, v_reg_no, v_name, v_category,
(driver_user_id = ?) AS is_current
FROM tms_vehicle
WHERE driver_user_id IS NULL OR driver_user_id = ?
ORDER BY v_id DESC";
if ($s = $mysqli->prepare($sql)) {
$s->bind_param('ii', $driver_id, $driver_id);
$s->execute();
$r = $s->get_result();
while($row = $r->fetch_assoc()) { $rows[] = $row; }
$s->close();
}


echo json_encode($rows);