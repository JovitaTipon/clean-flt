<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Vehicle Booking System Transport Saccos, Matatu Industry">
    <meta name="author" content="MartDevelopers">
    <title>Booking Cars - User| Client Dashboard</title>
    <!-- Custom fonts for this template-->
    <link href="vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <!-- Page level plugin CSS-->
    <link href="vendor/datatables/dataTables.bootstrap4.css" rel="stylesheet">
    <!-- Custom styles for this template-->
    <link href="vendor/css/sb-admin.css" rel="stylesheet">
    
</head>



<!-- Modal: place this right before the closing </body> (outside main container) -->
<div class="modal fade" id="logTripModal" tabindex="-1" role="dialog" aria-labelledby="logTripModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document" >
    <div class="modal-content">

      <div class="modal-header" style="background-color: #000047; border-color: navy; color:antiquewhite;">
        <h5 class="modal-title w-100 text-center" id="logTripModalLabel">PLEASE PROVIDE DETAILS OF THE TRIP</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body" style="background-color: #000047; border-color: navy; color:antiquewhite;">
        <form method="POST" id="logTripForm" action="">
          <div class="form-group text-center">
            <label for="trip_id">Trip ID</label>
            <input type="text" id="trip_id" name="trip_id" class="form-control w-50 mx-auto" required>
          </div>

          <div class="form-group text-center">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" class="form-control w-50 mx-auto" required>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="pickup">Pick-Up Location</label>
              <input type="text" id="pickup" name="pickup" class="form-control" required>
            </div>
            <div class="form-group col-md-6">
              <label for="dropoff">Drop-Off Location</label>
              <input type="text" id="dropoff" name="dropoff" class="form-control" required>
            </div>
          </div>

          <div class="form-group text-center">
            <label for="odometer">Odometer Reading</label>
            <input type="number" id="odometer" name="odometer" class="form-control w-50 mx-auto" required>
          </div>

          <div class="text-center">
            <button type="submit" name="add_log" class="btn btn-success" style="background-color: navy; border-color: navy;">+ Log Trip</button>
          </div>
        </form>
      </div>

    </div>
  </div>
</div>
