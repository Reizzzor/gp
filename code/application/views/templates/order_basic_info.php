<div class="u_info"><h2>Информация о заказе</h2></div>
<?=$oi_table?>
<?php if(isset($d_table)): ?>
	<div class="u_info"><h2>Ткани</h2></div>
	<?php if(isset($d_type)): ?><div id="drp_type_info"><b>Тип кроя:</b> <?=$d_type?></div><?php endif; ?>
	<?=$d_table?>
<?php endif; ?>
<?php if(isset($price_tbl) && $price_tbl): ?>
	<div class="u_info"><h2>Стоимость заказа</h2></div>
	<?=$price_tbl?>
<?php endif; ?>
<?php if((isset($is_podium) && $is_podium) || (isset($not_standart) && $not_standart)  || (isset($comment) && $comment)): ?>
	<div class="u_info">
		<h2>Дополнительная информация</h2>
		<?php if(isset($is_podium) && $is_podium): ?><p><b>Подиумный образец</b></p><?php endif; ?>
		<?php if(isset($not_standart) && $not_standart): ?><p><b>Нестандартный образец</b></p><?php endif; ?>
		<?php if(isset($comment) && $comment): ?><p><b>Комментарий к заказу:</b> <?=$comment?></p><?php endif; ?>
	</div>
<?php endif; ?>

<?php if(!isset($i_mode) || !$i_mode): ?>
	<?php if(isset($customer) || isset($phone1) || isset($phone2)): ?>
		<div class="u_info">
			<h2>Информация о покупателе</h2>
			<?php if(isset($customer)): ?><p><b>Покупатель:</b> <?=$customer?></p><?php endif; ?>
			<?php if(isset($phone1)): ?><p><b>Телефон1:</b> <?=$phone1?></p><?php endif; ?>
			<?php if(isset($phone2)): ?><p><b>Телефон2:</b> <?=$phone2?></p><?php endif; ?>
		</div>
	<?php endif; ?>
	<?php if(isset($address) || isset($out_stamp)): ?>
		<div class="u_info">
			<h2>Информация о доставке</h2>
			<?php if(isset($address)): ?><p><b>Адрес доставки:</b> <?=$address?></p><?php endif; ?>
			<?php if(isset($out_stamp) && $out_stamp): ?><p><b>Дата доставки:</b> <?=$out_stamp?></p><?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="u_info">
		<h2>Информация об оплате</h2>
		<p><b>Наличие оплаты:</b> <?=$is_paid?></p>
	</div>
<?php endif; ?>