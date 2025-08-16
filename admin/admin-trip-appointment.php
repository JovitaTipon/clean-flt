 <?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
?>
 <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Vehicle Booking System Transport Saccos, Matatu Industry">
    <meta name="author" content="MartDevelopers">
    <title>Vehicle - Admin Dashboard</title>
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
    <link href="vendor/css/sb-admin.css" rel="stylesheet">
</head>
 <!--Head-->
 <?php include ('vendor/inc/head.php');?>
 <!--End Head-->
 <body id="page-top">
     <!--Navbar-->
     <?php include ('vendor/inc/nav.php');?>
     <!--End Navbar-->
     <div id="wrapper">
         <!-- Sidebar -->
         <?php include('vendor/inc/sidebar.php');?>
         <!--End Sidebar-->
         <div id="content-wrapper">
             <div class="container-fluid">
                 <!-- Breadcrumbs-->
                 <ol class="breadcrumb">
                    <h6 class="m-0 font-weight-bold fas" style="font-size: 30px; color:#000047;">Trip Appoinment</h6>
                 </ol>
                 <!-- Icon Cards-->
                 <div class="row" style="background-color: #000047; border-radius: 20px; border:2%; padding:1%; margin: 3% 2% 3% 2%;">
                    <div class="col-xl-3 col-sm-5 mb-5">
                        <div class="text-white h-100">
                            <div class="card-body table-responsive"  style="background-color: #000047;">
                                <div class="card-body-icon">
                                    <i class="fa fa-map-signs" aria-hidden="true"></i>
                                </div>
                                <a href="admin-list-vehicle.php" class="clearfix fas">
                                    <i aria-hidden="true"><div class="mr-4" style="font-size:1rem;">Total Booking</div></i>
                                </a>
                            </div>
                        </div>
                    </div>
                     <div class="col-xl-3 col-sm-5 mb-5">
                         <div class="text-white h-100">
                             <div class="card-body card-responsive" style="background-color: #000047;">
                                 <div class="card-body-icon">
                                     <i class="fas fa-fw fa-clipboard" aria-hidden="true"></i>
                                 </div>
                             </div>
                             <a class="clearfix fas" href="admin-view-booking.php">
                                <i aria-hidden="true"><div class="mr-4" style="font-size:1rem;">Completed Booking</div></i>
                             </a>
                         </div>
                     </div>
                     <div class="col-xl-3 col-sm-5 mb-5">
                         <div class="text-white h-100;">
                             <div class="card-body card-responsive">
                                 <div class="card-body-icon">
                                     <i class="fa fa-thumbs-down" aria-hidden="true"></i>
                                 </div>
                             </div>
                             <a class="clearfix fas" href="admin-manage-booking.php">
                                <i aria-hidden="true"><span class="mr-4" style="font-size:1rem;">Cancel Booking</span></i>
                             </a>
                         </div>
                     </div>
                     <div class="col-xl-3 col-sm-5 mb-5">
                         <div class="text-white h-100">
                             <div class="card-body table-responsive">
                                 <div class="card-body-icon">
                                     <i class="fa fa-street-view" aria-hidden="true"></i>
                                 </div>
                             </div>
                             <a class="clearfix fas" href="admin-create-booking.php">
                                <i aria-hidden="true"><span class="mr-4" style="font-size:1rem;">Creat New Bookings</span></i>
                             </a>
                         </div>
                     </div>
                 </div>
                 <!--Bookings-->
                 <div class="card mb-5 fas" style="background: #dedfdc67;">
                     <div class="card-body small" style="font-size: 1rem; color:#000047; background: #dedfdc67;">
                         <div class="table-responsive card" style="font-size: .7rem; background: #dedfdc67;">
                             <table class="card-table table-bordered table-striped table-hover small" id="dataTable" width="100%" cellspacing="0">
                                 <thead >
                                     <tr style="font-size: .7rem;">
                                         <th>#</th>
                                         <th>Date</th>
                                         <th>Time</th>
                                         <th>Costumer</th>
                                         <th>Pax</th>
                                         <th>Pickup Location</th>
                                         <th>Destination</th>
                                         <th>Reg No.</th>
                                         <th>Vehicle Type</th>
                                         <th>Driver</th>
                                         <th>Status</th>
                                     </tr>
                                 </thead>
                                 <tbody style="font-size: .7rem;">
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
                                        <!-- <?php 
                                            $datetime = $row->u_car_createdat;
                                                                $date = date("Y-m-d", strtotime($datetime));
                                                                $time = date("h:i A", strtotime($datetime));
                                        ?> -->
                                     <tr style="font-size: 15px;">
                                         <td><?php echo $cnt;?></td>
                                         <td><?php echo $date;?></td>
                                         <td><?php echo $time;?></td>
                                         <!-- <td><?php echo $row->u_car_date;?></td>
                                         <td><?php echo $row->u_car_time;?></td> -->
                                         <td><?php echo $row->u_fname;?></td>
                                         <td><?php echo $row->u_car_pax;?></td>
                                         <td><?php echo $row->u_car_pickup;?></td>
                                         <td><?php echo $row->u_car_destination;?></td>
                                         <td><?php echo $row->u_car_regno;?></td>
                                         <td><?php echo $row->u_car_type;?></td>
                                         <td><?php echo $row->u_car_driver;?></td>
                                         <!-- <td><?php echo $row->u_car_book_status;?></td> -->
                                         <td>
                                                <?php 
                                                    $status = $row->u_car_book_status;
                                                    if($status == "Available") {
                                                        echo '<span class="badge badge-success">Available</span>';
                                                    } elseif($status == "In Active") {
                                                        echo '<span class="badge badge-danger">In Active</span>';
                                                    } elseif($status == "Maintenance") {
                                                        echo '<span class="badge badge-warning">Maintenance</span>';
                                                    } else {
                                                        echo '<span class="badge badge-secondary">'.htmlspecialchars($status).'</span>';
                                                    }
                                                ?>
                                                <?php 
                                                    $status = $row->u_car_book_status;
                                                    switch($status) {
                                                        case 0:
                                                            echo '<span class="badge badge-danger">In Active</span>';
                                                            break;
                                                        case 1:
                                                            echo '<span class="badge badge-success">Available</span>';
                                                            break;
                                                        case 2:
                                                            echo '<span class="badge badge-warning">Maintenance</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge badge-secondary">Unknown</span>';
                                                    }
                                                ?>
                                            </td>
                                     </tr>
                                     <?php  $cnt = $cnt +1; }?>
                                 </tbody>
                             </table>
                             <div class="card-footer small text-muted">
                            <?php
                            date_default_timezone_set("Africa/Nairobi");
                            echo "Generated At : " . date("h:i:sa");
                            ?>
                     </div>
                         </div>
                     </div>
                     <div id="content-wrapper">
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
    <script src="vendor/chart.js/Chart.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
    <!-- Custom scripts for all pages-->
    <script src="vendor/js/sb-admin.min.js"></script>
    <!-- Demo scripts for this page-->
    <script src="vendor/js/demo/datatables-demo.js"></script>
    <script src="vendor/js/demo/chart-area-demo.js"></script>
 </body>
 </html>