<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();

  // ---------- Helpers ----------
  function count_q(mysqli $db, string $sql){
    if(!$stmt = $db->prepare($sql)) return 0;
    $stmt->execute();
    $stmt->bind_result($n);
    $stmt->fetch();
    $stmt->close();
    return (int)$n;
  }
  function table_exists(mysqli $db, string $table){
    $t = $db->real_escape_string($table);
    $res = $db->query("SHOW TABLES LIKE '{$t}'");
    return $res && $res->num_rows > 0;
  }

  // ---------- VEHICLES (tms_vehicle) ----------
  $vehicleTotal      = table_exists($mysqli,'tms_vehicle') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle") : 0;
  $vehicleAvailable  = table_exists($mysqli,'tms_vehicle') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle WHERE v_status='Available'") : 0;
  $vehicleOnTrip     = table_exists($mysqli,'tms_vehicle') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle WHERE v_status IN ('Booked','On Trip')") : 0;
  $vehicleMaint      = table_exists($mysqli,'tms_vehicle') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle WHERE v_status IN ('Undermaintenance','Maintenance')") : 0;

  // ---------- DRIVERS (tms_user_add_driver) ----------
  $driverTotal       = table_exists($mysqli,'tms_user_add_driver') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_user_add_driver WHERE u_category='Driver'") : 0;
  $driverAvailable   = table_exists($mysqli,'tms_user_add_driver') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_user_add_driver WHERE u_category='Driver' AND u_car_book_status LIKE 'Available%'") : 0;
  $driverOnTrip      = table_exists($mysqli,'tms_user_add_driver') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_user_add_driver WHERE u_category='Driver' AND (u_car_book_status LIKE 'On Trip%' OR u_car_book_status LIKE 'Booked%')") : 0;

  // ---------- BOOKINGS / TRIPS (tms_user) ----------
  $upcomingCnt       = table_exists($mysqli,'tms_user') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_user WHERE u_car_book_status IN ('Pending','Approved')") : 0;
  $completedCnt      = table_exists($mysqli,'tms_user') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_user WHERE u_car_book_status='Completed'") : 0;
  $cancelledCnt      = table_exists($mysqli,'tms_user') ? count_q($mysqli,"SELECT COUNT(*) FROM tms_user WHERE u_car_book_status IN ('Cancel','Cancelled')") : 0;
  $tripsToday        = table_exists($mysqli,'tms_user') ? count_q($mysqli,"
                           SELECT COUNT(*) FROM tms_user
                           WHERE (u_car_date = CURDATE()
                               OR DATE(u_car_bookdate) = CURDATE()
                               OR DATE(u_car_createdat) = CURDATE())
                        ") : 0;

  $hasAudit = table_exists($mysqli,'tms_audit_log');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include('vendor/inc/head.php'); ?>

  <!-- Inter + tiny Tailwind token usage (cards/grid only) -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    tailwind.config = {
      theme: {
        extend: {
          fontFamily: { sans: ['Inter','ui-sans-serif','system-ui'] },
          colors: { kaya:{ navy:'#0A0F2C', ink:'#000047' } },
          borderRadius: { '2xl':'1rem' }
        }
      }
    }
  </script>
  <style>html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}</style>
</head>

<body id="page-top">
  <?php include('vendor/inc/nav.php'); ?>

  <div id="wrapper">
    <?php include('vendor/inc/sidebar.php'); ?>

    <div id="content-wrapper">
      <div class="container-fluid">

        <h1 class="kaya-page-title">Admin Dashboard</h1>

        <!-- ===== High-level KPIs ===== -->
        <section class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <!-- Vehicles -->
          <div class="bg-white rounded-2xl shadow p-5">
            <div class="text-sm text-gray-500 font-semibold">Vehicles</div>
            <div class="mt-3 grid grid-cols-2 gap-3 text-center">
              <div>
                <div class="text-xs text-gray-500">Total</div>
                <div class="text-3xl font-bold"><?= $vehicleTotal ?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Available</div>
                <div class="text-3xl font-bold"><?= $vehicleAvailable ?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">On Trip</div>
                <div class="text-3xl font-bold"><?= $vehicleOnTrip ?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Maintenance</div>
                <div class="text-3xl font-bold"><?= $vehicleMaint ?></div>
              </div>
            </div>
          </div>

          <!-- Drivers -->
          <div class="bg-white rounded-2xl shadow p-5">
            <div class="text-sm text-gray-500 font-semibold">Drivers</div>
            <div class="mt-3 grid grid-cols-3 gap-3 text-center">
              <div>
                <div class="text-xs text-gray-500">Total</div>
                <div class="text-3xl font-bold"><?= $driverTotal ?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Available</div>
                <div class="text-3xl font-bold"><?= $driverAvailable ?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">On Trip</div>
                <div class="text-3xl font-bold"><?= $driverOnTrip ?></div>
              </div>
            </div>
          </div>

          <!-- Bookings -->
          <div class="bg-white rounded-2xl shadow p-5">
            <div class="text-sm text-gray-500 font-semibold">Bookings</div>
            <div class="mt-3 grid grid-cols-3 gap-3 text-center">
              <div>
                <div class="text-xs text-gray-500">Upcoming</div>
                <div class="text-3xl font-bold"><?= $upcomingCnt ?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Completed</div>
                <div class="text-3xl font-bold"><?= $completedCnt ?></div>
              </div>
              <div>
                <div class="text-xs text-gray-500">Cancelled</div>
                <div class="text-3xl font-bold"><?= $cancelledCnt ?></div>
              </div>
            </div>
          </div>

          <!-- Today -->
          <div class="bg-kaya-navy text-white rounded-2xl p-5">
            <div class="text-sm font-semibold opacity-90">Trips Today</div>
            <div class="text-5xl font-extrabold mt-2"><?= $tripsToday ?></div>
            <!--<div class="text-xs opacity-80 mt-2">Based on date in bookings (tms_user)</div>-->
          </div>
        </section>

        <!-- ===== Two-up cards: Recent Bookings + Live Vehicles ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          <!-- Recent Bookings (tms_user) -->
          <section class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-base font-semibold text-kaya-ink mb-4">Recent Bookings</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full text-left text-sm">
                <thead>
                  <tr class="text-gray-500">
                    <th class="py-2 pr-4 font-medium">Customer</th>
                    <th class="py-2 pr-4 font-medium">Driver</th>
                    <th class="py-2 pr-4 font-medium">When</th>
                    <th class="py-2 pr-4 font-medium">From → To</th>
                    <th class="py-2 pr-4 font-medium">Status</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php if (table_exists($mysqli,'tms_user')):
                    $q = $mysqli->prepare("
                      SELECT u_fname, u_lname, u_car_driver, u_car_date, u_car_time,
                             u_car_pickup, u_car_destination, u_car_book_status
                      FROM tms_user
                      ORDER BY u_id DESC
                      LIMIT 8
                    ");
                    $q->execute();
                    $res = $q->get_result();
                    while($b = $res->fetch_object()):
                      $when = trim(($b->u_car_date ?: '').' '.($b->u_car_time ?: ''));
                      $st   = $b->u_car_book_status ?: 'Pending';
                      $badge  = $st==='Available'   ? 'bg-green-100 text-green-700'
                              : ($st==='Approved'   ? 'bg-green-100 text-green-700'
                              : ($st==='Completed'  ? 'bg-blue-100  text-blue-700'
                              : ($st==='Maintenance'? 'bg-yellow-100 text-yellow-700'
                              : ($st==='In Active'  ? 'bg-red-100 text-red-700'
                              : ($st==='Cancel'     ? 'bg-red-100 text-red-700'
                                                    : 'bg-gray-100 text-gray-700')))));
                  ?>
                  <tr>
                    <td class="py-2 pr-4"><?= htmlspecialchars(trim($b->u_fname.' '.$b->u_lname)) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($b->u_car_driver) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($when) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($b->u_car_pickup) ?> → <?= htmlspecialchars($b->u_car_destination) ?></td>
                    <td class="py-2 pr-4"><span class="px-2 py-1 rounded <?= $badge ?>"><?= htmlspecialchars($st) ?></span></td>
                  </tr>
                  <?php endwhile; $q->close(); else: ?>
                  <tr><td class="py-3 text-gray-500" colspan="5">No bookings table found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>

          <!-- Live Vehicles (tms_vehicle + latest route from tms_user) -->
          <section class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-base font-semibold text-kaya-ink mb-4">Live Vehicles</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full text-left text-sm">
                <thead>
                  <tr class="text-gray-500">
                    <th class="py-2 pr-4 font-medium">Reg No.</th>
                    <th class="py-2 pr-4 font-medium">Driver</th>
                    <th class="py-2 pr-4 font-medium">Status</th>
                    <th class="py-2 pr-4 font-medium">Last From → To</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php if (table_exists($mysqli,'tms_vehicle')):
                    $sql = "
                      SELECT v.v_reg_no, v.v_driver, v.v_status,
                             (SELECT u_car_pickup FROM tms_user u WHERE u.u_car_regno=v.v_reg_no ORDER BY u_id DESC LIMIT 1) AS last_pick,
                             (SELECT u_car_destination FROM tms_user u WHERE u.u_car_regno=v.v_reg_no ORDER BY u_id DESC LIMIT 1) AS last_dest
                      FROM tms_vehicle v
                      ORDER BY v.v_id DESC
                      LIMIT 8
                    ";
                    if ($res = $mysqli->query($sql)):
                      while($v = $res->fetch_object()):
                        $st = $v->v_status ?: '—';
                        $badge  = $st==='Available'     ? 'bg-green-100 text-green-700'
                                : ($st==='Booked'       ? 'bg-blue-100  text-blue-700'
                                : ($st==='On Trip'      ? 'bg-blue-100  text-blue-700'
                                : ($st==='Undermaintenance' ? 'bg-yellow-100 text-yellow-700'
                                                            : 'bg-gray-100 text-gray-700')));
                  ?>
                  <tr>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->v_reg_no) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->v_driver) ?></td>
                    <td class="py-2 pr-4"><span class="px-2 py-1 rounded <?= $badge ?>"><?= htmlspecialchars($st) ?></span></td>
                    <td class="py-2 pr-4">
                      <?= htmlspecialchars($v->last_pick ?: '—') ?> → <?= htmlspecialchars($v->last_dest ?: '—') ?>
                    </td>
                  </tr>
                  <?php endwhile; else: ?>
                  <tr><td class="py-3 text-gray-500" colspan="4">No vehicle data.</td></tr>
                  <?php endif; else: ?>
                  <tr><td class="py-3 text-gray-500" colspan="4">No vehicles table found.</td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </section>
        </div>

        <!-- ===== Optional: Recent Activity (tms_audit_log) =====
        <?php if ($hasAudit): ?>
        <section class="bg-white rounded-2xl shadow p-6 mb-8">
          <h3 class="text-base font-semibold text-kaya-ink mb-4">Recent Activity</h3>
          <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
              <thead>
                <tr class="text-gray-500">
                  <th class="py-2 pr-4 font-medium">When</th>
                  <th class="py-2 pr-4 font-medium">Actor</th>
                  <th class="py-2 pr-4 font-medium">Action</th>
                  <th class="py-2 pr-4 font-medium">Booking #</th>
                  <th class="py-2 pr-4 font-medium">Details</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <?php
                  $log = $mysqli->query("SELECT id, actor_type, actor_id, action, booking_u_id, details, created_at
                                         FROM tms_audit_log ORDER BY id DESC LIMIT 10");
                  if ($log && $log->num_rows):
                    while($L = $log->fetch_object()):
                ?>
                <tr>
                  <td class="py-2 pr-4"><?= htmlspecialchars($L->created_at) ?></td>
                  <td class="py-2 pr-4"><?= htmlspecialchars(ucfirst($L->actor_type)).' #'.(int)$L->actor_id ?></td>
                  <td class="py-2 pr-4"><?= htmlspecialchars($L->action) ?></td>
                  <td class="py-2 pr-4"><?= (int)$L->booking_u_id ?></td>
                  <td class="py-2 pr-4">
                    <code class="text-gray-600">
                      <?= htmlspecialchars($L->details ?: '') ?>
                    </code>
                  </td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td class="py-3 text-gray-500" colspan="5">No activity yet.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </section>
        <?php endif; ?>
                -->

      </div><!-- /.container-fluid -->

      <?php include('vendor/inc/footer.php'); ?>
    </div><!-- /#content-wrapper -->
  </div><!-- /#wrapper -->

  <!-- Vendor JS -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
</body>
</html>
