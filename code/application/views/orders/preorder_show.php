<div id="order_consist">
	<h1>Информация о заказе<?=$sln_order_id ? " № $sln_order_id" : ''?></h1>
	<?php if(isset($seller)): ?><div id="seller_name"><b>Продавец:</b> <i><?=$seller?></i></div><?php endif; ?>
	<?php foreach($rows as $i): ?><?=$i?><?php endforeach; ?>
	<?php if($cstmr_surname): ?>
		<div class="u_info">
			<h2>Заказчик</h2>
			<?php
				$customer = '';
				foreach(['surname', 'name1', 'name2'] as $i){
					if(${'cstmr_'.$i}) $customer .= ${'cstmr_'.$i} . ' ';
				}
				if($customer) $customer = substr($customer, 0, -1);
			?>
			<?php if($customer): ?><p><b>ФИО покупателя:</b> <?=$customer?></p><?php endif; ?>
			<?php if($phone1): ?><p><b>Телефон1:</b> <?=$phone1?></p><?php endif; ?>
			<?php if($phone2): ?><p><b>Телефон2:</b> <?=$phone2?></p><?php endif; ?>
			<?php if($email): ?><p><b>E-mail:</b> <?=$email?></p><?php endif; ?>
		</div>
	<?php endif; ?>
	<div class="u_info">
		<h2>Доставка</h2>
		<?php $dt_arr=array('client'=>'клиенту', 'salon'=>'в салон', 'pickup'=>'самовывоз'); 
		if($dlvr_house &&  $dlvr_corp) $dlvr_house = $dlvr_house.(ctype_digit($dlvr_corp[0]) ? 'стр' : '').$dlvr_corp; ?>
		<p><b>Тип доставки:</b> <?=$dt_arr[$dlvr_type]?></p>
		<?php if($dlvr_type == 'client'): ?>
			<p>
				<b>Адрес доставки:</b>
				<?php if(isset($dlvr_city)): ?> Город: <i><?=$dlvr_city?></i><?php endif; ?>
				<?php if(isset($dlvr_street) && $dlvr_street): ?> Улица: <i><?=$dlvr_street?></i><?php endif; ?>
				<?php if(isset($dlvr_house)): ?> Дом: <i><?=$dlvr_house?></i><?php endif; ?>
				<?php if(isset($dlvr_flat) && $dlvr_flat): ?> Кв: <i><?=$dlvr_flat?></i><?php endif; ?>
				<?php if(isset($dlvr_porch) && $dlvr_porch): ?> Подъезд: <i><?=$dlvr_porch?></i><?php endif; ?>
				<?php if(isset($dlvr_stage) && $dlvr_stage): ?> Этаж: <i><?=$dlvr_stage?></i><?php endif; ?>
				<?php if(isset($dlvr_dmphn) && $dlvr_dmphn): ?> Домофон: <i><?=$dlvr_dmphn?></i><?php endif; ?>
			</p>
			<p><b>Грузовой лифт:</b> <?=isset($has_lift) ? 'Да' : 'Нет'?></p>
		<?php elseif($dlvr_type == 'salon' && isset($sln_address)): ?>
			<p><b>Адрес доставки:</b> <?=$sln_address?></p>
		<?php endif; ?>
		<?php if(isset($dlvr_comment) && $dlvr_comment): ?><p><b>Комментарий по доставке:</b> <?=$dlvr_comment?></p><?php endif; ?>
	</div>
	<div id="btn_line">
		<div class="usr_btn"><?=form_button('undo', 'Назад', 'onClick="history.back(); return false;"')?></div>
	</div>
</div>