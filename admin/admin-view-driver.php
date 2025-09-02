<?php
// ========== KAYA · Driver Details ==========
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = require_admin();

$did = isset($_GET['d_u_id']) ? (int)$_GET['d_u_id'] : 0;
if ($did <= 0) { header('Location: admin-manage-driver.php'); exit; }

/* DELETE (from toolbar) */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_driver']) && (int)$_POST['d_u_id'] === $did) {
  if ($s = $mysqli->prepare("DELETE FROM tms_user_add_driver WHERE d_u_id=? LIMIT 1")) {
    $s->bind_param('i',$did); $ok = $s->execute(); $s->close();
  }
  header('Location: admin-manage-driver.php'); exit;
}

/* FETCH record */
$drv = null;
if ($s = $mysqli->prepare("SELECT d_u_id,u_fname,u_lname,u_phone,u_addr,u_car_type,u_car_regno,u_car_book_status,u_email 
                           FROM tms_user_add_driver WHERE d_u_id=? LIMIT 1")) {
  $s->bind_param('i',$did); $s->execute(); $r=$s->get_result(); $drv=$r->fetch_assoc(); $s->close();
}
if (!$drv) { header('Location: admin-manage-driver.php'); exit; }

/* small helpers */
function status_badge($raw){
  $txt='Not Available'; $class='badge badge-secondary';
  if (stripos($raw,'avail')!==false){ $txt='Available'; $class='badge badge-success'; }
  elseif (stripos($raw,'trip')!==false || stripos($raw,'book')!==false || stripos($raw,'service')!==false){ $txt='On Trip'; $class='badge badge-primary'; }
  elseif ($raw===''){ $txt='Available'; $class='badge badge-success'; }
  return '<span class="'.$class.' px-2 py-1">'.$txt.'</span>';
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
  .kaya-page-title{font-weight:800;font-size:2rem;line-height:1.1;color:#000047;margin:0 0 1rem}
  .kaya-card{background:#fff;border-radius:1rem;border:1px solid #e5e7eb;box-shadow:0 8px 24px rgba(0,0,0,.06);padding:1rem}
  .detail-row{display:flex;align-items:center;padding:.75rem 0;border-top:1px solid #f1f5f9}
  .detail-row:first-child{border-top:0}
  .detail-row .label{width:240px;color:#6b7280;font-weight:600}
  .kaya-toolbar .kaya-actions{display:flex;gap:.5rem;flex-wrap:wrap}
  .kaya-toolbar .btn{padding:.5rem .9rem;border-radius:.5rem;font-weight:600}
  .btn-kaya-primary{background:#0A0F2C;border:1px solid #0A0F2C;color:#fff}
  .btn-kaya-primary:hover{background:#0c1438;border-color:#0c1438}
  .btn-kaya-danger-outline{background:#fff;border:1px solid #dc2626;color:#dc2626}
  .btn-kaya-danger-outline:hover{background:#fee2e2}
</style>
<body id="page-top">
<?php include('vendor/inc/nav.php'); ?>
<div id="wrapper">
  <?php include('vendor/inc/sidebar.php'); ?>

  <div id="content-wrapper">
    <div class="container-fluid">

      <h1 class="kaya-page-title">Driver Details</h1>

      <!-- Toolbar (right actions) -->
      <div class="kaya-toolbar d-flex align-items-center mb-3">
        <div class="ml-auto kaya-actions">
          <a href="admin-manage-driver.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left mr-1"></i>Back</a>
          <a href="driver-edit.php?d_u_id=<?= (int)$drv['d_u_id'] ?>" class="btn btn-kaya-primary"><i class="fas fa-pen mr-1"></i>Edit</a>
          <form method="post" class="d-inline" onsubmit="return confirm('Delete this driver?');" style="margin:0;">
            <input type="hidden" name="d_u_id" value="<?= (int)$drv['d_u_id'] ?>">
            <button name="delete_driver" class="btn btn-kaya-danger-outline"><i class="fas fa-trash mr-1"></i>Delete</button>
          </form>
        </div>
      </div>

      <div class="kaya-card">
        <div class="mb-2 font-weight-bold" style="font-size:1.125rem;">
          <?= htmlspecialchars(trim(($drv['u_fname']??'').' '.($drv['u_lname']??''))) ?>
          <span class="ml-2 align-middle"><?= status_badge($drv['u_car_book_status'] ?? '') ?></span>
        </div>

        <div class="detail-row">
          <div class="label">Email</div>
          <div><?= htmlspecialchars($drv['u_email'] ?: '—') ?></div>
        </div>
        <div class="detail-row">
          <div class="label">Contact #</div>
          <div><?= htmlspecialchars($drv['u_phone'] ?: '—') ?></div>
        </div>
        <div class="detail-row">
          <div class="label">Address</div>
          <div><?= htmlspecialchars($drv['u_addr'] ?: '—') ?></div>
        </div>
        <div class="detail-row">
          <div class="label">Vehicle / Type</div>
          <div><?= htmlspecialchars($drv['u_car_type'] ?: '—') ?></div>
        </div>
        <div class="detail-row">
          <div class="label">License #</div>
          <div><?= htmlspecialchars($drv['u_car_regno'] ?: '—') ?></div>
        </div>
        <div class="detail-row">
          <div class="label">Status</div>
          <div><?= status_badge($drv['u_car_book_status'] ?? '') ?></div>
        </div>
      </div>

    </div>
    <?php include('vendor/inc/footer.php'); ?>
  </div>
</div>

<!-- vendor js -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>


</body>
</html>
