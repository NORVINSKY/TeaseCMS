<?php
/*
	Страница видео (тема «Lumen»).
	Данные: $P_TITLE, $P_DESC, $P_CREATE, $P_DURATION, $P_THUMB, $P_TAGS, $P_CATS,
	        $N_CONTENT — код плеера (embed), похожие записи через p_relatedPages().
*/

global $P_TITLE, $P_DESC, $P_CREATE, $P_DURATION, $P_THUMB, $P_TAGS, $P_CATS,
	$P_RELATED, $N_CONTENT, $URI, $SITE_NAME;

#Похожие записи: 4 в сайдбар «Up next» (на десктопе), они же + ещё 8 — в сетку ниже
$REL_BLOCKS = p_relatedPages();
$REL_UPNEXT = array_slice($REL_BLOCKS, 0, 4);
$REL_MORE   = array_slice($REL_BLOCKS, 4, 8);

#Категории и теги страницы — для крошек, блока Tags и источника
$PAGE_CATS  = v_cats_list();
$PAGE_TAGS  = v_tag_chips();
$PAGE_CATCH = v_cat_chips();
$PAGE_CAT   = (!empty($PAGE_CATS))? $PAGE_CATS[0] : null;

#Полное число просмотров (на странице видео показываем без сокращения)
ob_start(); p_views($P_CREATE, $P_TITLE); $PAGE_VIEWS = (int)ob_get_clean();

#Первая буква названия сайта — аватар блока «Источник»
$PAGE_AVATAR = mb_strtoupper(mb_substr((string)$SITE_NAME, 0, 1));
?>
<main id="main" class="container video-page">
    <nav class="breadcrumbs" aria-label="Breadcrumb">
        <a href="<?php echo _URL_; ?>">Home</a><span class="sep" aria-hidden="true">/</span>
        <?php if($PAGE_CAT): ?>
        <a href="<?php echo v_esc(_URL_.$PAGE_CAT['slug'].'/'); ?>"><?php echo v_esc($PAGE_CAT['title']); ?></a><span class="sep" aria-hidden="true">/</span>
        <?php endif; ?>
        <span aria-current="page"><?php echo v_esc($P_TITLE); ?></span>
    </nav>

    <div class="video-layout">
        <div>
            <div class="player">
                <?php echo $N_CONTENT; ?>
            </div>

            <h1 class="video-title"><?php echo v_esc($P_TITLE); ?></h1>

            <div class="video-meta">
                <span><?php echo number_format($PAGE_VIEWS, 0, '.', ','); ?> views</span>
                <span aria-hidden="true">&middot;</span>
                <?php if(!empty($P_CREATE)): ?>
                <span>Published <time datetime="<?php echo v_esc(v_iso($P_CREATE)); ?>"><?php echo v_esc(p_timeago($P_CREATE)); ?></time></span>
                <span aria-hidden="true">&middot;</span>
                <?php endif; ?>
                <span class="badge">18+</span>
                <?php if(!empty($P_DURATION)): ?>
                <span class="badge"><?php echo v_esc($P_DURATION); ?></span>
                <?php endif; ?>
            </div>

            <?php if(!empty($PAGE_TAGS) || !empty($PAGE_CATCH)): ?>
            <div class="tags">
                <?php foreach($PAGE_CATCH as $PAGE_CHIP){ echo $PAGE_CHIP; } ?>
                <?php foreach($PAGE_TAGS as $PAGE_CHIP){ echo $PAGE_CHIP; } ?>
            </div>
            <?php endif; ?>

            <div class="video-description">
                <?php if(!empty($P_DESC)): ?>
                <p><?php echo v_esc($P_DESC); ?></p>
                <?php else: ?>
                <p>Watch this scene on <?php echo v_esc($SITE_NAME); ?>. All models are over 18 years old.</p>
                <?php endif; ?>
            </div>

        </div>

        <?php if(!empty($REL_UPNEXT)): ?>
        <aside aria-label="Up next">
            <div class="upnext-panel">
                <h2>Up next</h2>
                <div class="upnext-list">
                    <?php foreach($REL_UPNEXT as $block){ include _GUI_.'inc/mini-card.php'; } ?>
                </div>
            </div>
        </aside>
        <?php endif; ?>
    </div>

    <?php if(!empty($REL_UPNEXT) || !empty($REL_MORE)): ?>
    <section class="more-recs" aria-label="Recommended videos">
        <h2 class="section-heading">Recommended</h2>
        <div class="video-grid">
            <?php
            foreach($REL_UPNEXT as $block){ include _GUI_.'inc/card.php'; }
            foreach($REL_MORE as $block){ include _GUI_.'inc/card.php'; }
            ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<?php
#Schema.org BreadcrumbList
$crumbs = [['name' => 'Home', 'url' => '']];
if($PAGE_CAT){ $crumbs[] = ['name' => $PAGE_CAT['title'], 'url' => _URL_.$PAGE_CAT['slug'].'/']; }
$crumbs[] = ['name' => $P_TITLE, 'url' => _URL_.$URI._EXT_];
v_breadcrumbs_jsonld($crumbs);
?>
