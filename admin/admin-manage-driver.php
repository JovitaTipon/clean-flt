<?php
  // ========== KAYA · Manage Drivers ==========
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid = require_admin();

  /* -------------------------------------------
   * CREATE: add a driver into tms_user_add_driver
   * -------------------------------------------
   * Minimal fields mapped to your table columns.
   * (u_category is fixed to 'Driver' via hidden input)
   */
  if (isset($_POST['add_driver'])) {
    $fname   = trim($_POST['u_fname']);
    $lname   = trim($_POST['u_lname']);
    $phone   = trim($_POST['u_phone']);
    $addr    = trim($_POST['u_addr']);
    $ctype   = trim($_POST['u_car_type']);     // e.g., 'Bus', 'Sedan'
    $lic     = trim($_POST['u_car_regno']);    // license or badge no.
    $status  = trim($_POST['u_car_book_status']); // 'Available', 'On Trip', etc.
    $email   = trim($_POST['u_email']);
    $cat     = 'Driver';

    $sql = "INSERT INTO tms_user_add_driver
              (u_fname, u_lname, u_phone, u_addr, u_car_type, u_car_regno, u_car_book_status, u_category, u_email)
            VALUES (?,?,?,?,?,?,?,?,?)";
    if ($stmt = $mysqli->prepare($sql)) {
      $stmt->bind_param('sssssssss', $fname,$lname,$phone,$addr,$ctype,$lic,$status,$cat,$email);
      $ok = $stmt->execute();
      $stmt->close();
      if ($ok) {
        // OPTIONAL: also create a login user record if your app needs it.
        // $pwdHash = password_hash('TempPass123!', PASSWORD_DEFAULT);
        // $sql2 = "INSERT INTO tms_user (u_fname,u_lname,u_phone,u_addr,u_category,u_email,u_pwd)
        //          VALUES (?,?,?,?,?,?,?)";
        // if ($s2 = $mysqli->prepare($sql2)) {
        //   $s2->bind_param('sssssss',$fname,$lname,$phone,$addr,$cat,$email,$pwdHash);
        //   $s2->execute(); $s2->close();
        // }
        $succ = "Driver Added Successfully";
      } else {
        $err = "Please Try Again Later";
      }
    } else {
      $err = "DB error while preparing insert.";
    }
  }

  /* -------------------------------------------
   * DELETE: remove driver by primary key (d_u_id)
   * -------------------------------------------
   */
  if (isset($_POST['delete_driver'])) {
    $del_id = intval($_POST['delete_driver_id']);
    if ($del_id > 0) {
      $sql = "DELETE FROM tms_user_add_driver WHERE d_u_id = ?";
      if ($stmt = $mysqli->prepare($sql)) {
        $stmt->bind_param('i', $del_id);
        $ok = $stmt->execute();
        $stmt->close();
        $succ = $ok ? "Driver deleted." : "Delete failed. Try again.";
      }
    }
  }

  /* -------------------------------------------
   * FETCH: drivers list (only category=Driver)
   * -------------------------------------------
   */
  $drivers = [];
  $sql = "SELECT d_u_id, u_fname, u_lname, u_phone, u_addr, u_car_type, u_car_regno,
                 u_car_book_status, u_email
          FROM tms_user_add_driver
          WHERE u_category='Driver'
          ORDER BY d_u_id DESC";
  if ($stmt = $mysqli->prepare($sql)) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) { $drivers[] = $row; }
    $stmt->close();
  }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include('vendor/inc/head.php'); // your standard head (Bootstrap, sb-admin, etc.) ?>
  <!-- Inter font to match your other pages -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* Global font + title consistent with Admin Dashboard */
    html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
    .kaya-page-title{font-weight:800;font-size:2rem;line-height:1.1;color:#000047;margin:0 0 1rem}

    /* Card wrapper for the table — matches other pages */
    .kaya-card{
      background:#fff;border-radius:1rem;border:1px solid #e5e7eb;
      box-shadow:0 8px 24px rgba(0,0,0,.06);padding:1rem;
    }

    /* Table look — light dividers, no heavy borders */
    .kaya-table thead th{font-weight:600;color:#6b7280;border:0}
    .kaya-table tbody td{border-top:1px solid #f1f5f9;vertical-align:middle}

    /* Toolbar buttons: same tone as other pages (pro look) */
    .btn-kaya-primary{background:#0A0F2C;border:1px solid #0A0F2C;color:#fff}
    .btn-kaya-primary:hover{background:#0c1438;border-color:#0c1438}
    .btn-kaya-danger-outline{background:#fff;border:1px solid #dc2626;color:#dc2626}
    .btn-kaya-danger-outline:hover{background:#fee2e2}
    .kaya-toolbar .btn{padding:.5rem .9rem;border-radius:.5rem;font-weight:600}

    /* Status colors mapped from u_car_book_status */
    .status-available{color:#16a34a;font-weight:600}    /* Available */
    .status-trip{color:#2563eb;font-weight:600}         /* On Trip / Booked / In Service */
    .status-off{color:#dc2626;font-weight:600}          /* Not Available / others */

    /* Icon action cluster (not cramped) */
    .actions{display:flex;gap:.4rem}
    .actions .btn{padding:.375rem .5rem;border-radius:.5rem}

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
</head>

<body id="page-top">
  <?php include('vendor/inc/nav.php'); ?>
  <div id="wrapper">
    <?php include('vendor/inc/sidebar.php'); ?>

    <div id="content-wrapper">
      <div class="container-fluid">

        <h1 class="kaya-page-title">Manage Drivers</h1>

        <!-- Success/Fail toasts via Bootstrap alerts (optional) -->
        <?php if(!empty($succ)): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($succ) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
          </div>
        <?php endif; ?>
        <?php if(!empty($err)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($err) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
          </div>
        <?php endif; ?>

        <!-- Toolbar (right aligned) -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="ml-auto">
            <button class="btn btn-kaya-primary mr-2" data-toggle="modal" data-target="#addDriverModal">New Driver</button>
            <button class="btn btn-kaya-danger-outline" data-toggle="modal" data-target="#deleteDriverModal">Delete Driver</button>
          </div>
        </div>

        <!-- Drivers table -->
        <div class="kaya-card">
          <div class="table-responsive">
            <table id="dataTable" class="table kaya-table table-borderless">
              <thead>
                <tr>
                  <th style="width:56px">#</th>
                  <th>Driver</th>
                  <th>License #</th>
                  <th>Contact #</th>
                  <th>Status</th>
                  <th style="width:220px">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $n=1;
                  foreach($drivers as $d):
                    // Map DB statuses to display + color class
                    $raw = (string)($d['u_car_book_status'] ?? '');
                    $txt = $raw ?: 'Available';
                    $cls = 'status-trip';
                    if (stripos($raw,'avail')!==false) { $txt='Available';    $cls='status-available'; }
                    elseif (stripos($raw,'trip')!==false || stripos($raw,'book')!==false || stripos($raw,'service')!==false) {
                      $txt='On Trip'; $cls='status-trip';
                    } else if ($raw==='') { $txt='Available'; $cls='status-available'; }
                    else { $txt='Not Available'; $cls='status-off'; }
                ?>
                <tr>
                  <td><?= $n++; ?></td>
                  <td class="font-weight-semibold"><?= htmlspecialchars($d['u_fname'].' '.$d['u_lname']) ?></td>
                  <td><?= htmlspecialchars($d['u_car_regno']) ?></td>
                  <td><?= htmlspecialchars($d['u_phone']) ?></td>
                  <td class="<?= $cls ?>"><?= htmlspecialchars($txt) ?></td>
                  <td class="actions">
                    <!-- Icons only: info (view), edit (pen), eye (monitor/logs), trash (delete) -->
                    <a class="btn btn-outline-secondary"  title="View"
                       href="driver-view.php?d_u_id=<?= (int)$d['d_u_id'] ?>"><i class="fas fa-info-circle"></i></a>
                    <a class="btn btn-outline-secondary"  title="Edit"
                       href="driver-edit.php?d_u_id=<?= (int)$d['d_u_id'] ?>"><i class="fas fa-pen"></i></a>
                    <a class="btn btn-outline-secondary"  title="Monitor"
                       href="admin-view-syslogs.php?driver=<?= urlencode($d['u_fname'].' '.$d['u_lname']) ?>"><i class="fas fa-eye"></i></a>
                    <button class="btn btn-outline-danger" title="Delete"
                            data-toggle="modal" data-target="#deleteDriverModal"
                            data-driver-id="<?= (int)$d['d_u_id'] ?>">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ========== Add Driver Modal ========== -->
        <div class="modal fade" id="addDriverModal" tabindex="-1" role="dialog" aria-labelledby="addDriverModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <form method="POST">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="addDriverModalLabel">Add Driver</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>First Name</label>
                      <input type="text" name="u_fname" class="form-control" required>
                    </div>
                    <div class="form-group col-md-6">
                      <label>Last Name</label>
                      <input type="text" name="u_lname" class="form-control">
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Contact #</label>
                        <input type="tel" name="u_phone" class="form-control" maxlength="32"
                            placeholder="+63 912 345 6789">
                    </div>
                    <div class="form-group col-md-6">
                      <label>Email</label>
                      <input type="email" name="u_email" class="form-control">
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>Address</label>
                      <input type="text" name="u_addr" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                      <label>Vehicle / Type</label>
                      <select name="u_car_type" class="form-control">
                        <option>Bus</option><option>Sedan</option><option>SUV</option><option>Van</option>
                      </select>
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>License #</label>
                      <input type="text" name="u_car_regno" class="form-control">
                    </div>
                    <div class="form-group col-md-6">
                      <label>Status</label>
                      <select name="u_car_book_status" class="form-control">
                        <option>Available</option>
                        <option>On Trip</option>
                        <option>Not Available</option>
                      </select>
                    </div>
                  </div>

                  <!-- Fixed category -->
                  <input type="hidden" name="u_category" value="Driver">
                </div>
                <div class="modal-footer">
                  <button type="submit" name="add_driver" class="btn btn-kaya-primary">Add Driver</button>
                  <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- ========== Delete Driver Modal ========== -->
        <div class="modal fade" id="deleteDriverModal" tabindex="-1" role="dialog" aria-labelledby="deleteDriverModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <form method="POST">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="deleteDriverModalLabel">Delete Driver</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <p class="mb-2">Select a driver to delete:</p>
                  <select class="form-control" name="delete_driver_id" id="delete_driver_id">
                    <?php foreach($drivers as $d): ?>
                      <option value="<?= (int)$d['d_u_id'] ?>">
                        <?= htmlspecialchars($d['u_fname'].' '.$d['u_lname'].' — '.$d['u_car_regno']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="modal-footer">
                  <button type="submit" name="delete_driver" class="btn btn-kaya-danger-outline">Delete</button>
                  <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                </div>
              </div>
            </form>
          </div>
        </div>

      </div><!-- /.container-fluid -->

      <!-- Keep the global footer to preserve the consistent sidebar toggle behavior -->
      <?php include("vendor/inc/footer.php");?>
    </div><!-- /#content-wrapper -->
  </div><!-- /#wrapper -->

  <!-- Vendor JS -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
  <script src="vendor/js/sb-admin.min.js"></script>

  <script>
    // DataTables: same pagination/search feel as Trip Appointments
    $('#dataTable').DataTable({
      pageLength: 10,
      order: [[0,'asc']],
      columnDefs: [
        { orderable: false, targets: [5] } // actions not sortable
      ]
    });

    // When clicking the per-row delete icon, preselect that driver in modal
    $('#deleteDriverModal').on('show.bs.modal', function (e) {
      var trigger = $(e.relatedTarget);
      var id = trigger.data('driver-id');
      if (id) { $('#delete_driver_id').val(id); }
    });

    <script>
    (function () {
    var MOBILE_MAX = 991, body = document.body;

    // Ensure a backdrop exists for mobile drawer
    if (!document.querySelector('.kaya-backdrop')) {
        var b = document.createElement('div');
        b.className = 'kaya-backdrop';
        b.addEventListener('click', function(){ body.classList.remove('kaya-drawer-open'); });
        document.body.appendChild(b);
    }

    function handleToggle(e){
        if (e) e.preventDefault();

        // Support both SB-Admin and our “kaya” approach
        var sidebar = document.querySelector('#accordionSidebar') || document.querySelector('.sidebar');

        // Desktop collapse vs. mobile drawer
        if (window.innerWidth <= MOBILE_MAX) {
        body.classList.toggle('kaya-drawer-open');
        } else {
        body.classList.toggle('kaya-collapsed');
        }

        // SB-Admin's original toggles (safe no-ops if classes not present)
        body.classList.toggle('sidebar-toggled');
        if (sidebar) sidebar.classList.toggle('toggled');
    }

    // Hook up both toggles if present in nav.php
    ['#sidebarToggle', '#sidebarToggleTop'].forEach(function(sel){
        var btn = document.querySelector(sel);
        if (btn) { btn.removeEventListener('click', handleToggle); btn.addEventListener('click', handleToggle); }
    });

    // Close drawer on resize up to desktop
    window.addEventListener('resize', function(){
        if (window.innerWidth > MOBILE_MAX) body.classList.remove('kaya-drawer-open');
    });
    })();

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
