<div class="useredit_title">Заказчик</div>
<div class="inputs_line">
	<?php foreach([['Фамилия', 'surname'], ['Имя', 'name1'], ['Отчество', 'name2']] as $i):?>
		<div>
			<div class="lbl"><?=$i[0]?>: </div>
			<?php $params = array('name'=>'cstmr_'.$i[1], 'id'=>'cstmr_'.$i[1]);
			      if(isset(${$params['name']})) $params['value'] = ${$params['name']}; ?>
			<?=form_input($params)?>
		</div>
	<?php endforeach;?>
</div>
<div class="useredit_title">Контакты</div>
<div class="inputs_line">
	<?php foreach([['Телефон', 'phone1'], ['Телефон2', 'phone2'], ['Эл. почта', 'email']] as $i):?>
		<div>
			<div class="lbl"><?=$i[0]?>: </div>
			<?php $params = array('name'=>$i[1], 'id'=>$i[1]);
			      if($i[1] != 'email'){
				$params['class'] = 'phone_input';
				if(isset(${$params['name']}) && ${$params['name']}) $params['value'] = substr(${$params['name']}, 1);
			      }
			      elseif(isset(${$params['name']}) && ${$params['name']}) $params['value'] = ${$params['name']}; ?>
			<?=form_input($params)?>
		</div>
	<?php endforeach;?>
</div>
<div class="useredit_title">Доставка</div>
<div id="delivery">
	<?php foreach(array('salon'=>'В салон'.($sln_address ? " ($sln_address)" : ''), 'client'=>'Клиенту', 'pickup'=>'Самовывоз') as $k=>$vl):?>
		<span>
			<?=form_radio(array('name'=>'dlvr_type', 'id'=>'dlvr_'.$k, 'value'=>$k, 'checked'=> (!isset($dlvr_type) && $k == 'salon') || (isset($dlvr_type) && $dlvr_type == $k) ? TRUE : FALSE, 'onClick'=>'dlvrHandle(this);'))?>
			<?=form_label($vl, 'dlvr_'.$k)?>
		</span>
	<?php endforeach;?>
</div>
<div id="dlvr_address" style="display: none">
	<div class="useredit_title">Адрес</div>
	<?php foreach([['Город', 'city'], ['Улица', 'street'], ['Дом', 'house', ['Стр/корп', 'corp']], ['Кв/оф', 'flat', ['Подъезд', 'porch']], ['Домофон', 'dmphn', ['Этаж', 'stage']]] as $i=>$vl):?>
		<?=in_array($i, [0, 3]) ? '<div class="inputs_line">' : '' ?>
		<div>
			<?php if(count($vl) > 2): ?>
				<div class="dlvr_short">
					<div class="lbl"><?=$vl[0]?>: </div>
					<?php $params = array('name'=>'dlvr_'.$vl[1], 'id'=>'dlvr_'.$vl[1], 'class'=>'client_dlvr');
						if(isset(${$params['name']}) && ${$params['name']} && ${$params['name']} != '0') $params['value'] = ${$params['name']}; ?>
					<?=form_input($params)?>
				</div>
				<div class="dlvr_short">
					<div class="lbl"><?=$vl[2][0]?>: </div>
					<?php   $params = array('name'=>'dlvr_'.$vl[2][1], 'id'=>'dlvr_'.$vl[2][1], 'class'=>'client_dlvr');
						if(isset(${$params['name']}) && ${$params['name']} && ${$params['name']} != '0') $params['value'] = ${$params['name']}; ?>
					<?=form_input($params)?>
				</div>
			<?php else: ?>
				<div class="lbl"><?=$vl[0]?>: </div>
				<?php $params = array('name'=>'dlvr_'.$vl[1], 'id'=>'dlvr_'.$vl[1], 'class'=>'client_dlvr');
				if(isset(${$params['name']}) && ${$params['name']} && ${$params['name']} != '0') $params['value'] = ${$params['name']}; ?>
				<?=form_input($params)?>			
			<?php endif; ?>
		</div>
		<?=($i == 2) ? '</div>' : ''?>
	<?php endforeach;?>
	<div>
		<div class="lbl" id="has_lift_lbl">Грузовой лифт: </div>
		<?php $chckd = isset($has_lift) ? $has_lift : '0'; ?>
		<?=form_checkbox(array('name' => 'has_lift', 'id' => 'has_lift', 'class'=>'client_dlvr', 'checked' => (int)$chckd, 'value' => $chckd, 'onChange' => 'cbSetValue(\'has_lift\')'))?>
	</div></div>
	<div class="useredit_title">Стоимость доставки(предварительно): </div>
	<div class="input_chkbox_line">
		<?php foreach(['summ_dlvr'=>'Доставка', 'summ_up'=>'Подъём'] as $i=>$vl):?>
			<div class="lbl"  id="<?=$i?>_lbl"><?=$vl?>: </div>
			<?=form_input(array('name'=>$i, 'id'=>$i, 'class'=>'client_dlvr', 'value'=>isset($$i) && $$i != '0' ? ((int)$$i)/100 : NULL, 'onChange'=>'countFinalPrice();'))?>
		<?php endforeach;?>
		<div class="lbl"  id="count_dlvr_lbl">Включить в счёт: </div>
		<?php $chckd = isset($count_dlvr) ? $count_dlvr : '0'; ?>
		<?=form_checkbox(array('name'=>'count_dlvr', 'id'=>'count_dlvr', 'class'=>'client_dlvr', 'checked' => (int)$chckd, 'value' => $chckd, 'onChange' => 'cbSetValue(\'count_dlvr\'); countFinalPrice();'))?>
	</div>
</div>
<?=form_textarea(array('name'=>'dlvr_comment', 'id'=>'dlvr_comment', 'class'=>'client_dlvr', 'value'=>isset($dlvr_comment) ? $dlvr_comment : '', 'placeholder'=>'Комментарий по доставке'))?>
<?php if(isset($is_rsrv) && $is_rsrv): ?>
	<div class="inputs_line">
		<div class="lbl">Бланк заказа: </div>
		<input type="file" name="blank_upld" id="blank_upld" size="30"/>
		<div class="lbl">Доставочный лист: </div>
		<input type="file" name="dlvr_upld" id="dlvr_upld" size="30"/>
	</div>
<?php endif; ?>
<?php if(isset($storage) && $storage): ?>
	<div id="rsrv_example">
		<div class="lbl">Образец: </div>
		<?=form_checkbox(array('name'=>'rsrv_is_example', 'id'=>'rsrv_is_example', 'class'=>'client_dlvr', 'checked' => 0, 'value' => FALSE, 'onChange' => 'cbSetValue(\'rsrv_is_example\');'))?>
	</div>
<?php endif; ?>
<?php if(isset($is_podium) && $is_podium): ?>
	<?php
		$params = array();
		for($i=0;$i<=100;$i++){
			$params['val'.$i] = $i.'%';
		}
	?>
	<div id="rsrv_extra">
		<div class="lbl">Доп. скидка: </div>
		<?=form_dropdown('dscnt_retail', $params, 'val0')?>
	</div>
<?php endif; ?>