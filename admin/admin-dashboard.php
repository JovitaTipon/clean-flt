<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="fleet monitoring system">
  <meta name="author" content="kaya">
  <title>Vehicle - Admin Dashboard</title>

  <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
  <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet"><!-- ok to keep or remove -->
  <link href="vendor/css/sb-admin.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">

  <script src="https://cdn.tailwindcss.com"></script>
  <script>
  tailwind.config = {
    theme: {
      extend: {
        fontFamily: { sans: ['Inter','ui-sans-serif','system-ui'] },
        colors: { kaya:{ navy:'#0A0F2C', ink:'#000047', cloud:'#F3F4F6' } },
        borderRadius: { '2xl':'1rem' }
      }
    }
  }
  </script>
  <style>html,body{font-family:Inter,system-ui,-apple-system,Segoe UI,Roboto,sans-serif}</style>
</head>

<body id="page-top">
  <?php include("vendor/inc/nav.php");?>

  <div id="wrapper">
    <?php include("vendor/inc/sidebar.php");?>

    <div id="content-wrapper">
      <div class="container-fluid">

        <?php
          // Counters
          $active_q = "SELECT COUNT(*) FROM tms_user WHERE u_car_book_status='Available'";
          $maint_q  = "SELECT COUNT(*) FROM tms_user WHERE u_car_book_status='Maintenance'";
          $route_q  = "SELECT COUNT(*) FROM tms_user WHERE u_car_book_status IN ('Approved','On Route')";
          $trips_q  = "SELECT COUNT(*) FROM tms_user WHERE DATE(u_car_bookdate)=CURDATE()";
          function get_count($mysqli,$sql){ $s=$mysqli->prepare($sql); $s->execute(); $s->bind_result($n); $s->fetch(); $s->close(); return (int)$n; }
          $activeVehicles = get_count($mysqli,$active_q);
          $maintenance    = get_count($mysqli,$maint_q);
          $onRoute        = get_count($mysqli,$route_q);
          $tripsToday     = get_count($mysqli,$trips_q);
        ?>

        <h1 class="text-3xl font-bold text-kaya-ink mb-6">Admin Dashboard</h1>

        <!-- Fleet Summary -->
        <section class="bg-kaya-navy text-white rounded-2xl p-6 mb-8">
          <h2 class="text-lg font-semibold mb-4">Fleet Summary</h2>
          <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
            <div>
              <div class="text-sm/5 opacity-70">Active Vehicles</div>
              <div class="text-4xl font-bold mt-1"><?= $activeVehicles ?></div>
            </div>
            <div>
              <div class="text-sm/5 opacity-70">Maintenance Alert</div>
              <div class="text-4xl font-bold mt-1"><?= $maintenance ?></div>
            </div>
            <div>
              <div class="text-sm/5 opacity-70">On Route</div>
              <div class="text-4xl font-bold mt-1"><?= $onRoute ?></div>
            </div>
            <div>
              <div class="text-sm/5 opacity-70">Trips Today</div>
              <div class="text-4xl font-bold mt-1"><?= $tripsToday ?></div>
            </div>
          </div>
        </section>

        <!-- Two cards -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">
          <!-- Recent Alerts -->
          <section class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-base font-semibold text-kaya-ink mb-4">Recent Alerts</h3>
            <ul class="space-y-3 text-sm">
              <?php
                $alerts = $mysqli->query("SELECT u_car_regno,u_car_type,u_car_book_status FROM tms_user WHERE u_car_book_status IN ('Maintenance','In Active','Pending') ORDER BY u_id DESC LIMIT 6");
                if($alerts){ while($a=$alerts->fetch_object()){ ?>
                  <li class="text-red-600">
                    <?= htmlspecialchars($a->u_car_book_status) ?> — Vehicle <?= htmlspecialchars($a->u_car_regno) ?> (<?= htmlspecialchars($a->u_car_type) ?>)
                  </li>
                <?php } } else { ?>
                  <li class="text-gray-500">No alerts.</li>
              <?php } ?>
            </ul>
          </section>

          <!-- Live Vehicles -->
          <section class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-base font-semibold text-kaya-ink mb-4">Live Vehicles</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full text-left text-sm">
                <thead>
                  <tr class="text-gray-500">
                    <th class="py-2 pr-4 font-medium">Vehicles</th>
                    <th class="py-2 pr-4 font-medium">Driver</th>
                    <th class="py-2 pr-4 font-medium">Status</th>
                    <th class="py-2 pr-4 font-medium">Pickup</th>
                    <th class="py-2 pr-4 font-medium">Destination</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php
                    $ret="SELECT u_car_regno,u_car_driver,u_car_book_status,u_car_pickup,u_car_destination FROM tms_user ORDER BY u_id DESC LIMIT 8";
                    $stmt= $mysqli->prepare($ret); $stmt->execute(); $res=$stmt->get_result();
                    while($row=$res->fetch_object()){
                      $status = htmlspecialchars($row->u_car_book_status);
                      $badge  = $status==='Available' ? 'bg-green-100 text-green-700'
                            : ($status==='Maintenance' ? 'bg-yellow-100 text-yellow-700'
                            : ($status==='In Active' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700'));
                  ?>
                  <tr>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_regno) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_driver) ?></td>
                    <td class="py-2 pr-4"><span class="px-2 py-1 rounded <?= $badge ?>"><?= $status ?></span></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_pickup) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_destination) ?></td>
                  </tr>
                  <?php } $stmt->close(); ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <!-- Removed legacy "Bookings" tables -->
      </div><!-- /.container-fluid -->
    </div><!-- /#content-wrapper -->
  </div><!-- /#wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header"><h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
        </div>
        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
          <a class="btn btn-danger" href="admin-logout.php">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- JS -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="vendor/chart.js/Chart.min.js"></script><!-- you can remove if unused -->
  <script src="vendor/datatables/jquery.dataTables.js"></script><!-- you can remove if unused -->
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script><!-- you can remove if unused -->
  <script src="vendor/js/sb-admin.min.js"></script>
  <script src="vendor/js/demo/datatables-demo.js"></script><!-- safe to remove -->
  <script src="vendor/js/demo/chart-area-demo.js"></script><!-- safe to remove -->
</body>
</html>
