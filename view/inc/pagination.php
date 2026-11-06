<?php
/*
	Пагинация (тема «Lumen»).
	Ожидает: $P_COUNT — всего страниц, $CG_CUPAG — текущая страница.
	Ссылки строятся через v_page_url() с учётом типа страницы (PGTYPE).
	Адаптив: на экранах <480px номера скрываются, остаются стрелки
	и статус «Page X of Y».
*/
if(!isset($P_COUNT) || empty($P_COUNT) || (int)$P_COUNT < 2){ return; }

$pg_cur   = (isset($CG_CUPAG) && (int)$CG_CUPAG > 0)? (int)$CG_CUPAG : 1;
$pg_total = (int)$P_COUNT;
$pg_prev  = ($pg_cur > 1)? v_page_url($pg_cur - 1) : '';
$pg_next  = ($pg_cur < $pg_total)? v_page_url($pg_cur + 1) : '';

#Окно номеров: 5 страниц вокруг текущей
$pg_ws = max(1, $pg_cur - 2);
$pg_we = min($pg_total, $pg_cur + 2);
?>
<div class="pagination">
	<nav aria-label="Pagination">
		<?php if($pg_prev !== ''): ?>
		<a class="page-btn" rel="prev" href="<?php echo v_esc($pg_prev); ?>" aria-label="Previous page">Prev</a>
		<?php else: ?>
		<span class="page-btn is-disabled" aria-disabled="true">Prev</span>
		<?php endif; ?>

		<span class="page-numbers">
			<?php if($pg_ws > 1): ?>
			<a class="page-btn" href="<?php echo v_esc(v_page_url(1)); ?>" aria-label="Page 1">1</a>
			<?php if($pg_ws > 2): ?>
			<span class="page-ellipsis" aria-hidden="true">&hellip;</span>
			<?php endif; ?>
			<?php endif; ?>

			<?php for($pg_i = $pg_ws; $pg_i <= $pg_we; $pg_i++): ?>
				<?php if($pg_i === $pg_cur): ?>
				<span class="page-btn is-current" aria-current="page"><?php echo $pg_i; ?></span>
				<?php else: ?>
				<a class="page-btn" href="<?php echo v_esc(v_page_url($pg_i)); ?>" aria-label="Page <?php echo $pg_i; ?>"><?php echo $pg_i; ?></a>
				<?php endif; ?>
			<?php endfor; ?>

			<?php if($pg_we < $pg_total): ?>
			<?php if($pg_we < $pg_total - 1): ?>
			<span class="page-ellipsis" aria-hidden="true">&hellip;</span>
			<?php endif; ?>
			<a class="page-btn" href="<?php echo v_esc(v_page_url($pg_total)); ?>" aria-label="Page <?php echo $pg_total; ?>"><?php echo $pg_total; ?></a>
			<?php endif; ?>
		</span>

		<span class="page-indicator">Page <?php echo $pg_cur; ?> of <?php echo $pg_total; ?></span>

		<?php if($pg_next !== ''): ?>
		<a class="page-btn" rel="next" href="<?php echo v_esc($pg_next); ?>" aria-label="Next page">Next</a>
		<?php else: ?>
		<span class="page-btn is-disabled" aria-disabled="true">Next</span>
		<?php endif; ?>
	</nav>
</div>
