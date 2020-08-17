<div id="po_title">Заказ ткани</div>
<?=form_open(isset($po['id']) ? "poedrp/save/$po_id/".$po['id'] : 'poedrp/save/'.$po_id, 'id="editPOEntryForm" class="po_settings" onSubmit="return saveChanges();"')?>
<div class="useredit_title">Ткань</div>
<div id="drp_select" class="dt">
	<?php 
		$tm_dd = array('val0'=>'Выбрать торг. марку');
		for($i=0;$i<count($rp_drapery);$i++){
			$tm_dd['val'.($i+1)] = $rp_drapery[$i]['trade_mark'];
		}
	?>
	<div class="ddd" id="tm">
		<?=form_dropdown('tmDD', $tm_dd, 'val0', 'id="tmDD" onChange="chngTM(this);"')?>
	</div>
	<div class="ddd" id="coll" style="display:none">
		<?=form_dropdown('collDD', array('val0'=>'Выбрать коллекцию'), 'val0', 'id="collDD" onChange="chngColl(this);"')?>
	</div>
	<div class="ddd" id="drapery" style="display:none">
		<?=form_dropdown('draperyDD', array('val0'=>'Выбрать ткань'), 'val0', 'id="draperyDD" class="draperyDD" onChange="chngDrp(this);"')?>
	</div>
</div>
<?=form_input(array('type'=>'hidden', 'id'=>'summ_total', 'name'=>'summ_total')) //эту сумму держим в копейках ?> 
<div class="useredit_title">Опции</div>
<div id="poe_drp_options">
	<div id="poe_drp_category">
		<span class="poe_drp_title">Категория ткани: <span id="poe_drp_ctgr_val" class="poe_drp_val"><?=isset($po['ctgr_name']) ? $po['ctgr_name'] : 'не задана'?></span></span>
	</div>
	<div id="poe_drp_price"<?=$hide_fin_part ? ' style="display: none"' : ''?>>
		<span class="poe_drp_title">Цена: <span id="poe_drp_price_val" class="poe_drp_val">не задана</span></span>
	</div>
	<div id="poe_drp_quantity">
		<span class="poe_drp_title">Количество(в метрах): </span>
		<?=form_input(array('name'=>'quantity', 'id'=>'quantity', 'value'=>isset($po['quantity']) ? $po['quantity']/100 : '', 'onChange'=>'setPOEOptions();'))?>
	</div>
	<div id="poe_drp_summ"<?=$hide_fin_part ? ' style="display: none"' : ''?>>
		<span class="poe_drp_title">Стоимость: <span id="poe_drp_summ_val" class="poe_drp_val"><?=isset($po['total_summ']) ? ($po['total_summ']/100).' руб.' : 'нет'?></span></span>
	</div>
</div>
<div class="useredit_title">Комментарий к заказу</div>
<p><?=form_textarea(array('name'=>'nstrd_descr', 'id'=>'nstrd_descr', 'value'=>isset($po['nstrd_descr']) ? $po['nstrd_descr'] : '', 'placeholder'=>'Опишите детали заказа'))?></p>
<div id="btn_line">
	<div class="usr_btn">
		<?=form_button(array('name'=>'apply', 'type'=>'submit', 'content'=>'Сохранить'))?>
	</div>
	<div class="usr_btn">
		<?=form_button('undo', 'Отмена', 'onClick="location.href=\'/preorders/'.$po_id.'\';"')?>
	</div>
</div>
<?=form_close()?>