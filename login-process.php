<?php
session_start();
include('admin/vendor/inc/config.php');

/* -------- helpers -------- */
function verify_any($plain, $stored) {
  if ($stored === null) return ['ok'=>false,'method'=>null];
  $stored = (string)$stored;

  // Modern (password_hash: bcrypt/argon/…)
  $info = password_get_info($stored);
  if (!empty($info['algo']) && $info['algo'] !== 0) {
    return ['ok'=>password_verify($plain, $stored), 'method'=>'hash'];
  }

  // Legacy MD5
  if (ctype_xdigit($stored) && strlen($stored) === 32) {
    return ['ok'=> (md5($plain) === strtolower($stored)), 'method'=>'md5'];
  }

  // Fallback: plain text (discouraged, but handled)
  return ['ok'=> hash_equals($stored, $plain), 'method'=>'plain'];
}

function upgrade_password_if_needed(mysqli $db, $table, $idField, $idVal, $plain, $method) {
  if ($method === 'hash' || !$idVal) return; // already modern or no id
  $new = password_hash($plain, PASSWORD_DEFAULT);
  $pwdCol = ($table==='tms_admin') ? 'a_pwd' : 'u_pwd';
  if ($stmt = $db->prepare("UPDATE {$table} SET {$pwdCol}=? WHERE {$idField}=? LIMIT 1")) {
    $stmt->bind_param('si', $new, $idVal);
    $stmt->execute();
    $stmt->close();
  }
}

/* -------- main -------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  $_SESSION['error'] = "Invalid request.";
  header("Location: index.php"); exit();
}

$email     = trim($_POST['email'] ?? '');
$password  = trim((string)($_POST['password'] ?? ''));   // <-- trim here
$user_type = trim($_POST['user_type'] ?? '');

if ($email === '' || $password === '' || ($user_type !== 'admin' && $user_type !== 'user')) {
  $_SESSION['error'] = "All fields are required.";
  header("Location: index.php"); exit();
}

try {
  if ($user_type === 'admin') {
    // ADMIN
    $stmt = $mysqli->prepare("SELECT a_id, a_name, a_email, a_pwd FROM tms_admin WHERE a_email=? LIMIT 1");
    if (!$stmt) throw new Exception("Database error: ".$mysqli->error);
    $stmt->bind_param('s',$email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
      $stmt->bind_result($a_id,$a_name,$a_email,$a_pwd);
      $stmt->fetch();

      $check = verify_any($password,$a_pwd);
      if ($check['ok']) {
        upgrade_password_if_needed($mysqli,'tms_admin','a_id',$a_id,$password,$check['method']);
        $_SESSION['a_id']      = (int)$a_id;
        $_SESSION['a_name']    = $a_name;
        $_SESSION['a_email']   = $a_email;
        $_SESSION['user_type'] = 'admin';
        header("Location: admin/admin-dashboard.php"); exit();
      }
      throw new Exception("Invalid password");
    }
    throw new Exception("Admin not found");

  } else {
    // USER
    $stmt = $mysqli->prepare("
      SELECT u_id,
             CONCAT(COALESCE(u_fname,''),' ',COALESCE(u_lname,'')) AS u_name,
             u_email, u_pwd, COALESCE(u_category,'User') AS u_category
      FROM tms_user
      WHERE u_email=?
      ORDER BY u_id DESC
      LIMIT 1
    ");
    if (!$stmt) throw new Exception("Database error: ".$mysqli->error);
    $stmt->bind_param('s',$email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
      $stmt->bind_result($u_id,$u_name,$u_email,$u_pwd,$u_category);
      $stmt->fetch();

      $check = verify_any($password,$u_pwd);
      if ($check['ok']) {
        upgrade_password_if_needed($mysqli,'tms_user','u_id',$u_id,$password,$check['method']);
        $_SESSION['u_id']       = (int)$u_id;
        $_SESSION['u_name']     = $u_name;
        $_SESSION['u_email']    = $u_email;
        $_SESSION['user_type']  = 'user';
        $_SESSION['u_category'] = $u_category;
        header("Location: usr/user-dashboard.php"); exit();
      }
      throw new Exception("Invalid password");
    }
    throw new Exception("User not found");
  }

} catch (Exception $e) {
  $_SESSION['error'] = $e->getMessage();
  header("Location: index.php"); exit();
}
