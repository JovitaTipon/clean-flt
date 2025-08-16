<!DOCTYPE html>
<html lang="en">
<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid=$_SESSION['a_id'];
  include('vendor/inc/head.php');
?>
<body id="page-top">
    <?php include("vendor/inc/nav.php");?>
    <div id="wrapper">
        <!-- Sidebar -->
        <?php include('vendor/inc/sidebar.php');?>
        <div id="content-wrapper">
            <div class="container-fluid fas">
                <ol class="breadcrumb">
                    <h6 class="m-0 font-weight-bold" style="font-size: 30px; color:#000047;">User's Vehicle Monito</h6>     
                 </ol>
                <!-- Breadcrumbs-->
                <!-- Header Title -->
                <h4 style="font-size: 15px; font-weight: bold;"></h4>
                    <iframe 
                        src="https://maps.google.com/maps?q=14.5995,120.9842&z=12&output=embed" 
                        width="100%" 
                        height="400" 
                        frameborder="0" 
                        style="border:0" 
                        allowfullscreen 
                        aria-hidden="false" 
                        tabindex="0">
                    </iframe>
                </div>
<!-- Google Maps API Script -->
<script src="https://maps.googleapis.com/maps/api/js?key=YOUR_GOOGLE_MAPS_API_KEY&callback=initMap"
        async defer></script>
                <!-- Trip History Cards -->
                <div class="row">
                    <!-- Trip History Card -->
                    <div class="col-md-12 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-primary text-white">Trip History</div>
                            <div class="card-body" style="background: #dedfdcb0; color:#000047">
                                <p><strong>Date / Time:</strong> <span class="float-right">View</span></p>
                                <hr>
                                <p><strong>Start-End Location:</strong> <span class="float-right">View</span></p>
                                <hr>
                                <p><strong>Assigned Driver:</strong> <span class="float-right">Felipe</span></p>
                                <hr>
                                <p><strong>Distance (km):</strong> <span class="float-right">300 km</span></p>
                                <hr>
                                <p><strong>Fuel Used:</strong> <span class="float-right">1 Ltr</span></p>
                            </div>
                        </div>
                    </div>
                    <!-- Placeholder for 2nd Card -->
                    <div class="col-md-12 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-info text-white">Current Route</div>
                            <div class="card-body" style="background: #dedfdcb0; color:#000047">
                                <p><strong>Start Location </strong> <span class="float-right">View</span></p>
                                <hr>
                                <p><strong>Destination</strong> <span class="float-right">View</span></p>
                                <hr>
                                <p><strong>Assigned Driver:</strong> <span class="float-right">Felipe</span></p>
                                <hr>
                                <p><strong>ETA</strong> <span class="float-right">20 Mins</span></p>
                                <hr>
                                <!-- <p><strong>Fuel Used:</strong> <span class="float-right">1 Ltr</span></p> -->
                            </div>
                        </div>
                    </div>
                    <!-- Placeholder for 3rd Card -->
                    <div class="col-md-12 col-lg-4 mb-4">
                        <div class="card h-100">
                            <div class="card-header bg-warning text-white">Diagnostics</div>
                            <div class="card-body" style="background: #dedfdcb0; color:#000047">
                                <p><strong>Fuel Level</strong> <span class="float-right">45%</span></p>
                                <hr>
                                <p><strong>Speed</strong> <span class="float-right">60km/hr</span></p>
                                <hr>
                                <p><strong>OBD Status</strong> <span class="float-right">Needs Attenion</span></p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Footer Timestamp -->
                <div class="card-footer small text-muted">
                    <?php
                    date_default_timezone_set("Asia/Manila");
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
                        <span aria-hidden="true">&times;</span>
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
    <!-- Scripts -->
    <script src="vendor/jquery/jquery.min.js"></script>
    <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="vendor/jquery-easing/jquery.easing.min.js"></script>
    <script src="vendor/datatables/jquery.dataTables.js"></script>
    <script src="vendor/datatables/dataTables.bootstrap4.js"></script>
    <script src="js/sb-admin.min.js"></script>
    <script src="js/demo/datatables-demo.js"></script>
</body>
</html>
