<?php

//Редиректируем по уму
s_redirect(1);

$URI = urldecode($match['params']['id']);
if(file_exists(_CACHE_.'tags/'.$URI.'.dat')){

	$TAG_TITLE = mb_ucwords(str_replace('-', ' ', $URI));

	//Pagenavi
	$rawPData = file(_CACHE_.'tags/'.$URI.'.dat');
	$rawPData = array_chunk($rawPData, 20);
	
	list($P_COUNT, $CG_CUPAG, $VD_ID, $V_DATA) = archivePrepare();
	define('PGTYPE', 'tag');
	
	//Делаем выборку	
	$AP_DATA = a_prepareData($rawPData[$VD_ID]);

}else{ define('PGTYPE', '404'); page404(); }