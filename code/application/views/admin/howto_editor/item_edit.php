<?php if($i_type == 'imgs'): ?>
	<div>
		<div class="img_item">
			<?=anchor('', img($pre_link), 'onClick="viewImg(\''.site_url($link).'\'); return false;"')?>
		</div>
		<div class="upld_options">
			<p id="doc_<?=$id?>"><?=$name?></p>
			<div><?=$ctgr?>(#<span id="dspl_<?=$id?>"><?=$pos?></span>)</div>
			<span>[Добавлен <?=$added?>]</span>
		</div>
		<div class="open_editor">
			<?=anchor('', 'Редактировать', 'onClick="editUpld('.$id.', \'val'.$aggr_id.'\'); return false;"')?>
		</div>
	</div>	
<?php endif; ?>



