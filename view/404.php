<?php
/*
	Страница 404 (тема «Lumen»).
	Данные: $PAGE_DESC (описание из SEO-функций CMS), v_cats_all() — категории сайта.

	Шаблон рендерится изнутри page404() (область видимости — функция),
	поэтому нужные переменные импортируются явно.
*/
global $PAGE_DESC, $SITE_NAME;
$v_error_cats = array_slice(v_cats_all(), 0, 6, true);
?>
<main id="main" class="container">
    <section class="error-page" aria-labelledby="err-title">
        <div class="error-code">404</div>
        <h1 id="err-title" class="error-title">Page not found</h1>
        <p class="error-text">
            <?php echo !empty($PAGE_DESC)? v_esc($PAGE_DESC) : 'The page you were looking for does not exist or has moved. Try the home page or browse a category below.'; ?>
        </p>

        <div class="error-actions">
            <a class="btn btn--primary" href="<?php echo _URL_; ?>">Go to home</a>
            <a class="btn" href="<?php echo _URL_; ?>sitemap/1">View sitemap</a>
        </div>

        <div class="error-cats">
            <h2>Popular categories</h2>
            <ul>
                <?php foreach($v_error_cats as $v_ec_url => $v_ec_title): ?>
                <li><a href="<?php echo v_esc($v_ec_url); ?>"><?php echo v_esc($v_ec_title); ?></a></li>
                <?php endforeach; ?>
                <li><a href="<?php echo _URL_; ?>sitemap/1">Sitemap</a></li>
            </ul>
        </div>
    </section>
</main>
