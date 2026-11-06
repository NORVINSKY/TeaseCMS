<?php
/*
	Категория (тема «Lumen»): /{slug}/ и /{slug}/N/
	Также обрабатывается индекс тэгов (/tag/) — контроллер отдаёт PGTYPE=tag и $TAG_TITLE='Tag list'.
	Данные: $CAT_TITLE, $AP_DATA, $PAGE_DESC, $P_COUNT / $CG_CUPAG, $URI.
*/

global $CAT_TITLE, $AP_DATA, $P_COUNT, $CG_CUPAG, $TAG_TITLE, $html_TAGLIST, $URI;

#Индекс тэгов — отдельный экран
if(PGTYPE === 'tag' && isset($TAG_TITLE) && $TAG_TITLE === 'Tag list'){
	$v_tag_total = v_tags_all();
	$v_tag_count = count($v_tag_total);
	?>
	<main id="main" class="container">
		<div class="sitemap">
			<h1>Tags</h1>
			<p class="sitemap-note"><?php echo number_format($v_tag_count, 0, '.', ','); ?> tags &mdash; every tag opens a focused corner of the collection.</p>

			<?php if(!empty($v_tag_total)): ?>
			<div class="sitemap-group" style="margin-top:20px">
				<h2>All tags</h2>
				<ul class="link-columns">
					<?php foreach($v_tag_total as $v_tag_url => $v_tag_title): ?>
					<li><a href="<?php echo v_esc($v_tag_url); ?>"><?php echo v_esc($v_tag_title); ?></a></li>
					<?php endforeach; ?>
				</ul>
			</div>
			<?php else: ?>
			<p class="form-note">No tags yet.</p>
			<?php endif; ?>
		</div>
	</main>
	<?php
	return;
}

#Счётчик видео в категории — по индексному файлу cats/{slug}.dat
$v_cat_total = 0;
if(isset($URI) && $URI !== ''){
	$v_cat_lines = @file(_CACHE_.'cats/'.$URI.'.dat');
	if(is_array($v_cat_lines)){ $v_cat_total = count(array_filter(array_map('trim', $v_cat_lines))); }
}

$cat_i = 0;
?>
<main id="main" class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="<?php echo _URL_; ?>">Home</a><span class="sep" aria-hidden="true">/</span><a href="<?php echo _URL_; ?>sitemap/1#categories">Categories</a><span class="sep" aria-hidden="true">/</span><span aria-current="page"><?php echo v_esc($CAT_TITLE); ?></span>
    </nav>

    <div class="category-header">
        <h1><?php echo v_esc($CAT_TITLE); ?></h1>
        <span class="category-meta"><?php echo number_format($v_cat_total, 0, '.', ','); ?> videos</span>
        <?php if(!empty($PAGE_DESC)): ?>
        <p class="category-desc"><?php echo v_esc($PAGE_DESC); ?></p>
        <?php endif; ?>
    </div>

    <?php if(isset($AP_DATA) && !empty($AP_DATA)): ?>
    <section aria-label="Videos in this category">
        <div class="video-grid mt-16">
            <?php
            foreach($AP_DATA as $block){
                $block['eager'] = ($cat_i < 4); //первые 4 превью грузим сразу
                $cat_i++;
                include _GUI_.'inc/card.php';
            }
            ?>
        </div>

        <?php include _GUI_.'inc/pagination.php'; ?>

    <?php else: ?>
    <p class="form-note">No videos in this category yet.</p>
    <?php endif; ?>

    <?php v_breadcrumbs_jsonld([
        ['name' => 'Home', 'url' => ''],
        ['name' => 'Categories', 'url' => _URL_.'sitemap/1'],
        ['name' => $CAT_TITLE, 'url' => _URL_.$URI.'/'],
    ]); ?>
</main>
