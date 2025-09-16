<?php
session_start();
include('../vendor/inc/config.php');
include('../vendor/inc/checklogin.php');
check_login();
require_admin();


header('Content-Type: application/json');
$driver_id = isset($_GET['driver_id']) ? (int)$_GET['driver_id'] : 0;
$vehicle_id = isset($_GET['vehicle_id']) ? (int)$_GET['vehicle_id'] : 0;


$out = ['driver'=>null,'vehicle'=>null];


if ($vehicle_id) {
$sql = "SELECT v.v_id, v.v_reg_no, v.v_name, u.u_id, u.u_fname, u.u_lname
FROM tms_vehicle v
LEFT JOIN tms_user u ON u.u_id = v.driver_user_id
WHERE v.v_id = ?";
if ($s=$mysqli->prepare($sql)){
$s->bind_param('i',$vehicle_id);
$s->execute();
$s->bind_result($vid,$vreg,$vname,$uid,$uf,$ul);
if ($s->fetch()){
$out['vehicle'] = ['v_id'=>$vid,'label'=>trim(($vreg?:$vname))];
if ($uid) $out['driver'] = ['u_id'=>$uid,'label'=>trim("$uf $ul")];
}
$s->close();
}
}
elseif ($driver_id) {
$sql = "SELECT u.u_id, u.u_fname, u.u_lname, v.v_id, v.v_reg_no, v.v_name
FROM tms_user u
LEFT JOIN tms_vehicle v ON v.driver_user_id = u.u_id
WHERE u.u_id = ?";
if ($s=$mysqli->prepare($sql)){
$s->bind_param('i',$driver_id);
$s->execute();
$s->bind_result($uid,$uf,$ul,$vid,$vreg,$vname);
if ($s->fetch()){
$out['driver'] = ['u_id'=>$uid,'label'=>trim("$uf $ul")];
if ($vid) $out['vehicle'] = ['v_id'=>$vid,'label'=>trim(($vreg?:$vname))];
}
$s->close();
}
}


echo json_encode($out);