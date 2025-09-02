<?php
  session_start();
  include('vendor/inc/config.php');
  include('vendor/inc/checklogin.php');
  check_login();
  $aid = $_SESSION['a_id'];

  if (isset($_POST['add_user'])) {
    $u_fname = $_POST['u_fname'];
    $u_lname = $_POST['u_lname'];
    $u_car_date = $_POST['u_car_date'];
    $u_car_time = $_POST['u_car_time'];
    $u_car_pax  = $_POST['u_car_pax'];
    $u_car_pickup = $_POST['u_car_pickup'];
    $u_car_destination = $_POST['u_car_destination'];
    $u_car_regno = $_POST['u_car_regno'];
    $u_car_type = $_POST['u_car_type'];
    $u_car_driver = $_POST['u_car_driver'];
    $u_category = $_POST['u_category'];
    $u_email = $_POST['u_email'];
    $u_pwd = password_hash($_POST['u_pwd'], PASSWORD_DEFAULT);

    $query = "INSERT INTO tms_user (
      u_fname, u_lname, u_car_date, u_car_time, u_car_pax,
      u_car_pickup, u_car_destination, u_car_regno,
      u_car_type, u_car_driver, u_category, u_email, u_pwd,
      u_car_book_status
    ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, 'Pending')";

    if ($stmt = $mysqli->prepare($query)) {
      $stmt->bind_param(
        'sssssssssssss',
        $u_fname, $u_lname, $u_car_date, $u_car_time, $u_car_pax,
        $u_car_pickup, $u_car_destination, $u_car_regno,
        $u_car_type, $u_car_driver, $u_category, $u_email, $u_pwd
      );
      $ok = $stmt->execute();
      $stmt->close();
      $succ = $ok ? "Booking created." : "Please Try Again Later";
    } else { $err = "DB error while preparing statement."; }
  }
?>
<!DOCTYPE html>
<html lang="en">
<?php include('vendor/inc/head.php'); ?>
<body id="page-top">
  <?php include('vendor/inc/nav.php'); ?>
  <div id="wrapper">
    <?php include('vendor/inc/sidebar.php'); ?>

    <div id="content-wrapper">
      <div class="container-fluid">

        <h1 class="kaya-page-title">Create Trip Appointments</h1>

        <!-- Toolbar (tabs left, actions right) -->
        <div class="kaya-toolbar d-flex align-items-center mb-3">
          <div class="btn-group" role="group" aria-label="Filters">
            <a href="admin-trip-appointment.php" class="btn kaya-tab">Upcoming</a>
            <a href="admin-view-booking.php"   class="btn kaya-tab">Completed</a>
          </div>
          <div class="kaya-actions ml-auto btn-group" role="group" aria-label="Actions">
            <a href="admin-create-booking.php" class="btn btn-kaya-primary">New Trip</a>
            <a href="admin-manage-booking.php" class="btn btn-kaya-danger-outline">Cancelled</a>
          </div>
        </div>

        <?php if(!empty($succ)): ?>
          <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($succ) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
          </div>
        <?php endif; ?>
        <?php if(!empty($err)): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($err) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
          </div>
        <?php endif; ?>

        <!-- Create form -->
        <section class="kaya-card">
          <form method="POST" class="px-2">
            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Date</label>
                <input type="date" required class="form-control" name="u_car_date">
              </div>
              <div class="form-group col-md-6">
                <label>Time</label>
                <input type="time" required class="form-control" name="u_car_time">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Customer</label>
                <input type="text" required class="form-control" name="u_fname">
              </div>
              <div class="form-group col-md-6" style="display:none">
                <label>Last Name</label>
                <input type="text" class="form-control" name="u_lname">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-4">
                <label>Pax</label>
                <input type="number" class="form-control" name="u_car_pax">
              </div>
              <div class="form-group col-md-4">
                <label>Vehicle Type</label>
                <input type="text" class="form-control" name="u_car_type">
              </div>
              <div class="form-group col-md-4">
                <label>Reg No.</label>
                <input type="text" class="form-control" name="u_car_regno">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Pickup Location</label>
                <input type="text" class="form-control" name="u_car_pickup">
              </div>
              <div class="form-group col-md-6">
                <label>Destination</label>
                <input type="text" class="form-control" name="u_car_destination">
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-md-6">
                <label>Driver</label>
                <input type="text" class="form-control" name="u_car_driver">
              </div>
              <div class="form-group col-md-6" style="display:none">
                <label>Category</label>
                <input type="text" class="form-control" value="User" name="u_category">
              </div>
            </div>

            <div class="form-row" style="display:none">
              <div class="form-group col-md-6">
                <label>Email</label>
                <input type="email" class="form-control" name="u_email">
              </div>
              <div class="form-group col-md-6">
                <label>Password</label>
                <input type="password" class="form-control" name="u_pwd">
              </div>
            </div>

            <button type="submit" name="add_user" class="btn btn-kaya-primary">Create Booking</button>
            <a href="admin-trip-appointment.php" class="btn btn-outline-secondary ml-2">Back</a>
          </form>
        </section>

      </div>
      <?php include('vendor/inc/footer.php'); ?>
    </div>
  </div>

  <!-- Vendor JS -->
  <script src="vendor/jquery/jquery.min.js"></script>
  <script src="vendor/bootstrap/js/bootstrap.bundle.min.js"></script>
  <script src="vendor/jquery-easing/jquery.easing.min.js"></script>

  <style>
    footer.sticky-footer{ background:transparent!important; height:0!important; border:0!important; box-shadow:none!important; }
    footer.sticky-footer .container, footer.sticky-footer .copyright{ display:none!important; }
    #wrapper #content-wrapper{ padding-bottom:0!important; }
  </style>
</body>
</html>
