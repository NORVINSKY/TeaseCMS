<?php
ini_set('display_errors', 'Off');
ini_set('display_startup_errors', 'Off');
error_reporting(0);

session_start();
$erroLogs = '';
mb_internal_encoding("UTF-8");

include_once realpath(__DIR__ .'/config.php');
include_once _LIB_ .'func.php';


#Удаление невалидного контента
if(_DEBUG_ && isset($argv[1]) && $argv[1] == 'delete'){
	include_once _LIB_ .'delete.php'; die;
}

#CLI импорт контента
if(_DEBUG_ && isset($argv[1]) && $argv[1] == 'import'){
	$argc = count($argv);
	// Сдвигаем аргументы, чтобы argv[1] был URL
	$cliArgs = array_slice($argv, 1);
	$argv = $cliArgs;
	include_once __DIR__ . '/import.php'; die;
}

#Доступ к удалённому управлению
if(stripos($_SERVER['REQUEST_URI'], '?api') == '1'){
	include_once _LIB_ .'api.php'; die;
}

trackedData();

#Клоака
if(isset($_GET[_RMARK_])){
  include_once _LIB_ .'cloak.php'; die;
}

#Наполение галеры
if(_DEBUG_ && stripos($_SERVER['REQUEST_URI'], '?import') == '1'){
	include_once _LIB_ .'import.php'; die;
}

#Sitemap.xml
if(preg_match('~^.*\/sitemap\.xml~', $_SERVER['REQUEST_URI'])){
	header ('Content-type: text/xml; charset=utf-8');
	sitemapXML(); die;
}

#Robots.txt
if(preg_match('~^.*\/robots\.txt~', $_SERVER['REQUEST_URI'])){
	header('Content-Type: text/plain; charset=utf-8');
	robotsTXT(); die;
}

#Чистим кэш
if(_DEBUG_ && isset($_GET['clean'])){

	delTree(_CACHE_); delTree(_ROOT_.'static/'); @unlink(_DATA_.'READY'); @unlink(_DATA_.'dynOptions.php');
	die('DONE?'); //Позже запилить проверку на удачную очистку
}

#Подготавливаем структуру
if(!file_exists(_DATA_.'READY')){

	$structures = [_CACHE_.'pages/'=>0, _CACHE_.'counter/'=>1, _CACHE_.'cats/'=>1, _CACHE_.'tags/'=>1, _CACHE_.'sitemap/'=>1, _ROOT_.'static/'=>0 , _DATA_.'adv/'=>1 ];

	foreach($structures as $struct=>$st){
		if(!file_exists($struct)){
			if (!mkdir($struct, 0777, true)) {
				$erroLogs .= 'Could not create "'.$struct.'"dir.<br>';
			}else{ if($st === 1){ file_put_contents($struct.'.htaccess', "<FilesMatch \"\.(dat|txt)\.?.*\">\r\nOrder allow,deny\r\nDeny from all\r\n</FilesMatch>", LOCK_EX); } }
		}
	} if(file_exists(_CACHE_)){ file_put_contents(_CACHE_.'.htaccess', "<FilesMatch \"\.(dat|txt)\.?.*\">\r\nOrder allow,deny\r\nDeny from all\r\n</FilesMatch>", LOCK_EX); }

    if(!file_exists(_DATA_.'cloakBox.php')){ file_put_contents(_DATA_.'cloakBox.php', ''); }

	//Подготовка категорий
	$TAGLIST = file(_DATA_.'TAGLIST.txt');
	$TAGLIST = array_map('cleanStr', $TAGLIST);
	shuffle($TAGLIST);

STARTER();

if(empty($erroLogs)){ file_put_contents(_DATA_.'READY', time(), LOCK_EX); }else{ die($erroLogs); }
}

(file_exists(_DATA_.'dynOptions.php'))? include_once _DATA_.'dynOptions.php' : die('File <b>dynOptions.php</b> not found.') ;


#Счётчик. Пишем данные о посетителе
if(_CNT_ === 1){
  $c_referer = (isset($_SERVER["HTTP_REFERER"]))? $_SERVER["HTTP_REFERER"] : 'DIRECT' ;
  $counterString = time().'##$##'.$_SERVER["REQUEST_URI"].'##$##'.$c_referer.'##$##'.$_SERVER["HTTP_USER_AGENT"].'##$##'.getUserIP()."\r\n";
  file_put_contents(_CACHE_.'counter/CN_'.date('dmY').'.dat', $counterString, FILE_APPEND|LOCK_EX);
}

#Роутинг страниц
$router = new AltoRouter();

$router->map('GET','/', null, 'home');
$router->map('GET','/page/[i:id]/?', null, 'home-page');
$router->map('GET','/sitemap/[i:id]/?', null, 'sitemap');
$router->map('GET','/[:id]'._EXT_, null, 'page');
$router->map('GET','/[:id]/[i:pg]?/?', null, 'category');
//Текстовые страницы регистрируются после категории, чтобы слаг категории «text» не перехватывался
//POST — обработчик формы контактов (обманка с благодарностью)
$router->map('GET|POST','/text/[:id]?', null, 'text-page');
$router->map('GET','/tag/[*:id]?/[i:pg]?/?', null, 'tag');

$match = $router->match();

if($match){
	if(file_exists(_LIB_.$match['name'].'.php')){ include_once _LIB_.$match['name'].'.php'; }else{ define('PGTYPE', '404'); page404(); }
	if(file_exists(_GUI_.$match['name'].'.php')){ include_once _GUI_.'inc/header.php'; include_once _GUI_.$match['name'].'.php'; include_once _GUI_.'inc/footer.php';}else{ define('PGTYPE', '404'); page404(); }
}else{ define('PGTYPE', '404'); page404(); }

if(_DEBUG_){
	$execTime = number_format((float)(microtime(true) - $_SERVER["REQUEST_TIME_FLOAT"]), 3, '.', '');
	echo "<code>Execution Time: ".$execTime."sec.</code>";
}
