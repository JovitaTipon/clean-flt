 <?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
  //Add USer
  if (isset($_POST['add_user'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_car_date = $_POST['u_car_date'];
    $u_car_time = $_POST['u_car_time'];
    $u_car_pax = $_POST['u_car_pax'];
    $u_car_pickup = $_POST['u_car_pickup'];
    $u_car_destination = $_POST['u_car_destination'];
    $u_car_regno = $_POST['u_car_regno'];
    $u_car_type = $_POST['u_car_type'];
    $u_car_driver = $_POST['u_car_driver'];
    $u_category = $_POST['u_category'];
    $u_email = $_POST['u_email'];
    $u_pwd = password_hash($_POST['u_pwd'], PASSWORD_DEFAULT); // use secure password hash
    $query = "INSERT INTO tms_user (
        u_fname, u_lname, u_car_date, u_car_time, u_car_pax, 
        u_car_pickup, u_car_destination, u_car_regno, 
        u_car_type, u_car_driver, u_category, u_email, u_pwd
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $mysqli->prepare($query);
    $stmt->bind_param(
        'sssssssssssss', 
        $u_fname, $u_lname, $u_car_date, $u_car_time, $u_car_pax, 
        $u_car_pickup, $u_car_destination, $u_car_regno, 
        $u_car_type, $u_car_driver, $u_category, $u_email, $u_pwd
    );
    if ($stmt->execute()) {
        $succ = "User Added";
    } else {
        $err = "Please Try Again Later";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php');?>
<body id="page-top">
     <!--Start Navigation Bar-->
     <?php include("vendor/inc/nav.php");?>
     <!--Navigation Bar-->
     <div id="wrapper">  
         <!-- Sidebar -->
         <?php include("vendor/inc/sidebar.php");?>
         <!--End Sidebar-->
         <div id="content-wrapper">
            <ol class="breadcrumb">
                    <h6 class="m-0 font-weight-bold fas" style="font-size: 30px; color:#000047;">Create Booking</h6>
                 </ol>
             <div class="container-fluid fas">
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
                 <hr>
                 <div class="card">
                     <div class="card-header">
                         Add Booking
                     </div>
                     <div class="card-body" style="background: #dedfdcb0; color:#000047">
                         <!--Add User Form-->
                         <form method="POST">
                            <div class="form-group">
                                <label for="u_car_date">Date</label>
                                <input type="date" required class="form-control" id="u_car_date" name="u_car_date">
                            </div>
                            <div class="form-group">
                                <label for="u_car_time">Time</label>
                                <input type="time" required class="form-control" id="u_car_time" name="u_car_time">
                            </div>
                             <div class="form-group">
                                 <label for="exampleInputEmail1">Costumer</label>
                                 <input type="text" required class="form-control" id="exampleInputEmail1" name="u_fname">
                             </div>
                             <div class="form-group" style="display:none">
                                 <label for="exampleInputEmail1">Last Name</label>
                                 <input type="text" class="form-control" id="exampleInputEmail1" name="u_lname">
                             </div>
                             <div class="form-group">
                                 <label for="exampleInputEmail1">Pax</label>
                                 <input type="number" class="form-control" id="exampleInputEmail1" name="u_car_pax">
                             </div>
                             <div class="form-group">
                                 <label for="exampleInputEmail1">Pickup Location</label>
                                 <input type="text" class="form-control" id="exampleInputEmail1" name="u_car_pickup">
                             </div>
                             <div class="form-group">
                                 <label for="exampleInputEmail1">Destination</label>
                                 <input type="text" class="form-control" id="exampleInputEmail1" name="u_car_destination">
                             </div>
                             <div class="form-group">
                                 <label for="exampleInputEmail1">Reg no.</label>
                                 <input type="text" class="form-control" id="exampleInputEmail1" name="u_car_regno">
                             </div>
                             <div class="form-group">
                                 <label for="exampleInputEmail1">Vehicle Type</label>
                                 <input type="text" class="form-control" id="exampleInputEmail1" name="u_car_type">
                             </div>
                             <div class="form-group">
                                 <label for="exampleInputEmail1">Driver</label>
                                 <input type="text" class="form-control" id="exampleInputEmail1" name="u_car_driver">
                             </div>
                             <div class="form-group" style="display:none">
                                 <label for="exampleInputEmail1">Category</label>
                                 <input type="text" class="form-control" id="exampleInputEmail1" value="User" name="u_category">
                             </div>
                             <div class="form-group" style="display:none">
                                 <label for="exampleInputEmail1">Email address</label>
                                 <input type="email" class="form-control" name="u_email"">
                            </div> 
                            <div class=" form-group" style="display:none">
                                 <label for="exampleInputPassword1">Password</label>
                                 <input type="password" class="form-control" name="u_pwd" id="exampleInputPassword1">
                             </div>
                             <button type="submit" name="add_user" class="btn btn-success">Add Booking</button>
                         </form>
                         <!-- End Form-->
                     </div>
                 </div>
                 <hr>
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
         <script src="vendor/chart.js/Chart.min.js"></script>
         <script src="vendor/datatables/jquery.dataTables.js"></script>
         <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
         <!-- Custom scripts for all pages-->
         <script src="vendor/js/sb-admin.min.js"></script>
         <!-- Demo scripts for this page-->
         <script src="vendor/js/demo/datatables-demo.js"></script>
         <script src="vendor/js/demo/chart-area-demo.js"></script>
         <!--INject Sweet alert js-->
         <script src="vendor/js/swal.js"></script>
</body>
</html>