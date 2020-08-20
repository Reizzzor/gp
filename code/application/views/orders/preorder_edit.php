<div id="po_title">Информация о <span id="page_name">заказе</span></div>
<?php $sbmt = (isset($po['id']) && $po['id']) ? 'preorders/save/'.$po['id'] : 'preorders/save'; ?>
<?=form_open_multipart($sbmt, 'id="editPreorderForm" class="po_settings" onSubmit="return saveChanges();"')?>
<?=form_input(array('type'=>'hidden', 'name'=>'adding_poe', 'id'=>'adding_poe', 'value'=>'n'))?>
<div class="useredit_title">Заказ</div>
<div class="inputs_line">
	<div>
		<div class="lbl">Заказ №: </div>
		<?php
			$fparams = array('name'=>'sln_order_id', 'id'=>'sln_order_id', 'oninput'=>'soidCheck();');
			if($next_soid && !isset($po['sln_order_id'])) $po['sln_order_id'] = $next_soid;
			$fparams['value'] = isset($po['sln_order_id']) ? $po['sln_order_id'] : '';
			if(isset($po['sln_order_id']) && $po['sln_order_id']) $fparams['readonly'] = TRUE;
			echo form_input($fparams);
		?>
	</div>
	<div>
		<div class="lbl">Фамилия продавца: </div>
		<?if(!isset($po['seller_id'])) $po['seller_id'] = 0; ?> 
		<?=form_dropdown('sellerDD', isset($sln_stuff) ? $sln_stuff : array('val0'=>'Выбрать'), 'val'.$po['seller_id'], 'id="sellerDD"')?>
	</div>
</div>
<div id="po_consist_top" class="inputs_line">
	<?=heading(isset($consist_table) ? 'Состав заказа:' : 'Заказ пуст', 3)?>
	<div id="poe_btn_line">
		<div class="usr_btn"><?=form_button('new_po_entry', 'Добавить продукцию', 'onClick="addPOEntry();"')?></div>
		<div class="usr_btn"><?=form_button('new_poe_drp', 'Добавить ткань', 'onClick="addPOEDrapery();"')?></div>
	</div>
</div>
<div id='po_consist'><?=isset($consist_table) ? $consist_table : '' ?></div>
<div id="preorder_summ"><?php if(!$hide_fin_part) echo isset($consist_table) ? 'Общая сумма заказа: <span id="po_summ_val">'.($summ_total).'</span> руб.' : '';?></div>
<div id="preorder_summ"><?php if(!$hide_fin_part) echo isset($debt) ? 'Задолженность: <span id="po_summ_val">'.($debt).'</span> руб.' : '';?></div>
<!--<div>Сумма и вид оплаты:</div>-->
<?=form_input(array('type'=>'text', 'name'=>'pre_summa', 'id'=>'adding_poe', 'value'=>'', 'placeholder' => 'Введите сумму оплаты'))?>
<select name="type_prepayment" id="">
	<?foreach ($type_prepayment as $tp){?>
		<option value="<?=$tp['id']?>"><?=$tp['name']?></option>
	<?}?>
</select>
<br>
<?=$prepaymentTable ?? '<br>'?>
<br>

<?=$dlvr?>
<div class="useredit_title">Доставочный лист</div>
<div id="po_xtra_files">
<?php 
	$span_fname = isset($dlvr_list) ? '<span class="xtra_file_name">'.$dlvr_list.'</span>' : '';
	$span_del = isset($dlvr_list) ? '<span class="xtra_file_del" onClick="ajaxPO(\'del_xtra_file\', \''.$dlvr_list.'\');">Удалить</span>' : '';
	$xf_list_title = isset($dlvr_list) ? '' : 'Доставочный лист не загружен';
	$xf_display = isset($dlvr_list) ? 'style="display:none"' : '';
?>
	<div>
		<div id="xtra_files_lbl">При необходимости Вы можете отправить нам доставочный лист в формате PDF.</div>
		<input type="file" name="xtra_file_upld" id="xtra_file_upld" size="30" onChange="xfUpldHandle(this);" <?=$xf_display?>/>
	</div>
	<div>	
		<div id="xtra_filelist_title"><?=$xf_list_title?></div>
		<div id="xtra_filelist"><?=$span_fname.$span_del?></div>
	</div>
</div>
<div id="btn_line">
	<div class="usr_btn">
		<?= form_button(array('name' => 'apply', 'type' => 'submit', 'content' => 'Сохранить')) ?>
	</div>
	<div class="usr_btn">
		<?=form_button('undo', 'Отмена', 'onClick="location.href=\'/preorders\';"')?>
	</div>
</div>
<?php echo form_close();?>
