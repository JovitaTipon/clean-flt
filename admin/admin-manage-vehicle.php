 <?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
  if(isset($_GET['del']))
{
      $id=intval($_GET['del']);
      $adn="delete from tms_vehicle where v_id=?";
      $stmt= $mysqli->prepare($adn);
      $stmt->bind_param('i',$id);
      $stmt->execute();
      $stmt->close();	 
        if($stmt)
        {
          $succ = "Vehicle Removed";
        }
          else
          {
            $err = "Try Again Later";
          }
  }
  if (isset($_POST['delete_vehicle'])) {
    $delete_id = $_POST['delete_vehicle_id'];
    $stmt = $mysqli->prepare("DELETE FROM tms_vehicle WHERE v_id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
        echo "<script>
            setTimeout(function() {
                swal('Deleted!', 'Vehicle has been deleted.', 'success');
            }, 100);
            setTimeout(function(){
                window.location.href='admin-manage-vehicle.php';
            }, 1500);
        </script>";
    } else {
        echo "<script>
            setTimeout(function() {
                swal('Error!', 'Something went wrong.', 'error');
            }, 100);
        </script>";
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
                    <h6 class="m-0 font-weight-bold" style="font-size: 30px; color:#000047;">Manage Vehicle</h6>
                 </ol>
                 <!-- Breadcrumbs-->
                 <?php if(isset($succ)) {?>
                 <!--This code for injecting an alert-->
                 <script>
                 setTimeout(function() {
                         swal("Success!", "<?php echo $succ;?>!", "success");
                     },
                     100);
                 </script>
                 <?php } ?>
                 <?php if(isset($err)) {?>
                 <!--This code for injecting an alert-->
                 <script>
                 setTimeout(function() {
                         swal("Failed!", "<?php echo $err;?>!", "Failed");
                     },
                     100);
                 </script>
                 <?php } ?>
                 <!-- Top Buttons -->
                    <div class="mb-3 d-flex justify-content-end gap-2">
                    <!-- Create Button -->
                    <button type="button" class="btn btn-success btn-sm" data-toggle="modal" data-target="#createVehicleModal" style="background: #000047; padding:10px;  margin: 3px; border: 10px; border-radius: 10%;">
                        <i class="fas fa-plus"></i> Create Vehicle
                    </button>
                    <!-- Create Vehicle Modal -->
                    <div class="modal fade" id="createVehicleModal" tabindex="-1" role="dialog" aria-labelledby="createVehicleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg" role="document">
                        <form id="createVehicleForm" enctype="multipart/form-data">
                        <div class="modal-content" style="background: #dedfdcb0; color:#000047">
                            <div class="modal-header">
                            <h5 class="modal-title" id="createVehicleModalLabel">Create New Vehicle</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span>&times;</span>
                            </button>
                            </div>
                            <div class="modal-body">
                            <!-- Create Vehicle Form -->
                            <div class="form-group">
                                <label>Name</label>
                                <input type="text" required class="form-control" name="v_name">
                            </div>
                            <div class="form-group">
                                <label>Plate Number</label>
                                <input type="text" class="form-control" name="v_reg_no">
                            </div>
                            <div class="form-group">
                                <label>Pax</label>
                                <input type="number" class="form-control" name="v_pass_no">
                            </div>
                            <div class="form-group" style="display:none">
                                <label>Driver</label>
                                <select class="form-control" name="v_driver">
                                    <?php
                                $driverRet = "SELECT * FROM tms_user WHERE u_category = 'Driver'";
                                $stmt = $mysqli->prepare($driverRet);
                                $stmt->execute();
                                $res = $stmt->get_result();
                                while ($row = $res->fetch_object()) {
                                    echo "<option>{$row->u_fname} {$row->u_lname}</option>";
                                }
                                ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Vehicle Category</label>
                                <select class="form-control" name="v_category">
                                <option>Bus</option>
                                <option>Sedan</option>
                                <option>SUV</option>
                                <option>Van</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Vehicle Status</label>
                                <select class="form-control" name="v_status">
                                <option>Booked</option>
                                <option>Available</option>
                                <option>UnderMaintenance</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Vehicle Picture</label>
                                <input type="file" class="form-control" name="v_dpic">
                            </div>
                            </div>
                            <div class="modal-footer">
                            <button type="submit"  class="btn btn-success">Create Vehicle</button>
                            <button type="submit" class="btn btn-secondary" data-dismiss="modal">Close</button>
                            </div>
                        </div>
                        </form>
                    </div>
                    </div>
                    <!-- Delete Button -->
                    <button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#deleteVehicleModal" style="background: #8B0000; padding: 10px; margin: 3px; border: 10px; border-radius: 10%;">
                        <i class="fas fa-trash"></i> Delete Vehicle
                    </button>
                    </div>
                 <!-- DataTables Example -->
                 <div class="card mb-3">
                     <div class="card-header">
                         <i class="fas fa-users"></i>
                         Available
                     </div>
                     <div class="card-body" style="background: #dedfdcb0; color:#000047">
                         <div class="table-responsive">
                             <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                 <thead>
                                     <tr>
                                         <th>#</th>
                                         <th>Name</th>
                                         <th>Registration Number</th>
                                         <th>Driver</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <?php
                                    $ret="SELECT * FROM tms_vehicle "; 
                                    $stmt= $mysqli->prepare($ret) ;
                                    $stmt->execute() ;//ok
                                    $res=$stmt->get_result();
                                    $cnt=1;
                                    while($row=$res->fetch_object())
                                {
                                ?>
                                 <tbody>
                                     <tr>
                                         <td><?php echo $cnt;?></td>
                                         <td><?php echo $row->v_name;?></td>
                                         <td><?php echo $row->v_reg_no;?></td>
                                         <td><?php echo $row->v_driver;?></td>
                                         <td>
                                             <a href="admin-manage-single-vehicle.php?v_id=<?php echo $row->v_id;?>" class="badge badge-success">Update</a>
                                             <a href="admin-manage-vehicle.php?del=<?php echo $row->v_id;?>" class="badge badge-danger">Delete</a>
                                         </td>
                                     </tr>
                                 </tbody>
                                 <?php $cnt = $cnt+1; }?>
                             </table>
                         </div>
                     </div>
                 </div>
             </div>
                 <!-- DataTables Example -->
                 <div class="card mb-3 fas">
                     <div class="card-header">
                         <i class="fas fa-bus"></i>
                         Vehicles
                     </div>
                     <div class="card-body" style="background: #dedfdcb0; color:#000047">
                         <div class="table-responsive">
                             <table class="table table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing="0">
                                 <thead>
                                     <tr>
                                         <th>#</th>
                                         <th>Driver</th>
                                         <th>Registration Number</th>
                                         <!-- <th>Driver</th> -->
                                         <!-- <th>Contact #</th> -->
                                         <th>Category</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <?php
                                    $ret="SELECT * FROM tms_vehicle "; 
                                    $stmt= $mysqli->prepare($ret) ;
                                    $stmt->execute() ;//ok
                                    $res=$stmt->get_result();
                                    $cnt=1;
                                    while($row=$res->fetch_object())
                                {
                                ?>
                                 <tbody>
                                     <tr>
                                         <td><?php echo $cnt;?></td>
                                         <td><?php echo $row->v_driver;?></td>
                                         <!-- <td><?php echo $row->v_name;?></td> -->
                                         <td><?php echo $row->v_reg_no;?></td>
                                         <!-- <td><?php echo $row->v_pass_no;?></td> -->
                                         <td><?php echo $row->v_category;?></td>
                                         <td><?php if($row->v_status == "Available"){ echo '<span class = "badge badge-success">'.$row->v_status.'</span>'; } else { echo '<span class = "badge badge-danger">'.$row->v_status.'</span>';}?></td>
                                     </tr>
                                 </tbody>
                                 <?php $cnt = $cnt+1; }?>
                             </table>
                             <ol class="breadcrumb">
                            </ol>
                            <hr>
                                     <?php
                                    // $ret="SELECT * FROM tms_user where v_category = 'Driver' "; //sql code to get to ten trains randomly
                                    // $stmt= $mysqli->prepare($ret) ;
                                    // $stmt->execute() ;//ok
                                    // $res=$stmt->get_result();
                                    $cnt=1;
                                    while($row=$res->fetch_object())
                                    {
                                    ?>
                                     <option><?php echo $row->u_fname;?> <?php echo $row->u_lname;?></option>
                                     <?php }?>
                                 </select>
                             </div>
                         <!-- End Form-->
                     </div>
                 </div>
                         </div>
                     </div>
                     <!-- <div class="card-footer small text-muted">Updated yesterday at 11:59 PM</div> -->
                 </div>
             </div>
             <!-- /.container-fluid -->
             <!-- Sticky Footer -->
             <!-- <?php include("vendor/inc/footer.php");?> -->
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
     <!-- SweetAlert -->
    <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
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
     <script src="js/createVehicle.js"></script>
     <!-- Demo scripts for this page-->
     <script src="js/demo/datatables-demo.js"></script>
 </body>
 </html>