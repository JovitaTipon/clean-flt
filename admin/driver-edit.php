<?php
// ========== KAYA · Edit Driver ==========
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = require_admin();

$did = isset($_GET['d_u_id']) ? (int)$_GET['d_u_id'] : 0;
if ($did <= 0) { header('Location: admin-manage-driver.php'); exit; }

/* SAVE */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save_driver'])) {
  $fname  = trim($_POST['u_fname']);
  $lname  = trim($_POST['u_lname']);
  $phone  = trim($_POST['u_phone']);
  $addr   = trim($_POST['u_addr']);
  $ctype  = trim($_POST['u_car_type']);
  $lic    = trim($_POST['u_car_regno']);
  $status = trim($_POST['u_car_book_status']);
  $email  = trim($_POST['u_email']);

  $sql = "UPDATE tms_user_add_driver
          SET u_fname=?, u_lname=?, u_phone=?, u_addr=?, u_car_type=?, u_car_regno=?, u_car_book_status=?, u_email=?
          WHERE d_u_id=?";
  if ($s = $mysqli->prepare($sql)) {
    $s->bind_param('ssssssssi',$fname,$lname,$phone,$addr,$ctype,$lic,$status,$email,$did);
    $ok = $s->execute(); $s->close();
    if ($ok) { header("Location: admin-view-driver.php?d_u_id=".$did); exit; }
    $err = "Update failed. Please try again.";
  } else { $err = "DB error while preparing update."; }
}

/* FETCH current */
$drv = null;
if ($s = $mysqli->prepare("SELECT d_u_id,u_fname,u_lname,u_phone,u_addr,u_car_type,u_car_regno,u_car_book_status,u_email 
                           FROM tms_user_add_driver WHERE d_u_id=? LIMIT 1")) {
  $s->bind_param('i',$did); $s->execute(); $r=$s->get_result(); $drv=$r->fetch_assoc(); $s->close();
}
if (!$drv) { header('Location: admin-manage-driver.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
  .kaya-page-title{font-weight:800;font-size:2rem;line-height:1.1;color:#000047;margin:0 0 1rem}
  .kaya-card{background:#fff;border-radius:1rem;border:1px solid #e5e7eb;box-shadow:0 8px 24px rgba(0,0,0,.06);padding:1rem}
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

      <h1 class="kaya-page-title">Edit Driver</h1>

      <div class="kaya-toolbar d-flex align-items-center mb-3">
        <div class="ml-auto kaya-actions">
          <a href="admin-view-driver.php?d_u_id=<?= (int)$drv['d_u_id'] ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
          </a>
          <a href="admin-manage-driver.php" class="btn btn-kaya-danger-outline">Drivers</a>
        </div>
      </div>

      <?php if(!empty($err)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($err) ?>
          <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
        </div>
      <?php endif; ?>

      <div class="kaya-card">
        <form method="post">
          <div class="form-row">
            <div class="form-group col-md-6">
              <label>First Name</label>
              <input type="text" name="u_fname" class="form-control" required value="<?= htmlspecialchars($drv['u_fname']) ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Last Name</label>
              <input type="text" name="u_lname" class="form-control" value="<?= htmlspecialchars($drv['u_lname']) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Contact #</label>
              <input type="tel" name="u_phone" class="form-control" maxlength="32" value="<?= htmlspecialchars($drv['u_phone']) ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Email</label>
              <input type="email" name="u_email" class="form-control" value="<?= htmlspecialchars($drv['u_email']) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>Address</label>
              <input type="text" name="u_addr" class="form-control" value="<?= htmlspecialchars($drv['u_addr']) ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Vehicle / Type</label>
              <select name="u_car_type" class="form-control">
                <?php
                  $types = ['Bus','Sedan','SUV','Van'];
                  foreach ($types as $t) {
                    $sel = ($drv['u_car_type']===$t)?'selected':'';
                    echo "<option $sel>".htmlspecialchars($t)."</option>";
                  }
                ?>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label>License #</label>
              <input type="text" name="u_car_regno" class="form-control" value="<?= htmlspecialchars($drv['u_car_regno']) ?>">
            </div>
            <div class="form-group col-md-6">
              <label>Status</label>
              <select name="u_car_book_status" class="form-control">
                <?php
                  $statuses = ['Available','On Trip','Not Available'];
                  foreach ($statuses as $s) {
                    $sel = (stripos($drv['u_car_book_status'],$s)!==false || $drv['u_car_book_status']===$s) ? 'selected':'';
                    echo "<option $sel>".htmlspecialchars($s)."</option>";
                  }
                ?>
              </select>
            </div>
          </div>

          <div class="mt-3">
            <button class="btn btn-kaya-primary" name="save_driver" type="submit">Save Changes</button>
            <a class="btn btn-outline-secondary" href="admin-view-driver.php?d_u_id=<?= (int)$drv['d_u_id'] ?>">Cancel</a>
          </div>
        </form>
      </div>

    </div>
    <?php include('vendor/inc/footer.php'); ?>
  </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>

</body>
</html>
