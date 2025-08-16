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
            <ol class="breadcrumb">
                    <h6 class="m-0 font-weight-bold fas" style="font-size: 30px; color:#000047;">Cansel Booking</h6>       
                 </ol>
             <div class="container-fluid"> 
                 <!-- Breadcrumbs-->
                 <!--Bookings-->
                 <div class="card mb-3 fas">
                     <div class="card-header">
                         <i class="fas fa-table"></i>
                         Bookings
                     </div>
                     <div class="card-body" style="background: #dedfdcb0; color:#000047">
                         <div class="table-responsive">
                             <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                 <thead>
                                     <tr>
                                         <th>#</th>
                                         <th>Name</th>
                                         <th>Phone</th>
                                         <th>Vehicle Type</th>
                                         <th>Vehicle Reg No</th>
                                         <th>Booking date</th>
                                         <th>Status</th>
                                         <th>Action</th>
                                     </tr>
                                 </thead>
                                 <tbody>
                                    <?php
                                    $ret = "SELECT * FROM tms_user WHERE u_car_book_status IN ('Cancel', 'Undermaintenance')";
                                    $stmt= $mysqli->prepare($ret) ;
                                    $stmt->execute() ;//ok
                                    $res=$stmt->get_result();
                                    $cnt=1;
                                    while($row=$res->fetch_object())
                                    {
                                    ?>
                                     <tr>
                                         <td><?php echo $cnt;?></td>
                                         <td><?php echo $row->u_fname;?> <?php echo $row->u_lname;?></td>
                                         <td><?php echo $row->u_phone;?></td>
                                         <td><?php echo $row->u_car_type;?></td>
                                         <td><?php echo $row->u_car_regno;?></td>
                                         <td><?php echo $row->u_car_bookdate;?></td>
                                         <?php
                                            $status = $row->u_car_book_status;
                                            if ($status == "Pending") {
                                                echo '<span class="badge badge-warning">'.$status.'</span>';
                                            } elseif ($status == "Approved") {
                                                echo '<span class="badge badge-success">'.$status.'</span>';
                                            } elseif ($status == "Cancel") {
                                                echo '<span class="badge badge-danger">'.$status.'</span>';
                                            } elseif ($status == "Undermaintenance") {
                                                echo '<span class="badge badge-dark">'.$status.'</span>';
                                            } else {
                                                echo '<span class="badge badge-secondary">'.$status.'</span>';
                                            }
                                            ?>
                                         <td>
                                             <a href="admin-approve-booking.php?u_id=<?php echo $row->u_id;?>" class="badge badge-success"><i class="fa fa-check"></i> Approve</a>
                                             <a href="admin-delete-booking.php?u_id=<?php echo $row->u_id;?>" class="badge badge-danger">
                                                <i class="fa fa-trash"></i> Delete
                                            </a>
                                         </td>
                                     </tr>
                                     <?php  $cnt = $cnt +1; }?>
                                 </tbody>
                             </table>
                         </div>
                     </div>
                     <div class="card-footer small text-muted">
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