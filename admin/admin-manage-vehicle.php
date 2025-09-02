<?php
/**
 * KAYA • Manage Vehicles
 * - Canonical page layout (nav.php, sidebar.php, footer.php).
 * - Icon-only actions with tooltips (View, Edit, Monitor, Delete).
 * - DataTables pagination/search/sort.
 * - Inline comments explain non-obvious parts.
 */

session_start();
include('vendor/inc/config.php');
include('vendor/inc/checklogin.php');
check_login();
$aid = require_admin();

/* ---------------------------
 * DELETE via modal (POST)
 * ---------------------------
 * The Delete modal posts 'delete_vehicle' + 'delete_vehicle_id'.
 */
if (isset($_POST['delete_vehicle'])) {
  $delete_id = (int) $_POST['delete_vehicle_id'];
  $stmt = $mysqli->prepare("DELETE FROM tms_vehicle WHERE v_id = ?");
  $stmt->bind_param("i", $delete_id);
  $ok = $stmt->execute();
  $stmt->close();

  // Give immediate feedback; then refresh to reflect the change.
  if ($ok) {
    echo "<script>
            setTimeout(function(){ swal('Deleted!','Vehicle has been deleted.','success'); }, 100);
            setTimeout(function(){ window.location.href='admin-manage-vehicle.php'; }, 1200);
          </script>";
  } else {
    echo "<script>
            setTimeout(function(){ swal('Error','Something went wrong.','error'); }, 100);
          </script>";
  }
}

/* --------------------------------
 * FETCH VEHICLES (one pass)
 * --------------------------------
 * Used for the table + to populate the Delete modal <select>.
 */
$vehicles = [];
$sql = "SELECT v_id, v_name, v_reg_no, v_driver, v_category, v_status
        FROM tms_vehicle
        ORDER BY v_id DESC";
if ($stmt = $mysqli->prepare($sql)) {
  $stmt->execute();
  $res = $stmt->get_result();
  while ($row = $res->fetch_assoc()) {
    $vehicles[] = $row;
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <?php include('vendor/inc/head.php'); ?><!-- Shared bootstrap/meta/css; keep it identical on all pages -->

  <!-- Inter font to match the rest of your modernized pages -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">
  <style>
    /* Global font alignment */
    html,body{font-family:Inter,system-ui,-apple-system,"Segoe UI",Roboto,sans-serif}

    /* Consistent page title */
    .kaya-page-title{font-weight:800;font-size:2rem;line-height:1.1;color:#000047;margin:0 0 1rem}

    /* Card wrapper for the table */
    .kaya-card{
      background:#fff; border-radius:1rem; box-shadow:0 8px 24px rgba(0,0,0,.06);
      padding:1rem; border:1px solid #e5e7eb;
    }

    /* Table refinements */
    .kaya-table thead th{font-weight:600;color:#6b7280;border:0}
    .kaya-table tbody td{border-top:1px solid #f1f5f9; vertical-align:middle}

    /* Icon-only action buttons */
    .btn-icon{
      width:34px; height:34px; display:inline-flex; align-items:center; justify-content:center;
      border-radius:.5rem; padding:0;
    }
    .actions .btn-icon + .btn-icon{ margin-left:.25rem; }

    /* Status colors (text) */
    .status-available{color:#16a34a;font-weight:600}   /* green   */
    .status-service{color:#2563eb;font-weight:600}     /* blue    */
    .status-maint{color:#dc2626;font-weight:600}       /* red     */

    /* Toolbar buttons (neutral/pro look) */
    .kaya-toolbar .btn{padding:.5rem .9rem;border-radius:.5rem;font-weight:600}
    .btn-kaya-primary{background:#0A0F2C;border:1px solid #0A0F2C;color:#fff}
    .btn-kaya-primary:hover{background:#0c1438;border-color:#0c1438;color:#fff}
  </style>
</head>

<body id="page-top">
  <!-- Fixed top navbar (has #sidebarToggle) -->
  <?php include('vendor/inc/nav.php'); ?>

  <div id="wrapper">
    <!-- Left rail (id="kayaSidebar") -->
    <?php include('vendor/inc/sidebar.php'); ?>

    <!-- Main content -->
    <div id="content-wrapper">
      <div class="container-fluid">

        <!-- Title -->
        <h1 class="kaya-page-title">Manage Vehicles</h1>

        <!-- Top toolbar -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="ml-auto">
            <button class="btn btn-kaya-primary" data-toggle="modal" data-target="#createVehicleModal">
              <i class="fas fa-plus mr-1"></i> New Vehicle
            </button>
          </div>
        </div>

        <!-- Vehicles table -->
        <div class="kaya-card">
          <div class="table-responsive">
            <!-- Use a dedicated ID ('vehiclesTable') so we can initialize DataTables cleanly here -->
            <table id="vehiclesTable" class="table kaya-table table-hover table-borderless align-middle">
              <thead class="thead-light">
                <tr>
                  <th style="width:56px">#</th>
                  <th>Vehicle</th>
                  <th>Type</th>
                  <th>Status</th>
                  <th class="actions">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                  $n=1;
                  foreach($vehicles as $v):
                    // Normalize the status to text + color class
                    $raw = (string)($v['v_status'] ?? '');
                    $txt = $raw ?: 'Available';
                    $cls = 'status-service';
                    if (stripos($raw,'avail')!==false){ $txt='Available';   $cls='status-available'; }
                    if (stripos($raw,'service')!==false){ $txt='In Service'; $cls='status-service'; }
                    if (stripos($raw,'maint')!==false){ $txt='Maintenance'; $cls='status-maint'; }
                    if (stripos($raw,'book')!==false){ $txt='Booked';       $cls='status-service'; }

                    $vid = (int)$v['v_id'];
                    $regOrName = $v['v_reg_no'] ?: $v['v_name'];
                ?>
                <tr>
                  <td><?= $n++; ?></td>
                  <td class="font-weight-semibold"><?= htmlspecialchars($regOrName) ?></td>
                  <td><?= htmlspecialchars($v['v_category']) ?></td>
                  <td class="<?= $cls ?>"><?= htmlspecialchars($txt) ?></td>
                  <td class="actions">
                    <!-- Info / View -->
                    <a href="admin-view-vehicle.php?v_id=<?= $vid ?>"
                       class="btn btn-sm btn-outline-secondary btn-icon"
                       data-toggle="tooltip" title="View">
                      <i class="fas fa-info-circle" aria-hidden="true"></i>
                      <span class="sr-only">View</span>
                    </a>

                    <!-- Edit -->
                    <a href="admin-manage-single-vehicle.php?v_id=<?= $vid ?>"
                       class="btn btn-sm btn-outline-secondary btn-icon"
                       data-toggle="tooltip" title="Edit">
                      <i class="fas fa-pencil-alt" aria-hidden="true"></i>
                      <span class="sr-only">Edit</span>
                    </a>

                    <!-- Monitor -->
                    <a href="admin-view-syslogs.php?reg=<?= urlencode($v['v_reg_no']) ?>"
                       class="btn btn-sm btn-outline-secondary btn-icon"
                       data-toggle="tooltip" title="Monitor">
                      <i class="fas fa-eye" aria-hidden="true"></i>
                      <span class="sr-only">Monitor</span>
                    </a>

                    <!-- Delete (opens modal, pre-fills select) -->
                    <button type="button"
                            class="btn btn-sm btn-outline-danger btn-icon"
                            data-toggle="modal"
                            data-target="#deleteVehicleModal"
                            data-vehicle-id="<?= $vid ?>"
                            title="Delete">
                      <i class="fas fa-trash" aria-hidden="true"></i>
                      <span class="sr-only">Delete</span>
                    </button>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- ================= Create Vehicle Modal =================
             Front-end only. Your createVehicle.js should handle the actual creation. -->
        <div class="modal fade" id="createVehicleModal" tabindex="-1" role="dialog" aria-labelledby="createVehicleModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg" role="document">
            <form id="createVehicleForm" enctype="multipart/form-data">
              <div class="modal-content" style="background:#f8fafc;color:#0f172a">
                <div class="modal-header">
                  <h5 class="modal-title" id="createVehicleModalLabel">Create New Vehicle</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
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
                      <input type="number" class="form-control" name="v_pass_no">
                    </div>

                    <!-- Hidden: you kept driver selection hidden in your current flow -->
                    <div class="form-group col-md-4" style="display:none">
                      <label>Driver</label>
                      <select class="form-control" name="v_driver">
                        <?php
                          $driverRet = "SELECT u_fname, u_lname FROM tms_user WHERE u_category = 'Driver'";
                          if ($s=$mysqli->prepare($driverRet)) {
                            $s->execute(); $r=$s->get_result();
                            while($d=$r->fetch_object()){
                              echo "<option>".htmlspecialchars("{$d->u_fname} {$d->u_lname}")."</option>";
                            }
                            $s->close();
                          }
                        ?>
                      </select>
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
                  </div>

                  <div class="form-row">
                    <div class="form-group col-md-6">
                      <label>Vehicle Status</label>
                      <select class="form-control" name="v_status">
                        <option>Available</option>
                        <option>Booked</option>
                        <option>UnderMaintenance</option>
                      </select>
                    </div>
                    <div class="form-group col-md-6">
                      <label>Vehicle Picture</label>
                      <input type="file" class="form-control" name="v_dpic">
                    </div>
                  </div>
                </div>

                <div class="modal-footer">
                  <button type="submit" class="btn btn-kaya-primary">Create Vehicle</button>
                  <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Close</button>
                </div>
              </div>
            </form>
          </div>
        </div>

        <!-- ================= Delete Vehicle Modal =================
             Lets you pick a vehicle, or it's pre-filled by clicking a row's Delete icon. -->
        <div class="modal fade" id="deleteVehicleModal" tabindex="-1" role="dialog" aria-labelledby="deleteVehicleModalLabel" aria-hidden="true">
          <div class="modal-dialog" role="document">
            <form method="POST">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="deleteVehicleModalLabel">Delete Vehicle</h5>
                  <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                  <p class="mb-2">Choose a vehicle to delete:</p>
                  <select class="form-control" name="delete_vehicle_id" id="delete_vehicle_id">
                    <?php foreach($vehicles as $v): ?>
                      <option value="<?= (int)$v['v_id'] ?>">
                        <?= htmlspecialchars(($v['v_reg_no'] ?: $v['v_name']).' — '.$v['v_category']) ?>
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

      </div><!-- /.container-fluid -->

      <!-- Shared footer (contains the sidebar toggle logic) -->
      <?php include('vendor/inc/footer.php'); ?>
    </div><!-- /#content-wrapper -->
  </div><!-- /#wrapper -->

  <!-- Scroll to Top -->
  <a class="scroll-to-top rounded" href="#page-top"><i class="fas fa-angle-up"></i></a>

  <!-- SweetAlert -->
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>

  <!-- Vendor JS -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <!-- DataTables (pagination/search/sort) -->
  <script src="vendor/datatables/jquery.dataTables.js"></script>
  <script src="vendor/datatables/dataTables.bootstrap4.js"></script>

  <!-- Optional SB-Admin JS (if you use components from it) -->
  <script src="js/sb-admin.min.js"></script>

  <script>
    // Initialize Bootstrap tooltips for icon-only buttons
    $(function () { $('[data-toggle="tooltip"]').tooltip(); });

    // Initialize DataTable (pagination & search)
    $('#vehiclesTable').DataTable({
      pageLength: 10,
      lengthMenu: [10, 25, 50, 100],
      order: [[0, 'desc']], // newest first by row number
      columnDefs: [
        { targets: -1, orderable: false, searchable: false } // actions column
      ]
    });

    // Pre-fill Delete modal when clicking a row's trash icon
    $('#deleteVehicleModal').on('show.bs.modal', function (e) {
      var trigger = $(e.relatedTarget);
      var id = trigger.data('vehicle-id');
      if (id) { $('#delete_vehicle_id').val(id); }
    });
  </script>
</body>
</html>
