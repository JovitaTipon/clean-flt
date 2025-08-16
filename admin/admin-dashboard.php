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
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Page level plugin CSS-->
    <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="vendor/css/sb-admin.css" rel="stylesheet">
</head>
<body id="page-top">
    <!--Start Navigation Bar-->
    <?php include("vendor/inc/nav.php");?>
    <!--Navigation Bar-->
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include("vendor/inc/sidebar.php");?>
        <!--End Sidebar-->
        <div id="content-wrapper" class="">
            <div class="container-fluid">
                <!-- Breadcrumbs-->
                 <ol class="breadcrumb">
                    <h6 class="m-0 font-weight-bold fas" style="font-size: 30px; color:#000047;">Dashboard</h6>
                 </ol>
                <!-- Icon Cards-->
                <div class="row" style="background-color: #000047; border-radius: 20px; border:2%; padding:1%; margin: 3% 2% 3% 2%;">
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class="text-white h-100" style="background-color: #000047;">
                            <div class="card-body">
                                <a href="admin-view-user.php">
                                <div class="card-body-icon">
                                    <i class="fas fa-fw fa-users"></i>
                                </div>
                                <?php
                                //code for summing up number of users 
                                $result ="SELECT count(*) FROM tms_user where u_category = 'User'";
                                $stmt = $mysqli->prepare($result);
                                $stmt->execute();
                                $stmt->bind_result($user);
                                $stmt->fetch();
                                $stmt->close();
                                ?>
                                <div class="mr-5 fas" style="font-size: 25px;"><span class="badge badge-danger"><?php echo $user;?></span> Users</div>
                            </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div class=" text-white o-hidden h-100" style="background-color: #000047;">
                            <div class="card-body">
                                <a href="admin-view-driver.php">
                                <div class="card-body-icon">
                                    <i class="fas fa-fw fa fa-id-card"></i>
                                </div>
                                <?php
                                //code for summing up number of drivers
                                $result ="SELECT count(*) FROM tms_user where u_category = 'Driver'";
                                $stmt = $mysqli->prepare($result);
                                $stmt->execute();
                                $stmt->bind_result($driver);
                                $stmt->fetch();
                                $stmt->close();
                                ?>
                                <div class="mr-5 fas" style="font-size: 25px;"><span class="badge badge-danger"><?php echo $driver;?></span> Drivers</div>
                            </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div  class=" text-white o-hidden h-100" style="background-color: #000047;">
                            <div class="card-body">
                                <a href="admin-view-vehicle.php">
                                    <div class="card-body-icon">
                                        <i class="fas fa-fw fa fa-bus"></i>
                                    </div>
                                    <?php
                                    //code for summing up number of vehicles
                                    $result ="SELECT count(*) FROM tms_vehicle";
                                    $stmt = $mysqli->prepare($result);
                                    $stmt->execute();
                                    $stmt->bind_result($vehicle);
                                    $stmt->fetch();
                                    $stmt->close();
                                    ?>
                                <div class="mr-5 fas" style="font-size: 25px;"><span class="badge badge-danger"><?php echo $vehicle;?></span> Vehicles</div>
                            </div>
                            </a>
                        </div>
                    </div>
                    <div class="col-xl-3 col-sm-6 mb-3">
                        <div  class=" text-white o-hidden h-100" style="background-color: #000047;">
                            <div class="card-body">
                                <a href="admin-view-booking.php">
                                <div class="card-body-icon">
                                    <i class="fas fa-fw fa fa-address-book"></i>
                                </div>
                                <?php
                                //code for summing up number of booking 
                                $result ="SELECT count(*) FROM tms_user where u_car_book_status = 'Approved' || u_car_book_status = 'Pending' ";
                                $stmt = $mysqli->prepare($result);
                                $stmt->execute();
                                $stmt->bind_result($book);
                                $stmt->fetch();
                                $stmt->close();
                                ?>
                                <div class="mr-5 fas" style="font-size: 25px;"><span class="badge badge-danger"><?php echo $book;?></span> Bookings</div>
                            </div>
                            </a>
                        </div>
                    </div>
                </div>
                <!--Bookings-->
                <div class="card mb-3 fas">
                    <style>
                            .badge.unknown {
                                border: none !important;
                                background-color: #e4e8e5!important;
                                color: black;
                                padding: 5px 10px;
                                border-radius: 10px;
                                font-size: 14px;
                            }
                    </style>
                    <div class="row">
                    <div class="mb-3 col-xl-5 col-sm-6 mb-9">
                        <div class="card text-white bg-dark o-hidden h-70">
                            <div class="card-body card"  style="background: #dedfdc67; color:#000047">
                                <div class="card-body-icon">
                                    <i class="fas fa-fw fa-users" style="color:red!important;  border: none!important;"></i>
                                </div>
                                    <?php
                                    //code for summing up number of users 
                                    $result ="SELECT count(*) FROM tms_user where u_category = 'User'";
                                    $stmt = $mysqli->prepare($result);
                                    $stmt->execute();
                                    $stmt->bind_result($user);
                                    $stmt->fetch();
                                    $stmt->close();
                                    ?>
                                <div class="table-table table-striped table-hover small" style="color:#000047;">
                                    <div class="table-responsive card small" style="background: #dedfdcb0; color:#000047">
                                        <table class="table-bordered table-striped table-hover small" id="dataTable" width="100%" cellspacing=".5rem">
                                            <thead>
                                                <tr style="font-size: .6rem;">
                                                    <th>#</th>
                                                    <th>Name</th>
                                                    <th>Phone</th>
                                                    <th>Vehicle Type</th>
                                                    <th>Reg No</th>
                                                    <th>Booking date</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $ret="SELECT * FROM tms_user where u_car_book_status = 'Approved' || u_car_book_status = 'Pending' "; //get all bookings
                                                $stmt= $mysqli->prepare($ret) ;
                                                $stmt->execute() ;//ok
                                                $res=$stmt->get_result();
                                                $cnt=1;
                                                while($row=$res->fetch_object())
                                                {
                                                ?>
                                                <tr style="font-size: .6rem;">
                                                    <td><?php echo $cnt;?></td>
                                                    <td><?php echo $row->u_fname;?> <?php echo $row->u_lname;?></td>
                                                    <td><?php echo $row->u_phone;?></td>
                                                    <td><?php echo $row->u_car_type;?></td>
                                                    <td><?php echo $row->u_car_regno;?></td>
                                                    <td><?php echo $row->u_car_bookdate;?></td>
                                                    <td><?php if($row->u_car_book_status == "Pending"){ echo '<span class = "badge badge-warning">'.$row->u_car_book_status.'</span>'; } else { echo '<span class = "badge badge-success">'.$row->u_car_book_status.'</span>';}?></td>
                                                </tr>
                                                <?php  $cnt = $cnt +1; }?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3 col-xl-7 col-sm-6 mb-9">
                        <div class="table-table card text-white bg-dark h-50">
                           <div class="card-body card" style="background: #dedfdc67; color:#000047">
                                                <div class="table-responsive small card">
                                                    <table class="table-bordered table-striped table-hover" id="dataTable" width="100%" cellspacing=".5" style="background: #dedfdc67;">
                                                        <thead>
                                                            <tr style="font-size: .6rem;">
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
                                                            <!-- <?php 
                                                                $datetime = $row->u_car_createdat;
                                                                                    $date = date("Y-m-d", strtotime($datetime));
                                                                                    $time = date("h:i A", strtotime($datetime));
                                                            ?> -->
                                                            <tr style="font-size: .8rem;">
                                                                <td><?php echo $cnt;?></td>
                                                                <td><?php echo $date;?></td>
                                                                <td><?php echo $time;?></td>
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
                                                                            echo '<span class="badge badge-secondary"></span>';
                                                                    }
                                                                ?>
                                                            </td>
                                                    </tr>
                                                    <?php  $cnt = $cnt +1; }?>
                                                </tbody>
                                            </table>
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