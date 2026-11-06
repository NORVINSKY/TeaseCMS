<?php
/*
	Горизонтальная карточка «Up next» (тема «Lumen», класс .upnext-card).
	Ожидает переменную $block:
	['title', 'uri', 'thumb', 'time', 'duration']
	uri — полный URL страницы (так отдаёт p_relatedPages()).
	Подключение: include _GUI_.'inc/mini-card.php';
*/
if(!empty($block)):

	$m_title = isset($block['title'])? $block['title'] : '';
	$m_uri   = isset($block['uri'])? $block['uri'] : '#';
	$m_thumb = isset($block['thumb'])? $block['thumb'] : '';
	$m_time  = isset($block['time'])? (int)$block['time'] : 0;
	$m_dur   = isset($block['duration'])? $block['duration'] : '';
	?>
	<a class="upnext-card" href="<?php echo v_esc($m_uri); ?>">
		<div class="upnext-thumb">
			<img src="<?php p_thumb($m_thumb); ?>" width="640" height="360" alt="" loading="lazy" decoding="async">
			<?php if($m_dur !== ''): ?>
			<span class="card-duration"><?php echo v_esc($m_dur); ?></span>
			<?php endif; ?>
		</div>
		<div class="upnext-body">
			<div class="upnext-title"><?php echo v_esc($m_title); ?></div>
			<div class="upnext-meta">
				<span><?php echo v_views($m_time, $m_title); ?> views</span>
				<span aria-hidden="true">&middot;</span>
				<span><?php echo v_esc(p_timeago($m_time)); ?></span>
			</div>
		</div>
	</a>
	<?php
endif;
