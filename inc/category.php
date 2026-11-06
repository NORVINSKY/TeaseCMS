<?php

//Редиректируем по уму
s_redirect(2);

//Костыль сайтмапа
if(stripos($_SERVER["REQUEST_URI"], '/sitemap') !== false ){
	header("HTTP/1.1 301 Moved Permanently"); 
	header("Location: /sitemap/1"); die;
}

if(preg_match("~^/tag/~is", $_SERVER["REQUEST_URI"], $mtch)){ //Костыль тэгам
	define('PGTYPE', 'tag');
	$TAG_TITLE = 'Tag list';
	$t_TAGLIST = scandir(_CACHE_.'tags/');
	$t_TAGLIST = cleanArray($t_TAGLIST, ['.','..','.htaccess']);
	
	$html_TAGLIST = ''; 
	foreach($t_TAGLIST as $t_tag){
		$t_tag = str_replace('.dat', '', $t_tag);
		$Tt_tag = str_replace('-', ' ', $t_tag);
		$html_TAGLIST .= '<li><a href="'._URL_.'tag/'.$t_tag.'">'.mb_ucwords($Tt_tag).'</a></li>';
	}
	
}else{
	$URI = $match['params']['id'];
	if(file_exists(_CACHE_.'cats/'.$URI.'.dat')){

		$CAT_TITLE = mb_ucwords(str_replace('-', ' ', $URI));

		//Pagenavi
		$rawPData = file(_CACHE_.'cats/'.$URI.'.dat');
		$rawPData = array_chunk($rawPData, 20);
		
		list($P_COUNT, $CG_CUPAG, $VD_ID, $V_DATA) = archivePrepare();
		define('PGTYPE', 'category');
		
		//Делаем выборку		
		$AP_DATA = a_prepareData($rawPData[$VD_ID]);

	}else{
			define('PGTYPE', '404'); page404(); 
	}		
}
