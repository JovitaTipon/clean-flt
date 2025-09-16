<?php
/**
 * KAYA • Manage Vehicles (with Create + Assign Driver)
 * - Canonical includes (nav.php, sidebar.php, footer.php)
 * - Create Vehicle (in-page POST, with optional photo upload)
 * - Shows joined driver; Assign/Change via modal (server POST)
 * - DataTables for pagination/search/sort
 *
 * Requires:
 *   - tms_vehicle: v_id, v_name, v_reg_no, v_pass_no, v_category, v_status, v_dpic (nullable), driver_user_id (nullable)
 *   - tms_user: u_id, u_fname, u_lname, u_category ('Driver')
 */

session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = require_admin();

$mysqli->set_charset('utf8mb4');

/* ----------------- helpers ----------------- */
function column_exists(mysqli $db, string $table, string $col): bool {
  $t = $db->real_escape_string($table);
  $c = $db->real_escape_string($col);
  $r = $db->query("SHOW COLUMNS FROM `{$t}` LIKE '{$c}'");
  return $r && $r->num_rows > 0;
}
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

$has_driver_fk = column_exists($mysqli, 'tms_vehicle', 'driver_user_id');

/* ----------------- CREATE vehicle (POST) ----------------- */
if (isset($_POST['create_vehicle'])) {
  $v_name     = trim($_POST['v_name'] ?? '');
  $v_reg_no   = trim($_POST['v_reg_no'] ?? '');
  $v_pass_no  = (int)($_POST['v_pass_no'] ?? 0);
  $v_category = trim($_POST['v_category'] ?? 'Sedan');
  $v_status   = trim($_POST['v_status'] ?? 'Available');

  // Optional driver on create (only if FK column exists)
  $driver_id  = $has_driver_fk ? (int)($_POST['driver_user_id'] ?? 0) : 0;
  if ($driver_id <= 0) $driver_id = null;

  // Optional image upload
  $v_dpic_path = null;
  if (!empty($_FILES['v_dpic']['name']) && is_uploaded_file($_FILES['v_dpic']['tmp_name'])) {
    $allowed = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
    $mime = mime_content_type($_FILES['v_dpic']['tmp_name']);
    if (isset($allowed[$mime])) {
      $ext  = $allowed[$mime];
      $dir  = __DIR__ . '/vendor/img/vehicles';
      if (!is_dir($dir)) @mkdir($dir, 0775, true);
      $fname = 'veh_' . time() . '_' . mt_rand(1000,9999) . '.' . $ext;
      $dest  = $dir . '/' . $fname;
      if (@move_uploaded_file($_FILES['v_dpic']['tmp_name'], $dest)) {
        $v_dpic_path = 'vendor/img/vehicles/' . $fname; // web path to store in DB
      }
    }
  }

  // Insert (wrap in transaction if assigning driver)
  $mysqli->begin_transaction();
  try {
    if ($driver_id && $has_driver_fk) {
      // ensure 1:1 by clearing this driver from any other vehicle
      if ($s = $mysqli->prepare("UPDATE tms_vehicle SET driver_user_id=NULL WHERE driver_user_id=?")) {
        $s->bind_param('i', $driver_id); $s->execute(); $s->close();
      }
    }

    if ($has_driver_fk) {
      $sql = "INSERT INTO tms_vehicle (v_name, v_reg_no, v_pass_no, v_category, v_status, v_dpic, driver_user_id)
              VALUES (?,?,?,?,?,?,?)";
      if ($s = $mysqli->prepare($sql)) {
        $s->bind_param('ssisssi', $v_name, $v_reg_no, $v_pass_no, $v_category, $v_status, $v_dpic_path, $driver_id);
        $ok = $s->execute(); $s->close();
      } else { $ok=false; }
    } else {
      $sql = "INSERT INTO tms_vehicle (v_name, v_reg_no, v_pass_no, v_category, v_status, v_dpic)
              VALUES (?,?,?,?,?,?)";
      if ($s = $mysqli->prepare($sql)) {
        $s->bind_param('ssisss', $v_name, $v_reg_no, $v_pass_no, $v_category, $v_status, $v_dpic_path);
        $ok = $s->execute(); $s->close();
      } else { $ok=false; }
    }

    if (!$ok) throw new Exception('Insert failed');

    $mysqli->commit();
    echo "<script>
            setTimeout(function(){ swal('Created!','Vehicle has been added.','success'); }, 120);
            setTimeout(function(){ window.location.href='admin-manage-vehicle.php'; }, 1000);
          </script>";
  } catch (Throwable $e) {
    $mysqli->rollback();
    echo "<script>setTimeout(function(){ swal('Error','Could not create vehicle.','error'); }, 120);</script>";
  }
}

/* ----------------- ASSIGN driver (POST, no AJAX) ----------------- */
if ($has_driver_fk && isset($_POST['assign_driver'])) {
  $vehicle_id = (int)($_POST['assign_vehicle_id'] ?? 0);
  $driver_id  = (int)($_POST['assign_driver_id'] ?? 0); // 0 => unassign

  $mysqli->begin_transaction();
  try {
    // clear current driver for this vehicle
    if ($s = $mysqli->prepare("UPDATE tms_vehicle SET driver_user_id=NULL WHERE v_id=?")) {
      $s->bind_param('i', $vehicle_id); $s->execute(); $s->close();
    }
    if ($driver_id > 0) {
      // unhook this driver from any other vehicle
      if ($s = $mysqli->prepare("UPDATE tms_vehicle SET driver_user_id=NULL WHERE driver_user_id=?")) {
        $s->bind_param('i', $driver_id); $s->execute(); $s->close();
      }
      // assign
      if ($s = $mysqli->prepare("UPDATE tms_vehicle SET driver_user_id=? WHERE v_id=?")) {
        $s->bind_param('ii', $driver_id, $vehicle_id); $s->execute(); $s->close();
      }
    }
    $mysqli->commit();
    echo "<script>
            setTimeout(function(){ swal('Saved','Assignment updated.','success'); }, 120);
            setTimeout(function(){ window.location.href='admin-manage-vehicle.php'; }, 900);
          </script>";
  } catch (Throwable $e) {
    $mysqli->rollback();
    echo "<script>setTimeout(function(){ swal('Error','Could not assign driver.','error'); }, 120);</script>";
  }
}

/* ----------------- DELETE vehicle (POST) ----------------- */
if (isset($_POST['delete_vehicle'])) {
  $delete_id = (int) $_POST['delete_vehicle_id'];
  if ($stmt = $mysqli->prepare("DELETE FROM tms_vehicle WHERE v_id = ?")) {
    $stmt->bind_param("i", $delete_id);
    $ok = $stmt->execute();
    $stmt->close();

    if ($ok) {
      echo "<script>
              setTimeout(function(){ swal('Deleted!','Vehicle has been deleted.','success'); }, 120);
              setTimeout(function(){ window.location.href='admin-manage-vehicle.php'; }, 1000);
            </script>";
    } else {
      echo "<script>setTimeout(function(){ swal('Error','Something went wrong.','error'); }, 120);</script>";
    }
  }
}

/* ----------------- FETCH: vehicles + joined driver ----------------- */
$vehicles = [];
$sql = "SELECT v.v_id, v.v_name, v.v_reg_no, v.v_pass_no, v.v_category, v.v_status, v.v_dpic,
               u.u_id AS driver_id, u.u_fname, u.u_lname
        FROM tms_vehicle v
        LEFT JOIN tms_user u ON ".($has_driver_fk ? "u.u_id = v.driver_user_id" : "0")."
        ORDER BY v.v_id DESC";
if ($stmt = $mysqli->prepare($sql)) {
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) $vehicles[] = $row;
  $stmt->close();
}

/* ----------------- FETCH: drivers (+ their current vehicle) ----------------- */
$drivers = []; // u_id, name, current_vehicle_id
$sqlD = "SELECT u.u_id,
               TRIM(CONCAT(COALESCE(u.u_fname,''),' ',COALESCE(u.u_lname,''))) AS name,
               ".($has_driver_fk ? "(SELECT v_id FROM tms_vehicle WHERE driver_user_id=u.u_id LIMIT 1)" : "NULL")." AS current_vehicle_id
         FROM tms_user u
         WHERE u.u_category='Driver'
         ORDER BY u.u_id DESC";
if ($s = $mysqli->prepare($sqlD)) {
  $s->execute();
  $r = $s->get_result();
  while ($row = $r->fetch_assoc()) $drivers[] = $row;
  $s->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include('vendor/inc/head.php'); ?>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}
    .kaya-page-title{font-weight:800;font-size:2rem;line-height:1.1;color:#000047;margin:0 0 1rem}
    .kaya-card{background:#fff;border-radius:1rem;box-shadow:0 8px 24px rgba(0,0,0,.06);padding:1rem;border:1px solid #e5e7eb}
    .kaya-table thead th{font-weight:600;color:#6b7280;border:0}
    .kaya-table tbody td{border-top:1px solid #f1f5f9;vertical-align:middle}
    .btn-icon{width:34px;height:34px;display:inline-flex;align-items:center;justify-content:center;border-radius:.5rem;padding:0}
    .actions .btn-icon + .btn-icon{margin-left:.25rem}
    .status-available{color:#16a34a;font-weight:600}
    .status-service{color:#2563eb;font-weight:600}
    .status-maint{color:#dc2626;font-weight:600}
    .btn-kaya-primary{background:#0A0F2C;border:1px solid #0A0F2C;color:#fff}
    .btn-kaya-primary:hover{background:#0c1438;border-color:#0c1438;color:#fff}
    .veh-thumb{width:40px;height:28px;object-fit:cover;border-radius:.25rem;border:1px solid #e5e7eb;margin-right:.5rem}
  </style>
</head>
<body id="page-top">
  <?php include('vendor/inc/nav.php'); ?>
  <div id="wrapper">
    <?php include('vendor/inc/sidebar.php'); ?>
    <div id="content-wrapper">
      <div class="container-fluid">

        <h1 class="kaya-page-title">Manage Vehicles</h1>

        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="ml-auto">
            <button class="btn btn-kaya-primary" data-toggle="modal" data-target="#createVehicleModal">
              <i class="fas fa-plus mr-1"></i> New Vehicle
            </button>
          </div>
        </div>

        <div class="kaya-card">
          <div class="table-responsive">
            <table id="vehiclesTable" class="table kaya-table table-hover table-borderless align-middle">
              <thead class="thead-light">
                <tr>
                  <th style="width:56px">#</th>
                  <th>Vehicle</th>
                  <th>Driver</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th class="actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $n=1; foreach($vehicles as $v):
                  $vid = (int)$v['v_id'];
                  $regOrName = $v['v_reg_no'] ?: $v['v_name'];
                  $driverLabel = $has_driver_fk && $v['driver_id'] ? trim(($v['u_fname']??'').' '.($v['u_lname']??'')) : '—';

                  $raw = (string)($v['v_status'] ?? '');
                  $txt = $raw ?: 'Available';
                  $cls = 'status-service';
                  if (stripos($raw,'avail')!==false){ $txt='Available';   $cls='status-available'; }
                  if (stripos($raw,'service')!==false){ $txt='In Service'; $cls='status-service'; }
                  if (stripos($raw,'maint')!==false){ $txt='Maintenance'; $cls='status-maint'; }
                  if (stripos($raw,'book')!==false){ $txt='Booked';       $cls='status-service'; }
                ?>
                <tr>
                  <td><?= $n++; ?></td>
                  <td class="font-weight-semibold">
                    <?php if(!empty($v['v_dpic'])): ?>
                      <img class="veh-thumb" src="<?= h($v['v_dpic']) ?>" alt="">
                    <?php endif; ?>
                    <?= h($regOrName ?: '—') ?>
                  </td>
                  <td><?= h($driverLabel) ?></td>
                  <td><?= h($v['v_category']) ?></td>
                  <td class="<?= $cls ?>"><?= h($txt) ?></td>
                  <td class="actions">
                    <a href="admin-view-vehicle.php?v_id=<?= $vid ?>" class="btn btn-sm btn-outline-secondary btn-icon" data-toggle="tooltip" title="View">
                      <i class="fas fa-info-circle"></i><span class="sr-only">View</span>
                    </a>
                    <a href="admin-manage-single-vehicle.php?v_id=<?= $vid ?>" class="btn btn-sm btn-outline-secondary btn-icon" data-toggle="tooltip" title="Edit">
                      <i class="fas fa-pencil-alt"></i><span class="sr-only">Edit</span>
                    </a>
                    <?php if ($has_driver_fk): ?>
                      <button type="button"
                              class="btn btn-sm btn-outline-secondary btn-icon"
                              data-toggle="modal"
                              data-target="#assignDriverModal"
                              data-vehicle-id="<?= $vid ?>"
                              data-current-driver-id="<?= (int)($v['driver_id']??0) ?>"
                              title="Assign / Change Driver">
                        <i class="fas fa-exchange-alt"></i><span class="sr-only">Assign</span>
                      </button>
                    <?php endif; ?>
                    <a href="admin-view-syslogs.php?reg=<?= urlencode($v['v_reg_no']) ?>" class="btn btn-sm btn-outline-secondary btn-icon" data-toggle="tooltip" title="Monitor">
                      <i class="fas fa-eye"></i><span class="sr-only">Monitor</span>
                    </a>
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-icon"
                            data-toggle="modal"
                            data-target="#deleteVehicleModal"
                            data-vehicle-id="<?= $vid ?>"
                            title="Delete">
                      <i class="fas fa-trash"></i><span class="sr-only">Delete</span>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ========== Create Vehicle Modal (WORKING) ========== -->
        <div class="modal fade" id="createVehicleModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <form method="POST" enctype="multipart/form-data">
              <div class="modal-content" style="background:#f8fafc;color:#0f172a">
                <div class="modal-header">
                  <h5 class="modal-title">Create New Vehicle</h5>
                  <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="create_vehicle" value="1">
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>Name</label>
                      <input type="text" required class="form-control" name="v_name">
                    </div>
                    <div class="form-group col-md-6">
                      <label>Plate Number</label>
                      <input type="text" class="form-control" name="v_reg_no">
                    </div>
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-4">
                      <label>Pax</label>
                      <input type="number" class="form-control" name="v_pass_no" min="0" value="0">
                    </div>
                    <div class="form-group col-md-4">
                      <label>Vehicle Category</label>
                      <select class="form-control" name="v_category">
                        <option>Bus</option>
                        <option>Sedan</option>
                        <option>SUV</option>
                        <option>Van</option>
                      </select>
                    </div>
                    <div class="form-group col-md-4">
                      <label>Status</label>
                      <select class="form-control" name="v_status">
                        <option>Available</option>
                        <option>Booked</option>
                        <option>UnderMaintenance</option>
                      </select>
                    </div>
                  </div>

                  <?php if ($has_driver_fk): ?>
                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>Assign Driver (optional)</label>
                      <select class="form-control" name="driver_user_id">
                        <option value="">— None —</option>
                        <?php foreach($drivers as $d): ?>
                          <?php if (empty($d['current_vehicle_id'])): ?>
                            <option value="<?= (int)$d['u_id'] ?>"><?= h($d['name'] ?: ('Driver #'.(int)$d['u_id'])) ?></option>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </select>
                      <small class="text-muted">Only unassigned drivers are listed here. You can always change later.</small>
                    </div>
                    <div class="form-group col-md-6">
                      <label>Vehicle Picture</label>
                      <input type="file" class="form-control" name="v_dpic" accept="image/*">
                    </div>
                  </div>
                  <?php else: ?>
                  <div class="form-row">
                    <div class="form-group col-md-12">
                      <label>Vehicle Picture</label>
                      <input type="file" class="form-control" name="v_dpic" accept="image/*">
                    </div>
                  </div>
                  <?php endif; ?>
                </div>

                <div class="modal-footer">
                  <button type="submit" class="btn btn-kaya-primary">Create Vehicle</button>
                  <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- ========== Assign/Change Driver Modal (POST back to this page) ========== -->
        <?php if ($has_driver_fk): ?>
        <div class="modal fade" id="assignDriverModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <form method="POST">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Assign / Change Driver</h5>
                  <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <input type="hidden" name="assign_driver" value="1">
                  <input type="hidden" name="assign_vehicle_id" id="assign_vehicle_id">
                  <label>Driver</label>
                  <select class="form-control" name="assign_driver_id" id="assign_driver_id">
                    <!-- options injected by JS to include unassigned + current -->
                  </select>
                  <small class="text-muted d-block mt-2">Choose “— None —” to unassign.</small>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-kaya-primary">Save</button>
                  <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                </div>
              </div>
            </form>
          </div>
        </div>
        <?php endif; ?>

        <!-- ========== Delete Vehicle Modal ========== -->
        <div class="modal fade" id="deleteVehicleModal" tabindex="-1" role="dialog" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <form method="POST">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title">Delete Vehicle</h5>
                  <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <p class="mb-2">Choose a vehicle to delete:</p>
                  <select class="form-control" name="delete_vehicle_id" id="delete_vehicle_id">
                    <?php foreach($vehicles as $v): ?>
                      <option value="<?= (int)$v['v_id'] ?>">
                        <?= h(($v['v_reg_no'] ?: $v['v_name']).' — '.$v['v_category']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="modal-footer">
                  <button type="submit" name="delete_vehicle" class="btn btn-outline-danger">Delete</button>
                  <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancel</button>
                </div>
              </div>
            </form>
          </div>
        </div>

      </div>
      <?php include('vendor/inc/footer.php'); ?>
    </div>
  </div>

  <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
  <script src="js/sb-admin.min.js"></script>

  <script>
    $(function(){ $('[data-toggle="tooltip"]').tooltip(); });

    $('#vehiclesTable').DataTable({
      pageLength: 10,
      lengthMenu: [10,25,50,100],
      order: [[0,'desc']],
      columnDefs: [{targets:-1, orderable:false, searchable:false}]
    });

    // Pre-fill Delete modal
    $('#deleteVehicleModal').on('show.bs.modal', function (e) {
      var id = $(e.relatedTarget).data('vehicle-id');
      if (id) $('#delete_vehicle_id').val(id);
    });

    // Driver list for assign modal (unassigned + current)
    const DRIVERS = <?=
      json_encode(array_map(function($d){
        return [
          'u_id' => (int)$d['u_id'],
          'name' => $d['name'] ?: ('Driver #'.(int)$d['u_id']),
          'current_vehicle_id' => isset($d['current_vehicle_id']) ? (int)$d['current_vehicle_id'] : null
        ];
      }, $drivers), JSON_UNESCAPED_UNICODE);
    ?>;

    $('#assignDriverModal').on('show.bs.modal', function(e){
      var $btn = $(e.relatedTarget);
      var vehicleId = parseInt($btn.data('vehicle-id'),10) || 0;
      var currentDriverId = parseInt($btn.data('current-driver-id'),10) || 0;

      $('#assign_vehicle_id').val(vehicleId);
      var $sel = $('#assign_driver_id').empty();

      // Always include None
      $('<option/>').val('0').text('— None —').appendTo($sel);

      // Filter: unassigned OR currently assigned to this vehicle
      var opts = DRIVERS.filter(function(d){
        return !d.current_vehicle_id || d.current_vehicle_id === vehicleId;
      }).sort(function(a,b){ return (a.name||'').localeCompare(b.name||''); });

      opts.forEach(function(d){
        var $o = $('<option/>').val(d.u_id).text(d.name);
        if (d.u_id === currentDriverId) $o.attr('selected', true);
        $sel.append($o);
      });
    });
  </script>
</body>
</html>
