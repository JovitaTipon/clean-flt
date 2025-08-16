<?php
session_start();
include('vendor/inc/config.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['v_name'] ?? '';
    $reg_no = $_POST['v_reg_no'] ?? '';
    $pax = $_POST['v_pass_no'] ?? '';
    $driver = $_POST['v_driver'] ?? '';
    $category = $_POST['v_category'] ?? '';
    $status = $_POST['v_status'] ?? '';
    $aid = $_SESSION['a_id'];

    // Handle uploaded image
    $uploadDir = "vendor/img/vehicle/";
    $imgName = $_FILES['v_dpic']['name'];
    $imgTmp = $_FILES['v_dpic']['tmp_name'];
    $imgPath = $uploadDir . basename($imgName);

    if (move_uploaded_file($imgTmp, $imgPath)) {
        $stmt = $mysqli->prepare("INSERT INTO tms_vehicle (v_name, v_reg_no, v_pass_no, v_driver, v_category, v_status, v_dpic) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssissss", $name, $reg_no, $pax, $driver, $category, $status, $imgName);

        if ($stmt->execute()) {
            $newId = $stmt->insert_id;
            echo json_encode([
                "success" => true,
                "vehicle" => [
                    "id" => $newId,
                    "name" => $name,
                    "reg_no" => $reg_no,
                    "driver" => $driver
                ]
            ]);
        } else {
            echo json_encode(["success" => false, "message" => "DB insert failed"]);
        }
    } else {
        echo json_encode(["success" => false, "message" => "Image upload failed"]);
    }
}
?>
