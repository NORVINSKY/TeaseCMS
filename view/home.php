<?php
/*
	Главная страница (тема «Lumen»): сетка видео + пагинация.
	Данные: $AP_DATA — массив блоков видео, $P_COUNT / $CG_CUPAG — пагинация.
*/
global $AP_DATA, $P_COUNT, $CG_CUPAG;

$home_i = 0;
?>
<main id="main" class="container">
    <h1 class="sr-only">Latest videos</h1>

    <?php if(isset($AP_DATA) && !empty($AP_DATA)): ?>
    <section aria-label="Video grid">
        <div class="video-grid mt-16">
            <?php
            foreach($AP_DATA as $block){
                $block['eager'] = ($home_i < 4); //первые 4 превью грузим сразу
                $home_i++;
                include _GUI_.'inc/card.php';
            }
            ?>
        </div>

        <?php include _GUI_.'inc/pagination.php'; ?>
    </section>

    <?php else: ?>
    <p class="form-note">No videos yet. Import content to populate the site.</p>
    <?php endif; ?>
</main>
