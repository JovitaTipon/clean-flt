<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
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
             <div class="container-fluid">
                <ol class="breadcrumb">
                    <h6 class="m-0 font-weight-bold fas" style="font-size: 30px; color:#000047;">List of Vehicles</h6>   
                 </ol>
                 <!-- Breadcrumbs-->
                  <div class="card mb-4 fas">
                     <div class="card-body" style="background: #dedfdcb0; color:#000047">
                         <div class="table-responsive">
                             <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0" style="color:#000047;">
                                 <thead>
                                     <tr style="font-size: 15px;">
                                         <th>#</th>
                                         <th>Date</th>
                                         <th>Time</th>
                                         <th>Costumer</th>
                                         <th>Pickup Location</th>
                                         <th>Destination</th>
                                         <th>Reg No.</th>
                                         <th>Vehicle Type</th>
                                         <th>Driver</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                     <?php
                                    $ret="SELECT * FROM tms_user "; //get all bookings
                                    $stmt= $mysqli->prepare($ret) ;
                                    $stmt->execute() ;//ok
                                    $res=$stmt->get_result();
                                    $cnt=1;
                                    // Get unique vehicle names
                                    $vehicle_query = "SELECT DISTINCT u_car_type FROM tms_user";
                                    $vehicle_stmt = $mysqli->prepare($vehicle_query);
                                    $vehicle_stmt->execute();
                                    $vehicle_result = $vehicle_stmt->get_result();
                                    $vehicle_options = [];
                                    while($v = $vehicle_result->fetch_object()) {
                                        $vehicle_options[] = $v->u_car_type;
                                    }
                                    // Get unique drivers
                                    $driver_query = "SELECT DISTINCT u_lname FROM tms_user";
                                    $driver_stmt = $mysqli->prepare($driver_query);
                                    $driver_stmt->execute();
                                    $driver_result = $driver_stmt->get_result();
                                    $driver_options = [];
                                    while($d = $driver_result->fetch_object()) {
                                        $driver_options[] = $d->u_lname;
                                    }
                                    while($row=$res->fetch_object())
                                    {
                                    ?>
                                     <!--  -->
                                     <tr style="font-size: 15px;">
                                         <td><?php echo $cnt;?></td>
                                         <td><?php echo $row->u_car_date;?></td>
                                         <td><?php echo $row->u_car_time;?></td>
                                         <td><?php echo $row->u_fname;?></td>
                                         <td><?php echo $row->u_car_pickup;?></td>
                                         <td><?php echo $row->u_car_destination;?></td>
                                         <td><?php echo $row->u_car_regno;?></td>
                                         <td>
                                            <select class="form-control">
                                                <?php foreach($vehicle_options as $option): ?>
                                                    <option value="<?php echo $option; ?>" <?php if($row->u_car_type == $option) echo 'selected'; ?>>
                                                        <?php echo $option; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-control">
                                                <?php foreach($driver_options as $option): ?>
                                                    <option value="<?php echo $option; ?>" <?php if($row->u_lname == $option) echo 'selected'; ?>>
                                                        <?php echo $option; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </td>
                                         <td>
                                        <?php 
                                        $status = trim(ucwords(strtolower($row->u_car_book_status)));
                                        if ($status == "Available") {
                                            echo '<span class="badge badge-success">Available</span>';
                                        } elseif ($status == "Booked") {
                                            echo '<span class="badge badge-warning">Booked</span>';
                                        } elseif ($status == "In Service") {
                                            echo '<span class="badge badge-primary">In Service</span>';
                                        } else {
                                            echo '<span class="badge badge-secondary">'.$status.'</span>'; // fallback
                                        }
                                        ?>
                                        </td>
                                     </tr>
                                     <?php  $cnt = $cnt +1; }?>
                                 </tbody>
                             </table>
                         </div>
                     </div>
                    <!-- <div id="content-wrapper"> -->                     <div class="card-footer small text-muted">
                        <?php
                        date_default_timezone_set("Africa/Nairobi");
                        echo "The time is " . date("h:i:sa");
                        ?>
                     </div>
                 </div>
                 <!-- /.container-fluid -->
                 <!-- Sticky Footer -->
                 <?php include("vendor/inc/footer.php");?>
             </div>
             <!-- /.content-wrapper -->
         </div>
         <!-- /#wrapper -->
         <!-- Scroll to Top Button-->
         <a class="scroll-to-top rounded" href="#page-top">
             <i class="fas fa-angle-up"></i>
         </a>
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











