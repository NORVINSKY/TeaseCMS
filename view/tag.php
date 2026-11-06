<?php
/*
	Страница тэга (тема «Lumen»): /tag/{slug} и /tag/{slug}/N
	Данные: $TAG_TITLE, $AP_DATA, $PAGE_DESC, $P_COUNT / $CG_CUPAG, $URI.
*/
global $TAG_TITLE, $AP_DATA, $P_COUNT, $CG_CUPAG, $URI;

#Счётчик видео по тэгу — по индексному файлу tags/{slug}.dat
$v_tag_total = 0;
if(isset($URI) && $URI !== ''){
	$v_tag_lines = @file(_CACHE_.'tags/'.$URI.'.dat');
	if(is_array($v_tag_lines)){ $v_tag_total = count(array_filter(array_map('trim', $v_tag_lines))); }
}

$tag_i = 0;
?>
<main id="main" class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="<?php echo _URL_; ?>">Home</a><span class="sep" aria-hidden="true">/</span><a href="<?php echo _URL_; ?>tag/">Tags</a><span class="sep" aria-hidden="true">/</span><span aria-current="page"><?php echo v_esc($TAG_TITLE); ?></span>
    </nav>

    <div class="category-header">
        <h1><?php echo v_esc($TAG_TITLE); ?></h1>
        <span class="category-meta"><?php echo number_format($v_tag_total, 0, '.', ','); ?> videos</span>
        <?php if(!empty($PAGE_DESC)): ?>
        <p class="category-desc"><?php echo v_esc($PAGE_DESC); ?></p>
        <?php endif; ?>
    </div>

    <?php if(isset($AP_DATA) && !empty($AP_DATA)): ?>
    <section aria-label="Videos with this tag">
        <div class="video-grid mt-16">
            <?php
            foreach($AP_DATA as $block){
                $block['eager'] = ($tag_i < 4); //первые 4 превью грузим сразу
                $tag_i++;
                include _GUI_.'inc/card.php';
            }
            ?>
        </div>

        <?php include _GUI_.'inc/pagination.php'; ?>

    <?php else: ?>
    <p class="form-note">No videos with this tag.</p>
    <?php endif; ?>

    <?php v_breadcrumbs_jsonld([
        ['name' => 'Home', 'url' => ''],
        ['name' => 'Tags', 'url' => _URL_.'tag/'],
        ['name' => $TAG_TITLE, 'url' => _URL_.'tag/'.$URI],
    ]); ?>
</main>
