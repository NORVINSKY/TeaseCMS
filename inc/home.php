<?php

if(!file_exists(_CACHE_.'home.dat') || filesize(_CACHE_.'home.dat') < 50){

	//формируем файл главной страницы
	$HOME_PAGE_FILE = '';
	file_put_contents(_CACHE_.'home.dat', $HOME_PAGE_FILE, LOCK_EX);
}

//Редиректируем по уму
if(isset($match['params']['id'])){ s_redirect(1); }

	$rawPData = array_reverse(file(_CACHE_.'home.dat'));
	$rawPData = array_unique($rawPData);
	$rawPData = array_chunk($rawPData, 20);
	
	list($P_COUNT, $CG_CUPAG, $VD_ID, $V_DATA) = archivePrepare();
	define('PGTYPE', 'home');
	
	$AP_DATA = a_prepareData($rawPData[$VD_ID]);
