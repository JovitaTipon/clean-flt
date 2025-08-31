<?php
/**
 * Driver Daily Reports — Admin
 * - Lists driver-submitted daily reports from tms_driver_report
 * - Admin can Verify or Flag a report
 * - Uses your shared navbar/sidebar includes so layout stays consistent
 */

session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();

/* If you added require_admin() in checklogin.php, use it. Otherwise fall back gracefully. */
if (function_exists('require_admin')) {
  $aid = require_admin();
} else {
  if (!isset($_SESSION['a_id'])) { header('Location: index.php'); exit; }
  $aid = (int)$_SESSION['a_id'];
}

/* -------------------------------
   Handle status updates (POST)
   ------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['report_id'])) {
  $rid = (int) $_POST['report_id'];
  if ($_POST['action'] === 'verify' || $_POST['action'] === 'flag') {
    $newStatus = $_POST['action'] === 'verify' ? 'verified' : 'flagged';
    $stmt = $mysqli->prepare("UPDATE tms_driver_report
                              SET status=?, verified_by=?, verified_at=NOW()
                              WHERE report_id=?");
    $stmt->bind_param('sii', $newStatus, $aid, $rid);
    $stmt->execute();
    $stmt->close();
    header('Location: ' . basename(__FILE__) . '?ok=1');
    exit;
  }
}

/* -------------------------------
   Fetch reports for the table
   ------------------------------- */
$sql = "SELECT r.*,
               CONCAT(d.u_fname,' ',d.u_lname) AS driver_name,
               v.v_reg_no AS vehicle_reg
        FROM tms_driver_report r
        LEFT JOIN tms_user_add_driver d ON d.d_u_id = r.driver_id
        LEFT JOIN tms_vehicle v ON v.v_id = r.vehicle_id
        ORDER BY r.trip_date DESC, r.report_id DESC";
$rows = [];
if ($res = $mysqli->query($sql)) {
  while ($row = $res->fetch_assoc()) $rows[] = $row;
  $res->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>

<body id="page-top">
  <?php include("vendor/inc/nav.php"); ?>
  <div id="wrapper">
    <?php include('vendor/inc/sidebar.php'); ?>

    <!-- Page-specific CSS (keep it here to avoid duplicating <head>) -->
    <style>
    html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
    .kaya-page-title{font-weight:800;font-size:2rem;line-height:1.1;color:#000047;margin:0 0 1rem}
    .kaya-card{background:#fff;border-radius:1rem;box-shadow:0 8px 24px rgba(0,0,0,.06);padding:1rem;border:1px solid #e5e7eb}

    /* Table look */
    .kaya-table thead th{font-weight:600;color:#6b7280;border:0}
    .kaya-table tbody td{vertical-align:middle} /* <— keep alignment only */

    /* One clean separator per row, drawn on each cell so it spans full width */
    .kaya-table{border-collapse:separate;border-spacing:0}
    .kaya-table tbody td,
    .kaya-table tbody th{
        border-bottom:1px solid #E9EEF5 !important;  /* full-width line */
    }
    .kaya-table tbody tr:last-child td,
    .kaya-table tbody tr:last-child th{
        border-bottom:0 !important;                  /* optional: no line after last row */
    }

    /* Status chips */
    .chip{display:inline-block;padding:.25rem .5rem;border-radius:.5rem;font-size:.8rem;font-weight:600}
    .chip-submitted{background:#eef2ff;color:#3730a3}
    .chip-verified{background:#e8f5e9;color:#1b5e20}
    .chip-flagged{background:#fff7ed;color:#9a3412}

    /* Actions column: width + tidy icon layout */
    .kaya-table th.actions,
    .kaya-table td.actions{width:130px}
    .kaya-table td.actions{
        display:flex;gap:8px;align-items:center;justify-content:flex-start;
        white-space:nowrap;padding-right:16px;
    }

    /* Compact icon buttons */
    .btn-icon{width:36px;height:36px;display:inline-flex;align-items:center;justify-content:center;border-radius:8px}
    .btn-verify{border:1px solid #16a34a;color:#16a34a;background:#fff}
    .btn-verify:hover{background:#ecfdf5}
    .btn-flag{border:1px solid #dc2626;color:#dc2626;background:#fff}
    .btn-flag:hover{background:#fee2e2}

    /* Mobile off-canvas + backdrop (works for #accordionSidebar or .sidebar) */
    @media (max-width: 991.98px){
    #accordionSidebar, .sidebar { 
        transform: translateX(-100%); 
        transition: transform .2s ease;
        will-change: transform;
    }
    body.kaya-drawer-open #accordionSidebar,
    body.kaya-drawer-open .sidebar {
        transform: none;
    }

    .kaya-backdrop{
        position: fixed; inset: 0;
        background: rgba(0,0,0,.35);
        opacity: 0; pointer-events: none;
        transition: opacity .2s ease;
        z-index: 1040; /* above content, below navbar */
    }
    body.kaya-drawer-open .kaya-backdrop{
        opacity: 1; pointer-events: auto;
    }
    }
    </style>


    <div id="content-wrapper">
      <div class="container-fluid">
        <h1 class="kaya-page-title">Driver Daily Reports</h1>

        <div class="kaya-card">
          <div class="table-responsive">
            <table id="dataTable" class="table kaya-table table-borderless">
              <thead>
                <tr>
                  <th style="width:56px">#</th>
                  <th>Date</th>
                  <th>Driver</th>
                  <th>Vehicle</th>
                  <th>Odo Start</th>
                  <th>Odo End</th>
                  <th>Total Km</th>
                  <th>Fuel (L)</th>
                  <th>Route</th>
                  <th>Status</th>
                  <th class="actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $n = 1;
                foreach ($rows as $r):
                  $status = $r['status'] ?: 'submitted';
                  $chipClass = $status === 'verified' ? 'chip-verified'
                              : ($status === 'flagged' ? 'chip-flagged' : 'chip-submitted');
                ?>
                <tr>
                  <td><?= $n++; ?></td>
                  <td><?= htmlspecialchars($r['trip_date']) ?></td>
                  <td><?= htmlspecialchars($r['driver_name'] ?: '—') ?></td>
                  <td><?= htmlspecialchars($r['vehicle_reg'] ?: '—') ?></td>
                  <td><?= htmlspecialchars($r['odometer_start']) ?></td>
                  <td><?= htmlspecialchars($r['odometer_end']) ?></td>
                  <td><?= htmlspecialchars($r['total_km']) ?></td>
                  <td><?= htmlspecialchars($r['fuel_used_liters']) ?></td>
                  <td>
                    <?= htmlspecialchars($r['route_from'] ?: '—') ?> → <?= htmlspecialchars($r['route_to'] ?: '—') ?>
                    <?php if (!empty($r['notes'])): ?>
                      <div class="text-muted small mt-1" title="Notes"><?= htmlspecialchars($r['notes']) ?></div>
                    <?php endif; ?>
                  </td>
                  <td><span class="chip <?= $chipClass ?>"><?= ucfirst($status) ?></span></td>
                  <td class="actions">
                    <!-- Verify -->
                    <form method="post" class="d-inline">
                      <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                      <input type="hidden" name="action" value="verify">
                      <button class="btn btn-icon btn-verify" title="Verify" <?= $status==='verified'?'disabled':'' ?>>
                        <i class="fas fa-check"></i>
                      </button>
                    </form>

                    <!-- Flag -->
                    <form method="post" class="d-inline">
                      <input type="hidden" name="report_id" value="<?= (int)$r['report_id'] ?>">
                      <input type="hidden" name="action" value="flag">
                      <button class="btn btn-icon btn-flag" title="Flag" <?= $status==='flagged'?'disabled':'' ?>>
                        <i class="fas fa-flag"></i>
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
      <?php include("vendor/inc/footer.php"); ?>
    </div>
  </div>

  <!-- Scroll to Top -->
  <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

  <!-- Vendor JS -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
  <script src="vendor/js/sb-admin.min.js"></script>
  <script src="vendor/js/demo/datatables-demo.js"></script>


</script>
<!-- Optional: make sure the rail/backdrop sit above page content on mobile -->
    <style>
    @media (max-width: 991.98px){
    .kaya-rail{ z-index:1045; }   /* sidebar above content */
}
    </style>
    <script>
    (function () {
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;

    btn.addEventListener('click', function (e) {
        e.preventDefault();

        // SB-Admin convention (mobile opens the off-canvas)
        document.body.classList.toggle('sidebar-toggled');

        // Your rail: collapse/expand on desktop
        var rail = document.getElementById('kayaSidebar');
        if (rail) rail.classList.toggle('kaya-rail--collapsed');
    });

    // Keep the content pushed below the fixed navbar
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
