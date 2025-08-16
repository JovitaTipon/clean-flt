<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
  if (isset($_POST['add_driver'])) {
    $fname = $_POST['u_fname'];
    $lname = $_POST['u_lname'];
    $phone = $_POST['u_phone'];
    $addr = $_POST['u_addr'];
    $category = $_POST['u_category']; // should be 'Driver'
    $email = $_POST['u_email'];
    $pwd = sha1(md5($_POST['u_pwd'])); // encrypt the password
    // Prepare insert query
    $query = "INSERT INTO tms_user_add_driver (u_fname, u_lname, u_phone, u_addr, u_category, u_email, u_pwd) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param('sssssss', $fname, $lname, $phone, $addr, $category, $email, $pwd);
    
    if ($stmt->execute()) {
        $succ = "Driver Added Successfully";
    } else {
        $err = "Please Try Again Later";
    }
}
  if(isset($_GET['del']))
{
      $id=intval($_GET['del']);
      $adn="delete from tms_user where u_id=?";
      $stmt= $mysqli->prepare($adn);
      $stmt->bind_param('i',$id);
      $stmt->execute();
      $stmt->close();	 
        if($stmt)
        {
          $succ = "Driver Fired";
        }
          else
          {
            $err = "Try Again Later";
          }
  }
?>
<?php
if (isset($_POST['add_driver'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_phone = $_POST['u_phone'];
    $u_addr = $_POST['u_addr'];
    $u_category = $_POST['u_category'];
    $u_email = $_POST['u_email'];
    $u_pwd = password_hash($_POST['u_pwd'], PASSWORD_DEFAULT); // secure password
    $query = "INSERT INTO tms_user (u_fname, u_lname, u_phone, u_addr, u_category, u_email, u_pwd) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param("sssssss", $u_fname, $u_lname, $u_phone, $u_addr, $u_category, $u_email, $u_pwd);
    if ($stmt->execute()) {
        $succ = "Driver Added Successfully";
    } else {
        $err = "Something went wrong!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php');?>
<body id="page-top">
    <?php include("vendor/inc/nav.php");?>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('vendor/inc/sidebar.php');?>
        <div id="content-wrapper">
            <div class="container-fluid fas">
                <ol class="breadcrumb">
                    <h6 class="m-0 font-weight-bold" style="font-size: 30px; color:#000047;">Manage Drivers</h6>
                 </ol>
                <div class=" d-flex justify-content-between align-items-center">
                        </div>
                        <div class="card-header d-flex justify-content-between align-items-center">
                             <!-- <h6 class="m-0 font-weight-bold text-secondary" style="font-size: 30px;"></h6> -->
                            <h6 class="m-0 font-weight-bold text-primary"></h6>
                            <div class="small" style="font-size: .1rem; padding: 2px;">
                                <!-- Add Button Trigger Modal -->
                                <button class="btn btn-success btn-sm" data-toggle="modal" data-target="#addDriverModal" style="border: 10px; border-radius: 10%; background: #000047; padding: 10px; margin: 0px;">
                                    <i class="fas fa-plus"></i> New Driver
                                </button>
                                <!-- Optional Refresh Button -->
                                <a href="" class="btn btn-danger btn-sm" style="border: 10px; border-radius: 10%; background:#8B0000; padding: 10px;  margin: 0px;">
                                    <i class="fas fas fa-trash"></i> Delete
                                </a>
                            </div>
                        </div>
                 <div class="card mb-1 fas" style="background: #dedfdcb0; color:#000047;">
                        <!-- Add Driver Modal -->
                        <div class="modal fade" id="addDriverModal" tabindex="-1" role="dialog" aria-labelledby="addDriverModalLabel" aria-hidden="true" >
                            <div class="modal-dialog" role="document">
                                <form method="POST">
                                <div class="modal-content">
                                    <div class="modal-header">
                                    <h5 class="modal-title" id="addDriverModalLabel">Add Driver</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                    </div>
                                    <div class="modal-body">
                                        <!-- Form fields -->
                                        <div class="form-group">
                                            <label>First Name</label>
                                            <input type="text" required class="form-control" name="u_fname">
                                        </div>
                                        <div class="form-group">
                                            <label>Last Name</label>
                                            <input type="text" class="form-control" name="u_lname">
                                        </div>
                                        <div class="form-group">
                                            <label>Contact</label>
                                            <input type="text" class="form-control" name="u_phone">
                                        </div>
                                        <div class="form-group">
                                            <label>Address</label>
                                            <input type="text" class="form-control" name="u_addr">
                                        </div>
                                        <div class="form-group">
                                            <label>Passenger</label>
                                            <input type="text" class="form-control" name="u_car_pax">
                                        </div>
                                        <div class="form-group">
                                            <label>License #</label>
                                            <input type="text" class="form-control" name="u_car_regno">
                                        </div>
                                        <div class="form-group">
                                            <label>Status</label>
                                            <input type="text" class="form-control" name="u_registration">
                                        </div>
                                        <div class="form-group" style="display:none;">
                                            <label>Category</label>
                                            <input type="text" class="form-control" name="u_category" value="Driver">
                                        </div>
                                        <div class="form-group">
                                            <label>Email address</label>
                                            <input type="email" class="form-control" name="u_email">
                                        </div>
                                        <div class="form-group" style="display:none">
                                            <label>Password</label>
                                            <input type="password" class="form-control" name="u_pwd">
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                    <button type="submit" name="add_driver" class="btn btn-success">Add Driver</button>
                                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                    </div>
                                </div>
                                </form>
                            </div>
                        </div>
                <div class="card-header">
                </div>
                <div class="card-body" style="background: #dedfdcb0; color:#000047;">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Vehicle</th>
                                    <th>Type</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $ret = "SELECT * FROM tms_user_add_driver WHERE u_category ORDER BY d_u_id DESC";
                                $stmt = $mysqli->prepare($ret);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                $cnt = 1;
                                while ($row = $res->fetch_object()) {
                                ?>
                                <tr>
                                        <td><?php echo $cnt; ?></td>
                                        <td><?php echo $row->u_fname . " " . $row->u_lname; ?></td>
                                        <td><?php echo $row->u_registration; ?></td>
                                        <td><?php echo $row->u_phone; ?></td>
                                        <td><?php echo $row->u_email; ?></td>
                                        <td><?php echo $row->u_registration; ?></td>
                                        <td><?php echo $row->u_pass_no; ?></td>
                                        <td>
                                            <?php
                                            if ($row->v_status == 'Vacant') {
                                                echo '<span class="badge badge-success">Available</span>';
                                            } elseif ($row->v_status == 'Booked') {
                                                echo '<span class="badge badge-primary">In Service</span>';
                                            } elseif ($row->v_status == 'Damaged') {
                                                echo '<span class="badge badge-danger">Maintenance</span>';
                                            } else {
                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="vehicle-view.php?v_id=<?php echo $row->v_id; ?>" class="badge badge-info"><i class="fas fa-eye"></i> View</a>
                                            <a href="vehicle-edit.php?v_id=<?php echo $row->v_id; ?>" class="badge badge-warning"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="vehicle-monitor.php?v_id=<?php echo $row->v_id; ?>" class="badge badge-secondary"><i class="fas fa-desktop"></i> Monitor</a>
                                        </td>
                                    </tr>
                                    <?php $cnt++; } ?>
                                    <?php
                                    $ret = "SELECT * FROM tms_vehicle ORDER BY v_id DESC";
                                    $stmt = $mysqli->prepare($ret);
                                    $stmt->execute();
                                    $res = $stmt->get_result();
                                    $cnt = 0;
                                    while ($row = $res->fetch_object()) {
                                    ?>
                                    <tr>
                                        <td><?php echo $cnt; ?></td>
                                        <td><?php echo $row->v_name; ?></td>
                                        <td><?php echo $row->v_pass_no; ?></td>
                                        <td>
                                            <?php
                                            if ($row->v_status == 'Vacant') {
                                                echo '<span class="badge badge-success">Available</span>';
                                            } elseif ($row->v_status == 'Booked') {
                                                echo '<span class="badge badge-primary">In Service</span>';
                                            } elseif ($row->v_status == 'Damaged') {
                                                echo '<span class="badge badge-danger">Maintenance</span>';
                                            } else {
                                                echo '<span class="badge badge-secondary">Unknown</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <a href="vehicle-view.php?v_id=<?php echo $row->v_id; ?>" class="badge badge-info"><i class="fas fa-eye"></i> View</a>
                                            <a href="vehicle-edit.php?v_id=<?php echo $row->v_id; ?>" class="badge badge-warning"><i class="fas fa-edit"></i> Edit</a>
                                            <a href="vehicle-monitor.php?v_id=<?php echo $row->v_id; ?>" class="badge badge-secondary"><i class="fas fa-desktop"></i> Monitor</a>
                                        </td>
                                    </tr>
                                <?php $cnt++; } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!-- /.container-fluid -->
        </div>
        <a class="scroll-to-top rounded" href="#page-top">
        <i class="fas fa-angle-up"></i>
        </a>
    </div>
    <!-- /#wrapper -->
      <!-- Sticky Footer -->
            <?php include("vendor/inc/footer.php");?>
        <!-- /.content-wrapper
    <!-- Scroll to Top Button-->
    <!-- Logout Modal-->
    <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">×</span>
                    </button>
                </div>
                <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                    <a class="btn btn-danger" href="admin-logout.php">Logout</a>
                </div>
            </div>
        </div>
    </div>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Bootstrap core JavaScript-->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <!-- Core plugin JavaScript-->
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <!-- Page level plugin JavaScript-->
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="js/sb-admin.min.js"></script>
    <!-- Demo scripts for this page-->
    <script src="js/demo/datatables-demo.js"></script>
</body>
</html>