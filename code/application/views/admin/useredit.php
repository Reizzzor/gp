<?=$is_create ? heading("Создание нового пользователя", 1) : ''?>
<div id='useredit'><?=form_open_multipart($is_create ? "admin/users/submit" : "admin/users/submit/$id", 'method="post" id="editUserForm" onSubmit="return saveChanges();"')?>
<?php if(!$is_create): ?>
	<div id="users_top">
		<?=heading("Редактирование данных пользователя [id=$id]", 1, array('id'=>'left'))?>
		<div class="usr_btn" id="right">
			<?=form_button('deluser', 'Удалить пользователя', 'onClick="location.href=\'/admin/users/delete/'.$id.'\';"')?>
		</div>
	</div>
<?php endif; ?>
<div id="acсsess">
	<div class="useredit_title">Настройки доступа в личный кабинет</div>
	<?php foreach($labels as $k=>$vl): ?>
		<?php if(($k!==1)&&$vl): ?><p><div class="lbl"><?=$vl?></div>
		<?php else: ?><?=$vl?>
		<?php endif; ?>
		<?php if(!$k): ?><?=form_dropdown('user_type', $user_options[$k], $user_type, 'id="utype_select" onChange="uTypeSetVisible();"')?>
		<?php elseif($k===1): ?><?=form_checkbox($user_options[$k])?>
		<?php elseif($k<4): ?><?=form_input($user_options[$k])?>
		<?php else: ?><?=form_password($user_options[$k])?>
		<?php endif; ?>
		<?php if($k&&($k!==4)): ?></p><?php endif; ?>
	<?php endforeach; ?>
</div>
<div class="hidden_div" id="sln">
	<div class="useredit_title">Информация о салоне</div>
	<div id="sln_name_div"><p>
		<div class="lbl">Название салона:</div>
		<?=form_input(array('name'=>'slnInput', 'id'=>'slnInput', 'readonly'=>TRUE, 'value'=> isset($slnInput) ? $slnInput : ''))?>
		<?php foreach(['hide_archive', 'show_preorders', 'hide_fin_part', 'kagent_id', 'sln_id'] as $i): ?>
		<?php if(!isset($$i) || !$$i) $$i = 0; ?>
		<?php endforeach; ?>
		<?=form_dropdown('slnDD', $salons, 'val'.$sln_id, 'id="slnDD" onChange="fillSlnFields(\'slnDD\');"')?>
	</p></div>
	<div id="sln_ka_div"><p>
		<div class="lbl">Контрагент:</div>
		<?=form_input(array('name'=>'ka4slnInput', 'id'=>'ka4slnInput', 'readonly'=>TRUE, 'value'=> isset($ka4slnInput) ? $ka4slnInput : ''))?>
		<?=form_dropdown('ka4slnDD', $kagents, 'val'.$kagent_id, 'id="ka4slnDD" onChange="fillSlnFields(\'ka4slnDD\');"')?>
	</p></div>
	<div id="inn"><p>
		<div class="lbl">ИНН Контрагента:</div><?=form_input(array('name'=>'k4sINN', 'id'=>'k4sINN', 'readonly'=>TRUE, 'value'=> isset($k4sINN) ? $k4sINN : ''))?>
		Скрывать архивные заказы: <?=form_checkbox(array('name'=>'hide_archive', 'id'=>'hide_archive', 'checked' => (int)$hide_archive, 'value' => $hide_archive, 'onChange' => 'cbSetValue(\'hide_archive\')'))?>	
	</p></div>
	<div><p>
		<div class="lbl">Адрес салона: </div><?=form_input(array('name'=>'address', 'id'=>'address', 'placeholder'=>'Введите адрес салона', 'value'=> isset($address) ? $address : ''))?>
		Показывать новые заказы: <?=form_checkbox(array('name'=>'show_preorders', 'id'=>'show_preorders', 'checked' => (int)$show_preorders, 'value' => $show_preorders, 'onChange' => 'cbSetValue(\'show_preorders\')'))?>	
	</p></div>
	<div><p>
		<div class="lbl">Телефон: </div><?=form_input(array('name'=>'phone', 'id'=>'phone', 'placeholder'=>'Введите тел.номер: ', 'value'=> isset($phone) && $phone ? substr($phone, 1) : ''))?>
		Скрывать фин.часть: <?=form_checkbox(array('name'=>'hide_fin_part', 'id'=>'hide_fin_part', 'checked' => (int)$hide_fin_part, 'value' => $hide_fin_part, 'onChange' => 'cbSetValue(\'hide_fin_part\')'))?>
	</p></div>
	<div><p>
		<div class="lbl">E-mail для клиентов: </div><?=form_input(array('name'=>'mailto', 'id'=>'mailto', 'placeholder'=>'Введите эл.почту: ', 'value'=> isset($mailto) ? $mailto : ''))?>
	</p></div>
	<div><p>
		<div class="lbl">Префикс: </div><?=form_input(array('name'=>'prefix', 'id'=>'prefix', 'placeholder'=>'Введите префикс: ', 'value'=> isset($prefix) ? $prefix : ''))?>	
	</p></div>
	<div><p>
		<div class="lbl">Сотрудники:</div>
		<div class="mngr_list">
			<div id="sln_stuff"></div>
			<div class="mngr_add" id="stuff_add">
				<?=form_input(array('name'=>'add_sln_stuff', 'id'=>'add_sln_stuff', 'placeholder'=>'Введите ФИО сотрудника'))?>
				<?=anchor('', 'Добавить', 'onClick="addSlnStuff(); return false;"')?>
			</div>
		</div>
	</p></div>
</div>
<div class="hidden_div" id="ka"><div class="useredit_title">Информация о контрагенте</div></div>
<div class="hidden_div" id="manager">
	<div class="useredit_title">Информация об управляющем</div>
	<div id="mngr_salons">
		<div class="lbl">Салоны:</div>
		<div class="mngr_list">
			<div id="active_salons"></div>
			<div class="mngr_add">Добавить: <?=form_dropdown('mngr_sln_dd', array('val0'=>'Выбрать'), 'val0', 'id="mngr_sln_dd" onChange="mngrAddSalon();"')?></div>
		</div>
	</div>
	<div id="mngr_emails">
		<div class="lbl">Список E-mail:</div>
		<div class="mngr_list"><div id="active_emails"></div>
			<div class="mngr_add">
				<?=form_input(array('name'=>'mngr_add_email', 'id'=>'mngr_add_email', 'placeholder'=>'Введите E-mail'))?>
				<?=anchor('', 'Добавить', 'onClick="mngrAddEmail(); return false;"')?>
			</div>
		</div>
	</div>
 	<?=form_input(array('type'=>'hidden', 'id'=>'mngr_hidden', 'name'=>'mngr_hidden'))?>
</div>
<div id="btn_line">
	<div class="usr_btn"><?=form_button(array('name'=>'apply', 'type'=>'submit', 'content'=>'Сохранить изменения'))?></div>
	<div class="usr_btn"><?=form_button('undo', 'Отмена', 'onClick="location.href=\'/admin/users\';"')?></div>
</div>
<?=form_close('</div>')?>
