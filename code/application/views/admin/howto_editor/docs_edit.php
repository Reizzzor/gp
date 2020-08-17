<?=heading("Редактирование загруженных $u_name2", 1)?>
<div class="usr_btn">
	<?=form_button('new_upld', 'Добавить '.$u_name, 'id="new_upld" onClick="newUpld();"')?>
</div>
<div id="list_title">
	<?=heading(isset($upld_list) ? $list_hdr : 'В данном разделе не найдено ни одного '.$u_name1, 2)?>
</div>
<div id="docs_table_wrapper">
	<?=isset($upld_list) ? $upld_list : ''?>
</div>
<div id="upld_dialog">
	<?=form_open_multipart('admin/'.$u_type.'_submit', 'id="hdn_form" onSubmit="return checkUpldDial();"')?>
	<div class="usr_btn" id="del_upld">
		<?=form_button('delUpld', 'Удалить '.$u_name, 'id="delUpld" onClick="upldDelConfirm();"')?>
	</div>
	<div id="InfoUpldDial"></div>
	<?=form_input(array('type'=>'hidden', 'id'=>'upldHidden'))?>
	<?=$u_type == 'video' ? form_input(array('name'=>'fileUpload', 'id'=>'fileUpload', 'placeholder'=>'Введите ссылку на видео', 'size'=>20)) : '<input type="file" name="fileUpload" id="fileUpload" size="20" />'?>
	<?=form_textarea(array('type'=>'text', 'name'=>'upld_file_name', 'id'=>'upld_file_name', 'rows'=>3, 'cols'=>30, 'placeholder'=>'Введите название '.$u_name1.', которое будет показываться пользователю'))?>
	<?=form_dropdown('ctgr_dd', $dd_arr, 'Выбрать', 'id="ctgr_dd" onChange="editDsplDD();"')?>
	<p>Позиция для показа:</p>
	<?=form_dropdown('dspl_dd', array('val1'=>1), 'Выбрать', 'id="dspl_dd"')?>
	<div class="usr_btn">
		<?=form_button(array('name'=>'apply', 'type'=>'submit', 'content'=>'Сохранить изменения'))?>
	</div>
	<div class="usr_btn">
		<?=form_button('undo', 'Отмена', 'onClick="location.href=\'/admin/'.$u_type.'_edit\';"')?>
	</div>
<?=form_close('</div>')?>
<?=isset($upld_list) ? $viewer : ''?>

