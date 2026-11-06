<?php

$returnString = '';
	
#Отдаем данные счетчика
if(stripos($_SERVER["REQUEST_URI"], 'api=getcounter') !== false){
	$currentDate = date('dmY'); 

	if(file_exists(_CACHE_.'counter/')){
		$FILES = scandir(_CACHE_.'counter/');
		foreach($FILES as $file){
			if($file=='.htaccess' || $file=='..' || $file=='.' || strpos($file, $currentDate) !== false){ continue; }
			$returnString .= file_get_contents(_CACHE_.'counter/'.$file); 
		} unset($file);
		
		echo $returnString;
		
		//удаляем данные счетчика
		if(stripos($_SERVER["REQUEST_URI"], '&delete') !== false){
			foreach($FILES as $file){
				if($file=='..' || $file=='.' || strpos($file, $currentDate) !== false || strpos($file, 'htaccess') !== false){ continue; }
				unlink(_CACHE_.'counter/'.$file);
			} unset($file);
		}
	}else{ die('NO_FOLDER_EXISTS'); }
	die;
}