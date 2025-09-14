<?php
// KAYA booking actions (shared by Admin pages)
session_start();
require_once __DIR__ . '/vendor/inc/config.php';
require_once __DIR__ . '/vendor/inc/checklogin.php';
check_login();

// Always use UTF8MB4 to avoid collation issues
$mysqli->set_charset('utf8mb4');
@$mysqli->query("SET collation_connection='utf8mb4_unicode_ci'");

// ---------- helpers ----------
function table_exists(mysqli $db, string $t): bool {
  $t = $db->real_escape_string($t);
  $r = $db->query("SHOW TABLES LIKE '{$t}'");
  return $r && $r->num_rows > 0;
}
function is_admin_user(): bool {
  return (function_exists('is_admin') && is_admin())
      || isset($_SESSION['a_id']);
}
function actor_id(): int {
  // Prefer accounts.id if present
  if (isset($_SESSION['a_id'])) return (int)$_SESSION['a_id'];
  if (isset($_SESSION['u_id'])) return (int)$_SESSION['u_id'];
  return 0;
}
function log_event(mysqli $db, int $bookingId, ?int $actorId, string $actorRole, string $eventType, array $details = []): void {
  if (!table_exists($db,'booking_events')) return;
  $sql = "INSERT INTO booking_events(booking_id,actor_id,actor_role,event_type,details)
          VALUES(?,?,?,?,?)";
  if ($st = $db->prepare($sql)) {
    $json = json_encode($details, JSON_UNESCAPED_UNICODE);
    $st->bind_param('iisss', $bookingId, $actorId, $actorRole, $eventType, $json);
    $st->execute();
    $st->close();
  }
}
function back_to(string $fallback = 'admin-trip-appointment.php'){
  $to = $_SERVER['HTTP_REFERER'] ?? $fallback;
  header('Location: ' . $to);
  exit;
}

// ---------- route ----------
$action = $_POST['action'] ?? '';
$bid    = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if (!$action || !$bid) back_to();

// Which schema do we have?
$hasNew = table_exists($mysqli,'bookings');
$actorRole = is_admin_user() ? 'admin' : 'driver';
$actorId   = actor_id();

// =============== ADMIN ACTIONS ===============
if ($action === 'admin_cancel' && is_admin_user()) {
  if ($hasNew) {
    if ($s = $mysqli->prepare("UPDATE bookings SET status='cancelled', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
      log_event($mysqli,$bid,$actorId,'admin','cancel');
    }
  } else {
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Cancel' WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to('admin-manage-booking.php');
}

if ($action === 'admin_approve' && is_admin_user()) {
  if ($hasNew) {
    // Put it in an approved / driver-ready state
    if ($s = $mysqli->prepare("UPDATE bookings SET status='accepted', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
      log_event($mysqli,$bid,$actorId,'admin','assign');
    }
  } else {
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Approved' WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to();
}

if ($action === 'admin_complete' && is_admin_user()) {
  if ($hasNew) {
    if ($s = $mysqli->prepare("UPDATE bookings SET status='completed', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
      log_event($mysqli,$bid,$actorId,'admin','complete_trip');
    }
  } else {
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Completed' WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to('admin-view-booking.php');
}

/* --------- THIS IS THE ONE YOU NEED --------- */
if ($action === 'admin_restore' && is_admin_user()) {
  if ($hasNew) {
    // Move back to queue
    if ($s = $mysqli->prepare("UPDATE bookings SET status='awaiting_driver', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
      log_event($mysqli,$bid,$actorId,'admin','restore');
    }
  } else {
    // Legacy
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Pending' WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to('admin-trip-appointment.php');
}

if ($action === 'admin_delete' && is_admin_user()) {
  if ($hasNew) {
    // booking_events/booking_offers/booking_runs have ON DELETE CASCADE in the new schema
    if ($s = $mysqli->prepare("DELETE FROM bookings WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  } else {
    if ($s = $mysqli->prepare("DELETE FROM tms_user WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to('admin-manage-booking.php');
}

// =============== DRIVER ACTIONS ===============
if ($action === 'driver_accept') {
  if ($hasNew) {
    if ($s = $mysqli->prepare("UPDATE bookings SET status='accepted', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
      log_event($mysqli,$bid,$actorId,'driver','accept');
    }
  } else {
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Approved' WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to();
}

if ($action === 'driver_decline') {
  $reason = trim((string)($_POST['reason'] ?? ''));
  if ($hasNew) {
    if ($s = $mysqli->prepare("UPDATE bookings SET status='rejected', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
      log_event($mysqli,$bid,$actorId,'driver','reject',['reason'=>$reason]);
    }
  } else {
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Cancel' WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to('admin-manage-booking.php');
}

// =============== TRIP START / END ===============
if ($action === 'trip_start') {
  if ($hasNew) {
    // Start ride -> in_progress, record pickup_button_at
    if ($s = $mysqli->prepare("UPDATE bookings SET status='in_progress', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
    // Ensure run row exists
    if (table_exists($mysqli,'booking_runs')) {
      $mysqli->query("INSERT IGNORE INTO booking_runs(booking_id,pickup_button_at) VALUES ($bid,NOW())");
      $mysqli->query("UPDATE booking_runs SET pickup_button_at=COALESCE(pickup_button_at,NOW()) WHERE booking_id=$bid");
    }
    log_event($mysqli,$bid,$actorId,$actorRole,'start_trip');
  } else {
    // Legacy doesn’t track start/end; just mark Approved (already handled)
  }
  back_to();
}

if ($action === 'trip_end') {
  if ($hasNew) {
    if ($s = $mysqli->prepare("UPDATE bookings SET status='completed', updated_at=NOW() WHERE id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
    if (table_exists($mysqli,'booking_runs')) {
      $mysqli->query("UPDATE booking_runs SET dropoff_button_at=NOW() WHERE booking_id=$bid");
    }
    log_event($mysqli,$bid,$actorId,$actorRole,'complete_trip');
  } else {
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Completed' WHERE u_id=?")) {
      $s->bind_param('i',$bid); $s->execute(); $s->close();
    }
  }
  back_to('admin-view-booking.php');
}

// Default fallback
back_to();
