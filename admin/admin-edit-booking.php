<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
if (!function_exists('is_admin') || !is_admin()) { header('Location: admin-trip-appointment.php'); exit; }
$aid = (int)($_SESSION['a_id'] ?? 0);

$u_id = isset($_GET['u_id']) ? (int)$_GET['u_id'] : 0;
if (!$u_id) { header('Location: admin-trip-appointment.php'); exit; }

if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['save'])) {
  $date  = trim($_POST['u_car_date']);
  $time  = trim($_POST['u_car_time']);
  $fname = trim($_POST['u_fname']);
  $lname = trim($_POST['u_lname']);
  $pax   = trim($_POST['u_car_pax']);
  $pick  = trim($_POST['u_car_pickup']);
  $dest  = trim($_POST['u_car_destination']);
  $reg   = trim($_POST['u_car_regno']);
  $type  = trim($_POST['u_car_type']);
  $drv   = trim($_POST['u_car_driver']);
  $status= trim($_POST['u_car_book_status']);

  $sql = "UPDATE tms_user
          SET u_car_date=?, u_car_time=?, u_fname=?, u_lname=?, u_car_pax=?,
              u_car_pickup=?, u_car_destination=?, u_car_regno=?, u_car_type=?,
              u_car_driver=?, u_car_book_status=?
          WHERE u_id=?";
  if ($s=$mysqli->prepare($sql)) {
    $s->bind_param('ssssissssssi',$date,$time,$fname,$lname,$pax,$pick,$dest,$reg,$type,$drv,$status,$u_id);
    $ok = $s->execute(); $s->close();
    // audit
    if ($ok) {
      // lightweight re-use of helper from edit page
      $mk = $mysqli->prepare("CREATE TABLE IF NOT EXISTS tms_audit_log (id INT AUTO_INCREMENT PRIMARY KEY, actor_type ENUM('admin','driver') NOT NULL, actor_id INT NOT NULL, action VARCHAR(50) NOT NULL, booking_u_id INT NOT NULL, details JSON NULL, created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
      @$mk->execute(); @$mk->close();
      $d = json_encode(['status'=>$status], JSON_UNESCAPED_UNICODE);
      $a = $mysqli->prepare("INSERT INTO tms_audit_log(actor_type,actor_id,action,booking_u_id,details) VALUES ('admin',?,?,?,?)");
      if ($a){ $act='edit'; $a->bind_param('sis',$aid,$u_id,$d); $a->execute(); $a->close(); }
    }
    header('Location: admin-trip-appointment.php?updated=1'); exit;
  }
}

// load current
$row = null;
if ($s=$mysqli->prepare("SELECT * FROM tms_user WHERE u_id=?")) {
  $s->bind_param('i',$u_id);
  $s->execute();
  $res = $s->get_result();
  $row = $res->fetch_assoc();
  $s->close();
}
if (!$row) { header('Location: admin-trip-appointment.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<body id="page-top">
<?php include('vendor/inc/nav.php'); ?>
<div id="wrapper">
  <?php include('vendor/inc/sidebar.php'); ?>
  <div id="content-wrapper">
    <div class="container-fluid">
      <h1 class="kaya-page-title">Edit Booking</h1>
      <div class="kaya-card p-3">
        <form method="post">
          <div class="form-row">
            <div class="form-group col-md-3">
              <label>Date</label>
              <input type="date" class="form-control" name="u_car_date" value="<?= htmlspecialchars($row['u_car_date'] ?? '') ?>">
            </div>
            <div class="form-group col-md-3">
              <label>Time</label>
              <input type="time" class="form-control" name="u_car_time" value="<?= htmlspecialchars($row['u_car_time'] ?? '') ?>">
            </div>
            <div class="form-group col-md-3">
              <label>First name</label>
              <input type="text" class="form-control" name="u_fname" value="<?= htmlspecialchars($row['u_fname'] ?? '') ?>">
            </div>
            <div class="form-group col-md-3">
              <label>Last name</label>
              <input type="text" class="form-control" name="u_lname" value="<?= htmlspecialchars($row['u_lname'] ?? '') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-2"><label>Pax</label>
              <input type="number" class="form-control" name="u_car_pax" value="<?= htmlspecialchars($row['u_car_pax'] ?? '') ?>">
            </div>
            <div class="form-group col-md-5"><label>Pickup</label>
              <input type="text" class="form-control" name="u_car_pickup" value="<?= htmlspecialchars($row['u_car_pickup'] ?? '') ?>">
            </div>
            <div class="form-group col-md-5"><label>Destination</label>
              <input type="text" class="form-control" name="u_car_destination" value="<?= htmlspecialchars($row['u_car_destination'] ?? '') ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-3"><label>Reg No.</label>
              <input type="text" class="form-control" name="u_car_regno" value="<?= htmlspecialchars($row['u_car_regno'] ?? '') ?>">
            </div>
            <div class="form-group col-md-3"><label>Vehicle Type</label>
              <input type="text" class="form-control" name="u_car_type" value="<?= htmlspecialchars($row['u_car_type'] ?? '') ?>">
            </div>
            <div class="form-group col-md-3"><label>Driver</label>
              <input type="text" class="form-control" name="u_car_driver" value="<?= htmlspecialchars($row['u_car_driver'] ?? '') ?>">
            </div>
            <div class="form-group col-md-3"><label>Status</label>
              <select name="u_car_book_status" class="form-control">
                <?php
                  $opts=['Pending','Approved','Completed','Cancel','Maintenance','In Active','Available'];
                  foreach($opts as $opt){
                    $sel = ($row['u_car_book_status']===$opt)?'selected':'';
                    echo "<option $sel>".htmlspecialchars($opt)."</option>";
                  }
                ?>
              </select>
            </div>
          </div>

          <div class="text-right">
            <button class="btn btn-kaya-primary" name="save" value="1">Save</button>
            <a class="btn btn-outline-secondary" href="admin-trip-appointment.php">Back</a>
          </div>
        </form>
      </div>
    </div>
    <?php include('vendor/inc/footer.php'); ?>
  </div>
</div>

<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
  (function () {
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      document.body.classList.toggle('sidebar-toggled');
      var rail = document.getElementById('kayaSidebar');
      if (rail) rail.classList.toggle('kaya-rail--collapsed');
    });
  })();
</script>
</body>
</html>
