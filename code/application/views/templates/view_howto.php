<div id="subsections">
	<?=heading($pv['title'], 1)?>
	<?php if(!empty($pv['subsections'])){
		foreach($pv['subsections'] as $i){
			echo '<p>'.anchor($ss_link.$i['type'].'/'.$i['aggr_id'], $i['title']).'</p>';
		}
	} ?>
</div>
