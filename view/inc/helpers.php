<?php
/*
	Вспомогательные функции шаблона (дизайн «Nocturne»).
	Подключается из view/inc/header.php — раньше любого контентного шаблона.

	Все функции проверяются на существование, чтобы шаблон можно было
	подключить повторно без фатальных ошибок.
*/

#Экранирование для вывода в атрибуты и текст
if(!function_exists('v_esc')){
	function v_esc($s){
		return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
	}
}

#Число просмотров в компактном виде: 1.2M / 348K / 1,240
if(!function_exists('v_num')){
	function v_num($n){
		$n = (int)$n;

		if($n >= 1000000){
			$m = round($n/1000000, 1);
			return ((floor($m) == $m)? (int)$m : $m).'M';
		}
		if($n >= 1000){
			$k = round($n/1000, 1);
			return ((floor($k) == $k)? (int)$k : $k).'K';
		}
		return number_format($n, 0, '.', ',');
	}
}

#Просмотры (фейковый счётчик CMS) в компактном виде
if(!function_exists('v_views')){
	function v_views($time, $title){
		ob_start(); p_views($time, $title); $v = ob_get_clean();
		return v_num((int)$v);
	}
}

#Дата публикации: Oct 12, 2026
if(!function_exists('v_date')){
	function v_date($ts){
		$ts = (int)$ts;
		if($ts <= 0){ return ''; }
		return date('M j, Y', $ts);
	}
}

#Дата в формате ISO 8601 для <time datetime="...">
if(!function_exists('v_iso')){
	function v_iso($ts){
		$ts = (int)$ts;
		return ($ts > 0)? date('c', $ts) : '';
	}
}

#URL страницы пагинации — зависит от типа страницы
if(!function_exists('v_page_url')){
	function v_page_url($n){
		global $URI;

		$n = (int)$n;
		$uri = (isset($URI))? $URI : '';

		switch(PGTYPE){
			case 'home':
				return ($n <= 1)? _URL_ : _URL_.'page/'.$n;
			case 'category':
				return ($n <= 1)? _URL_.$uri.'/' : _URL_.$uri.'/'.$n.'/';
			case 'tag':
				return ($n <= 1)? _URL_.'tag/'.$uri : _URL_.'tag/'.$uri.'/'.$n;
			case 'sitemap':
				return ($n <= 1)? _URL_.'sitemap/1' : _URL_.'sitemap/'.$n;
		}
		return _URL_;
	}
}

#Все категории сайта (для мега-меню и страницы карты сайта)
if(!function_exists('v_cats_all')){
	function v_cats_all(){
		$cats = r_categories();
		return is_array($cats)? $cats : [];
	}
}

#Все теги сайта (для страницы карты сайта)
if(!function_exists('v_tags_all')){
	function v_tags_all($limit = 0){
		$out = [];

		if(file_exists(_CACHE_.'tags/')){
			foreach(scandir(_CACHE_.'tags/') as $f){
				if($f === '.' || $f === '..' || $f === '.htaccess' || substr($f, -4) !== '.dat'){ continue; }
				$slug = substr($f, 0, -4);
				$out[_URL_.'tag/'.$slug] = mb_ucwords(str_replace('-', ' ', $slug));
				if($limit > 0 && count($out) >= $limit){ break; }
			}
		}
		return $out;
	}
}

#Домен сайта без www — для динамической подстановки в тексты
if(!function_exists('v_site_domain')){
	function v_site_domain(){
		$host = defined('_HOST_')? _HOST_ : '';
		if($host === '' && !empty($_SERVER['SERVER_NAME'])){ $host = $_SERVER['SERVER_NAME']; }
		$host = preg_replace('~^www\.~i', '', trim((string)$host));
		return $host;
	}
}

#Замена бренда-заглушки в текстовых страницах на домен сайта (UPPERCASE).
#Тексты лежат в data/text/*.html и правке не подлежат, поэтому замена
#выполняется на этапе вывода: «Adult Video Platform» → «ADULT.MY».
if(!function_exists('v_brand_replace')){
	function v_brand_replace($html){
		$html = (string)$html;
		if($html === ''){ return ''; }

		$domain = mb_strtoupper(v_site_domain(), 'UTF-8');
		if($domain === ''){ return $html; }

		//Варианты написания заглушки (регистр и лишние пробелы)
		$html = preg_replace('~Adult\s+Video\s+Platform~iu', $domain, $html);
		return $html;
	}
}

#Текстовые (юридические) страницы сайта: slug => заголовок
if(!function_exists('v_text_pages')){
	function v_text_pages(){
		$map = [
			'privacy' => 'Privacy policy',
			'terms'   => 'Terms of service',
			'dmca'    => 'DMCA &amp; takedowns',
			'contact' => 'Contact us',
		];
		$out = [];

		if(file_exists(_DATA_.'text/')){
			foreach(scandir(_DATA_.'text/') as $f){
				if(substr($f, -5) !== '.html'){ continue; }
				$slug = substr($f, 0, -5);
				$out[$slug] = isset($map[$slug])? $map[$slug] : mb_ucwords(str_replace('-', ' ', $slug));
			}
		}
		return $out;
	}
}

#Общее количество видео в каталоге (по строке home.dat)
if(!function_exists('v_total_videos')){
	function v_total_videos(){
		if(!file_exists(_CACHE_.'home.dat')){ return 0; }

		$lines = array_filter(array_map('trim', file(_CACHE_.'home.dat', LOCK_EX)));
		return count(array_unique($lines));
	}
}

#Оглавление текстовой страницы: заголовки h2/h3 получают id, собирается список
#Возвращает массив: ['html' => контент с проставленными id, 'toc' => список пунктов]
if(!function_exists('v_doc_toc')){
	function v_doc_toc($html){
		$items = [];

		$html = preg_replace_callback('~<h([23])([^>]*)>(.*?)</h\1>~is', function($m) use(&$items){
			$tag  = $m[1];
			$attr = $m[2];
			$text = trim(strip_tags($m[3]));

			$id = '';
			if(preg_match('~id\s*=\s*"([^"]+)"~i', $attr, $idm)){ $id = $idm[1]; }
			else{
				$slug = strtolower(trim(preg_replace('~[^A-Za-z0-9]+~', '-', $text), '-'));
				if($slug === ''){ $slug = 'section'; }
				$base = $slug; $n = 1;
				while(in_array($slug, array_column($items, 'id'))){ $n++; $slug = $base.'-'.$n; }
				$id = $slug;
				$attr .= ' id="'.$id.'"';
			}

			$items[] = ['id' => $id, 'text' => $text, 'level' => (int)$tag];
			return '<h'.$tag.$attr.'>'.$m[3].'</h'.$tag.'>';
		}, $html);

		return ['html' => $html, 'toc' => $items];
	}
}

#Теги видео в виде бейджей .tag (для страницы видео)
if(!function_exists('v_tag_chips')){
	function v_tag_chips(){
		global $P_TAGS;
		$out = [];

		if(!empty($P_TAGS)){
			$raw = @unserialize($P_TAGS);
			if(is_array($raw)){
				foreach($raw as $tag){
					$tag = trim($tag);
					if($tag === ''){ continue; }
					$out[] = '<a class="tag" href="'.v_esc(_URL_.'tag/'.str_replace(' ', '-', $tag)).'">'.v_esc($tag).'</a>';
				}
			}
		}
		return $out;
	}
}

#Категории видео в виде бейджей .tag (для страницы видео)
if(!function_exists('v_cat_chips')){
	function v_cat_chips(){
		global $P_CATS;
		$out = [];

		if(!empty($P_CATS)){
			$raw = @unserialize($P_CATS);
			if(is_array($raw)){
				foreach($raw as $cat){
					$slug = normalizeCatSlug($cat);
					if($slug === ''){ continue; }
					$out[] = '<a class="tag" href="'.v_esc(_URL_.$slug.'/').'">'.v_esc(mb_ucwords(str_replace('-', ' ', $slug))).'</a>';
				}
			}
		}
		return $out;
	}
}

#BreadcrumbList (Schema.org) для внутренних страниц.
#$items — массив [['name' => '...', 'url' => 'путь от корня'], ...]; первый пункт — Home.
#URL может быть пустым (главная) или начинаться с _URL_ — оба варианта приводятся к абсолютным.
if(!function_exists('v_breadcrumbs_jsonld')){
	function v_breadcrumbs_jsonld($items){
		if(!is_array($items) || empty($items)){ return; }

		$list = [];
		$n = 0;
		foreach($items as $item){
			$n++;
			$url = (isset($item['url'])? $item['url'] : '');
			if($url !== '' && strpos($url, _URL_) !== 0){ $url = _URL_.ltrim($url, '/'); }
			$list[] = [
				'@type'    => 'ListItem',
				'position' => $n,
				'name'     => (string)(isset($item['name'])? $item['name'] : ''),
				'item'     => _PROTO_.$url,
			];
		}

		echo '<script type="application/ld+json">'.json_encode([
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => $list,
		], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).'</script>'."\n";
	}
}

#Список категорий видео (для хлебных крошек)
if(!function_exists('v_cats_list')){
	function v_cats_list(){
		global $P_CATS;
		$out = [];

		if(!empty($P_CATS)){
			$raw = @unserialize($P_CATS);
			if(is_array($raw)){
				foreach($raw as $cat){
					$slug = normalizeCatSlug($cat);
					if($slug === ''){ continue; }
					$out[] = ['slug' => $slug, 'title' => mb_ucwords(str_replace('-', ' ', $slug))];
				}
			}
		}
		return $out;
	}
}
