<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php
    // Shared <head> (Bootstrap, sb-admin.css, meta, etc.)
    // Keep this on every page for consistency.
    include('vendor/inc/head.php');
  ?>

  <!-- Optional: font + Tailwind for the dashboard cards -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    // Tailwind tokens used by the summary + card sections
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
  <style>html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}</style>
</head>

<body id="page-top">
  <!-- Fixed top navbar (contains #sidebarToggle). Keep this include on all pages. -->
  <?php include('vendor/inc/nav.php'); ?>

  <div id="wrapper">
    <!-- Left rail (id="kayaSidebar"). Keep this include on all pages. -->
    <?php include('vendor/inc/sidebar.php'); ?>

    <!-- Main content pane -->
    <div id="content-wrapper">
      <div class="container-fluid">

        <?php
          // --- Fetch dashboard counters (small helper to avoid duplication) ---
          function get_count($mysqli,$sql){
            $s = $mysqli->prepare($sql);
            $s->execute();
            $s->bind_result($n);
            $s->fetch();
            $s->close();
            return (int)$n;
          }

          $activeVehicles = get_count($mysqli, "SELECT COUNT(*) FROM tms_user WHERE u_car_book_status='Available'");
          $maintenance    = get_count($mysqli, "SELECT COUNT(*) FROM tms_user WHERE u_car_book_status='Maintenance'");
          $onRoute        = get_count($mysqli, "SELECT COUNT(*) FROM tms_user WHERE u_car_book_status IN ('Approved','On Route')");
          $tripsToday     = get_count($mysqli, "SELECT COUNT(*) FROM tms_user WHERE DATE(u_car_bookdate)=CURDATE()");
        ?>

        <!-- Page title: use the same class everywhere to keep typography consistent -->
        <h1 class="kaya-page-title">Admin Dashboard</h1>

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

        <!-- Two cards row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-10">

          <!-- Recent Alerts -->
          <section class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-base font-semibold text-kaya-ink mb-4">Recent Alerts</h3>
            <ul class="space-y-3 text-sm">
              <?php
                $alerts = $mysqli->query("
                  SELECT u_car_regno, u_car_type, u_car_book_status
                  FROM tms_user
                  WHERE u_car_book_status IN ('Maintenance','In Active','Pending')
                  ORDER BY u_id DESC
                  LIMIT 6
                ");
                if ($alerts && $alerts->num_rows) {
                  while ($a = $alerts->fetch_object()) {
                    echo '<li class="text-red-600">'
                       . htmlspecialchars($a->u_car_book_status)
                       . ' — Vehicle '
                       . htmlspecialchars($a->u_car_regno)
                       . ' ('
                       . htmlspecialchars($a->u_car_type)
                       . ')</li>';
                  }
                } else {
                  echo '<li class="text-gray-500">No alerts.</li>';
                }
              ?>
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
                    $stmt = $mysqli->prepare("
                      SELECT u_car_regno, u_car_driver, u_car_book_status, u_car_pickup, u_car_destination
                      FROM tms_user
                      ORDER BY u_id DESC
                      LIMIT 8
                    ");
                    $stmt->execute();
                    $res = $stmt->get_result();
                    while ($row = $res->fetch_object()) {
                      $status = htmlspecialchars($row->u_car_book_status);
                      // Simple status -> badge color mapping
                      $badge  = $status==='Available'   ? 'bg-green-100 text-green-700'
                              : ($status==='Maintenance'? 'bg-yellow-100 text-yellow-700'
                              : ($status==='In Active'  ? 'bg-red-100 text-red-700'
                                                        : 'bg-gray-100 text-gray-700'));
                  ?>
                  <tr>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_regno) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_driver) ?></td>
                    <td class="py-2 pr-4">
                      <span class="px-2 py-1 rounded <?= $badge ?>"><?= $status ?></span>
                    </td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_pickup) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($row->u_car_destination) ?></td>
                  </tr>
                  <?php } $stmt->close(); ?>
                </tbody>
              </table>
            </div>
          </section>

        </div><!-- /cards row -->

        <!-- Note: removed legacy "Bookings" tables to keep the dashboard clean -->
      </div><!-- /.container-fluid -->

      <!-- Shared footer include: contains the sidebar toggle script. Keep this on every page. -->
      <?php include('vendor/inc/footer.php'); ?>
    </div><!-- /#content-wrapper -->
  </div><!-- /#wrapper -->

  <!-- Vendor JS (Bootstrap, etc.) -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  
  <script src="vendor/chart.js/Chart.min.js"></script>
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
  <script src="vendor/js/demo/datatables-demo.js"></script>
  <script src="vendor/js/demo/chart-area-demo.js"></script>
  
</body>
</html>
