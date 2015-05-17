<?php

$environment = $_SERVER["SERVER_NAME"];
switch($environment){
  case 'production':
  case 'fk.patrizio.me':
    include 'environments/production.php';
  break;  
  // case 'staging':
  //   include 'environments/staging.php';
  // break;
  case 'localhost':
  case 'development':
    include 'environments/development.php';
  break;
  /*default:
    include 'environments/default.php'
  break;*/
}

