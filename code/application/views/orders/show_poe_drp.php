<div class="poe_content">
	<div class="useredit_title"><?=$name?></div>
	<div class="poe_table">
		<div class="poe_rp">
			<div class="poe_options">
				<div><span class="info_title">Коллекция: </span><span class="info_entry"><?=$collection?></span></div>
				<div><span class="info_title">Торговая марка: </span><span class="info_entry"><?=$trade_mark?></span></div>
				<div><span class="info_title">Категория: </span><span class="info_entry"><?=$ctgr_name?></span></div>
				<div><span class="info_title">Количество: </span><span class="info_entry"><?=$quantity?> м</span></div>
				<div <?=isset($summ) ? '' : 'style="display: none"'?>><span class="info_title">Стоимость: </span><span class="info_entry"><?=isset($summ) ? $summ : ''?></span></div>
			</div>
		</div>
	</div>
</div>