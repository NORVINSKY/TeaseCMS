<?php

/*
$LINKS - массив ссылок
$f_cnt - кол-во файлов сайтмапов
$PAGE_NUM - текущая страница
*/

//Редиректируем по уму
s_redirect(1);

//Формируем сайтмап
sitemapGen();

$LINKS = []; $f_cnt = 0; 
$PAGE_NUM = $match['params']['id'];

//Пока так, потом переделать в более порядочный вид
foreach(scandir(_CACHE_.'sitemap') as $f){
	if(stripos($f, 'stmpg') !== false){ $f_cnt++; }
}

if(file_exists(_CACHE_.'sitemap/stmpg-'.$match['params']['id'].'.dat')){ define('PGTYPE', 'sitemap');
	$LINKS = unserialize(file_get_contents(_CACHE_.'sitemap/stmpg-'.$match['params']['id'].'.dat', LOCK_EX));
}else{ define('PGTYPE', '404'); page404(); }
