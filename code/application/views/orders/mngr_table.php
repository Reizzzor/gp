<div id="users_top">
	<h2 id="left"><?=$tab_title?></h2>
	<div id="right" class="mngr_filter">
		Фильтр: 
		<?=form_dropdown('filter_type', array('salons'=>'По салонам', 'kagents'=>'По контрагентам'), 'salons', 'id="filter_type" onChange="fillFilterDD();"')?>
		<?=form_dropdown('filter', array('val0'=>'(Все)'), 'val0', 'id="filter" onChange="filterRows(\'orders_table\');"')?>
	</div>
</div>
<?=$ord_tbl?>