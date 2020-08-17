<html>
	<head>
		<?=link_tag(array('href'=>'css/print.css', 'rel'=>'stylesheet', 'type'=>'text/css', 'media'=>'print'))?>
		<script src="<?=site_url('js/main.js')?>"></script>
		<script src="<?=site_url('js/GraphicsJS.js')?>"></script>
		<script src="<?=site_url('js/rp_modules.js')?>"></script>
	</head>
	<body>
		<div id="header">
			<div id="gp_logo"><img src="http://geniuspark.ru/images/Logo_gp2_5v9z-r3.jpg" /></div>
			<div id="gp_contacts">
				<div id="gp_url">www.geniuspark.ru</div>
				<div id="gp_email">info@geniuspark.ru</div>
				<div id="gp_phone">+7(495)249-29-23</div>
				<div id="sln_name"><?=$salon?></div>
				<div id="sln_address"<?=(isset($sln_mailto) || isset($sln_phone)) ? '' : ' style="display:none"'?>><?=$sln_address?></div>
				<div id="sln_email"><?=isset($sln_mailto) ? $sln_mailto : ''?></div>
				<div id="sln_phone"><?=isset($sln_phone) ? $sln_phone : ''?></div>
			</div>
		</div>
		<div id="order_no">
			<span class="info_title">Номер заказа на фабрике: </span><span class="info_entry"><?=isset($sln_order_id) ? $sln_order_id : '_____________'?></span>
			<span class="info_title">От: </span><span class="info_entry"><?=$poe_date?></span>
		</div>
		<div id="exp_nst"><span><?=isset($is_example) ? 'Образец' : ''?></span><span><?=isset($is_nonstandard) ? 'Нестандарт' : ''?></span></div>
		<div id="rp_name"><?=$rp_name?></div>
		<div id="ka_info">
			<span class="info_title">Юр.лицо: </span><span class="info_entry"><?=$kagent?></span>
			<span class="info_title">Салон: </span><span class="info_entry"><?=$salon?></span>
		</div>
		<div id="customer"><span class="info_title">Заказчик(ФИО):&nbsp;</span><span class="info_entry"><?=isset($customer) && $customer ? $customer : '_______________________________________________________________' ?></span></div>
		<div id="delivery">
			<span class="info_title">АДРЕС ДОСТАВКИ: </span>
		<?php if($dlvr_type == 'client'): ?>
			город:&nbsp;<span class="info_entry"><?=isset($dlvr_city) ? $dlvr_city : '___________________' ?></span>
			улица:&nbsp;<span class="info_entry"><?=isset($dlvr_street) ? $dlvr_street : '____________________________' ?></span>
			дом:&nbsp;<span class="info_entry"><?=isset($dlvr_house) ? $dlvr_house : '___' ?></span>
			кв/оф:&nbsp;<span class="info_entry"><?=isset($dlvr_flat) ? $dlvr_flat : '___' ?></span>
			подъезд:&nbsp;<span class="info_entry"><?=isset($dlvr_porch) ? $dlvr_porch : '___' ?></span>
			домофон:&nbsp;<span class="info_entry"><?=isset($dlvr_dmphn) ? $dlvr_dmphn : '___' ?></span>
			этаж:&nbsp;<span class="info_entry"><?=isset($dlvr_stage) ? $dlvr_stage : '___' ?></span>
			грузовой&nbsp;лифт:&nbsp;<span class="info_entry"><?=isset($has_lift) ? 'ДА' : 'НЕТ' ?></span>
		<?php elseif($dlvr_type == 'salon'): ?>
			(В САЛОН)<?=$sln_address?>
		<?php else: ?>
			САМОВЫВОЗ
		<?php endif; ?>
		</div>
		<div id="cstmr_contacts">
			<span class="info_title">КОНТАКТЫ: </span>
			телефон:&nbsp;<span class="info_entry"><?=isset($phone1) ? $phone1 : '_____________' ?></span>
			телефон2:&nbsp;<span class="info_entry"><?=isset($phone2) ? $phone2 : '_____________' ?></span>
			<span id="email">e-mail:&nbsp;<span class="info_entry"><?=isset($email) ? $email : '________________' ?></span></span>
		</div>
		<div id="extra_options">
			<?php foreach($extra as $i):?>
				<span class="info_title"><?=mb_strtoupper($i[0])?></span><span class="info_entry"><?=$i[1]?></span>
			<?php endforeach;?>
		</div>
		<div id="content">
			<?php if(!isset($rp_pic_src )) $rp_pic_src = ''; ?>
			<div id="rp_pic"<?=$rp_pic_src ? '' : ' style="display: none"'?>><img src="<?=$rp_pic_src?>" /></div>
			<div>
				<div id="cm_graphics"<?=$cm_list ? '' : ' style="display: none"' ?>></div>
			</div>
		</div>
		<div id="qq_table">
			<div>
				<span <?=isset($category) ? '' : 'style="display: none"'?>> Категория изделия: <?=isset($category) ? $category : ''?></span>
				<span <?=$quantity == 1 ? 'style="display: none"' : ''?>> Количество: <?=$quantity?></span>
			</div>
			<?=$drapery_table?>
		</div>
		<div id="drp_notice" <?=$drapery_table ? '' : 'style="display: none"'?>>Если ткань имеет крупный рапорт/рисунок/полосу - указать это в графе "Ткань/кожа"</div>
		<div class="terms_title">Обращаем ваше внимание: </div>
		<div class="gp_terms">
			Декоративная отстрочка не выполняется на изделиях из рыхлых и ворсовых тканей, тканей с крупным жестким рубчиком, тканей с контрастной основой, тканей с
			крупнофактурной выделкой. Для изделий из натуральной кожи допускается наличие мелких волосяных трещин, назначитепьных складок, отличий в
			интенсивности оттенка цвета на разных участках изделия‚ так как это является естественной особенностью данного материала. Необходимо не реже одного раза
			в 6 месяцев смазывать силиконовой смазкой шарнирные соединения механизма трансформации, а также подтягивать крепежные соединения. Наличие
			устойчивого «запаха нового изделия» допустимо для всех изделий и может сохраняться в течение месяца с момента доставки.
		</div>
		<div class="terms_title">Браком не является: </div>
		<div class="gp_terms">
			Незначительное (не более двух сантиметров на один модуль)отклонение от заявленных габаритных размеров, а также появление скрипа во время эксплуатации
			не является браком. Незначительное отличие цвета и тона готового изделия от цвета образца ткани,образца декоративных элементов с натуральным или
			синтетическим покрытием не является 6раком. 3амятия ворсовых тканей в процессе эксплуатации не является 6раком. Легкие складки на изделии, не влияющие
			на эксплуатационные свойства, не являются браком.
		</div>
		<div id="warranty">Предприятие-изготовитель оставляет зa собой право вносить конструктивные изменения, не влияющие на качество и внешний вид изделия.</div>
		<div id="dlvr_sign"><span class="info_title">С условиями и сроками доставки ознакомлен: </span>_____________________________________ <span>(подпись)</span></div>
		<div id="signs">
			<span class="info_title">Продавец: _________________________________</span>
			<span class="info_title">Заказчик: _________________________________</span>
		</div>
		<div id="under_signs">
			<span>(подпись/расшифровка/дата)</span>
			<span>(подпись/расшифровка/дата)</span>
		</div>
	</body>
	<script>
		
		var poId, savedDrps, cmStage, lbLayer, cmvw, cmSize, poEntry, strId, drpNames;
		var chsnModules = []; //массив выбранных модулей, заполняется объектами вида {id:id, name:name, x:length, y:width}
		var savedCM = [];
		var exmv = [];
		var is_print = true;
		var sclCft = 0.48;
		<?php if(isset($cm_list)) echo "savedCM = JSON.parse('".$cm_list."');\n"; ?>
		if(savedCM.length>0 && !savedCM[0].id) savedCM = [];
		else{
			for(var i=0;i<savedCM.length;i++){
					savedCM[i].x = +(savedCM[i].x);
					savedCM[i].y = +(savedCM[i].y);
				}
			parseSavedCM();
			//sclCft = getScaleCft();
			//console.log('sclCft: ' + sclCft);
			listCM();
			cmStage.scale(sclCft, sclCft, 0, 0);
			/*var rp = document.getElementById('cm_graphics');
			document.getElementById('content').style.height = `${rp.clientHeight*0.8}px`;*/
			
		}
		window.onload = function () {
			window.print();
		}
	</script>
</html>