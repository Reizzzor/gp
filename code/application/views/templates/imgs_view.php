<div id="list_title">
	<?=heading(empty($imgs) ? 'Данный раздел пуст!' : $category, 1)?>
</div>
<div id="imgs_list">
	<?php foreach($imgs as $i): ?>
		<?=$i?>
	<?php endforeach; ?>
</div>
<div id="viewer_imgs" style="display: none">
	<div>
		<span class="pic_switcher" id="prev_pic" style="display: none">
			<?=anchor('', 'Пред.', 'onClick="changePic(false); return false;"')?>
		</span>
		<span class="pic_switcher" id="next_pic" style="display: none">
			<?=anchor('', 'След.', 'onClick="changePic(true); return false;"')?>
		</span>
	</div>
	<div id="hp_wrapper">
		<img id="hidden_img" />
	</div>
	<span id="viewer_close_img">
		<?=anchor('', 'Закрыть', 'onClick="hideDiv(\'viewer_imgs\'); return false;"')?>
	</span>
</div>
	
