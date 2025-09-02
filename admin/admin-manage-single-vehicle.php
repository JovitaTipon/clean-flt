<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

// --- Resolve vehicle id once
$vehId = isset($_GET['v_id']) ? (int)$_GET['v_id'] : 0;
if ($vehId <= 0) { header('Location: admin-manage-vehicle.php'); exit; }

// --- Fetch current vehicle (used for form + fallback image if no upload)
$vehicle = null;
if ($s = $mysqli->prepare("SELECT v_id, v_name, v_reg_no, v_driver, v_category, v_status, v_dpic FROM tms_vehicle WHERE v_id=? LIMIT 1")) {
  $s->bind_param('i', $vehId);
  $s->execute();
  $res = $s->get_result();
  $vehicle = $res->fetch_assoc();
  $s->close();
}
if (!$vehicle) { header('Location: admin-manage-vehicle.php'); exit; }

// --- Update (POST)
if (isset($_POST['upate_veh'])) {
  $v_name     = trim($_POST['v_name'] ?? '');
  $v_reg_no   = trim($_POST['v_reg_no'] ?? '');
  $v_driver   = trim($_POST['v_driver'] ?? '');
  $v_category = trim($_POST['v_category'] ?? '');
  $v_status   = trim($_POST['v_status'] ?? '');

  // keep existing picture if no new file was selected
  $v_dpic = $vehicle['v_dpic'] ?? '';

  if (!empty($_FILES['v_dpic']['name'])) {
    // very light sanitization of filename
    $fname = preg_replace('/[^A-Za-z0-9._-]/', '_', $_FILES['v_dpic']['name']);
    if (is_uploaded_file($_FILES['v_dpic']['tmp_name'])) {
      if (@move_uploaded_file($_FILES['v_dpic']['tmp_name'], "../vendor/img/".$fname)) {
        $v_dpic = $fname;
      }
    }
  }

  // Update
  if ($stmt = $mysqli->prepare("UPDATE tms_vehicle SET v_name=?, v_reg_no=?, v_driver=?, v_category=?, v_dpic=?, v_status=? WHERE v_id=?")) {
    $stmt->bind_param('ssssssi', $v_name, $v_reg_no, $v_driver, $v_category, $v_dpic, $v_status, $vehId);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
      $succ = "Vehicle Updated";
      // refresh $vehicle for re-render
      if ($s = $mysqli->prepare("SELECT v_id, v_name, v_reg_no, v_driver, v_category, v_status, v_dpic FROM tms_vehicle WHERE v_id=? LIMIT 1")) {
        $s->bind_param('i', $vehId);
        $s->execute();
        $vehicle = $s->get_result()->fetch_assoc();
        $s->close();
      }
    } else {
      $err = "Please Try Again Later";
    }
  } else {
    $err = "Database error.";
  }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<body id="page-top">

  <?php include("vendor/inc/nav.php"); ?>

  <div id="wrapper">
    <?php include("vendor/inc/sidebar.php"); ?>

    <div id="content-wrapper">
      <div class="container-fluid">

        <h1 class="kaya-page-title">Edit Vehicle</h1>

        <!-- Toolbar: back to list -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="ml-auto">
            <a href="admin-manage-vehicle.php" class="btn btn-outline-secondary">
              <i class="fas fa-arrow-left mr-1"></i> Back to Vehicles
            </a>
          </div>
        </div>

        <?php if(isset($succ)): ?>
          <script>
            setTimeout(function(){ swal("Success!", "<?= $succ ?>", "success"); }, 100);
          </script>
        <?php endif; ?>
        <?php if(isset($err)): ?>
          <script>
            setTimeout(function(){ swal("Failed!", "<?= $err ?>", "error"); }, 100);
          </script>
        <?php endif; ?>

        <!-- Edit card -->
        <section class="kaya-card p-3 p-md-4">
          <form method="POST" enctype="multipart/form-data">
            <div class="row">
              <!-- Left column: fields -->
              <div class="col-lg-8">
                <div class="form-group">
                  <label class="font-weight-semibold">Vehicle Name</label>
                  <input type="text" name="v_name" required class="form-control"
                         value="<?= htmlspecialchars($vehicle['v_name'] ?? '') ?>">
                </div>

                <div class="form-group">
                  <label class="font-weight-semibold">Vehicle Registration Number</label>
                  <input type="text" name="v_reg_no" class="form-control"
                         value="<?= htmlspecialchars($vehicle['v_reg_no'] ?? '') ?>">
                </div>

                <div class="form-group">
                  <label class="font-weight-semibold">Driver</label>
                  <input type="text" name="v_driver" class="form-control"
                         value="<?= htmlspecialchars($vehicle['v_driver'] ?? '') ?>">
                </div>

                <div class="form-row">
                  <div class="form-group col-md-6">
                    <label class="font-weight-semibold">Vehicle Category</label>
                    <select class="form-control" name="v_category">
                      <?php
                        $cats = ['Bus','Sedan','SUV','Van'];
                        $curr = $vehicle['v_category'] ?? '';
                        foreach ($cats as $c) {
                          $sel = ($c===$curr)?'selected':'';
                          echo "<option $sel>".htmlspecialchars($c)."</option>";
                        }
                      ?>
                    </select>
                  </div>

                  <div class="form-group col-md-6">
                    <label class="font-weight-semibold">Vehicle Status</label>
                    <select class="form-control" name="v_status">
                      <?php
                        $statuses = ['Available','Booked','UnderMaintenance'];
                        $curS = $vehicle['v_status'] ?? '';
                        foreach ($statuses as $s) {
                          $sel = ($s===$curS)?'selected':'';
                          echo "<option $sel>".htmlspecialchars($s)."</option>";
                        }
                      ?>
                    </select>
                  </div>
                </div>

                <div class="mt-3">
                  <button type="submit" name="upate_veh" class="btn btn-kaya-primary">
                    <i class="fas fa-save mr-1"></i> Update Vehicle
                  </button>
                  <a href="admin-manage-vehicle.php" class="btn btn-outline-secondary ml-2">Cancel</a>
                </div>
              </div>

              <!-- Right column: image -->
              <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card shadow-sm" style="border-radius:.75rem; overflow:hidden;">
                  <?php
                    $img = !empty($vehicle['v_dpic']) ? "../vendor/img/".rawurlencode($vehicle['v_dpic']) : "";
                  ?>
                  <?php if ($img): ?>
                    <img src="<?= $img ?>" class="card-img-top" alt="Vehicle photo">
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center"
                         style="height:220px;background:#f8fafc;color:#6b7280">
                      <i class="fas fa-image fa-2x mr-2"></i> No photo
                    </div>
                  <?php endif; ?>
                  <div class="card-body">
                    <label class="font-weight-semibold d-block mb-2">Vehicle Picture</label>
                    <input type="file" class="form-control-file" name="v_dpic" accept="image/*">
                    <small class="text-muted d-block mt-2">Leave blank to keep the current image.</small>
                  </div>
                </div>
              </div>
            </div>
          </form>
        </section>

      </div>

      <?php include("vendor/inc/footer.php"); ?>
    </div>
  </div>

  <!-- Vendor JS -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>


  <style>
    /* Keep footer invisible like the other modernized pages */
    footer.sticky-footer{ background:transparent!important; height:0!important; border:0!important; box-shadow:none!important; }
    footer.sticky-footer .container, footer.sticky-footer .copyright{ display:none!important; }
    #wrapper #content-wrapper{ padding-bottom:0!important; }
    /* Tighter, modern labels */
    .form-group label{ color:#0f172a; }
  </style>
</body>
</html>
