<div class="poe_content">
	<div class="useredit_title"><?=$name?></div>
	<div class="poe_table">
		<div class="poe_rp">
			<div class="poe_options">
				<div<?=isset($category) ? '' : ' style="display: none"'?>><span class="info_title">Категория изделия: </span><span class="info_entry"><?=isset($category) ? $category : ''?></span></div>
				<div<?=$quantity == 1 ? ' style="display: none"' : ''?>><span class="info_title">Количество: </span><span class="info_entry"><?=$quantity?></span></div>
				<div<?=isset($nd_summ) ? '' : ' style="display: none"'?>><span class="info_title">Стоимость без учета скидки: </span><span class="info_entry"><?=isset($nd_summ) ? $nd_summ : ''?></span></div>
				<div<?=isset($dscnt) ? '' : ' style="display: none"'?>><span class="info_title">Скидка: </span><span class="info_entry"><?=isset($dscnt) ? $dscnt : ''?></span></div>
				<div<?=isset($summ) ? '' : ' style="display: none"'?>><span class="info_title">Стоимость: </span><span class="info_entry"><?=isset($summ) ? $summ : ''?></span></div>
				<div><span class="info_title">Образец: </span><span class="info_entry"><?=$is_example ? 'Да' : 'Нет'?></span></div>
				<div><span class="info_title">Нестандарт: </span><span class="info_entry"><?=$is_nonstandard ? 'Да' : 'Нет'?></span></div>
				<div<?=$comment ? '' : ' style="display: none"'?>><span class="info_title">Комментарий: </span><span class="info_entry"><?=$comment ? $comment : ''?></span></div>
				<?php foreach($extra as $i):?>
					<div><span class="info_title"><?=$i[0]?></span><span class="info_entry"><?=$i[1]?></span></div>
				<?php endforeach;?>
			</div>
			<?php if(!isset($rp_pic_src )) $rp_pic_src = ''; ?>
			<div class="poe_pic" <?=$rp_pic_src ? '' : 'style="display: none"'?>><img src="<?=$rp_pic_src?>" /></div>
			<div class="poe_modules_list" <?=isset($modules) ? '' : 'style="display: none"' ?>>
				<?php if(isset($modules) && !empty($modules)){
					echo '<div>Выбранные модули: </div>';
					foreach($modules as $i){
						echo '<div>'.$i['name'].'</div>';
					}
				}?>
			</div>
		</div>
	</div>
	<div class="poe_table"><?=$drapery_table?></div>
</div>