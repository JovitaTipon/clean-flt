<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid = $_SESSION['a_id'];
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

        <h1 class="kaya-page-title">Completed Trip Appointments</h1>

        <!-- Toolbar (tabs left, actions right) -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="btn-group" role="group" aria-label="Filters">
            <a href="admin-trip-appointment.php" class="btn kaya-tab">Upcoming</a>
            <a href="admin-view-booking.php" class="btn kaya-tab active">Completed</a>
          </div>
          <div class="kaya-actions ml-auto btn-group" role="group" aria-label="Actions">
            <a href="admin-create-booking.php" class="btn btn-kaya-primary">New Trip</a>
            <a href="admin-manage-booking.php" class="btn btn-kaya-danger-outline">Cancelled</a>
          </div>
        </div>

        <!-- Completed table -->
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
                </tr>
              </thead>
              <tbody>
                <?php
                  $ret = "SELECT * FROM tms_user WHERE u_car_book_status IN ('Approved','Completed') ORDER BY u_id DESC";
                  $stmt = $mysqli->prepare($ret);
                  $stmt->execute();
                  $res = $stmt->get_result();
                  $cnt = 0;
                  while ($row = $res->fetch_object()):
                    $status = $row->u_car_book_status;
                    if ($status == "Pending")      $badge = 'badge badge-warning';
                    elseif ($status == "Completed") $badge = 'badge badge-primary';
                    else                             $badge = 'badge badge-success';
                ?>
                <tr>
                  <td><?= $cnt; ?></td>
                  <td><?= htmlspecialchars($row->u_fname.' '.$row->u_lname); ?></td>
                  <td><?= htmlspecialchars($row->u_phone); ?></td>
                  <td><?= htmlspecialchars($row->u_car_type); ?></td>
                  <td><?= htmlspecialchars($row->u_car_regno); ?></td>
                  <td><?= htmlspecialchars($row->u_car_bookdate); ?></td>
                  <td><span class="<?= $badge ?> px-2 py-1"><?= htmlspecialchars($status); ?></span></td>
                </tr>
                <?php $cnt++; endwhile; ?>
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
    /* Kill the gray sticky footer background on this page */
    footer.sticky-footer{ background:transparent!important; height:0!important; border:0!important; box-shadow:none!important; }
    footer.sticky-footer .container, footer.sticky-footer .copyright{ display:none!important; }
    #wrapper #content-wrapper{ padding-bottom:0!important; }
  </style>
</body>
</html>
