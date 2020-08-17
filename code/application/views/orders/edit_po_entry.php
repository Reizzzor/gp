<div id="po_title">Заказ продукции</div>
<?php 
	if(isset($po_id)) $sbmt_link = isset($po['id']) ? "poentry/save/$po_id/{$po['id']}" : 'poentry/save/'.$po_id;
	else $sbmt_link = isset($po['id']) ? 'store/submit/'.$po['id'] : 'store/submit';
?>
<?=form_open($sbmt_link, 'id="editPOEntryForm" class="po_settings" onSubmit="return savePOEntryChanges();"')?>
<div id="checkboxes_line">
	<?php if(isset($strg_flag) && $strg_flag){
			$fparams = array('name'=>'strg_soid', 'id'=>'strg_soid', 'oninput'=>'soidCheck();');
			if(!isset($po['sln_order_id']) && isset($next_soid) && $next_soid) $po['sln_order_id'] = $next_soid;
			$fparams['value'] = isset($po['sln_order_id']) ? $po['sln_order_id'] : '';
			echo '<div class="lbl" id="soid_lbl">Заказ №: </div>'.form_input($fparams);
		}?>
	<div class="lbl" id="is_example_lbl">Образец: </div>
	<?php $chckd = isset($po['is_example']) ? $po['is_example'] : '0'; ?>
	<?=form_checkbox(array('name' => 'is_example', 'id' => 'is_example', 'checked' => (int)$chckd, 'value' => $chckd, 'onChange' => 'cbSetValue(\'is_example\'); setXtraDiscount();'))?>
	<div class="lbl" id="is_nonstandard_lbl">Нестандарт: </div>
	<?php $chckd = isset($po['is_nonstandard']) ? $po['is_nonstandard'] : '0'; ?>
	<?=form_checkbox(array('name' => 'is_nonstandard', 'id' => 'is_nonstandard', 'checked' => (int)$chckd, 'value' => $chckd, 'onChange' => 'nstrdChngHandler();'))?>
	
</div>
<p>
	<div class="lbl" id="model_lbl">Модель: </div>
	<?php 
		$rp_arr = array('val0'=>'Выбрать');
		foreach($r_prod as $i){
			$rp_arr['val'.$i['id']] = $i['name'];
		}
		echo form_dropdown('r_prodDD', $rp_arr, 'val'.(isset($model_id) ? $model_id : 0), 'id="r_prodDD" onChange="setOptions();"');
	?>
	<?php
		$params = array();
		for($i=0;$i<=100;$i++){
			$params['val'.$i] = $i.'%';
		}
	?>
	<div id="rp_quantity">Количество: <?=form_input(array('name'=>'quantity', 'id'=>'quantity', 'value'=>isset($po['quantity']) ? $po['quantity'] : '1', 'onChange'=>'countFinalPrice();'))?></div>
	<div id="common_category">Категория изделия: <span id="common_ctgr_val"><?=isset($po['category']) ? $po['category'] : 'не выбрана'?></span></div>
	<div id="common_price">
		<?php if(!$hide_fin_part){
			$price = isset($po['price']) ? $po['price'] : 'нет';
			echo 'Стоимость: <span id="common_price_val">'.$price.'</span>';
		}?>
	</div>
	<div id="discount">Скидка: <span id="discount_val"><?=isset($po['discount']) ? $po['discount'] : 0?></span>%</div>
	<?php
		if(!isset($po['xtra_discount'])) $po['xtra_discount'] = 0;
		if(isset($po['is_nonstandard']) && (int)$po['is_nonstandard']){
			if((int)$po['xtra_discount'] > 0){
				$nstrd_discount = 'val'.$po['xtra_discount'];
				$nstrd_markup = 'val0';
			}
			else{
				$nstrd_discount = 'val0';
				$nstrd_markup = 'val'.(-1*(int)$po['xtra_discount']);
			}
		}
	?>
	<div id="n_discount" style="display: none">
		Скидка: <?=form_dropdown('nstrd_discount', $params, isset($nstrd_discount) ? $nstrd_discount : 'val0', 'id="nstrd_discount" onChange="setNstrdDiscount();"')?>
		Наценка: <?=form_dropdown('nstrd_markup', $params, isset($nstrd_markup) ? $nstrd_markup : 'val0', 'id="nstrd_markup" onChange="setNstrdDiscount(true);"')?>
	</div>
	<div id="final_price"><?= !$hide_fin_part ? 'Итого: <span id="final_price_val">нет</span>' : ''?></div>
	<div id="xtra_discount">Доп. скидка: <?=form_dropdown('xtra_discount_val', $params, 'val'.$po['xtra_discount'], 'id="xtra_discount_val" onChange="setXtraDiscount();"')?></div>
	<?=form_input(array('type'=>'hidden', 'id'=>'summ_total', 'name'=>'summ_total'))?>
</p>
<div id="corners" style="display: none">
	<p>
		<div class="lbl" id="corners_lbl">Угол: </div>
		<?php $slctd = isset($po['corner']) ? array_search($po['corner'], [NULL, 'L', 'R']) : 0; ?>
		<?=form_dropdown('cornersDD', array('val0'=>'Выбрать', 'val1'=>'Левый', 'val2'=>'Правый'), 'val'.$slctd, 'id="cornersDD"')?>
	</p>
</div>
<div id="drapery">
	<div class="useredit_title">Варианты кроя</div>
	<?php 
		$tm_dd = array('val0'=>'Выбрать торг. марку');
		for($i=0;$i<count($rp_drapery);$i++){
			$tm_dd['val'.($i+1)] = $rp_drapery[$i]['trade_mark'];
		}
	?>
	<div id="drapery_options">
		<div id="drapery_type">
			<?php if(!isset($po['drapery_type'])) $po['drapery_type'] = 1;
			      $drr_arr = ['Всё изделие - в одной ткани', 'В разных тканях', 'Индивидуальный крой']; ?>
			<?php for($i=0; $i<count($drr_arr); $i++): ?>
				<span>
					<?=form_radio(array('name'=>'drapery_radio', 'id'=>'drapery_radio'.($i+1), 'value'=>$i+1, 'checked'=>$po['drapery_type'] == $i+1 ? TRUE : FALSE, 'onClick'=>'drrHandle(this);'))?>
					<?=form_label($drr_arr[$i], 'drapery_radio'.($i+1))?>
				</span>
			<?php endfor; ?>
		</div>
		<div class="useredit_title">Ткань</div>
		<div id="dt1" class="dt" style="display: none">
			<div class="ddd" id="tm0">
				<?=form_dropdown('tmDD0', $tm_dd, 'val0', 'id="tmDD0" onChange="dddChange(this);"')?>
			</div>
			<div class="ddd" id="coll0" style="display:none">
				<?=form_dropdown('collDD0', array('val0'=>'Выбрать коллекцию'), 'val0', 'id="collDD0" onChange="dddChange(this);"')?>
			</div>
			<div class="ddd" id="drapery0" style="display:none">
				<?=form_dropdown('draperyDD0', array('val0'=>'Выбрать ткань'), 'val0', 'id="draperyDD0" class="draperyDD" onChange="setCategory(this);"')?>
				<div class="drp_category" id="draperyDD0_category" style="display:none">Категория:<span class="drp_ctgr_val" id="draperyDD0_ctgr_val"></span></div>
			</div>
		</div>
		<div id="dt2" class="dt" style="display: none">
			<?php for($i=1;$i<=6;$i++): ?>
			<?php $tm_dd['val0'] = "(Ткань $i: Выбрать торг. марку)"; ?>
				<div class="d2">
					<div class="ddd" id="tm<?=$i?>">
						<?=form_dropdown('tmDD'.$i, $tm_dd, 'val0', 'id="tmDD'.$i.'" onChange="dddChange(this);"')?>
					</div>
					<div class="ddd" id="coll<?=$i?>" style="display:none">
						<?=form_dropdown('collDD'.$i, array('val0'=>"(Ткань $i: Выбрать коллекцию)"), 'val0', 'id="collDD'.$i.'" onChange="dddChange(this);"')?>
					</div>
					<div class="ddd" id="drapery<?=$i?>" style="display:none">
						<?=form_dropdown('draperyDD'.$i, array('val0'=>"(Ткань $i: Выбрать)"), 'val0', 'id="draperyDD'.$i.'" class="draperyDD" onChange="setCategory(this);"')?>
						<div class="drp_category" id="draperyDD<?=$i?>_category" style="display:none">Категория:<span class="drp_ctgr_val" id="draperyDD<?=$i?>_ctgr_val"></span></div>
					</div>
				</div>
			<?php endfor; ?>
			<span class="drp_img_wrapper" id="wrapper_dt2"><img id="pic_dt2" /></span>
		</div>
		<div id="dt3" class="dt" style="display: none">
			<div id="dt3_select">
				<?php for($i=1;$i<=8;$i++): ?>
				<div class="ddd">
					<?=form_input(array('name'=>'ind_comment'.$i, 'id'=>'ind_comment'.$i, 'placeholder'=>'Комментарий по ткани'.$i, 'value'=>isset($po['ind_comment'.$i]) ? $po['ind_comment'.$i] : NULL))?>
				</div>
				<?php $tm_dd['val0'] = "(Ткань $i: Выбрать торг. марку)"; ?>
				<div class="d2">
					<div class="ddd" id="ind_tm<?=$i?>">
						<?=form_dropdown('ind_tmDD'.$i, $tm_dd, 'val0', 'id="ind_tmDD'.$i.'" onChange="dddChange(this);"')?>
					</div>
					<div class="ddd" id="ind_coll<?=$i?>" style="display:none">
						<?=form_dropdown('ind_collDD'.$i, array('val0'=>"(Ткань $i: Выбрать коллекцию)"), 'val0', 'id="ind_collDD'.$i.'" onChange="dddChange(this);"')?>
					</div>
					<div class="ddd" id="ind_drapery<?=$i?>" style="display:none">
						<?=form_dropdown('ind_draperyDD'.$i, array('val0'=>"(Ткань $i: Выбрать)"), 'val0', 'id="ind_draperyDD'.$i.'" class="draperyDD" onChange="setCategory(this);"')?>
						<div class="drp_category" id="ind_draperyDD<?=$i?>_category" style="display:none">Категория:<span class="drp_ctgr_val" id="ind_draperyDD<?=$i?>_ctgr_val"></span></div>
					</div>
				</div>
			<?php endfor; ?>
			</div>
			<div class="drp_img_wrapper" id="wrapper_dt3"><img id="pic_dt3" /></div>
		</div>
	</div>
</div>
<div id="modules" style="display: none">
	<div class="useredit_title">Модули</div>
	<div id="av_modules_list"></div>
	<div id="chosen_modules" style="display: none">
		<div id="open_chosen_modules" onClick="toggleChsnModules();">Показать компановку</div>
		<div id="cm_graphics" style="display: none"></div>
		<div id="clear_modules_list" onClick="clearCM();" style="display: none">Удалить выбранные модули</div>
	</div>
	<?=form_input(array('type'=>'hidden', 'id'=>'cm_list_hidden', 'name'=>'cm_list_hidden'))?>
</div>
<div class="useredit_title" id="decor_title" style="display: none">Декор</div>
<div id="decor" style="display: none">
	<p>
		<div class="lbl">Цвет декора: </div>
		<?=form_dropdown('decorDD', array('val0'=>'Выбрать'), 'val0', 'id="decorDD"')?>
	</p>
</div>
<div id="nails" style="display: none">
	<p>
		<div class="lbl">Гвозди: </div>
		<?=form_dropdown('nailsDD', array('val0'=>'Выбрать'), 'val0', 'id="nailsDD"')?>
	</p>
</div>
<div id="stitching" style="display: none">
	<p>
		<div class="lbl">Отстрочка: </div>
		<?php $slctd = isset($po['stitching']) ? array_search($po['stitching'], [NULL, 'В тон', 'Контрастная светлая', 'Контрастная тёмная']) : 0; ?>
		<?=form_dropdown('stitchingDD', array('val0'=>'Выбрать', 'val1'=>'В тон', 'val2'=>'Контрастная светлая', 'val3'=>'Контрастная тёмная'), 'val'.$slctd, 'id="stitchingDD"')?>
	</p>
</div>
<div id="golf_options" style="display: none">
	<p>
		<div class="lbl">Подлокотник: </div>
		<?php $slctd = isset($po['armrest']) ? array_search($po['armrest'], [NULL, 'Прямой', 'Скошенный']) : 0; ?>
		<?=form_dropdown('armrestDD', array('val0'=>'Выбрать', 'val1'=>'Прямой', 'val2'=>'Скошенный'), 'val'.$slctd, 'id="armrestDD"')?>
	</p>
	<p>
		<div class="lbl">Кол-во подушек: </div>
		<?php $slctd = isset($po['pillows']) && $po['pillows'] ? $po['pillows'] : 0; ?>
		<?=form_dropdown('pillowsDD', array('val0'=>'Выбрать', 'val2'=>'2', 'val5'=>'5'), 'val'.$slctd, 'id="pillowsDD"')?>
	</p>
</div>
<div id="order_descr">
	<div class="useredit_title">Комментарий</div>	
	<?=form_textarea(array('name'=>'nstrd_descr', 'id'=>'nstrd_descr', 'value'=>isset($po['nstrd_descr']) ? $po['nstrd_descr'] : NULL, 'placeholder'=>'Комментарий к заказу'))?>
</div>
<div id="btn_line" style="text-align: left; margin-left: 20px">
	<div class="usr_btn">
		<?=form_button(array('name'=>'apply', 'type'=>'submit', 'content'=>'Сохранить'))?>
	</div>
	<div class="usr_btn">
		<?=form_button('undo', 'Отмена', 'onClick="location.href=\''.(isset($po_id) ? "/preorders/$po_id" : '/store').'\';"')?>
	</div>
</div>
<?=form_close()?>
