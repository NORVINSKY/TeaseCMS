<?php
/*
	Вертикальная карточка видео (тема «Lumen», класс .video-card).
	Ожидает переменную $block:
	['title', 'uri', 'thumb', 'time', 'duration', 'eager']
	uri — полный URL страницы (так отдаёт a_prepareData()).
	eager = true — загрузка превью без lazy (первые карточки ленты).
	Подключение: include _GUI_.'inc/card.php';
*/
if(!empty($block)):

	$c_title = isset($block['title'])? $block['title'] : '';
	$c_uri   = isset($block['uri'])? $block['uri'] : '#';
	$c_thumb = isset($block['thumb'])? $block['thumb'] : '';
	$c_time  = isset($block['time'])? (int)$block['time'] : 0;
	$c_dur   = isset($block['duration'])? $block['duration'] : '';
	$c_eager = !empty($block['eager']);
	?>
	<a class="video-card" href="<?php echo v_esc($c_uri); ?>">
		<div class="card-thumb">
			<img src="<?php p_thumb($c_thumb); ?>" width="640" height="360" alt=""<?php echo $c_eager? '':' loading="lazy"'; ?> decoding="async">
			<span class="play-badge" aria-hidden="true">
				<svg viewBox="0 0 24 24" fill="currentColor"><path d="M8 5v14l11-7z"/></svg>
			</span>
			<?php if($c_dur !== ''): ?>
			<span class="card-duration"><?php echo v_esc($c_dur); ?></span>
			<?php endif; ?>
		</div>
		<div class="card-body">
			<div class="card-title"><?php echo v_esc($c_title); ?></div>
			<div class="card-meta">
				<span><?php echo v_views($c_time, $c_title); ?> views</span>
				<span class="meta-dot" aria-hidden="true">&middot;</span>
				<span><?php echo v_esc(p_timeago($c_time)); ?></span>
			</div>
		</div>
	</a>
	<?php
endif;
