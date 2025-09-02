<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

// --- Get vehicle id
$vId = isset($_GET['v_id']) ? (int)$_GET['v_id'] : 0;
if ($vId <= 0) { header('Location: admin-manage-vehicle.php'); exit; }

// --- Delete (optional action from this page)
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['delete_vehicle'], $_POST['v_id'])) {
  $toDel = (int)$_POST['v_id'];
  if ($stmt = $mysqli->prepare("DELETE FROM tms_vehicle WHERE v_id=?")) {
    $stmt->bind_param('i', $toDel);
    $stmt->execute();
    $stmt->close();
  }
  header('Location: admin-manage-vehicle.php?deleted=1');
  exit;
}

// --- Fetch vehicle
$veh = null;
if ($s = $mysqli->prepare("SELECT v_id, v_name, v_reg_no, v_driver, v_category, v_status, v_dpic FROM tms_vehicle WHERE v_id=? LIMIT 1")) {
  $s->bind_param('i', $vId);
  $s->execute();
  $veh = $s->get_result()->fetch_assoc();
  $s->close();
}
if (!$veh) { header('Location: admin-manage-vehicle.php'); exit; }

// --- helpers
function status_badge_class($s){
  $s = strtolower(trim($s ?? ''));
  if ($s==='available')         return 'badge badge-success';
  if ($s==='booked')            return 'badge badge-primary';
  if ($s==='undermaintenance' || $s==='under maintenance') return 'badge badge-warning';
  return 'badge badge-secondary';
}
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

      <!-- Page title -->
      <h1 class="kaya-page-title">Vehicle Details</h1>


      <!-- Toolbar -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
        <div class="ml-auto kaya-actions">
            <a href="admin-manage-vehicle.php" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Back
            </a>

            <a href="admin-manage-single-vehicle.php?v_id=<?= (int)$veh['v_id'] ?>" class="btn btn-kaya-primary">
            <i class="fas fa-pen mr-1"></i> Edit
            </a>

            <form method="post" class="d-inline"
                onsubmit="return confirm('Delete this vehicle? This cannot be undone.');" style="margin:0;">
            <input type="hidden" name="v_id" value="<?= (int)$veh['v_id'] ?>">
            <button name="delete_vehicle" class="btn btn-kaya-danger-outline">
                <i class="fas fa-trash mr-1"></i> Delete
            </button>
            </form>
        </div>
        </div>


      <!-- Details -->
      <section class="kaya-card p-3 p-md-4">
        <div class="row">
          <!-- Left: meta/details -->
          <div class="col-lg-8">
            <div class="mb-3">
              <h2 class="mb-1" style="font-weight:700;color:#000047">
                <?= htmlspecialchars($veh['v_name'] ?: 'Untitled Vehicle') ?>
              </h2>
              <div class="d-flex align-items-center" style="gap:.5rem;">
                <span class="text-muted">Reg No.</span>
                <span class="font-weight-semibold"><?= htmlspecialchars($veh['v_reg_no'] ?: '—') ?></span>
                <span class="<?= status_badge_class($veh['v_status']) ?> ml-2 px-2 py-1">
                  <?= htmlspecialchars($veh['v_status'] ?: 'Unknown') ?>
                </span>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-borderless kaya-table mb-0">
                <tbody>
                  <tr>
                    <th style="width:220px;color:#6b7280;">Vehicle Name</th>
                    <td><?= htmlspecialchars($veh['v_name'] ?: '—') ?></td>
                  </tr>
                  <tr>
                    <th style="color:#6b7280;">Registration Number</th>
                    <td><?= htmlspecialchars($veh['v_reg_no'] ?: '—') ?></td>
                  </tr>
                  <tr>
                    <th style="color:#6b7280;">Driver</th>
                    <td><?= htmlspecialchars($veh['v_driver'] ?: '—') ?></td>
                  </tr>
                  <tr>
                    <th style="color:#6b7280;">Category</th>
                    <td><?= htmlspecialchars($veh['v_category'] ?: '—') ?></td>
                  </tr>
                  <tr>
                    <th style="color:#6b7280;">Status</th>
                    <td>
                      <span class="<?= status_badge_class($veh['v_status']) ?> px-2 py-1">
                        <?= htmlspecialchars($veh['v_status'] ?: 'Unknown') ?>
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Right: image -->
          <div class="col-lg-4 mt-4 mt-lg-0">
            <div class="card shadow-sm" style="border-radius:.75rem; overflow:hidden;">
              <?php
                $img = !empty($veh['v_dpic']) ? "../vendor/img/".rawurlencode($veh['v_dpic']) : "";
              ?>
              <?php if ($img): ?>
                <img src="<?= $img ?>" class="card-img-top" alt="Vehicle photo">
              <?php else: ?>
                <div class="d-flex align-items-center justify-content-center"
                     style="height:260px;background:#f8fafc;color:#6b7280">
                  <i class="fas fa-image fa-2x mr-2"></i> No photo
                </div>
              <?php endif; ?>
              <div class="card-body">
                <div class="small text-muted">Vehicle Picture</div>
                <div class="text-muted">You can update or replace this image from the Edit screen.</div>
              </div>
            </div>
          </div>
        </div>
      </section>

    </div>
    <?php include('vendor/inc/footer.php'); ?>
  </div>
</div>

<!-- Vendor JS -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>



<style>
    /* Consistent spacing and look for top-right action buttons */
    .kaya-toolbar .kaya-actions{
    display:flex;
    align-items:center;
    gap:.5rem;          /* adds breathing room between buttons */
    flex-wrap:wrap;     /* wrap nicely on small screens */
    }
    .kaya-toolbar .kaya-actions .btn{
    padding:.5rem .9rem;
    border-radius:.5rem;
    font-weight:600;
    }
    @media (max-width:575.98px){
    .kaya-toolbar .kaya-actions{ justify-content:flex-start; }
    }

  /* Hide legacy sticky footer space on this page */
  footer.sticky-footer{ background:transparent!important; height:0!important; border:0!important; box-shadow:none!important; }
  footer.sticky-footer .container, footer.sticky-footer .copyright{ display:none!important; }
  #wrapper #content-wrapper{ padding-bottom:0!important; }
</style>
</body>
</html>
