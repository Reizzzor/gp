<div id="list_title">
	<?=heading(empty($videos) ? 'Данный раздел пуст!' : $category, 1)?>
</div>
<?php if(!empty($videos)): ?>
	<div id="imgs_list">
		<?php foreach($videos as $i): ?><?=$i?><?php endforeach; ?>
	</div>
	<?=$viewer?>
<?php endif; ?>
