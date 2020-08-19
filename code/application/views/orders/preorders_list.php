<div id="btn_line">
	<div class="usr_btn"></div>
	<div class="usr_btn">
		<?php echo form_button('new_preorder', 'Создать новый заказ', 'onClick="location.href=\'/preorders/new\';"');?>
	</div>
</div>
<?=heading(isset($po_list) ? 'Список заказов' : 'Сохранённые заказы отсутствуют', 2)?>
<div id='pre_orders_list'><?=isset($po_list) ? $po_list : '' ?></div>
