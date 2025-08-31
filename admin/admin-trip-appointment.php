<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid = require_admin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <title>Vehicle - Trip Appointments</title>
  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet">
  <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
  <link href="vendor/css/sb-admin.css" rel="stylesheet">
  <!-- Inter font (global) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}</style>

  <style>
    /* Neutralize SB-Admin sticky footer on this page */
    footer.sticky-footer{
      background: transparent !important;
      height: 0 !important;
      border: 0 !important;
      box-shadow: none !important;
    }
    footer.sticky-footer .container,
    footer.sticky-footer .copyright{
      display: none !important;
    }
    /* Remove the extra bottom padding SB-Admin adds for its footer */
    #wrapper #content-wrapper{
      padding-bottom: 0 !important;
    }
  </style>

</head>
<body id="page-top">

  <?php include('vendor/inc/nav.php'); ?>

  <div id="wrapper">
    <?php include('vendor/inc/sidebar.php'); ?>

    <div id="content-wrapper">
      <div class="container-fluid">

        <h1 class="kaya-page-title">Trip Appointments</h1>

        <!-- Simple actions (buttons link to your existing pages) -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="btn-group" role="group" aria-label="Filters">
            <a href="admin-trip-appointment.php" class="btn kaya-tab active">Upcoming</a>
            <a href="admin-view-booking.php"   class="btn kaya-tab">Completed</a>
          </div>

          <!-- was: <div class="ml-auto"> -->
          <div class="kaya-actions ml-auto btn-group" role="group" aria-label="Actions">
            <a href="admin-create-booking.php" class="btn btn-kaya-primary">New Trip</a>
            <a href="admin-manage-booking.php" class="btn btn-kaya-danger-outline">Cancel</a>
          </div>
        </div>



        <!-- Table card -->
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
                  <th>Pick Up location</th>
                  <th>Destination</th>
                  <th>Reg No.</th>
                  <th>Vehicle Type</th>
                  <th>Driver</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
              <?php
                $ret="SELECT * FROM tms_user ORDER BY u_id DESC";
                $stmt= $mysqli->prepare($ret); $stmt->execute(); $res=$stmt->get_result(); $cnt=1;
                while($row=$res->fetch_object()){
                  $datetime = $row->u_car_createdat ?? null;
                  $date = $datetime ? date("M j, Y", strtotime($datetime)) : ($row->u_car_date ?? '');
                  $time = $datetime ? date("h:i A", strtotime($datetime)) : ($row->u_car_time ?? '');
                  $status = $row->u_car_book_status ?? 'Pending';
                  $chip = 'badge badge-secondary';
                  if ($status==='Pending')      $chip='badge badge-light';
                  if ($status==='Approved' || $status==='Completed' || $status==='Available') $chip='badge badge-success';
                  if ($status==='Maintenance')  $chip='badge badge-warning';
                  if ($status==='In Active')    $chip='badge badge-danger';
              ?>
                <tr>
                  <td><?= $cnt; ?></td>
                  <td><?= htmlspecialchars($date); ?></td>
                  <td><?= htmlspecialchars($time); ?></td>
                  <td><?= htmlspecialchars($row->u_fname.' '.$row->u_lname); ?></td>
                  <td><?= htmlspecialchars($row->u_car_pax); ?></td>
                  <td><?= htmlspecialchars($row->u_car_pickup); ?></td>
                  <td><?= htmlspecialchars($row->u_car_destination); ?></td>
                  <td><?= htmlspecialchars($row->u_car_regno); ?></td>
                  <td><?= htmlspecialchars($row->u_car_type); ?></td>
                  <td><?= htmlspecialchars($row->u_car_driver); ?></td>
                  <td><span class="<?= $chip; ?> px-2 py-1"><?= htmlspecialchars($status); ?></span></td>
                </tr>
              <?php $cnt++; } $stmt->close(); ?>
              </tbody>
            </table>
          </div>
        </div>

      </div>
      <?php include("vendor/inc/footer.php");?>
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
    (function () {
    var btn = document.getElementById('sidebarToggle');
    if (!btn) return;
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        // SB-Admin convention
        document.body.classList.toggle('sidebar-toggled');
        // Your rail markup
        var rail = document.getElementById('kayaSidebar');
        if (rail) rail.classList.toggle('kaya-rail--collapsed');
    });

    // Keep the sidebar flush under the fixed navbar
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
