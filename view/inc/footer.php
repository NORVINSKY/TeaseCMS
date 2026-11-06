<?php
/*
	Подвал сайта (тема «Lumen»): копирайт, метка 18+, ссылки на юридические страницы.
	Рекламный блок, код клоакинга и FluidVids подключаются здесь же — они
	обязаны идти после разметки страницы.
*/
#footer.php подключается как из глобальной области, так и изнутри page404()
global $SITE_NAME;

#Ссылки на текстовые (юридические) страницы
$v_footer_pages = v_text_pages();
?>
    <footer class="site-footer">
        <div class="container footer-inner">
            <span>&copy; <?php echo date('Y'); ?> <?php echo v_esc($SITE_NAME); ?></span>
            <span class="footer-sep" aria-hidden="true">&middot;</span>
            <span class="age-pill">18+</span>
            <span class="footer-sep" aria-hidden="true">&middot;</span>
            <nav class="footer-legal" aria-label="Legal">
                <?php foreach($v_footer_pages as $v_slug => $v_title): ?>
                <a href="<?php echo _URL_; ?>text/<?php echo v_esc($v_slug); ?>"><?php echo $v_title; ?></a>
                <?php endforeach; ?>
            </nav>
        </div>
    </footer>

    <!-- Рекламный блок и код клоакинга -->
    <?php r_loadfile('advert'); ?>
    <?php cloakBox(); ?>

    <!-- FluidVids: адаптивные iframe с видеохостингов -->
    <script src="<?php echo _URL_; ?>view/media/js/fluid.js"></script>
    <?php r_fluidvids(); ?>

</body>
</html>
