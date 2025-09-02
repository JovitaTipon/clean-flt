<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

$isAdmin = function_exists('is_admin') ? is_admin() : isset($_SESSION['a_id']);
$aid     = $isAdmin ? (int)($_SESSION['a_id'] ?? 0) : 0;

/* -------------------------------------------------
   (Optional) simple audit helper (no hard dependency)
   ------------------------------------------------- */
function kaya_audit($mysqli, $actorType, $actorId, $action, $bookingId, $details = []) {
  if (!$mysqli) return;

  // Create table the first time (safe no-op if it already exists)
  $sql = "CREATE TABLE IF NOT EXISTS tms_audit_log (
            id INT AUTO_INCREMENT PRIMARY KEY,
            actor_type ENUM('admin','driver') NOT NULL,
            actor_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            booking_u_id INT NOT NULL,
            details JSON NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
          ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
  @$mysqli->query($sql);

  $stmt = $mysqli->prepare("INSERT INTO tms_audit_log(actor_type,actor_id,action,booking_u_id,details)
                            VALUES (?,?,?,?,?)");
  if ($stmt) {
    $json = json_encode($details, JSON_UNESCAPED_UNICODE);
    // Important: one letter per value, no spaces -> s i s i s
    $stmt->bind_param('sisis', $actorType, $actorId, $action, $bookingId, $json);
    $stmt->execute();
    $stmt->close();
  }
}

/* -------------------------------------------------
   Resolve current DRIVER identity (for filtering + rights)
   ------------------------------------------------- */
$currentDriverId  = null;  // from tms_user_add_driver.d_u_id
$currentDriverName = null; // "First Last" (to match tms_user.u_car_driver)

if (!$isAdmin && isset($_SESSION['u_id'])) {
  // Read the logged-in "user" row to grab their email/name.
  if ($s = $mysqli->prepare("SELECT u_email, u_fname, u_lname FROM tms_user WHERE u_id=? LIMIT 1")) {
    $uid = (int)$_SESSION['u_id'];
    $s->bind_param('i',$uid);
    $s->execute();
    $s->bind_result($email, $fn, $ln);
    if ($s->fetch()) {
      $currentDriverName = trim(($fn ?? '').' '.($ln ?? ''));
      // try to map to tms_user_add_driver via email
      if (!empty($email)) {
        $s->close();
        if ($d = $mysqli->prepare("SELECT d_u_id, u_fname, u_lname FROM tms_user_add_driver WHERE u_email=? LIMIT 1")) {
          $d->bind_param('s',$email);
          $d->execute();
          $d->bind_result($did,$df,$dl);
          if ($d->fetch()) {
            $currentDriverId = (int)$did;
            $currentDriverName = trim(($df ?? '').' '.($dl ?? '')) ?: $currentDriverName;
          }
          $d->close();
        }
      } else {
        $s->close();
      }
    } else { $s->close(); }
  }
}

/* -------------------------------------------------
   Row actions (POST): approve / complete / cancel
   ------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['booking_id'], $_POST['do'])) {
  $id = (int)$_POST['booking_id'];
  $act = $_POST['do'];

  // Only allow drivers to cancel their own Pending/Approved.
  if (!$isAdmin) {
    if (!$currentDriverName) { header('Location: '.$_SERVER['REQUEST_URI']); exit; }
    $own = false;
    if ($s = $mysqli->prepare("SELECT u_car_driver, u_car_book_status FROM tms_user WHERE u_id=?")) {
      $s->bind_param('i',$id);
      $s->execute();
      $s->bind_result($drvName, $status);
      if ($s->fetch()) { $own = (trim($drvName) === $currentDriverName) && in_array($status, ['Pending','Approved']); }
      $s->close();
    }
    if ($act !== 'cancel' || !$own) { header('Location: '.$_SERVER['REQUEST_URI']); exit; }
  }

  if ($act === 'approve' && $isAdmin) {
    $stmt = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Approved' WHERE u_id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute(); $stmt->close();
    kaya_audit($mysqli,'admin',$aid,'approve',$id);
  }
  if ($act === 'complete' && $isAdmin) {
    $stmt = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Completed' WHERE u_id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute(); $stmt->close();
    kaya_audit($mysqli,'admin',$aid,'complete',$id);
  }
  if ($act === 'cancel') {
    $whoType = $isAdmin ? 'admin' : 'driver';
    $whoId   = $isAdmin ? $aid     : (int)($_SESSION['u_id'] ?? 0);
    $stmt = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Cancel' WHERE u_id=?");
    $stmt->bind_param('i',$id);
    $stmt->execute(); $stmt->close();
    kaya_audit($mysqli,$whoType,$whoId,'cancel',$id);
  }

  header('Location: '.$_SERVER['PHP_SELF'].'?ok=1'); exit;
}

/* -------------------------------------------------
   Fetch Upcoming list
   - Admin: anything not Completed/Cancel
   - Driver: only their own (by name match)
   ------------------------------------------------- */
$where  = "WHERE u_car_book_status NOT IN ('Completed','Cancel')";
$params = [];
$types  = '';

if (!$isAdmin && $currentDriverName) {
  $where .= " AND u_car_driver = ?";
  $params[] = $currentDriverName;
  $types   .= 's';
}

$sql = "SELECT u_id, u_car_date, u_car_time, u_fname, u_lname, u_car_pax,
               u_car_pickup, u_car_destination, u_car_regno, u_car_type,
               u_car_driver, u_car_book_status, u_car_createdat
        FROM tms_user
        $where
        ORDER BY u_id DESC";

$rows = [];
if ($stmt = $mysqli->prepare($sql)) {
  if ($types) { $stmt->bind_param($types, ...$params); }
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $rows[] = $row;
  $stmt->close();
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

      <h1 class="kaya-page-title">Trip Appointments</h1>

      <!-- Toolbar -->
      <div class="kaya-toolbar d-flex align-items-center mb-3" style="gap:.5rem;flex-wrap:wrap;">
        <div class="btn-group" role="group" aria-label="Filters">
          <a href="admin-trip-appointment.php" class="btn kaya-tab active">Upcoming</a>
          <a href="admin-view-booking.php"   class="btn kaya-tab">Completed</a>
        </div>

        <div class="kaya-actions ml-auto btn-group" role="group" aria-label="Actions" style="flex-wrap:nowrap;gap:.5rem;">
          <a href="admin-create-booking.php" class="btn btn-kaya-primary">New Trip</a>
          <a href="admin-manage-booking.php" class="btn btn-kaya-danger-outline">Cancelled</a>
        </div>
      </div>

      <!-- Table -->
      <div class="kaya-card">
        <div class="table-responsive px-2">
          <table id="dataTable" class="kaya-table table table-borderless">
            <thead>
              <tr>
                <th>#</th>
                <th>Date</th>
                <th>Time</th>
                <th>Customer</th>
                <th>Pax</th>
                <th>Pick Up</th>
                <th>Destination</th>
                <th>Reg No.</th>
                <th>Vehicle Type</th>
                <th>Driver</th>
                <th>Status</th>
                <th class="actions">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php
              $n=1;
              foreach ($rows as $r):
                $date = $r['u_car_createdat']
                        ? date('M j, Y', is_numeric($r['u_car_createdat']) ? (int)$r['u_car_createdat'] : strtotime($r['u_car_createdat']))
                        : ($r['u_car_date'] ?? '');
                $time = $r['u_car_createdat']
                        ? date('h:i A', is_numeric($r['u_car_createdat']) ? (int)$r['u_car_createdat'] : strtotime($r['u_car_createdat']))
                        : ($r['u_car_time'] ?? '');

                $status = $r['u_car_book_status'] ?: 'Pending';
                $chip = 'badge badge-secondary';
                if ($status==='Pending') $chip='badge badge-light';
                if (in_array($status,['Approved','Available'])) $chip='badge badge-success';
                if ($status==='Maintenance') $chip='badge badge-warning';
                if ($status==='In Active')   $chip='badge badge-danger';

                $canApprove  = $isAdmin && $status==='Pending';
                $canComplete = $isAdmin && in_array($status,['Approved']);
                $canCancel   = $isAdmin || ($currentDriverName && $r['u_car_driver']===$currentDriverName && in_array($status,['Pending','Approved']));
              ?>
              <tr>
                <td><?= $n++; ?></td>
                <td><?= htmlspecialchars($date) ?></td>
                <td><?= htmlspecialchars($time) ?></td>
                <td><?= htmlspecialchars(trim($r['u_fname'].' '.$r['u_lname'])) ?></td>
                <td><?= htmlspecialchars($r['u_car_pax']) ?></td>
                <td><?= htmlspecialchars($r['u_car_pickup']) ?></td>
                <td><?= htmlspecialchars($r['u_car_destination']) ?></td>
                <td><?= htmlspecialchars($r['u_car_regno']) ?></td>
                <td><?= htmlspecialchars($r['u_car_type']) ?></td>
                <td><?= htmlspecialchars($r['u_car_driver']) ?></td>
                <td><span class="<?= $chip ?> px-2 py-1"><?= htmlspecialchars($status) ?></span></td>
                <td class="actions" style="white-space:nowrap;">
                  <?php if ($isAdmin): ?>
                    <a href="admin-edit-booking.php?u_id=<?= (int)$r['u_id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit"><i class="fas fa-pen"></i></a>
                  <?php endif; ?>

                  <?php if ($canApprove): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="booking_id" value="<?= (int)$r['u_id'] ?>">
                      <input type="hidden" name="do" value="approve">
                      <button class="btn btn-sm btn-outline-success" title="Approve"><i class="fas fa-check"></i></button>
                    </form>
                  <?php endif; ?>

                  <?php if ($canComplete): ?>
                    <form method="post" class="d-inline">
                      <input type="hidden" name="booking_id" value="<?= (int)$r['u_id'] ?>">
                      <input type="hidden" name="do" value="complete">
                      <button class="btn btn-sm btn-outline-primary" title="Mark Completed"><i class="fas fa-check-circle"></i></button>
                    </form>
                  <?php endif; ?>

                  <?php if ($canCancel): ?>
                    <form method="post" class="d-inline" onsubmit="return confirm('Cancel this booking?');">
                      <input type="hidden" name="booking_id" value="<?= (int)$r['u_id'] ?>">
                      <input type="hidden" name="do" value="cancel">
                      <button class="btn btn-sm btn-outline-danger" title="Cancel"><i class="fas fa-ban"></i></button>
                    </form>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>

    </div>
    <?php include('vendor/inc/footer.php'); ?>
  </div>
</div>

<!-- JS -->
<script src="vendor/jquery/jquery.min.js"></script>
<script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="vendor/jquery-easing/jquery.easing.min.js"></script>
<script src="vendor/datatables/jquery.dataTables.js"></script>
<script src="vendor/datatables/dataTables.bootstrap4.js"></script>
<script src="vendor/js/sb-admin.min.js"></script>

<script>
  // DataTable
  $('#dataTable').DataTable({
    pageLength: 10,
    order: [[0,'desc']],
    columnDefs: [{ targets: -1, orderable:false, searchable:false }]
  });

  // Sidebar behaviour (your working snippet)
  (function () {
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      document.body.classList.toggle('sidebar-toggled');
      var rail = document.getElementById('kayaSidebar');
      if (rail) rail.classList.toggle('kaya-rail--collapsed');
    });
    function syncNavH(){
      var nav = document.querySelector('.navbar.kaya-white');
      if (!nav) return;
      var h = Math.round(nav.getBoundingClientRect().height || 64);
      document.documentElement.style.setProperty('--kaya-nav-h', h + 'px');
    }
    syncNavH(); window.addEventListener('resize', syncNavH);
  })();
</script>
</body>
</html>
