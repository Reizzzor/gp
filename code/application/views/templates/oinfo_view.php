<?=$basic_info?>
<?=form_open($user_target.'/send_bill/'.$order_id, 'method="post" id="send_bill_form"')?>
<?php if(isset($emails)): ?>
	<span>Адрес для отправки счёта: </span>
	<?=form_dropdown('emailsDD', $emails, 'val0', 'id="emailsDD"')?>
<?php endif; ?>
<?php $gbb_params = array('name'=>'get_bill_btn', 'type'=>'submit', 'id'=>'get_bill_btn', 'content'=>'Получить счёт'); ?>
<?php if($bill_disabled) $gbb_params['disabled'] = TRUE; ?>
<?php $undo_url = isset($emails) ? '\'/manager\'' : '\'/orders\'' ?>
<div id="btn_line">
	<div class="usr_btn"><?=form_button($gbb_params)?></div>
	<div class="usr_btn"><?=form_button('undo', 'Назад', 'onClick="location.href='.$undo_url.';"')?></div>
</div>
<?=form_close('</div>')?>
