<?php

#Пути
define('_ROOT_', __DIR__ . '/'); //Корень
define('_DATA_', _ROOT_ . 'data/');	//Данные
define('_STATIC_', _ROOT_ . 'static/');	//Статические файлы
define('_CACHE_', _DATA_ . 'cache/');	//кэш
define('_LIB_', _ROOT_ . 'inc/'); //Подключаемые файлы
define('_GUI_', _ROOT_ . 'view/'); //GUI файлы
define('_ADV_', _DATA_ . 'adv/'); //Файлы рекламных блоков

#Настройки
$SITE_NAME = 'PornGalaxy'; //Название сайта. Если не задано, будет использовано _HOST_ без доменной зоны
define('_EXT_', '.html'); //Расширение страниц [.html|.php|.aspx|.htm|.xhtml]
define('_SITEMAPLINKS_', 100 ); //Кол-во ссылок на одной странице карты сайта
define('_DEBUG_', true ); //Дебаг режим.
define('_RND_TTL_TPLS_', 0); //Использовать разные шаблоны тайтлов [1-ДА, 0-НЕТ]
define('_PSEUDO_', 0); //Использовать псевдо странички [1-ДА, 0-НЕТ]
define('_FRPAGES_', 1); //Кол-во страниц с фреймами [1-4] (работает только при _PSEUDO_ := 1)
define('_FRAMES_', 6); //Кол-во фреймов на страницу [4-8] (работает только при _PSEUDO_ := 1)
define('_SPIN_', 'all-purpose.txt'); //Spintax описания
define('_CNT_', 0); //Использовать встроенный счетчик [1-ДА, 0-НЕТ]
define('_RMARK_', 'tracking'); //URI для внутреннего редиректа на клоаку кода [вводить без знака - ?]
define('_RNREDIR_', 1); //редирект на рандомную страничку при переходе по _RMARK_ [1-ДА, 0-НЕТ]
define('_R_URL_', 'https://mail.ru'); //ссылка попандера


$DEFCAT_LIST = ['amateur', 'asian', 'babe', 'blonde', 'casting', 'college', 'ebony', 'lesbian', 'parody', 'redhead', 'small tits', 'teen']; //Категории по умолчанию
$BANNED_CATS = ['gay', 'trans', 'ladyboy', 'shemale', 'men', 'muscular-men']; //Запрещённые категории: видео с любой из них не добавляется на сайт. Формат свободный (регистр/пробелы/дефисы не важны, всё нормализуется к слагу). Пример: ['scat', 'loli'];
$TH_W = 370; $TH_H = 200; //Ширина и высота превьюшки
$DEF_TAGS = ['porno tube', 'porno video', 'sex', 'hardcore', 'hot', 'fucking', 'hd porn', 'models', 'exclusive', 'pornstar', 'fuck' ]; //Тэги по умолчанию

//Шаблоны seo заголовков
$TITLE_TPL = ['#KWD##DLM##SNM#', '#KWD##DLM##SKWD# | #SNM#', '#KWD##DLM# Watch #KWD# and download #TAG# movies at #SNM#'];

$BAD_WORDS = ['free', 'videos', 'video', 'movies', 'movie', 'clips', 'clip', 'vids', 'adults', 'adult',
							'pictures', 'picture', 'images', 'image', 'photos', 'photo', 'watch', 'xxx', 'download',
							'high quality', 'quality', 'media', 'porno', 'porn'];


$EMB_HOSTS = "'embed.redtube.com', 'www.youporn.com', 'www.pornhub.com'"; //сделать выборку данных из классов парсеров

define('_HOST_',  $_SERVER["SERVER_NAME"] ); //прописать домен сайта или вставить $_SERVER["SERVER_NAME"]
define('_URL_', '//'._HOST_.'/');
define('_PROTO_', 'http:'); //протокол по умолчанию [https: или http:]

#Разрешенные символы
define('_ALLOWED_', "\@'\/\"«»\&#;\.,\s\(\)\*:!\?"); //символы
define('_UMLAUTS_', "äöüßåæœçðñóáéíúýàèìòùâêîôûãñõëïÿšіїє"); //умляуты
define('_LETTERS_', "-a-zA-Z0-9"); //буквы

define('_VER_', '0.1b'); //Версия

#Всякое
date_default_timezone_set('Europe/Kiev');

// зоны доменов
$GLOBALS['DOMAIN_ZONES'] = array
(
	// generic tlds (source: http://en.wikipedia.org/wiki/Generic_top-level_domain)
	'G_TLD' => array(
		'biz','com','edu','gov','info','int','mil','name','net','org',
		'aero','asia','cat','coop','jobs','mobi','museum','pro','tel','travel',
		'arpa','root','ua',
		'berlin','bzh','cym','gal','geo','kid','kids','lat','mail','nyc','post','sco','web','xxx',
		'nato',
		'example','invalid','localhost','test',
		'bitnet','csnet','ip','local','onion','uucp',
		'in','ru','pp','co'   // note: not technically, but used in things like co.uk
	),

	// country tlds (source: http://en.wikipedia.org/wiki/Country_code_top-level_domain)
	'C_TLD' => array(// active
		'ac','ad','ae','af','ag','ai','al','am','an','ao','aq','ar','as','at','au','aw','ax','az',
		'ba','bb','bd','be','bf','bg','bh','bi','bj','bm','bn','bo','br','bs','bt','bw','by','bz',
		'ca','cc','cd','cf','cg','ch','ci','ck','cl','cm','cn','co','cr','cu','cv','cx','cy','cz',
		'de','dj','dk','dm','do','dz','ec','ee','eg','er','es','et','eu','fi','fj','fk','fm','fo',
		'fr','ga','gd','ge','gf','gg','gh','gi','gl','gm','gn','gp','gq','gr','gs','gt','gu','gw',
		'gy','hk','hm','hn','hr','ht','hu','id','ie','il','im','in','io','iq','ir','is','it','je',
		'jm','jo','jp','ke','kg','kh','ki','km','kn','kr','kw','ky','kz','la','lb','lc','li','lk',
		'lr','ls','lt','lu','lv','ly','ma','mc','md','mg','mh','mk','ml','mm','mn','mo','mp','mq',
		'mr','ms','mt','mu','mv','mw','mx','my','mz','na','nc','ne','nf','ng','ni','nl','no','np',
		'nr','nu','nz','om','pa','pe','pf','pg','ph','pk','pl','pn','pr','ps','pt','pw','py','qa',
		're','ro','ru','rw','sa','sb','sc','sd','se','sg','sh','si','sk','sl','sm','sn','sr','st',
		'sv','sy','sz','tc','td','tf','tg','th','tj','tk','tl','tm','tn','to','tr','tt','tv','tw',
		'tz','ua','ug','uk','us','uy','uz','va','vc','ve','vg','vi','vn','vu','wf','ws','ye','yu',
		'za','zm','zw','com','рф',
		// inactive
		'eh','kp','me','rs','um','bv','gb','pm','sj','so','yt','su','tp','bu','cs','dd','zr'
	)
);
