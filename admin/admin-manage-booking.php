<?php
session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = (int)($_SESSION['a_id'] ?? 0);

/* ---------- optional audit helper (safe no-op if table absent) ---------- */
function kaya_audit($mysqli, $actorType, $actorId, $action, $bookingId, $details = []) {
  if (!$mysqli) return;
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

  if ($stmt = $mysqli->prepare("INSERT INTO tms_audit_log(actor_type,actor_id,action,booking_u_id,details) VALUES (?,?,?,?,?)")) {
    $json = json_encode($details, JSON_UNESCAPED_UNICODE);
    $stmt->bind_param('sisis', $actorType, $actorId, $action, $bookingId, $json);
    $stmt->execute();
    $stmt->close();
  }
}

/* ----------------------------- inline actions ---------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['booking_id'], $_POST['do'])) {
  $id  = (int)$_POST['booking_id'];
  $act = $_POST['do'];

  if ($act === 'restore') {
    // move back to Upcoming by setting status to Pending
    if ($s = $mysqli->prepare("UPDATE tms_user SET u_car_book_status='Pending' WHERE u_id=?")) {
      $s->bind_param('i', $id);
      $s->execute(); $s->close();
      kaya_audit($mysqli, 'admin', $aid, 'restore_cancelled', $id);
    }
  } elseif ($act === 'delete') {
    if ($s = $mysqli->prepare("DELETE FROM tms_user WHERE u_id=?")) {
      $s->bind_param('i', $id);
      $s->execute(); $s->close();
      kaya_audit($mysqli, 'admin', $aid, 'delete_cancelled', $id);
    }
  }
  header('Location: '.$_SERVER['PHP_SELF'].'?ok=1'); exit;
}

/* ------------------------------ fetch rows ------------------------------- */
$rows = [];
if ($stmt = $mysqli->prepare("SELECT u_id,u_fname,u_lname,u_phone,u_car_type,u_car_regno,u_car_bookdate,u_car_book_status FROM tms_user WHERE u_car_book_status IN ('Cancel','Undermaintenance') ORDER BY u_id DESC")) {
  $stmt->execute();
  $res = $stmt->get_result();
  while ($r = $res->fetch_assoc()) $rows[] = $r;
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

        <h1 class="kaya-page-title">Cancelled Trip Appointments</h1>

        <!-- Toolbar (tabs left, actions right) -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="btn-group" role="group" aria-label="Filters">
            <a href="admin-trip-appointment.php" class="btn kaya-tab">Upcoming</a>
            <a href="admin-view-booking.php"   class="btn kaya-tab">Completed</a>
          </div>
          <div class="kaya-actions ml-auto btn-group" role="group" aria-label="Actions">
            <a href="admin-create-booking.php" class="btn btn-kaya-primary">New Trip</a>
            <a href="admin-manage-booking.php" class="btn btn-kaya-danger-outline">Cancelled</a>
          </div>
        </div>

        <!-- Cancelled table -->
        <section class="kaya-card">
          <div class="table-responsive">
            <table class="table kaya-table table-borderless" id="dataTable">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Phone</th>
                  <th>Vehicle Type</th>
                  <th>Vehicle Reg No</th>
                  <th>Booking date</th>
                  <th>Status</th>
                  <th class="actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $n=1; foreach ($rows as $row): 
                  $status = $row['u_car_book_status'];
                  $chip   = ($status === 'Cancel') ? 'chip chip--danger'
                         : (($status === 'Undermaintenance') ? 'chip chip--muted' : 'chip');
                ?>
                <tr>
                  <td><?= $n++; ?></td>
                  <td><?= htmlspecialchars($row['u_fname'].' '.$row['u_lname']); ?></td>
                  <td><?= htmlspecialchars($row['u_phone']); ?></td>
                  <td><?= htmlspecialchars($row['u_car_type']); ?></td>
                  <td><?= htmlspecialchars($row['u_car_regno']); ?></td>
                  <td><?= htmlspecialchars($row['u_car_bookdate']); ?></td>
                  <td><span class="<?= $chip; ?>"><?= htmlspecialchars($status); ?></span></td>
                  <td class="actions">
                    <!-- Restore to Upcoming -->
                    <form method="post" class="d-inline" data-toggle="tooltip" title="Restore to Upcoming">
                      <input type="hidden" name="booking_id" value="<?= (int)$row['u_id']; ?>">
                      <input type="hidden" name="do" value="restore">
                      <button class="btn btn-sm btn-outline-success btn-icon">
                        <i class="fas fa-undo" aria-hidden="true"></i>
                      </button>
                    </form>
                    <!-- Delete permanently -->
                    <form method="post" class="d-inline" onsubmit="return confirm('Permanently delete this cancelled booking?');" data-toggle="tooltip" title="Delete permanently">
                      <input type="hidden" name="booking_id" value="<?= (int)$row['u_id']; ?>">
                      <input type="hidden" name="do" value="delete">
                      <button class="btn btn-sm btn-outline-danger btn-icon">
                        <i class="fas fa-trash" aria-hidden="true"></i><span class="sr-only">Delete</span>
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
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
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
  <script src="vendor/js/sb-admin.min.js"></script>
  <script src="vendor/js/demo/datatables-demo.js"></script>

  <script>
    // DataTable
    $('#dataTable').DataTable({
      pageLength: 10,
      order: [[0,'desc']],
      columnDefs: [{ targets: -1, orderable:false, searchable:false }]
    });

    // tooltips for icon-only buttons
    $(function(){ $('[data-toggle="tooltip"]').tooltip(); });

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

  <style>
    /* Status pill to match KAYA tone */
    .chip{
      display:inline-flex; align-items:center;
      padding:.2rem .5rem; border-radius:.5rem;
      font-weight:600; font-size:.85rem;
      color:#0f172a; background:#eef2ff; border:1px solid #e5e7eb;
    }
    .chip--danger{ background:#fef2f2; color:#991b1b; border-color:#fecaca; }
    .chip--muted{ background:#f1f5f9; color:#334155; border-color:#cbd5e1; }

    /* Action buttons consistent with other pages */
    .btn-icon{ width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center; padding:0; border-radius:.5rem; }
    td.actions{ white-space:nowrap; }
    td.actions .btn-icon + .btn-icon{ margin-left:.25rem; }

    /* Neutralize sticky footer space on this page */
    footer.sticky-footer{ background:transparent!important; height:0!important; border:0!important; box-shadow:none!important; }
    footer.sticky-footer .container, footer.sticky-footer .copyright{ display:none!important; }
    #wrapper #content-wrapper{ padding-bottom:0!important; }
  </style>
</body>
</html>
