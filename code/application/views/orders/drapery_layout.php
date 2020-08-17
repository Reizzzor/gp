<div id="drp_tm_headers">
	<h1>Разбивка тканей</h1>
	<h3>
		<?=anchor('drp_layout/'.scandir('drp_layout')[3], 'Скачать действующую разбивкку')?>
		<?=count(scandir('drp_layout/archive')) > 2 ? anchor(site_url($arch_prefix.'/drapery/archive'), 'Архив разбивок') : ''?>
	<h3>
	<h2>Торговая марка</h2>
</div>
<div id="drp_tm_list">
	<?php foreach($tm_list as $i=>$vl): ?>
		<div class="drp_tm_title"><?=anchor('', $vl['trade_mark'], 'onClick="toggleDrpTm('.$i.'); return false;"')?></div>
		<table id="drp_colls<?=$i?>" class="drp_colls_table" style="display: none">
			<thead><tr>
				<th>Коллекция</th><th>Категория</th><th>Исключения</th>
			</tr></thead>
			<tbody>
				<?php foreach($vl['collections'] as $j): ?>
					<tr><td><?=$j['collection']?></td><td><?=$j['category']?></td><td><?=$j['exceptions']?></td></tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	<?php endforeach; ?>
</div>
