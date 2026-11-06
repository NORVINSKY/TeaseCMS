<?php
/*
	Шапка сайта (тема «Lumen»): <head>, мета-данные, шапка с логотипом,
	мега-меню категорий, переключателем темы и мобильным меню.
	Подключается из index.php перед шаблоном страницы.

	#Шаблон подключается как из глобальной области, так и изнутри page404()
	#(там область видимости — функция), поэтому явно импортируем глобальные переменные.
*/
global $SITE_NAME, $P_TITLE, $P_SEOTITLE, $P_DESC, $P_THUMB, $P_CREATE, $P_DURATION,
	$P_TAGS, $P_CATS, $URI, $CAT_TITLE, $TAG_TITLE, $TP_TITLE, $TP_DESC, $TP_SLUG,
	$PAGE_NUM, $P_COUNT, $CG_CUPAG, $T_DELIM, $B_TEND;

#Хелперы шаблона (v_esc, v_views, v_page_url и т.д.)
include_once _GUI_.'inc/helpers.php';

#SEO-значения собираем в буфер
ob_start(); r_title();       $PAGE_TITLE = trim(ob_get_clean());
ob_start(); r_description(); $PAGE_DESC  = trim(ob_get_clean());
ob_start(); r_keywords();    $PAGE_KWDS  = trim(ob_get_clean());

$OG_TYPE = (PGTYPE == 'page')? 'video.other' : 'website';
$OG_URL  = _PROTO_._URL_.ltrim($_SERVER['REQUEST_URI'], '/');
$OG_IMG  = _PROTO_._URL_.'view/assets/images/og-placeholder.svg';

if(PGTYPE == 'page' && !empty($P_THUMB)){ $OG_IMG = _PROTO_.p_thumb_url($P_THUMB); }

#Пагинация должна быть собрана до indexOpt(): r_pagenavi() заполняет
#$GLOBALS['prev-p'] / $GLOBALS['next-p'], из которых indexOpt() строит <link>
$r_pagenavi = r_pagenavi(15, '', 'current', 'span', 'page-numbers');

#Текущий раздел — для подсветки в меню
$nav_home    = (PGTYPE == 'home');
$nav_sitemap = (PGTYPE == 'sitemap');

#Категории для мега-меню и мобильного аккордеона
$v_header_cats = v_cats_all();
#Юридические страницы — для мобильного меню и подвала
$v_header_text = v_text_pages();

#Версия ассетов — по времени изменения файла, чтобы браузер не держал
#старую версию темы в кэше после обновления
$v_asset_v = (is_file(_GUI_.'assets/css/styles.css'))? filemtime(_GUI_.'assets/css/styles.css') : '1';
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title><?php echo v_esc($PAGE_TITLE); ?></title>
    <meta name="description" content="<?php echo v_esc($PAGE_DESC); ?>" />
    <meta name="keywords" content="<?php echo v_esc($PAGE_KWDS); ?>" />
    <meta name="rating" content="adult">

    <meta property="og:type" content="<?php echo $OG_TYPE; ?>">
    <meta property="og:site_name" content="<?php echo v_esc($SITE_NAME); ?>">
    <meta property="og:locale" content="en_US">
    <meta property="og:title" content="<?php echo v_esc($PAGE_TITLE); ?>">
    <meta property="og:description" content="<?php echo v_esc($PAGE_DESC); ?>">
    <meta property="og:url" content="<?php echo v_esc($OG_URL); ?>">
    <meta property="og:image" content="<?php echo v_esc($OG_IMG); ?>">
    <meta property="og:image:alt" content="<?php echo v_esc($SITE_NAME); ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="theme-color" content="#F5F3EF">

    <?php indexOpt(); ?>

    <link href="<?php echo _URL_; ?>favicon.ico" rel="shortcut icon" />

    <!-- Тема применяется до отрисовки, чтобы не было «мигания» светлой темы -->
    <script>
    (function(){try{var t=localStorage.getItem('theme'); if(!t){ t = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'; } document.documentElement.setAttribute('data-theme', t);}catch(e){document.documentElement.setAttribute('data-theme','light');}})();
    </script>

    <link rel="stylesheet" href="<?php echo _URL_; ?>view/assets/css/styles.css?v=<?php echo $v_asset_v; ?>">
    <script src="<?php echo _URL_; ?>view/assets/js/main.js?v=<?php echo $v_asset_v; ?>" defer></script>
    <noscript><style>.mobile-nav{position:static}</style></noscript>

    <?php if(PGTYPE == 'page' && !empty($P_TITLE)): ?>
    <?php
    //Разбор длительности "MM:SS" / "HH:MM:SS" в формат ISO 8601 (PT..M..S)
    $TP_MIN = 0; $TP_SEC = 0;
    if(!empty($P_DURATION)){
        $durParts = array_reverse(explode(':', (string)$P_DURATION));
        $TP_SEC = (int)(isset($durParts[0])? $durParts[0] : 0);
        $TP_MIN = (int)(isset($durParts[1])? $durParts[1] : 0);
    }
    ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "VideoObject",
      "name": <?php echo json_encode($P_TITLE); ?>,
      "description": <?php echo json_encode(isset($P_DESC)? $P_DESC : ''); ?>,
      "thumbnailUrl": <?php echo json_encode(_PROTO_.p_thumb_url(isset($P_THUMB)? $P_THUMB : '')); ?>,
      "uploadDate": <?php echo json_encode(date('c', (int)(isset($P_CREATE)? $P_CREATE : time()))); ?>,
      "duration": "PT<?php echo $TP_MIN; ?>M<?php echo $TP_SEC; ?>S",
      "embedUrl": <?php echo json_encode(_PROTO_._URL_.(isset($URI)? $URI : '')._EXT_); ?>,
      "inLanguage": "en",
      "isFamilyFriendly": false
    }
    </script>
    <?php elseif(PGTYPE == 'home'): ?>
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebSite",
      "url": <?php echo json_encode(_PROTO_._URL_); ?>,
      "name": <?php echo json_encode($SITE_NAME); ?>,
      "inLanguage": "en"
    }
    </script>
    <?php endif; ?>
</head>

<body>
    <a class="skip-link" href="#main">Skip to content</a>

    <header class="site-header">
        <div class="container header-inner">
            <a href="<?php echo _URL_; ?>" class="logo-box" aria-label="<?php echo v_esc($SITE_NAME); ?> — home">
                <span class="logo-mark" aria-hidden="true"></span>
                <span class="logo-text"><?php echo v_esc($SITE_NAME); ?></span>
            </a>

            <nav class="primary-nav" aria-label="Primary">
                <a class="nav-link<?php echo $nav_home? ' is-active':''; ?>" href="<?php echo _URL_; ?>"<?php echo $nav_home? ' aria-current="page"':''; ?>>Home</a>

                <?php if(!empty($v_header_cats)): ?>
                <div class="cat-wrap">
                    <button class="cat-trigger" data-cat-trigger aria-haspopup="true" aria-expanded="false" type="button">
                        Categories
                        <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true" focusable="false"><path d="M3 4.5l3 3 3-3"/></svg>
                    </button>
                    <div class="mega-menu" data-cat-panel role="menu" aria-label="Categories">
                        <div class="mega-scroll">
                            <ul class="mega-list" data-cat-list>
                                <?php foreach($v_header_cats as $v_cat_url => $v_cat_title): ?>
                                <li><a href="<?php echo v_esc($v_cat_url); ?>"><?php echo v_esc($v_cat_title); ?></a></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <a class="nav-link<?php echo $nav_sitemap? ' is-active':''; ?>" href="<?php echo _URL_; ?>sitemap/1"<?php echo $nav_sitemap? ' aria-current="page"':''; ?>>Sitemap</a>
            </nav>

            <div class="header-tools">
                <button class="theme-toggle" data-theme-toggle type="button" aria-pressed="false" aria-label="Switch to dark theme">
                    <svg data-icon-sun viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><circle cx="12" cy="12" r="4"/><path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"/></svg>
                    <svg data-icon-moon viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" hidden aria-hidden="true" focusable="false"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                </button>
                <button class="mobile-toggle" data-mobile-toggle aria-expanded="false" aria-label="Open menu" type="button">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" focusable="false"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
                </button>
            </div>
        </div>

        <div class="mobile-nav" data-mobile-nav>
            <a href="<?php echo _URL_; ?>">Home</a>
            <?php if(!empty($v_header_cats)): ?>
            <details class="cat-accordion">
                <summary>Categories</summary>
                <ul class="cat-grid" data-cat-mobile-list>
                    <?php foreach($v_header_cats as $v_cat_url => $v_cat_title): ?>
                    <li><a href="<?php echo v_esc($v_cat_url); ?>"><?php echo v_esc($v_cat_title); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </details>
            <?php endif; ?>
            <a href="<?php echo _URL_; ?>sitemap/1">Sitemap</a>
            <?php foreach($v_header_text as $v_text_slug => $v_text_title): ?>
            <a href="<?php echo _URL_; ?>text/<?php echo v_esc($v_text_slug); ?>"><?php echo $v_text_title; ?></a>
            <?php endforeach; ?>
        </div>
    </header>
