<?php
/*
	Карта сайта (тема «Lumen»): /sitemap/N
	Данные: $LINKS — массив [uri => заголовок] ссылок текущей страницы,
	        $f_cnt — всего страниц карты, $PAGE_NUM — текущая страница.
*/

global $LINKS, $f_cnt, $PAGE_NUM, $P_COUNT, $CG_CUPAG, $SITE_NAME;

$v_sitemap_cats = v_cats_all();
$v_sitemap_tags = v_tags_all(120);
$v_sitemap_text = v_text_pages();
$v_links_count = (is_array($LINKS) && !empty($LINKS))? count($LINKS) : 0;
?>
<main id="main" class="container">
    <div class="sitemap">
        <h1>Sitemap</h1>
        <p class="sitemap-note">Every public corner of <?php echo v_esc($SITE_NAME); ?>, grouped for a quick scan.</p>

        <div class="sitemap-grid">
            <section class="sitemap-group" aria-label="Main pages">
                <h2>Main pages</h2>
                <ul>
                    <li><a href="<?php echo _URL_; ?>">Home &mdash; The Collection</a></li>
                    <li><a href="<?php echo _URL_; ?>page/2">Latest videos &mdash; page 2</a></li>
                    <li><a href="<?php echo _URL_; ?>tag/">Tags index</a></li>
                    <li><a href="<?php echo _URL_; ?>sitemap.xml">XML sitemap</a></li>
                </ul>
            </section>

            <?php if(!empty($v_sitemap_cats)): ?>
            <section class="sitemap-group" aria-label="Categories" id="categories">
                <h2>Categories</h2>
                <ul>
                    <?php foreach($v_sitemap_cats as $v_cat_url => $v_cat_title): ?>
                    <li><a href="<?php echo v_esc($v_cat_url); ?>"><?php echo v_esc($v_cat_title); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <section class="sitemap-group" aria-label="Videos">
                <h2>Videos
                    <?php if(!empty($f_cnt) && $f_cnt > 1): ?>
                    <span class="sitemap-note">&mdash; part <?php echo (int)$PAGE_NUM; ?> of <?php echo (int)$f_cnt; ?></span>
                    <?php endif; ?>
                </h2>
                <ul>
                    <?php
                    if($v_links_count > 0){
                        foreach($LINKS as $v_uri => $v_title){
                            echo '<li><a href="'.v_esc(_URL_.$v_uri._EXT_).'">'.v_esc($v_title).'</a></li>';
                        }
                    }else{
                        echo '<li>No videos yet.</li>';
                    }
                    ?>
                </ul>
            </section>

            <?php if(!empty($v_sitemap_tags)): ?>
            <section class="sitemap-group" aria-label="Tags">
                <h2>Tags</h2>
                <ul>
                    <?php foreach($v_sitemap_tags as $v_tag_url => $v_tag_title): ?>
                    <li><a href="<?php echo v_esc($v_tag_url); ?>"><?php echo v_esc($v_tag_title); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
            <?php endif; ?>

            <section class="sitemap-group" aria-label="Informational and legal">
                <h2>Informational &amp; legal</h2>
                <ul>
                    <?php foreach($v_sitemap_text as $v_text_slug => $v_text_title): ?>
                    <li><a href="<?php echo _URL_; ?>text/<?php echo v_esc($v_text_slug); ?>"><?php echo $v_text_title; ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </section>
        </div>

        <?php include _GUI_.'inc/pagination.php'; ?>
    </div>
</main>

<?php v_breadcrumbs_jsonld([
    ['name' => 'Home', 'url' => ''],
    ['name' => 'Sitemap', 'url' => _URL_.'sitemap/1'],
]); ?>
