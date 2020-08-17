<div id="dl_arch_title">Архив разбивок</div>
<div id="dl_arch_list">
	<?php foreach($arch_list as $i): ?><?=anchor("drp_layout/archive/{$i['name']}", "Версия разбивки от {$i['stamp']}")?><?php endforeach; ?>
	<?=anchor(site_url($arch_prefix.'/drapery'), 'Назад')?>
</div>