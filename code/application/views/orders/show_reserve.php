<?=$basic_info?>
<?php if($dlv_info): ?>
	<?php if(isset($is_example) && $is_example): ?><div class="u_info"><h3>Подиумный образец</h3></div><?php endif; ?>
	<?php if($customer || isset($phone1) || isset($phone2) || isset($email)): ?>
		<div class="u_info">
			<h2>Информация о покупателе</h2>
			<?php if($customer): ?><p><b>Покупатель:</b> <?=$customer?></p><?php endif; ?>
			<?php if(isset($phone1)): ?><p><b>Телефон1:</b> <?=$phone1?></p><?php endif; ?>
			<?php if(isset($phone2)): ?><p><b>Телефон2:</b> <?=$phone2?></p><?php endif; ?>
			<?php if(isset($email)): ?><p><b>Эл. почта:</b> <?=$email?></p><?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="u_info">
		<h2>Информация о доставке</h2>
		<?php $dt_arr=array('client'=>'клиенту', 'salon'=>'в салон', 'pickup'=>'самовывоз'); ?>
		<p><b>Тип доставки:</b> <?=$dt_arr[$dlvr_type]?></p>
		<?php if($dlvr_type == 'client'): ?>
			<p>
				<b>Адрес доставки:</b>
				<?php if(isset($dlvr_city)): ?> Город: <i><?=$dlvr_city?></i><?php endif; ?>
				<?php if(isset($dlvr_street)): ?> Улица: <i><?=$dlvr_street?></i><?php endif; ?>
				<?php if(isset($dlvr_house)): ?> Дом: <i><?=$dlvr_house?></i><?php endif; ?>
				<?php if(isset($dlvr_flat)): ?> Кв: <i><?=$dlvr_flat?></i><?php endif; ?>
				<?php if(isset($dlvr_porch)): ?> Подъезд: <i><?=$dlvr_porch?></i><?php endif; ?>
				<?php if(isset($dlvr_stage)): ?> Этаж: <i><?=$dlvr_stage?></i><?php endif; ?>
				<?php if(isset($dlvr_dmphn)): ?> Домофон: <i><?=$dlvr_dmphn?></i><?php endif; ?>
			</p>
			<p><b>Грузовой лифт:</b> <?=isset($has_lift) ? 'Да' : 'Нет'?></p>
		<?php elseif($dlvr_type == 'salon' && isset($sln_address)): ?>
			<p><b>Адрес доставки:</b> <?=$sln_address?></p>
		<?php endif; ?>
		<?php if(isset($dlvr_comment)): ?><p><b>Комментарий по доставке:</b> <?=$dlvr_comment?></p><?php endif; ?>
	</div>
	<?php if(!isset($rsrv_id) && isset($dscnt_retail) && $dscnt_retail): ?>
		<div class="u_info">
			<h2>Информация о доп. скидке</h2>
			<p><b>Розничная скидка:</b> <?=$dscnt_retail?>%</p>
		</div>
	<?php endif; ?>
<?php endif; ?>
<?php if(isset($rsrv_id)): ?>
	<?=form_open('store/submit/'.$rsrv_id, 'method="post" id="rsrv_confirm_form"')?>
	<?php if($dscnt_whole == 0) $dscnt_whole = $dscnt_retail/2; ?>
	<div class="u_info">
		<h2>Дополнительные скидки</h2>
		<div class="inputs_line">
			<div class="lbl">Розничная: </div>
			<?=form_input(array('name'=>'dscnt_retail', 'id'=>'dscnt_retail', 'value'=>$dscnt_retail))?>
			<div class="lbl">% Оптовая: </div>
			<?=form_input(array('name'=>'dscnt_whole', 'id'=>'dscnt_whole', 'value'=>$dscnt_whole))?>
			<div class="lbl">%</div>
		</div>
	</div>
<?php endif; ?>
<div id="btn_line">
	<?php if(isset($rsrv_id)): ?>
		<?php $params = array('name'=>'save_cr', 'type'=>'submit', 'id'=>'save_cr', 'content'=>'Сохранить'); ?>
		<div class="usr_btn"><?=form_button($params)?></div>
	<?php endif; ?>
	<div class="usr_btn"><?=form_button('undo', 'Назад', 'onClick="history.back(); return false;"')?></div>
	<?php if(isset($rsrv_id)): ?><?=form_close()?><?php endif; ?>
</div>