<div id="list_title">
	<?=heading(isset($docs_table) ? 'Доступна для скачивания следующая документация' : 'В данном разделе не найдено ни одного документа', 1)?>
</div>
<div id="docs_table_wrapper">
	<?=isset($docs_table) ? $docs_table : ''?>
</div>
<?=isset($docs_table) ? $viewer : ''?>

