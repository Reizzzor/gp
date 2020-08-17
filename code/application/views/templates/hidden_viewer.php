<?php
	$viewer_id = 'viewer'.(isset($is_pic) ? '_imgs' : '');
	$closer_id = 'viewer_close'.(isset($is_pic) ? '_imgs' : '');
?>
<div id="<?=$viewer_id?>" style="display: none">
    <?php if(isset($is_pic)): ?>
        <div>
            <span class="pic_switcher" id="prev_pic" style="display: none"><?=anchor('', 'Пред.', 'onClick="changePic(false); return false;"')?></span>
            <span class="pic_switcher" id="next_pic" style="display: none"><?=anchor('', 'След.', 'onClick="changePic(true); return false;"')?></span>
        </div>
        <div id="hp_wrapper"><img id="hidden_img" /></div>
    <?php else: ?><div id="viewer_show"></div><?php endif; ?>
    <span id="<?=$closer_id?>"><?=anchor('', 'Закрыть', 'onClick="hideDiv(\''.$viewer_id.'\'); return false;"')?></span>
</div>
