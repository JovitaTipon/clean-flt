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
  function fetch_one(mysqli $db, string $sql){
    if (!$res = $db->query($sql)) return null;
    $row = $res->fetch_assoc();
    $res->close();
    return $row ?: null;
  }

  // ---------- What exists? ----------
  $hasVehiclesTbl   = table_exists($mysqli,'vehicles');
  $hasBookingsTbl   = table_exists($mysqli,'bookings');
  $hasAccountsTbl   = table_exists($mysqli,'accounts');
  $hasDriverProfTbl = table_exists($mysqli,'driver_profile');
  $hasFleetView     = table_exists($mysqli,'v_fleet_summary');

  $hasTmsVehicle    = table_exists($mysqli,'tms_vehicle');
  $hasTmsUser       = table_exists($mysqli,'tms_user');
  $hasTmsDriver     = table_exists($mysqli,'tms_user_add_driver');
  $hasAudit         = table_exists($mysqli,'tms_audit_log');

  // ---------- KPIs (prefer new schema / view) ----------

  // Vehicles block
  if ($hasFleetView) {
    $sum = fetch_one($mysqli, "SELECT total_vehicles, vehicles_available, vehicles_in_use,
                                      vehicles_maintenance, vehicles_inactive,
                                      trips_today, trips_in_progress, drivers_active_today
                                 FROM v_fleet_summary");
    $vehicleTotal     = (int)($sum['total_vehicles']        ?? 0);
    $vehicleAvailable = (int)($sum['vehicles_available']    ?? 0);
    $vehicleOnTrip    = (int)($sum['vehicles_in_use']       ?? 0); // vehicles currently in use
    $vehicleMaint     = (int)($sum['vehicles_maintenance']  ?? 0);
  } elseif ($hasVehiclesTbl) {
    $vehicleTotal     = count_q($mysqli,"SELECT COUNT(*) FROM vehicles");
    $vehicleAvailable = count_q($mysqli,"SELECT COUNT(*) FROM vehicles WHERE status='available'");
    $vehicleOnTrip    = count_q($mysqli,"SELECT COUNT(*) FROM vehicles WHERE status='in_use'");
    $vehicleMaint     = count_q($mysqli,"SELECT COUNT(*) FROM vehicles WHERE status='maintenance'");
  } elseif ($hasTmsVehicle) {
    // legacy fallback
    $vehicleTotal     = count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle");
    $vehicleAvailable = count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle WHERE v_status='Available'");
    $vehicleOnTrip    = count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle WHERE v_status IN ('Booked','On Trip')");
    $vehicleMaint     = count_q($mysqli,"SELECT COUNT(*) FROM tms_vehicle WHERE v_status IN ('Undermaintenance','Maintenance')");
  } else {
    $vehicleTotal = $vehicleAvailable = $vehicleOnTrip = $vehicleMaint = 0;
  }

  // Drivers block
  if ($hasDriverProfTbl) {
    $driverTotal     = $hasAccountsTbl ? count_q($mysqli,"SELECT COUNT(*) FROM accounts WHERE role='driver' AND is_active=1") : 0;
    $driverAvailable = count_q($mysqli,"SELECT COUNT(*) FROM driver_profile WHERE current_status='available'");
    $driverOnTrip    = count_q($mysqli,"SELECT COUNT(*) FROM driver_profile WHERE current_status='on_trip'");
  } elseif ($hasAccountsTbl) {
    $driverTotal     = count_q($mysqli,"SELECT COUNT(*) FROM accounts WHERE role='driver' AND is_active=1");
    // heuristic from bookings if profile table not present
    if ($hasBookingsTbl) {
      $driverOnTrip    = count_q($mysqli,"SELECT COUNT(DISTINCT driver_id) FROM bookings WHERE status IN ('accepted','in_progress') AND driver_id IS NOT NULL");
      $driverAvailable = max(0, $driverTotal - $driverOnTrip);
    } else {
      $driverOnTrip = 0; $driverAvailable = $driverTotal;
    }
  } elseif ($hasTmsDriver) {
    // legacy fallback
    $driverTotal     = count_q($mysqli,"SELECT COUNT(*) FROM tms_user_add_driver WHERE u_category='Driver'");
    $driverAvailable = count_q($mysqli,"SELECT COUNT(*) FROM tms_user_add_driver WHERE u_category='Driver' AND u_car_book_status LIKE 'Available%'");
    $driverOnTrip    = count_q($mysqli,"SELECT COUNT(*) FROM tms_user_add_driver WHERE u_category='Driver' AND (u_car_book_status LIKE 'On Trip%' OR u_car_book_status LIKE 'Booked%')");
  } else {
    $driverTotal = $driverAvailable = $driverOnTrip = 0;
  }

  // Bookings block + Trips Today
  if ($hasBookingsTbl) {
    $upcomingCnt  = count_q($mysqli,"SELECT COUNT(*) FROM bookings WHERE status IN ('pending','awaiting_driver','accepted')");
    $completedCnt = count_q($mysqli,"SELECT COUNT(*) FROM bookings WHERE status='completed'");
    $cancelledCnt = count_q($mysqli,"SELECT COUNT(*) FROM bookings WHERE status='cancelled'");
    $tripsToday   = count_q($mysqli,"SELECT COUNT(*) FROM bookings WHERE DATE(scheduled_start_at)=CURDATE()");
  } elseif ($hasTmsUser) {
    // legacy fallback
    $upcomingCnt  = count_q($mysqli,"SELECT COUNT(*) FROM tms_user WHERE u_car_book_status IN ('Pending','Approved')");
    $completedCnt = count_q($mysqli,"SELECT COUNT(*) FROM tms_user WHERE u_car_book_status='Completed'");
    $cancelledCnt = count_q($mysqli,"SELECT COUNT(*) FROM tms_user WHERE u_car_book_status IN ('Cancel','Cancelled')");
    $tripsToday   = count_q($mysqli,"SELECT COUNT(*) FROM tms_user
                                     WHERE (u_car_date = CURDATE()
                                         OR DATE(u_car_bookdate) = CURDATE()
                                         OR DATE(u_car_createdat) = CURDATE())");
  } else {
    $upcomingCnt = $completedCnt = $cancelledCnt = $tripsToday = 0;
  }
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
          </div>
        </section>

        <!-- ===== Two-up cards: Recent Bookings + Live Vehicles ===== -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

          <!-- Recent Bookings -->
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
                  <?php if ($hasBookingsTbl):
                    $q = $mysqli->prepare("
                      SELECT b.contact_name,
                             COALESCE(a.name,'—') AS driver_name,
                             b.scheduled_start_at,
                             b.pickup_point, b.dropoff_point,
                             b.status
                        FROM bookings b
                   LEFT JOIN accounts a ON a.id=b.driver_id
                    ORDER BY b.created_at DESC
                       LIMIT 8
                    ");
                    $q->execute();
                    $res = $q->get_result();
                    while($b = $res->fetch_object()):
                      $when = $b->scheduled_start_at ? date('M j, Y g:i A', strtotime($b->scheduled_start_at)) : '—';
                      $st   = (string)$b->status;
                      // badge map (new schema)
                      $badge  = in_array($st,['in_progress']) ? 'bg-green-100 text-green-700'
                              : ($st==='accepted'              ? 'bg-blue-100  text-blue-700'
                              : ($st==='completed'             ? 'bg-blue-100  text-blue-700'
                              : ($st==='cancelled'             ? 'bg-red-100   text-red-700'
                              : ($st==='rejected'              ? 'bg-red-100   text-red-700'
                              : 'bg-gray-100 text-gray-700'))));
                  ?>
                  <tr>
                    <td class="py-2 pr-4"><?= htmlspecialchars($b->contact_name ?: '—') ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($b->driver_name) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($when) ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($b->pickup_point) ?> → <?= htmlspecialchars($b->dropoff_point) ?></td>
                    <td class="py-2 pr-4"><span class="px-2 py-1 rounded <?= $badge ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ', $st))) ?></span></td>
                  </tr>
                  <?php endwhile; $q->close(); ?>
                  <?php elseif ($hasTmsUser): // legacy fallback ?>
                  <?php
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

          <!-- Live Vehicles -->
          <section class="bg-white rounded-2xl shadow p-6">
            <h3 class="text-base font-semibold text-kaya-ink mb-4">Live Vehicles</h3>
            <div class="overflow-x-auto">
              <table class="min-w-full text-left text-sm">
                <thead>
                  <tr class="text-gray-500">
                    <th class="py-2 pr-4 font-medium">Vehicle</th>
                    <th class="py-2 pr-4 font-medium">Status</th>
                    <th class="py-2 pr-4 font-medium">Pick Up</th>
                    <th class="py-2 pr-4 font-medium">Destination</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <?php if ($hasVehiclesTbl):
                    // vehicles + latest accepted/in_progress booking (if any)
                    $sql = "
                      SELECT v.name AS vehicle_name, v.plate_no, v.status AS vehicle_status,
                             (SELECT b.pickup_point
                                FROM bookings b
                               WHERE b.vehicle_id=v.id AND b.status IN ('accepted','in_progress')
                               ORDER BY b.scheduled_start_at DESC LIMIT 1) AS last_pick,
                             (SELECT b.dropoff_point
                                FROM bookings b
                               WHERE b.vehicle_id=v.id AND b.status IN ('accepted','in_progress')
                               ORDER BY b.scheduled_start_at DESC LIMIT 1) AS last_dest,
                             (SELECT b.status
                                FROM bookings b
                               WHERE b.vehicle_id=v.id AND b.status IN ('accepted','in_progress')
                               ORDER BY b.scheduled_start_at DESC LIMIT 1) AS booking_status
                        FROM vehicles v
                       ORDER BY FIELD(v.status,'in_use','maintenance','available','inactive'), v.name
                       LIMIT 8
                    ";
                    if ($res = $mysqli->query($sql)):
                      while($v = $res->fetch_object()):
                        $status = $v->booking_status ?: $v->vehicle_status;
                        $status_lc = strtolower((string)$status);
                        $badge  = $status_lc==='in_progress' ? 'bg-green-100 text-green-700'
                                : ($status_lc==='accepted'    ? 'bg-blue-100  text-blue-700'
                                : ($status_lc==='maintenance' ? 'bg-yellow-100 text-yellow-700'
                                : ($status_lc==='inactive'    ? 'bg-red-100   text-red-700'
                                                              : 'bg-gray-100  text-gray-700')));
                  ?>
                  <tr>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->vehicle_name) ?> (<?= htmlspecialchars($v->plate_no) ?>)</td>
                    <td class="py-2 pr-4"><span class="px-2 py-1 rounded <?= $badge ?>"><?= htmlspecialchars(ucwords(str_replace('_',' ', $status))) ?></span></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->last_pick ?: '—') ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->last_dest ?: '—') ?></td>
                  </tr>
                  <?php endwhile; else: ?>
                  <tr><td class="py-3 text-gray-500" colspan="4">No vehicle data.</td></tr>
                  <?php endif; ?>

                  <?php elseif ($hasTmsVehicle): // legacy fallback ?>
                  <?php
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
                        $badge  = $st==='Available'        ? 'bg-green-100 text-green-700'
                                : ($st==='Booked'          ? 'bg-blue-100  text-blue-700'
                                : ($st==='On Trip'         ? 'bg-blue-100  text-blue-700'
                                : ($st==='Undermaintenance'? 'bg-yellow-100 text-yellow-700'
                                                             : 'bg-gray-100 text-gray-700')));
                  ?>
                  <tr>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->v_reg_no) ?> — <?= htmlspecialchars($v->v_driver) ?></td>
                    <td class="py-2 pr-4"><span class="px-2 py-1 rounded <?= $badge ?>"><?= htmlspecialchars($st) ?></span></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->last_pick ?: '—') ?></td>
                    <td class="py-2 pr-4"><?= htmlspecialchars($v->last_dest ?: '—') ?></td>
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
