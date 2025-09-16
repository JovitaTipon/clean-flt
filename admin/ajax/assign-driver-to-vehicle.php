<?php
session_start();
include('../vendor/inc/config.php');
include('../vendor/inc/checklogin.php');
check_login();
require_admin();


$driver_id = (int)($_POST['driver_id'] ?? 0);
$vehicle_id = (int)($_POST['vehicle_id'] ?? 0);


header('Content-Type: application/json');
if (!$driver_id || !$vehicle_id) {
echo json_encode(['ok'=>false,'msg'=>'Missing ids']);
exit;
}


$mysqli->begin_transaction();
try {
// 1) Clear this driver from any other vehicle (unique constraint safety)
if ($s = $mysqli->prepare("UPDATE tms_vehicle SET driver_user_id = NULL WHERE driver_user_id = ? AND v_id <> ?")) {
$s->bind_param('ii', $driver_id, $vehicle_id);
$s->execute();
$s->close();
}
// 2) Assign driver to the chosen vehicle
if ($s = $mysqli->prepare("UPDATE tms_vehicle SET driver_user_id = ? WHERE v_id = ?")) {
$s->bind_param('ii', $driver_id, $vehicle_id);
$ok = $s->execute();
$s->close();
if (!$ok) throw new Exception('Update failed');
}
$mysqli->commit();
echo json_encode(['ok'=>true]);
} catch (Throwable $e) {
$mysqli->rollback();
echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}