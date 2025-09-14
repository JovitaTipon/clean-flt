<?php
session_start(); include('../admin/vendor/inc/config.php'); include('../admin/vendor/inc/checklogin.php'); check_login();
$driverId = (int)($_SESSION['u_id'] ?? 0);
$id = (int)($_POST['booking_id'] ?? 0);
$do = $_POST['do'] ?? '';
if(!$id||!in_array($do,['accept','reject','start','complete'])) exit('bad');

switch($do){
  case 'accept':
    $s=$mysqli->prepare("UPDATE bookings SET status='accepted', updated_at=NOW()
                         WHERE id=? AND driver_id=? AND status IN ('awaiting_driver','pending')");
    $s->bind_param('ii',$id,$driverId); $s->execute(); $s->close();
    $e=$mysqli->prepare("INSERT INTO booking_events(booking_id,actor_id,actor_role,event_type) VALUES(?,?,'driver','accept')");
    $e->bind_param('ii',$id,$driverId); $e->execute(); $e->close();
    break;
  case 'reject':
    $s=$mysqli->prepare("UPDATE bookings SET status='rejected', updated_at=NOW()
                         WHERE id=? AND driver_id=? AND status IN ('awaiting_driver','pending','accepted')");
    $s->bind_param('ii',$id,$driverId); $s->execute(); $s->close();
    $e=$mysqli->prepare("INSERT INTO booking_events(booking_id,actor_id,actor_role,event_type) VALUES(?,?,'driver','reject')");
    $e->bind_param('ii',$id,$driverId); $e->execute(); $e->close();
    break;
  case 'start':
    $s=$mysqli->prepare("UPDATE bookings SET status='in_progress', updated_at=NOW() WHERE id=? AND driver_id=? AND status='accepted'");
    $s->bind_param('ii',$id,$driverId); $s->execute(); $s->close();
    $r=$mysqli->prepare("INSERT INTO booking_runs(booking_id,driver_id,pickup_button_at) VALUES(?,?,NOW())
                         ON DUPLICATE KEY UPDATE pickup_button_at=VALUES(pickup_button_at)");
    $r->bind_param('ii',$id,$driverId); $r->execute(); $r->close();
    break;
  case 'complete':
    $s=$mysqli->prepare("UPDATE bookings SET status='completed', updated_at=NOW() WHERE id=? AND driver_id=? AND status='in_progress'");
    $s->bind_param('ii',$id,$driverId); $s->execute(); $s->close();
    $r=$mysqli->prepare("UPDATE booking_runs SET dropoff_button_at=NOW()
                         WHERE booking_id=? AND driver_id=?");
    $r->bind_param('ii',$id,$driverId); $r->execute(); $r->close();
    break;
}
header('Location: driver-trips.php');

