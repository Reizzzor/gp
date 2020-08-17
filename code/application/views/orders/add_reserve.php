<div id="order_consist">
	<?=$basic_info?>
	<?=form_open_multipart('reserve/submit/'.$order_id, 'id="hdn_dlvr_form" onSubmit="return dlvrConfirm();"')?>
	<div id="dlvr_content">
		<?=$dlvr?>
	</div>
	<?php
		$gbb_params = array('name'=>'get_bill_btn', 'id'=>'get_bill_btn', 'content'=>'Получить счёт', 'onClick'=>'location.href=\'/orders/send_bill/'.$order_id.'\';');
		if($bill_disabled) $gbb_params['disabled'] = TRUE;
		$blank_params = array('name'=>'print_blank', 'id'=>'print_blank', 'content'=>'Печать бланка', 'onClick'=>'ajaxRsrv(\'pre_save\');');
		if($blank_disabled) $blank_params['disabled'] = TRUE;
	?>
	<div id="btn_line">
		<div class="usr_btn"><?=form_button($blank_params)?></div>
		<div class="usr_btn"><?=form_button(array('name'=>'sbmt_rsrv', 'id'=>'sbmt_rsrv', 'type'=>'submit', 'content'=>'Оформить продажу'))?></div>
		<!--<?php if(!$strg): ?>
			<div class="usr_btn"><?=form_button($gbb_params)?></div>
		<?php endif; ?>-->
		<div class="usr_btn"><?=form_button('undo', 'Назад', 'onClick="history.back(); return false;"')?></div>
	</div>
	<a id="print_link" href="<?=site_url('orders/print_blank/'.$order_id)?>" target="_blank"></a>
	<?=form_close('</div>')?>
</div>