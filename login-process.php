<?php
session_start();
include('admin/vendor/inc/config.php');

/* verify against bcrypt/argon/md5/plain */
function verify_any($plain, $stored){
  if($stored===null) return false;
  $s=(string)$stored;
  if(preg_match('/^\$2[ayb]\$|\$argon2(id|i)\$/',$s)) return password_verify($plain,$s);
  if(ctype_xdigit($s) && strlen($s)===32) return md5($plain)===strtolower($s);
  return hash_equals($s,$plain);
}

if($_SERVER['REQUEST_METHOD']!=='POST'){ $_SESSION['error']='Invalid request.'; header('Location: index.php'); exit; }

$email=trim($_POST['email']??'');
$pass =(string)($_POST['password']??'');
$type =trim($_POST['user_type']??'');

if($email===''||$pass===''||!in_array($type,['admin','user'])){ $_SESSION['error']='All fields are required.'; header('Location:index.php'); exit; }

/* ---- try new accounts first ---- */
if($stmt=$mysqli->prepare("SELECT id,role,name,email,password_hash FROM accounts WHERE email=? AND is_active=1 LIMIT 1")){
  $stmt->bind_param('s',$email); $stmt->execute(); $stmt->bind_result($id,$role,$name,$em,$hash);
  if($stmt->fetch()){
    $stmt->close();
    $wantRole = ($type==='admin') ? 'admin' : 'driver';
    if($role===$wantRole && verify_any($pass,$hash)){
      if($role==='admin'){
        $_SESSION['a_id']=$id; $_SESSION['a_name']=$name; $_SESSION['a_email']=$em; $_SESSION['user_type']='admin';
        header('Location: admin/admin-dashboard.php'); exit;
      }else{
        $_SESSION['u_id']=$id; $_SESSION['u_name']=$name; $_SESSION['u_email']=$em; $_SESSION['user_type']='user';
        header('Location: usr/user-dashboard.php'); exit;
      }
    }
  } else { $stmt->close(); }
}

/* ---- legacy fallback (keeps old accounts working) ---- */
if($type==='admin'){
  $s=$mysqli->prepare("SELECT a_id,a_name,a_email,a_pwd FROM tms_admin WHERE a_email=? LIMIT 1");
  if($s){ $s->bind_param('s',$email); $s->execute(); $s->bind_result($a,$n,$e,$p);
    if($s->fetch() && verify_any($pass,$p)){ $s->close();
      $_SESSION['a_id']=$a; $_SESSION['a_name']=$n; $_SESSION['a_email']=$e; $_SESSION['user_type']='admin';
      header('Location: admin/admin-dashboard.php'); exit;
    } $s->close();
  }
}else{
  $s=$mysqli->prepare("SELECT u_id,CONCAT(COALESCE(u_fname,''),' ',COALESCE(u_lname,'')) AS n,u_email,u_pwd FROM tms_user WHERE u_email=? ORDER BY u_id DESC LIMIT 1");
  if($s){ $s->bind_param('s',$email); $s->execute(); $s->bind_result($u,$n,$e,$p);
    if($s->fetch() && verify_any($pass,$p)){ $s->close();
      $_SESSION['u_id']=$u; $_SESSION['u_name']=$n; $_SESSION['u_email']=$e; $_SESSION['user_type']='user';
      header('Location: usr/user-dashboard.php'); exit;
    } $s->close();
  }
}

$_SESSION['error']='Invalid email or password.';
header('Location: index.php'); exit;
