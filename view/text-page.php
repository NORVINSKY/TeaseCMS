<?php
/*
	Текстовая (юридическая) страница (тема «Lumen»): /text/{slug}
	Данные: $TP_TITLE, $TP_CONTENT, $TP_SENT, $TP_ERROR, $TP_NAME, $TP_UPDATED (filemtime).
*/
global $TP_TITLE, $TP_CONTENT, $TP_SENT, $TP_ERROR, $TP_NAME, $TP_UPDATED, $TP_SLUG, $SITE_NAME;

#Оглавление собирается из заголовков h2/h3 текста страницы
#Перед этим заглушка «Adult Video Platform» заменяется на домен сайта
$v_doc = v_doc_toc(v_brand_replace(!empty($TP_CONTENT)? $TP_CONTENT : ''));
?>
<main id="main" class="container">
    <div class="text-page">
        <nav class="breadcrumbs" aria-label="Breadcrumb">
            <a href="<?php echo _URL_; ?>">Home</a><span class="sep" aria-hidden="true">/</span><span aria-current="page"><?php echo v_esc(isset($TP_TITLE)? $TP_TITLE : ''); ?></span>
        </nav>

        <?php //Форма отправлена — показываем благодарность
        if(!empty($TP_SENT)): ?>

        <h1>Thank you!</h1>
        <p class="text-meta">Your message has been received<?php echo !empty($TP_NAME)? ', '.v_esc($TP_NAME) : ''; ?></p>

        <div class="text-layout">
            <div class="text-body">
                <p>Your request has been passed to the site administration. We usually reply within one business day.</p>
                <p>
                    <a class="btn" href="<?php echo _URL_; ?>text/contact">Send another message</a>
                    <a class="btn btn--primary" href="<?php echo _URL_; ?>">Back to the collection</a>
                </p>
            </div>
        </div>

        <?php else: ?>



        <div class="text-layout">


            <article class="text-body">
                <?php if(!empty($TP_ERROR)): ?>
                <p class="text-alert" role="alert"><?php echo v_esc($TP_ERROR); ?></p>
                <?php endif; ?>

                <?php echo $v_doc['html']; ?>
            </article>
        </div>

        <?php v_breadcrumbs_jsonld([
            ['name' => 'Home', 'url' => ''],
            ['name' => (isset($TP_TITLE)? $TP_TITLE : ''), 'url' => _URL_.'text/'.(isset($TP_SLUG)? $TP_SLUG : '')],
        ]); ?>

        <?php endif; ?>
    </div>
</main>
