<?php // booking_actions.php
session_start(); require_once 'admin/vendor/inc/config.php';

function log_event($mysqli,$booking_id,$actor_type,$actor_id,$action,$extra=[]){
  $stmt=$mysqli->prepare(
    "INSERT INTO booking_events(booking_id,actor_type,actor_id,action,notes,created_at)
     VALUES (?,?,?,?,?,NOW())");
  $notes = $extra['notes'] ?? null;
  $stmt->bind_param('isiss',$booking_id,$actor_type,$actor_id,$action,$notes);
  $stmt->execute();
}

if ($_SERVER['REQUEST_METHOD']!=='POST') { http_response_code(405); exit; }

$action      = $_POST['action'] ?? '';
$booking_id  = (int)($_POST['booking_id'] ?? 0);
$actor_type  = $_POST['actor_type'] ?? 'admin'; // 'driver' on driver pages
$actor_id    = (int)($_POST['actor_id'] ?? 0);
$reason      = trim($_POST['reason'] ?? '');

switch ($action) {
  case 'assign':
    $driver_id = (int)$_POST['driver_id'];
    $stmt=$mysqli->prepare(
      "UPDATE bookings SET driver_id=?, driver_response='awaiting', status='pending' WHERE booking_id=?");
    $stmt->bind_param('ii',$driver_id,$booking_id); $stmt->execute();
    log_event($mysqli,$booking_id,$actor_type,$actor_id,'assign_driver');
    break;

  case 'accept':
    $stmt=$mysqli->prepare(
      "UPDATE bookings SET driver_response='accepted', status='accepted'
       WHERE booking_id=? AND driver_id=?");
    $stmt->bind_param('ii',$booking_id,$actor_id); $stmt->execute();
    log_event($mysqli,$booking_id,'driver',$actor_id,'accept');
    break;

  case 'decline':
    $stmt=$mysqli->prepare(
      "UPDATE bookings SET driver_response='declined', status='pending'
       WHERE booking_id=? AND driver_id=?");
    $stmt->bind_param('ii',$booking_id,$actor_id); $stmt->execute();
    log_event($mysqli,$booking_id,'driver',$actor_id,'decline',['notes'=>$reason]);
    break;

  case 'admin_verdict': // approve or reject a driver's decline
    $verdict = ($_POST['verdict']==='approve') ? 'approved' : 'rejected';
    if ($verdict==='approved') {
      $stmt=$mysqli->prepare(
        "UPDATE bookings SET status='pending', driver_id=NULL, driver_response=NULL WHERE booking_id=?");
    } else {
      $stmt=$mysqli->prepare("UPDATE bookings SET status='accepted' WHERE booking_id=?");
    }
    $stmt->bind_param('i',$booking_id); $stmt->execute();
    log_event($mysqli,$booking_id,'admin',$actor_id,'admin_verdict',['notes'=>$verdict]);
    break;

  case 'start_trip':
    $stmt=$mysqli->prepare(
      "UPDATE bookings SET started_at=NOW(), status='in_progress' WHERE booking_id=? AND driver_id=?");
    $stmt->bind_param('ii',$booking_id,$actor_id); $stmt->execute();
    $mysqli->query("INSERT IGNORE INTO trip_metrics(booking_id,started_at) VALUES ($booking_id,NOW())");
    log_event($mysqli,$booking_id,'driver',$actor_id,'start_trip');
    break;

  case 'finish_trip':
    $stmt=$mysqli->prepare(
      "UPDATE bookings SET completed_at=NOW(), status='completed' WHERE booking_id=? AND driver_id=?");
    $stmt->bind_param('ii',$booking_id,$actor_id); $stmt->execute();
    $mysqli->query("UPDATE trip_metrics SET completed_at=NOW() WHERE booking_id=$booking_id");
    log_event($mysqli,$booking_id,'driver',$actor_id,'finish_trip');
    break;

  case 'cancel':
    $stmt=$mysqli->prepare("UPDATE bookings SET status='cancelled' WHERE booking_id=?");
    $stmt->bind_param('i',$booking_id); $stmt->execute();
    log_event($mysqli,$booking_id,$actor_type,$actor_id,'cancel',['notes'=>$reason]);
    break;

  case 'restore':
    $stmt=$mysqli->prepare("UPDATE bookings SET status='pending' WHERE booking_id=? AND status='cancelled'");
    $stmt->bind_param('i',$booking_id); $stmt->execute();
    log_event($mysqli,$booking_id,$actor_type,$actor_id,'restore_cancelled');
    break;

  case 'hard_delete':
    // Optional: you can enforce only for cancelled
    $stmt=$mysqli->prepare("DELETE FROM bookings WHERE booking_id=?");
    $stmt->bind_param('i',$booking_id); $stmt->execute();
    log_event($mysqli,$booking_id,$actor_type,$actor_id,'delete_cancelled');
    break;
}
header('Location: ' . ($_POST['redirect'] ?? 'admin-trip-appointment.php'));
