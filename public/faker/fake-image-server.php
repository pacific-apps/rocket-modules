<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: token, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PATCH, PUT, DELETE, OPTION');
header('Access-Control-Max-Age: 1728000');
if (!defined('LOCAL')) header('Content-Length: 0');
header('Access-Control-Allow-Credentials: true');
if ($_SERVER['REQUEST_METHOD'] == "OPTIONS") {
    header('Access-Control-Allow-Origin: *');
    header("Access-Control-Allow-Headers: X-API-KEY, Origin, X-Requested-With, Content-Type, Accept, Access-Control-Request-Method,Access-Control-Request-Headers, Authorization");
    header("HTTP/1.1 200 OK");
    exit();
}


if(isset($_FILES['file']['name'])){

   /* Getting file name */
   $filename = $_FILES['file']['name'];

   /* Location */
   $newFileName = (rand(1,1000000000))*13817283;
   $location = $_SERVER["DOCUMENT_ROOT"]."/aws/images/".$newFileName;
   if(move_uploaded_file($_FILES['file']['tmp_name'],$location)){
      $response = $location;
   }

   echo "http://localhost/aws/images/".$newFileName;
   exit;
}

echo 0;
