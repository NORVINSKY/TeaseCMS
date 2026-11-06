<?php

include_once _LIB_ .'branch/RollingCurl.class.php';
include_once _LIB_ .'branch/AngryCurl.class.php';
//include_once _LIB_ .'branch/simple_html_dom.php';
include_once _LIB_ .'branch/gd_imagestyle.php';
include_once _LIB_ .'branch/AltoRouter.php';
include_once _LIB_ .'branch/parsers.php';


#Чистим строку от всякого
function cleanStr($str, $st=0){
	if($st==1){$str = strip_tags($str);}
    $str = str_replace("\n", "", $str);
    $str = str_replace("\r", "", $str);
    $str = preg_replace("/\t/"," ","$str");
    $str = preg_replace('/[ ]+/', ' ', $str);
	$str = trim($str);
 return $str;
}

#Выдаем случайный UserAgent
function randUA(){
	$UA = file(_DATA_ . 'UA.txt');
	return cleanStr($UA[array_rand($UA)]);
}

#SPINTAX
class Spintax{
	public function process($text){
		return preg_replace_callback('/\{(((?>[^\{\}]+)|(?R))*)\}/x', [$this, 'replace'], $text);
	}
	public function replace($text){
		$text = $this->process($text[1]);
		$parts = explode('|', $text);
		return $parts[array_rand($parts)];
	}
}

#Делаем первую букву заглавной
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string, $enc='UTF-8') {
      return mb_strtoupper(mb_substr($string, 0, 1, $enc), $enc) .
             mb_substr($string, 1, mb_strlen($string, $enc), $enc);
    }
}

#Capitalize
function mb_ucwords($str) {
$str = mb_convert_case($str, MB_CASE_TITLE, "UTF-8");
return ($str);
}

#Чистим массив от всякого
function cleanArray($a, $m=['',' ']){
	return array_diff($a, $m);
}

#Строка в ID
function str2id($s){ return $st = preg_replace ("/[^a-zA-Z0-9]/", "-", cleanStr($s)); }

#Категория в каноничный слаг: единый вид для сравнения и имён файлов (регистр/пробелы/дефисы не важны)
function normalizeCatSlug($s){
	$slug = str2id(mb_strtolower(cleanStr($s)));
	$slug = preg_replace('/(\-){2,}/', '-', $slug);
	return trim($slug, '-');
}

#Проверка: есть ли среди категорий запрещённые (строгое равенство нормализованных слагов)
function isBannedCats($cats, $banned = null){
	if($banned === null){ $banned = isset($GLOBALS['BANNED_CATS']) ? $GLOBALS['BANNED_CATS'] : []; }
	if(empty($cats) || empty($banned)){ return false; }
	if(!is_array($cats)){ $cats = [$cats]; }
	if(!is_array($banned)){ $banned = [$banned]; }
	$banMap = [];
	foreach($banned as $b){
		$bs = normalizeCatSlug($b);
		if($bs !== ''){ $banMap[$bs] = true; }
	}
	if(empty($banMap)){ return false; }
	foreach($cats as $c){
		$cs = normalizeCatSlug($c);
		if($cs !== '' && isset($banMap[$cs])){ return true; }
	}
	return false;
}

#Задаем имя скачаной картинке
function renameIMG($tn, $w, $e, $p){
	$name = str2id($w).'-'.mt_rand(1, 9999).$e;
	if(file_exists($p.$name)){
		return renameIMG($tn, $w, $e);
	}else{
		rename($p.$tn, $p.$name);
		return $name.'!##$##!';
	}
	return false;
}

#Расширение картинки из mime-type
function getImgExt($ct){
	if(stripos($ct, 'jpeg') !== false){
		return '.jpg'; }
	elseif(stripos($ct, 'jpg') !== false){
		return '.jpg'; }
	elseif(stripos($ct, 'gif') !== false){
		return '.gif'; }
	elseif(stripos($ct, 'png') !== false){
		return '.png'; }
	elseif(stripos($ct, 'bmp') !== false){
		return '.bmp'; }
	else{	return '.jpg'; }
}

#Модный var_dump
function xx($v){ echo '<pre>'; die(var_dump($v)); }

//рекурсивное удаление папки
function delTree($dir) {
	if(file_exists($dir)){
	$files = array_diff(scandir($dir), array('.','..'));
	foreach ($files as $file) {
		 (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
	}	return rmdir($dir); }
}

//Обработка и скачивание превьюшки с проверками и повторными попытками
function getThumb($url, $uri){
	global $TH_W, $TH_H;

	if(empty($url)){
		return 'no_thumb.jpg';
	}

	$targetThumb = $uri.'-thumb.jpg';
	// Если уже существует валидная превьюшка, отдаем её
	if(file_exists(_STATIC_.$targetThumb) && filesize(_STATIC_.$targetThumb) > 100){
		return $targetThumb;
	}

	$t_name = uniqid('tmp_');
	$tempPath = _STATIC_.$t_name;
	$downloaded = false;

	// До 3 попыток скачивания с паузой при неудаче
	for($attempt = 1; $attempt <= 3; $attempt++){
		$imgData = getCurl($url);
		if(!empty($imgData) && strlen($imgData) > 500){
			file_put_contents($tempPath, $imgData, LOCK_EX);
			$sx = @getimagesize($tempPath);
			if($sx !== false && isset($sx[0]) && $sx[0] > 10 && isset($sx[1]) && $sx[1] > 10){
				$downloaded = true;
				break;
			}
		}
		if(file_exists($tempPath)){
			@unlink($tempPath);
		}
		if($attempt < 3){
			usleep(300000); // 300ms пауза перед повторной попыткой
		}
	}

	if($downloaded && file_exists($tempPath)){
		$sx = @getimagesize($tempPath);
		conv2JPEG($tempPath, $tempPath, $sx['mime']);
		$big_image = @imagecreatefromjpeg($tempPath);
		if($big_image !== false){
			$resized = imagestyle($big_image, "resize:{$TH_W} {$TH_H}");
			imagejpeg($resized, _STATIC_.$targetThumb, 85);
			@imagedestroy($resized);
			@imagedestroy($big_image);
		}
		@unlink($tempPath);

		// Финальная проверка созданного файла
		if(file_exists(_STATIC_.$targetThumb) && filesize(_STATIC_.$targetThumb) > 100 && @getimagesize(_STATIC_.$targetThumb) !== false){
			return $targetThumb;
		} else {
			if(file_exists(_STATIC_.$targetThumb)){
				@unlink(_STATIC_.$targetThumb);
			}
		}
	}

	// Очищаем временный файл, если остался
	if(file_exists($tempPath)){
		@unlink($tempPath);
	}

	// Логируем ошибку для отладки
	@file_put_contents(_DATA_.'errIMGs.txt', date('Y-m-d H:i:s')." | {$url}\r\n", FILE_APPEND|LOCK_EX);

	// Не удалось получить валидное изображение
	return false;
}

//Конвертируем в JPEG
function conv2JPEG($originalImage, $outputImage, $mime, $quality=100){

    $ext = getImgExt($mime);

    if(preg_match('/jpg|jpeg/i', $ext)){
			return true;
		}elseif(preg_match('/png/i', $ext)){
			$imageTmp=imagecreatefrompng($originalImage);
		}elseif(preg_match('/gif/i', $ext)){
      $imageTmp=imagecreatefromgif($originalImage);
		}elseif(preg_match('/bmp/i', $ext)){
      $imageTmp=imagecreatefrombmp($originalImage);
		}else{ return false; }

    // quality is a value from 0 (worst) to 100 (best)
    imagejpeg($imageTmp, $outputImage, $quality);
    imagedestroy($imageTmp);

    return true;
}

//Выводим 404 страницу
function page404(){
	header("HTTP/1.0 404 Not Found");
	include_once _GUI_.'inc/header.php';
	include_once _GUI_.'404.php';
	include_once _GUI_.'inc/footer.php';
	die();
}

#Цапля (cURL запрос)
function getCurl($lnk, $cookie='', $referer=''){

	if(substr($lnk, 0, 2) == '//'){ $lnk = 'http:'.$lnk; }

	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $lnk);
	curl_setopt ($ch, CURLOPT_HEADER, 0);
	curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 15);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 30);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, false);
	curl_setopt ($ch, CURLOPT_USERAGENT, randUA());

	if(!empty($referer)){
		curl_setopt ($ch, CURLOPT_REFERER, $referer);
	}

	if(!empty($cookie)){
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Cookie: {$cookie}"));
	}
	curl_setopt ($ch, CURLOPT_COOKIEJAR, _DATA_.'cookies.txt');
	curl_setopt ($ch, CURLOPT_COOKIEFILE, _DATA_.'cookies.txt');
	$result = curl_exec($ch);
	curl_close($ch);

	return $result;
}

//Дополнение к Spintax
function getSpintax($src, $t='', $fd='#KWD#', $sd='#SRC#'){
	global $SITE_NAME;
	$spntx = new Spintax();
	$desc = $spntx->process($src[array_rand($src)]); unset($spntx);
	if(!empty($t)){ $desc = str_replace($fd, $t, $desc); }
	return cleanStr(str_replace($sd, $SITE_NAME, $desc));
}

//Генерация спин-тайтла
function spinTitle($cat){
  global $TITLE_SPIN;
  $pre = getSpintax($TITLE_SPIN, $cat);
  return str_replace('#RND#', '0'.mt_rand(10,99), $pre);
}

//Создание карты сайта
function sitemapGen(){
	$SITEMAP = []; $SM_FILES = [];

	$rawFiles = cleanArray(scandir(_CACHE_.'sitemap'), ['.htaccess', '.', '..']);
	foreach($rawFiles as $f){
		if(stripos($f, 'stmpg') !== false){
			$SM_FILES[] = $f;
		}else{
			if(file_exists(_CACHE_.'sitemap/'.$f)){
				include _CACHE_.'sitemap/'.$f;
				if(unlink(_CACHE_.'sitemap/'.$f)){ $SITEMAP[$f]['title'] = $P_TITLE; $SITEMAP[$f]['time'] = $P_CREATE; }
			}
		}
	} unset($rawFiles);

	if(count($SITEMAP)<1){ return; }

	$SITEMAP = multisort($SITEMAP, 'time');

	//Разбиваем общий список на карты
	$MAPS = getRawMap($SITEMAP); unset($SITEMAP);

	$sm_cnt = count($SM_FILES);
	if($sm_cnt === 0){ //первый запуск, карт сайта еще нету
		foreach($MAPS as $k=>$MAP){ $k++;
			file_put_contents(_CACHE_.'sitemap/stmpg-'.$k.'.dat', serialize($MAP), LOCK_EX); }
	}else{
		$CURRENT_SM = unserialize(file_get_contents(_CACHE_.'sitemap/stmpg-'.$sm_cnt.'.dat', LOCK_EX));
		$cn_CURRENT_SM = count($CURRENT_SM);

		if($cn_CURRENT_SM+count($MAPS[0])<=_SITEMAPLINKS_){
			$CURRENT_SM = array_merge($CURRENT_SM, array_shift($MAPS));
			$CURRENT_SM = array_unique($CURRENT_SM);
			file_put_contents(_CACHE_.'sitemap/stmpg-'.$sm_cnt.'.dat', serialize($CURRENT_SM), LOCK_EX);

			foreach($MAPS as $MAP){ $sm_cnt++;
				file_put_contents(_CACHE_.'sitemap/stmpg-'.$sm_cnt.'.dat', serialize($MAP), LOCK_EX); }
		}else{

			if($cn_CURRENT_SM<_SITEMAPLINKS_){
				$diff = _SITEMAPLINKS_ - $cn_CURRENT_SM;

				$td_MAP = []; $dc=0;
				foreach($MAPS[0] as $mKey=>&$mData){
					$td_MAP[$mKey] = $mData;
					$mData = ''; $dc++;
					if($dc>=$diff){ break; }
				} $MAPS[0] = cleanArray($MAPS[0]);

				$CURRENT_SM = array_merge($CURRENT_SM, $td_MAP);
				$CURRENT_SM = array_unique($CURRENT_SM);

				file_put_contents(_CACHE_.'sitemap/stmpg-'.$sm_cnt.'.dat', serialize($CURRENT_SM), LOCK_EX);

				$t_MAPS = [];
				foreach($MAPS as $MAP){ $t_MAPS = array_merge($t_MAPS, $MAP); }
				$MAPS = $t_MAPS; unset($t_MAPS);

				$MAPS = getRawMap($MAPS);
				foreach($MAPS as $MAP){ $sm_cnt++;
					file_put_contents(_CACHE_.'sitemap/stmpg-'.$sm_cnt.'.dat', serialize($MAP), LOCK_EX); }

			}else{
			foreach($MAPS as $MAP){ $sm_cnt++;
				file_put_contents(_CACHE_.'sitemap/stmpg-'.$sm_cnt.'.dat', serialize($MAP), LOCK_EX); }
			}
		}
	}
}

//Подготавливаем данные для формирования карт сайта
function getRawMap($SITEMAP){
	$MAPS = []; $mc = 0; $cc = 0;
	foreach($SITEMAP as $uri=>$data){
		$MAPS[$mc][$uri] = (isset($data['title']))? $data['title'] : $data ;
		$cc++;
		if($cc===_SITEMAPLINKS_){ $mc++; $cc = 0;}
	} return $MAPS;
}


function multisort($array, $index){
	$new_arr = []; $result = [];

	foreach($array as $k=>$v){ $new_arr[$k] = $v[$index]; }

	asort($new_arr);
	$keys = array_keys($new_arr);

	foreach($new_arr as $k=>$v){
		$result[$k] = $array[$k];
	} return $result;
}


//Перемешиваем ассоциативный массив - php.net
function shuffle_assoc( $array ){
   $keys = array_keys( $array );
   shuffle( $keys );
   return array_merge( array_flip( $keys ) , $array );
}


//Создаем страничку
function createPage($data, $URI, $incat=''){
	global $TAGLIST, $DEF_TAGS, $DESC_SRC, $RATETXT_SRC, $MAIN_CONTENT, $DEFCAT_LIST, $BANNED_CATS, $embst;

	if(is_array($data) && !empty($data) && !file_exists(_CACHE_.'pages/'.$URI.'.php')){

      //Собираем тэги
      $TAGS = $data['tags'];
			if(count($TAGS) <= 3){
				shuffle($DEF_TAGS);
				$TAGS = array_merge($TAGS, $DEF_TAGS);
				$TAGS = array_unique($TAGS);
			} $TAGS = array_chunk($TAGS, mt_rand(3, 6));

		//Формирование категорий (до сборки файла, т.к. $PG['categories'] пишется в него)
		$catsList = [];
		if (is_array($incat)) {
			$catsList = $incat;
		} elseif (!empty($incat)) {
			$catsList = explode(';', $incat);
		}
		$catsList = array_filter(array_map('cleanStr', $catsList));
		if (empty($catsList)) {
			$catsList = [$DEFCAT_LIST[array_rand($DEFCAT_LIST)]];
		}
		$catsList = array_values(array_unique($catsList));
		//Запрещённые категории — страницу не создаём (страховка, основная проверка в импорте до скачивания превью)
		if (isBannedCats($catsList)) {
			return false;
		}
		$PG['categories'] = serialize($catsList);

			$PG['title'] = str_replace('$', '-',str_replace('"', "'", $data['title']));
			$PG['seotitle'] = seoTitle($PG['title'], $TAGS[0][0]);
			$PG['description'] = str_replace('$', '-', str_replace('"', "'", getSpintax($DESC_SRC, filterBW($data['title']))));
			$PG['keywords'] = str_replace('$', '-', str_replace('"', "'", implode(', ', $TAGS[0])));
			$PG['tags'] = str_replace('$', '-', serialize($TAGS[0]));
			$PG['embed'] = str_replace('"', "'", $data['embc']);

			// Проверяем и скачиваем превью. Если не удалось получить картинку — не создаем страницу!
			$thumbResult = getThumb($data['thumb'], $URI);
			if($thumbResult === false){
				return false;
			}
			$PG['thumb'] = $thumbResult;

			$PG['rating_txt'] = str_replace('$', '-', str_replace('"', "'", getSpintax($RATETXT_SRC, filterBW($data['title']))));
			$PG['frames'] = serialize($data['frames']);
			$PG['duration'] = $data['time'];
			//Дата публикации: сдвигаем в прошлое на случайный интервал до полугода,
			//чтобы импортированный пул не выглядел залитым одним днём
			$PG['create'] = time() - mt_rand(0, 182*86400) - mt_rand(0, 3599);



//Готовим содержимое файла
$PAGE_FILE = <<<PGFL
<?php
\$P_TITLE = "{$PG['title']}";
\$P_SEOTITLE = "{$PG['seotitle']}";
\$P_DESC = "{$PG['description']}";
\$P_KWDS = "{$PG['keywords']}";
\$P_TAGS = '{$PG['tags']}';
\$P_CATS = '{$PG['categories']}';
\$P_EMBED = "{$PG['embed']}";
\$P_THUMB = '{$PG['thumb']}';
\$P_R_TEXT = "{$PG['rating_txt']}";
\$P_DURATION = "{$PG['duration']}";
\$P_FRAMES = '{$PG['frames']}';
\$P_CREATE = "{$PG['create']}";

PGFL;

		#Записываем данные
		file_put_contents(_CACHE_.'pages/'.$URI.'.php', $PAGE_FILE, LOCK_EX);
		file_put_contents(_CACHE_.'sitemap/'.$URI, $PAGE_FILE, LOCK_EX);

		foreach($TAGS[0] as $tag){ file_put_contents(_CACHE_.'tags/'.str_replace(' ', '-', $tag).'.dat', $URI."\r\n", FILE_APPEND|LOCK_EX); }

		foreach ($catsList as $cItem) {
			$catSlug = normalizeCatSlug($cItem);
			if (!empty($catSlug)) {
				file_put_contents(_CACHE_.'cats/'.$catSlug.'.dat', $URI."\r\n", FILE_APPEND|LOCK_EX);
			}
		}

		file_put_contents(_CACHE_.'home.dat', $URI."\r\n", FILE_APPEND|LOCK_EX);
		//if(PGTYPE == 'page'){ file_put_contents(_CACHE_.'home.dat', $URI."\r\n", FILE_APPEND|LOCK_EX); }

	}else{
		return FALSE;
	}
}

//Формируем seo заголовок по шаблону
function seoTitle($w, $t){
	global $TITLE_TPL, $SITE_NAME, $B_TEND, $T_DELIM;


	$TPL = (_RND_TTL_TPLS_ === 1)? $TITLE_TPL[array_rand($TITLE_TPL)]: $TITLE_TPL[0] ;

	$TPL = str_replace('#KWD#', $w, $TPL);
	$TPL = str_replace('#TAG#', $t, $TPL);
	$TPL = str_replace('#SKWD#', '', $TPL); //быстрофикс
	$TPL = str_replace('#SNM#', $SITE_NAME, $TPL);
	$TPL = str_replace('#ST#', $B_TEND, $TPL);
	return str_replace('#DLM#', $T_DELIM, $TPL);
}

//Удаляем короткие слова
function dMiniKeys($a){ $o=[];
	if(is_array($a)){
		foreach($a as $p){
			if(mb_strlen($p) > 3){ $o[]=$p;}
		}$a=$o;
	} return $a;
}

//Выводим тайтл
function r_title(){
global $SITE_NAME, $T_DELIM, $B_TEND, $PAGE_NUM, $P_TITLE, $TAG_TITLE, $CAT_TITLE, $CG_CUPAG, $P_SEOTITLE, $TP_TITLE;
$CG_CUPAG = (isset($CG_CUPAG) && $CG_CUPAG > 0)? $CG_CUPAG : 1;
$PAGENUM = ($CG_CUPAG===1)? '' : ' | Page '.$CG_CUPAG ;
	if(PGTYPE == 'home'){
		echo $SITE_NAME.$T_DELIM.$B_TEND.$PAGENUM;
		return;
	}elseif(PGTYPE == 'page'){
		echo $P_SEOTITLE;
		return;
	}elseif(PGTYPE == 'category'){
		echo 'Category: '.$CAT_TITLE.$T_DELIM.$B_TEND.$PAGENUM;
		return;
	}elseif(PGTYPE == 'tag'){
		if($TAG_TITLE == 'Tag list'){
			echo $TAG_TITLE.$T_DELIM.$B_TEND;
		}else{
			echo 'Tag: '.filterBW($TAG_TITLE).$T_DELIM.$B_TEND.$PAGENUM;
		}
		return;
	}elseif(PGTYPE == 'sitemap'){
		echo 'Sitemap page #'.$PAGE_NUM.$T_DELIM.$B_TEND;
		return;
	}elseif(PGTYPE == 'text'){
		echo $TP_TITLE.$T_DELIM.$B_TEND;
		return;
	}elseif(PGTYPE == '404'){
		echo '404 page not found'.$T_DELIM.$B_TEND;
		return;
	}
}

//Выводим Description
function r_description(){
global $SITE_NAME, $TAG_TITLE, $CAT_TITLE, $CAT_DESC, $TAG_DESC,
			 $MAIN_DESC, $P_DESC, $SM_DESC, $P404_DESC, $TL_DESC, $TP_DESC;

	if(PGTYPE == 'home'){
		echo str_replace('#KWD#', $SITE_NAME, $MAIN_DESC);
		return;
	}elseif(PGTYPE == 'page'){
		echo $P_DESC;
		return;
	}elseif(PGTYPE == 'category'){
		echo str_replace('#KWD#', filterBW($CAT_TITLE), $CAT_DESC);
		return;
	}elseif(PGTYPE == 'tag'){
		if($TAG_TITLE == 'Tag list'){
			echo str_replace('#KWD#', $TAG_TITLE, $TL_DESC);
		}else{
			echo str_replace('#KWD#', filterBW($TAG_TITLE), $TAG_DESC);
		}
		return;
	}elseif(PGTYPE == 'sitemap'){
		echo str_replace('#KWD#', $SITE_NAME, $SM_DESC);
		return;
	}elseif(PGTYPE == 'text'){
		echo (isset($TP_DESC) && $TP_DESC !== '')? $TP_DESC : str_replace('#KWD#', $SITE_NAME, $P404_DESC);
		return;
	}elseif(PGTYPE == '404'){
		echo str_replace('#KWD#', $SITE_NAME, $P404_DESC);
		return;
	}
}

//Выводим ключи
function r_keywords(){
global $SITE_NAME, $TAG_TITLE, $CAT_TITLE, $DEF_TAGS, $P_KWDS, $TP_TITLE;

	if(PGTYPE == 'home'){
		echo $SITE_NAME.', '.implode(', ', $DEF_TAGS);
		return;
	}elseif(PGTYPE == 'page'){
		echo $P_KWDS;
		return;
	}elseif(PGTYPE == 'category'){
		echo $CAT_TITLE.', '.implode(', ', $DEF_TAGS);
		return;
	}elseif(PGTYPE == 'tag'){
			echo $TAG_TITLE.', '.implode(', ', $DEF_TAGS);
		return;
	}elseif(PGTYPE == 'sitemap'){
		echo 'Sitemap, '.$SITE_NAME.', '.implode(', ', $DEF_TAGS);
		return;
	}elseif(PGTYPE == 'text'){
		echo $TP_TITLE.', '.$SITE_NAME.', '.implode(', ', $DEF_TAGS);
		return;
	}elseif(PGTYPE == '404'){
		echo implode(', ', $DEF_TAGS);
		return;
	}
}

//Выводим тэги
function p_tags(){
	global $P_TAGS; $tags = [];

	if(!empty($P_TAGS)){
		$raw = @unserialize($P_TAGS);
		if(is_array($raw)){
			foreach($raw as $tag){
				$tags[] = '<a href="'._URL_.'tag/'.str_replace(' ', '-', $tag).'">'.$tag.'</a>';
			}
		}
	}

	if(count($tags) > 0){ echo implode(', ', $tags); }else{ echo 'No tags...'; }
}

//Выводим тэги в виде бейджей (новый дизайн)
function p_tagBadges(){
	global $P_TAGS; $out = [];

	if(!empty($P_TAGS)){
		$raw = @unserialize($P_TAGS);
		if(is_array($raw)){
			foreach($raw as $tag){
				$out[] = '<a class="badge" href="'._URL_.'tag/'.str_replace(' ', '-', $tag).'">'.htmlspecialchars($tag, ENT_QUOTES, 'UTF-8').'</a>';
			}
		}
	}

	echo implode(' ', $out);
}

//Выводим категории страницы
function p_categories(){
	global $P_CATS; $cats = [];

	if(!empty($P_CATS)){
		$raw = @unserialize($P_CATS);
		if(is_array($raw)){
			foreach($raw as $cat){
				$catSlug = normalizeCatSlug($cat);
				if($catSlug !== ''){
					$cats[] = '<a href="'._URL_.$catSlug.'/">'.mb_ucwords(str_replace('-', ' ', $catSlug)).'</a>';
				}
			}
		}
	}

	if(count($cats) > 0){ echo implode(', ', $cats); }else{ echo 'General'; }
}

//Ссылка на thumb (возвращает URL — используется в OG-тегах и шаблонах)
function p_thumb_url($thumb=''){
	global $P_THUMB;

	$thumb = (!empty($thumb))? $thumb : (isset($P_THUMB)? $P_THUMB : '');

	if($thumb === ''){ return _URL_.'data/no_thumb.jpg'; }

  if(strpos($thumb, '://') !== false){
    return $thumb;
  }else{
    return (file_exists(_STATIC_.$thumb))? _URL_.'static/'.$thumb : _URL_.'data/no_thumb.jpg' ;
  }
}

//Ссылка на thumb
function p_thumb($thumb=''){
	echo p_thumb_url($thumb);
}

//Человекочитаемое «время назад» (для карточек и страницы видео)
function p_timeago($ts=''){
	if(empty($ts) || !is_numeric($ts)){ return ''; }

	$diff = time() - (int)$ts;
	if($diff < 60){ return 'just now'; }

	$units = [31536000=>'year', 2592000=>'month', 604800=>'week', 86400=>'day', 3600=>'hour', 60=>'minute'];

	foreach($units as $sec=>$name){
		if($diff >= $sec){
			$n = (int) floor($diff/$sec);
			return $n.' '.$name.($n>1?'s':'').' ago';
		}
	}
	return 'just now';
}

//Фэйк счетчик просмотров
function p_views($time='', $title=''){
	global $P_CREATE, $P_TITLE;

	$P_CREATE = (!empty($time))? $time : $P_CREATE ;
	$P_TITLE = (!empty($title))? $title : $P_TITLE ;

	if(isset($P_CREATE) && !empty($P_CREATE)){

		$timeV = substr(crc32($P_TITLE), -2)*1; //Начальное число просмотров
		$timeD = substr($P_CREATE, -1)*1; //Делитель

		$timeD = ($timeD<1)? 1 : $timeD ;

		$timeC = round((time() - $P_CREATE) / (900*$timeD));
		$timeV +=$timeC;
		echo $timeV;

	}else{ echo '0'; }
	return;
}

//Выводим содержимое файла из папки data
function r_loadfile($fn){
	if(file_exists(_DATA_.$fn)){
		echo file_get_contents(_DATA_.$fn, LOCK_EX);
	}
}

//Делвем выборку информации для архивных страниц (главная, тэги, категории)
function a_prepareData($a){ $TAG_DATA = [];

	if(is_array($a) && count($a)>0){
		$invc = 0;
		foreach(array_map('cleanStr', $a) as $line){ $P_DELETED = false;
			if(file_exists(_CACHE_.'pages/'.$line.'.php')){
				// include, а не include_once: файл страницы уже мог быть подключён ранее
				// (текущая страница, блок related) — include_once пропустил бы его и
				// в выборку попали бы данные предыдущего файла
				include _CACHE_.'pages/'.$line.'.php';

                if($P_DELETED){ continue; }

				$TAG_DATA[$invc]['title'] = $P_TITLE;
				$TAG_DATA[$invc]['uri'] = _URL_.$line._EXT_;
				$TAG_DATA[$invc]['thumb'] = $P_THUMB;
				$TAG_DATA[$invc]['time'] = $P_CREATE;
				$TAG_DATA[$invc]['duration'] = isset($P_DURATION)? $P_DURATION : '';
				$invc++;
			}
		}
	}	return $TAG_DATA;
}

//Формируем массив с категориями
function r_categories(){
	global $DEFCAT_LIST;
	$cats = [];

	// Сначала проверяем категории по умолчанию
	if (is_array($DEFCAT_LIST)) {
		foreach($DEFCAT_LIST as $cat){
			$anch = cleanStr(str2id($cat));
			if(file_exists(_CACHE_.'cats/'.$anch.'.dat')){
				$cats[_URL_.$anch.'/'] = mb_ucwords(str_replace('-', ' ', $cat));
			}
		}
	}

	// Затем подхватываем все остальные существующие файлы категорий
	if (file_exists(_CACHE_.'cats/')) {
		$files = scandir(_CACHE_.'cats/');
		foreach ($files as $f) {
			if ($f === '.' || $f === '..' || $f === '.htaccess' || substr($f, -4) !== '.dat') {
				continue;
			}
			$anch = substr($f, 0, -4);
			$link = _URL_.$anch.'/';
			if (!isset($cats[$link])) {
				$title = mb_ucwords(str_replace('-', ' ', $anch));
				$cats[$link] = $title;
			}
		}
	}

	return $cats;
}

//Формируем массив с похожими записями
function p_relatedPages(){
	global $P_RELATED; $RETURN = []; $i=0;
	$TP_RELATED = $P_RELATED;

		if(is_array($TP_RELATED) && count($TP_RELATED)>=3){

			foreach($TP_RELATED as $block){ $P_DELETED = false;
				if(file_exists(_CACHE_.'pages/'.$block.'.php')){
					// include (не include_once) — иначе для страницы, уже подключённой
					// выше по коду, значения остались бы от предыдущего файла
					include _CACHE_.'pages/'.$block.'.php';

                    if($P_DELETED){ continue; }

					$RETURN[$i]['title'] = $P_TITLE;
					$RETURN[$i]['uri'] = _URL_.$block._EXT_;
					$RETURN[$i]['thumb'] = $P_THUMB;
					$RETURN[$i]['time'] = $P_CREATE;
					$RETURN[$i]['duration'] = isset($P_DURATION)? $P_DURATION : '';
					$i++;
				}
			}
		} return $RETURN;
}

//Вывод кода fluidvids
function r_fluidvids(){
	global $EMB_HOSTS;
	$EMB_HOSTS = (isset($EMB_HOSTS) && !empty($EMB_HOSTS))? $EMB_HOSTS : "'embed.redtube.com', 'www.youporn.com', 'www.pornhub.com'" ;
$CODE = <<<FLU
<script>
	fluidvids.init({
		selector: ['iframe', 'object'], // runs querySelectorAll()
		players: [] // players to support
	});
</script>
FLU;
echo $CODE;
}

//Просто строка для футера
function r_footstring(){
	global $SITE_NAME, $T_DELIM, $B_TEND;
	echo date('Y').' '.$SITE_NAME.$T_DELIM.$B_TEND;
}

//Список ссылок на новые ключи
function r_keylinksList($count=1, $tg1='', $tg2=''){


			$tempKeys = file(_CACHE_.'home.dat');
			shuffle($tempKeys);
			$tempKeys = array_chunk($tempKeys, $count);
			$Keys = array_map('cleanStr', $tempKeys[0]);


	$HTML = '';
	foreach($Keys as $uri){
		$HTML .= $tg1.'<a href="'._URL_.$uri._EXT_.'">'.mb_ucwords(str_replace('-', ' ', $uri)).'</a> '.$tg2;
	}
	echo $HTML;
}

function p_rating(){
	global $P_TITLE, $P_R_TEXT;

	if(isset($P_TITLE) && !empty($P_R_TEXT)){
		$dig = crc32($P_TITLE);
		$rat = substr($dig, -1)*1;
		$cnt = substr($dig, 0, 2)*1;
		$cnt = ($cnt<1)? 3 : $cnt ;

$block = <<<BLCK
<div itemscope itemtype="http://schema.org/Product">
	<span itemprop="name">{$P_TITLE}</span>
	<div itemprop="aggregateRating"
	itemscope itemtype="http://schema.org/AggregateRating">
	Rated <span itemprop="ratingValue">4.{$rat}</span>/5
	based on <span itemprop="reviewCount">{$cnt}</span> user reviews
	</div>
		<span itemprop="description">{$P_R_TEXT}</span>
</div>
BLCK;
echo $block;
	}
}

#Получаем IP посетителя
function getUserIP() {
	$array = array('HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR', 'HTTP_X_REMOTECLIENT_IP');
	foreach($array as $key)
		if(filter_var($_SERVER[$key], FILTER_VALIDATE_IP)) return $_SERVER[$key];
	return false;
}


#Генерируем карту сайта
function sitemapXML(){
	$sitemap = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
	$sitemap .= "<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n\n";

	//Главная
	$sitemap.="<url>\n";
	$sitemap.="<loc>"._URL_."</loc>\n";
	$sitemap.="<changefreq>daily</changefreq>\n";
	$sitemap.="</url>\n\n";

	//Карты сайта
	$sitemap.="<url>\n";
	$sitemap.="<loc>"._URL_."sitemap/1</loc>\n";
	$sitemap.="<changefreq>daily</changefreq>\n";
	$sitemap.="</url>\n\n";

	//Таглист
	$sitemap.="<url>\n";
	$sitemap.="<loc>"._URL_."tag/</loc>\n";
	$sitemap.="<changefreq>weekly</changefreq>\n";
	$sitemap.="</url>\n\n";

	//Текстовые (юридические) страницы
	if(file_exists(_DATA_.'text/')){
		foreach(scandir(_DATA_.'text/') as $tFile){
			if(substr($tFile, -5) !== '.html'){ continue; }
			$tSlug = substr($tFile, 0, -5);
			$sitemap.="<url>\n";
			$sitemap.="<loc>"._URL_."text/".$tSlug."</loc>\n";
			$sitemap.="<changefreq>monthly</changefreq>\n";
			$sitemap.="</url>\n\n";
		}
	}

	if(file_exists(_CACHE_.'home.dat')){
		$links = array_map('cleanStr', file(_CACHE_.'home.dat'));
		$links = cleanArray($links);

		foreach($links as $link){
			$sitemap.="<url>\n";
			$sitemap.="<loc>"._URL_.$link._EXT_."</loc>\n";
			$sitemap.="<changefreq>weekly</changefreq>\n";
			$sitemap.="</url>\n\n";
		}
	}

	$sitemap.="</urlset>";
	echo $sitemap;
}

#Генерируем robots.txt
function robotsTXT(){
	echo "User-agent: *\nCrawl-delay: 5\nDisallow:\nHost: "._HOST_.
	"\nSitemap: "._PROTO_._URL_."sitemap.xml";
}

#STARTER
function STARTER(){

	$s_DATA = [];
	include_once _DATA_.'STARTER.php';
	foreach($STARTER as $m=>$a){
		$s_DATA[$m] = getSpintax($a);
	}

	$s_HOST = explode('.', str_ireplace('www.', '', _HOST_));

	$s_SITENAME = 'if(empty($SITE_NAME)){ $SITE_NAME = "'.mb_strtoupper($s_HOST[0]).'"; }';

$INP_STARTER = <<<STRT
<?php
\$T_DELIM = " {$s_DATA['delim']} ";
\$B_TEND = "{$s_DATA['tend']}";
\$CAT_DESC = "{$s_DATA['cat_desc']}";
\$TAG_DESC = "{$s_DATA['tag_desc']}";
\$MAIN_DESC = "{$s_DATA['main_desc']}";
\$SM_DESC = "{$s_DATA['sm_desc']}";
\$P404_DESC = "{$s_DATA['p404_desc']}";
\$TL_DESC = "{$s_DATA['tl_desc']}";
\$GLOBALS['prev-p'] = "";
\$GLOBALS['next-p'] = "";

{$s_SITENAME}

STRT;

file_put_contents(_DATA_.'dynOptions.php', $INP_STARTER, LOCK_EX);
}


//фильтр bad_words
function filterBW($s){
	global $BAD_WORDS;
	return cleanStr(str_ireplace($BAD_WORDS, '', $s));
}

//навигация
function r_pagenavi($cnp=10, $t='', $ac='active', $cpt='span', $classes=''){
	global $P_COUNT, $URI, $CG_CUPAG ; $ipc = 0;
	if(isset($P_COUNT)){ $ot=''; $ct='';
		$PAGENAVI = '';

		if(!empty($t)){ $ot='<'.$t.'>'; $ct='</'.$t.'>'; }

		switch(PGTYPE){
    case "category":
        $fstruct = $URI.'/'; $struct = $URI.'/'; $e = '/'; break;
    case "tag":
        $fstruct = 'tag/'.$URI; $struct = 'tag/'.$URI.'/'; $e = '';break;
    case "home":
        $fstruct = ''; $struct = 'page/'; $e = '';break;
		default: return; }

		$cnp = ($cnp > $P_COUNT)? $P_COUNT : $cnp ;
		$r_cnp = round($cnp/2);
		$de =($CG_CUPAG < $r_cnp)? $CG_CUPAG-1 : $r_cnp-1 ;
		if($CG_CUPAG+$r_cnp>=$P_COUNT){ $de = $cnp-($P_COUNT-$CG_CUPAG)-1; }

		for($ipc; $ipc<$cnp; $ipc++){
			$pc = $CG_CUPAG - $de + $ipc;
			if($CG_CUPAG == $pc){
				$PAGENAVI .= ($pc === 1)? $ot.'<'.$cpt.' class="'.$classes.' '.$ac.'" >'.$pc.'</'.$cpt.'>'.$ct : $ot.'<'.$cpt.' class="'.$classes.' '.$ac.'" >'.$pc.'</'.$cpt.'>'.$ct ;
			}else{
				$PAGENAVI .= ($pc === 1)? $ot.'<a class="'.$classes.'" href="'._URL_.$fstruct.'">'.$pc.'</a>'.$ct : $ot.'<a class="'.$classes.'" href="'._URL_.$struct.$pc.$e.'">'.$pc.'</a>'.$ct ;
			}
		}

		if(!empty($PAGENAVI)){
			$prev = ($CG_CUPAG > 1)? $ot.'<a class="'.$classes.'" href="'._URL_.$struct.($CG_CUPAG-1).$e.'">Prev</a>'.$ct : '' ;
            $GLOBALS['prev-p'] = (!empty($prev))? _URL_.$struct.($CG_CUPAG-1).$e : '' ;

			$next = ($CG_CUPAG < $P_COUNT)? $ot.'<a class="'.$classes.'" href="'._URL_.$struct.($CG_CUPAG+1).$e.'">Next</a>'.$ct : '' ;
            $GLOBALS['next-p'] = (!empty($next))? _URL_.$struct.($CG_CUPAG+1).$e : '' ;

			return '<span class="pages">Pages: </span>'.$prev.$PAGENAVI.$next;
		}
	}
}

//Редиректируем по уму
function s_redirect($t){ $s=false;

if(stripos($_SERVER["REQUEST_URI"], '?') !== false ){
	list($uri, $params) = explode('?', $_SERVER["REQUEST_URI"]);
}else{
	$uri = $_SERVER["REQUEST_URI"]; $params = '';
}

if($t == '1' && substr($uri, -1) == '/'){ $s=true;
	$uri = substr($uri, 0, -1);
	$uri .= (!empty($params))? '?'.$params : '' ;
}elseif($t == '2' && substr($uri, -1) != '/'){ $s=true;
	$uri .= (!empty($params))? '/?'.$params : '/' ;
}

if($s){
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: {$uri}"); die;
}
}

//Подготовка данных для вывода списков
function archivePrepare(){
	global $rawPData, $match; $DATA = [];

	$DATA[0] = count($rawPData); //$P_COUNT

	if(isset($match['params']['id']) && isset($match['params']['pg']) && is_numeric($match['params']['pg'])){ $pageParam = $match['params']['pg']; }
	if(isset($match['params']['id']) && is_numeric($match['params']['id']) && !isset($match['params']['pg'])){ $pageParam = $match['params']['id']; }

	if(isset($pageParam)){
		$DATA[1] = $pageParam; //$CG_CUPAG
		$DATA[2] = $pageParam-1; //$VD_ID

		if(isset($rawPData[$DATA[2]])){
			$DATA[3] = $rawPData[$DATA[2]]; //$V_DATA
		}else{ define('PGTYPE', '404'); page404(); }

	}else{
		$DATA[2] = 0; //$VD_ID
		$DATA[1] = 1; //$CG_CUPAG
		$DATA[3] = $rawPData[$DATA[2]]; //$V_DATA
	}

	return $DATA;
}

//генерация случайного слова
function randWord($len=5){
$newWord = '';
$glas = ["a","e","i","y","o","u"];
$soglas = ["b","c","d","f","g","h","j","k","l","m","n","p","q","r","s","t","v","x","w","z"];

  for ($i=0; $i <$len/2 ; $i++) {
    $ng = rand(0, count($glas) - 1);
    $nsg = rand(0, count($soglas) - 1);
    $newWord .= $glas[$ng].$soglas[$nsg];
  } return $newWord;
}

//формируем контент для псевдо страниц
function preparePseudoPage($raw, $p){ global $URI, $FRPAGES, $P_EMBED;
  $url = _URL_.$URI._EXT_.'?step=';
  $html = "<style>.peka4:hover{text-decoration:none!important;} .peka4 span:hover{text-decoration:underline !important;} .pseudoFrame{ float:left; padding: 5px;} .psdClear{clear:both;} .pspBlock { position: relative;} .pspOver { position: absolute;  left: 0px;  top: 0px;  right: 0px;  bottom: 0px; width: 100%; height: 100%;}</style> \r\n";

  if(is_array($raw)){
    $ANCH_TOP = 'VIEW FULL VIDEO >>>'; $ANCH_BOT = 'VIEW FULL VIDEO >>>';

    if($p+1 <= $FRPAGES){
      $url .= $p+1;
      if($p+1 === $FRPAGES){ $ANCH_TOP = 'VIEW FULL VIDEO >>>'; $ANCH_BOT = 'VIEW FULL VIDEO >>>'; }
    }else{ $url .= 'video'; }

  $html .= "<h1 align='center'><a href='{$url}'>{$ANCH_TOP}</a></h1>\r\n";
    foreach($raw as $r){
      $html .= "<div class='pseudoFrame'><a href='{$url}'><img src='{$r}'></a></div> \r\n";
    }

    $html .= "<div class='psdClear'></div> \r\n"; $html .= "<h1 align='center'><a class='peka4' href='{$url}'>{$ANCH_BOT}</a></h1>\r\n";
  }else{ //тут формируем код псевдоплеера
    $url .= 'video';
    $html .= "<div class='pspBlock'>{$P_EMBED} <a href='{$url}' ><div class='pspOver'></div></a></div>";

  }

  return $html;
}

//Вставка рекламы
function setAdv($block, $pval='0'){
  //r_loadfile($fn) //вывод файла, отсчет идёт от data/
}


function indexOpt(){
global $match; $noind = '';

  if(PGTYPE == '404'){
    $noind .=  '<meta name="robots" content="noindex, follow" />'."\r\n";
  }elseif(PGTYPE == 'home'){
    $noind .=  '<link rel="canonical" href="'._URL_.'"/>'."\r\n";
  }elseif(PGTYPE == 'category'){
    $noind .=  '<link rel="canonical" href="'._URL_.$match['params']['id'].'/"/>'."\r\n";
  }elseif(PGTYPE == 'tag'){
    $noind .=  '<link rel="canonical" href="'._URL_.'tag/'.$match['params']['id'].'"/>'."\r\n";
  }elseif(PGTYPE == 'sitemap'){ global $PAGE_NUM, $f_cnt;
    $GLOBALS['prev-p'] = ($PAGE_NUM > 1)? _URL_.'sitemap/'.($PAGE_NUM-1): '' ;
    $GLOBALS['next-p'] = ($PAGE_NUM < $f_cnt)? _URL_.'sitemap/'.($PAGE_NUM+1): '' ;
  }elseif(PGTYPE == 'text'){ global $TP_SLUG;
    if(!empty($TP_SLUG)){ $noind .= '<link rel="canonical" href="'._URL_.'text/'.$TP_SLUG.'" />'."\r\n"; }
  }

  if(!empty($GLOBALS['prev-p'])){
    $noind .= '<link rel="prev" href="'.$GLOBALS['prev-p'].'" />'."\r\n"; }

  if(!empty($GLOBALS['next-p'])){
    $noind .= '<link rel="next" href="'.$GLOBALS['next-p'].'" />'."\r\n"; }
  echo $noind;
}

#Проброс данных (сбор параметров)
function trackedData(){
  $m = (isset($_GET[_RMARK_]))? 'c_trk' : 'trk' ;
  if(!isset($_SESSION[$m]['uid']) && !empty($_GET)){
    $_SESSION[$m]['uid'] = uniqid('uid');
    foreach($_GET as $gN=>$gV){
      if($gN == 'step'){ continue; }
      if($gN == _RMARK_){ continue; }
      $_SESSION[$m][$gN] = $gV;
    }
  }
}

//Проброс данных (параметры хранятся в макросах #P1#...#Px#)
//Принимает ссылку на трекер вида - track.my/?param1=#P1#&param2=#P2#&paramX=#Px#
function trackParams($url){
  if(isset($_SESSION['trk']['uid']) && !empty($_SESSION['trk']['uid'])){ $i=1;
    foreach($_SESSION['trk'] as $sN=>$sV){
      if($sN == 'uid'){ continue; }
      if(empty($sV)){ $sV = 'NAN';}
      $url = str_replace("#P{$i}#", $sV, $url); $i++;
    }
  } return preg_replace('~#.*#~iU', 'NAN', $url);
}

//Вывод файла с волшебными скриптами в шаблоне
function cloakBox(){
  if(isset($_SESSION['tracked']) && $_SESSION['tracked'] === true){
    $_SESSION['tracked'] = false;

    $rep = (isset($_SESSION['n_url']) && !empty($_SESSION['n_url']))? urlencode($_SESSION['n_url']) : '' ;
    $sub = (isset($_SESSION['sub_iddqd'] ) && !empty($_SESSION['sub_iddqd'] ))? $_SESSION['sub_iddqd'] : '' ;

    $ext_url = str_replace('##C_PAGE##', $rep, _R_URL_);
    $ext_url = str_replace('##SUB##', $sub, $ext_url);

    $_SESSION['sub_iddqd'] = '';
    include_once _DATA_.'cloakBox.php';
  }
}
