<script>
	var justLoaded = <?=isset($po_id) ? 'true' : 'false' ?>;
	var poId<?=isset($po_id) ? " = $po_id" : '' ?>;
	var savedDrp<?=isset($saved_drp) ? " = JSON.parse('".$saved_drp."')" : '' ?>;
	var poEntry<?=isset($poe_id) ? " = $poe_id" : '' ?>; 
	var hideFinPart = <?=$hide_fin_part ? 'true' : 'false' ?>;
	var drps, price;
	
	function ajaxPO(mthd, argm) 
	{
		var xhr = new XMLHttpRequest();
		xhr.open('POST', "<?=site_url('poedrphandler')?>", true);
		let qParams = 'method=' + mthd;
		if(mthd == 'get_drp_coll') qParams += '&tm_name=' + argm;
		else if(mthd == 'get_drapery') qParams += '&tm_name=' + argm.tmName + '&coll_name=' + argm.collName;
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange = function() {
			if(this.readyState != 4) return;
			if(this.status != 200){
				showMsg({type: 'error', text: 'Ошибка: ' + (this.status ? this.statusText : 'запрос не удался')});
				return;
			}
			let jr = JSON.parse(this.responseText);
			if(!jr){
				showMsg({type: 'error', text: 'Ошибка: некорректный ответ сервера'});
				return;
			}
			if(jr.code != 0){
				showMsg({type: 'error', text: 'Ошибка: ' + jr.message});
				return;
			}
			PORespHandler(jr);
		}
		xhr.send(qParams);
	}
	
	function PORespHandler(rpObj){
		let clctDD = document.getElementById('collDD');
		let drpDD = document.getElementById('draperyDD');
		let fDD = [];
		if(rpObj.method == 'get_drp_coll'){
			for(var i=0;i<rpObj.coll.length;i++){
				fDD.push({id: i+1, name: rpObj.coll[i]});
			}
			fillOptions(clctDD, fDD);
			if(savedDrp){
				for(i=0;i<clctDD.options.length;i++){
					if(savedDrp.collection == clctDD.options[i].text){
						clctDD.selectedIndex = i;
						chngColl(clctDD);
						break;
					}
				}
			}
			document.getElementById('coll').style.display = 'block';
		}
		else if(rpObj.method == 'get_drapery'){
			drps = rpObj.drp;
			for(var i=0;i<drps.length;i++){
				fDD.push({id: drps[i].id, name: drps[i].name});
			}
			fillOptions(drpDD, fDD);
			if(savedDrp){
				for(i=0;i<drpDD.options.length;i++){
					if('val' + savedDrp.id == drpDD.options[i].value){
						drpDD.selectedIndex = i;
						chngDrp(drpDD); //МБ переписать из savedDrp
						break;
					}
				}
				savedDrp = {};
			}
			document.getElementById('drapery').style.display = 'block';
		}
	}
	
	function chngTM(cDD){
		fillOptions(document.getElementById('draperyDD'), []);
		document.getElementById('drapery').style.display = 'none';
		fillOptions(document.getElementById('collDD'), []);
		document.getElementById('coll').style.display = 'none';
		resetPOEOptions();
		if(cDD.selectedIndex > 0) ajaxPO('get_drp_coll', cDD.options[cDD.selectedIndex].text);
	}
	
	function chngColl(cDD){
		fillOptions(document.getElementById('draperyDD'), []);
		document.getElementById('drapery').style.display = 'none';
		resetPOEOptions();
		let tmdd = document.getElementById('tmDD');
		if(cDD.selectedIndex > 0) ajaxPO('get_drapery', {tmName: tmdd.options[tmdd.selectedIndex].text, collName: cDD.options[cDD.selectedIndex].text});
	}
	
	function chngDrp(cDD)
	{
		if(cDD.selectedIndex == 0) resetPOEOptions();
		else{
			for(var i=0;i<drps.length;i++){
				if('val' + drps[i].id  == cDD.options[cDD.selectedIndex].value){
					price = drps[i].price;
					document.getElementById('poe_drp_ctgr_val').innerHTML = drps[i].ctgr_name;
					setPOEOptions();
					break;
				}
			}
		}
	}
	
	function setPOEOptions()
	{
		let qnt = document.getElementById('quantity').value.replace(',', '.');
		if(qnt.indexOf('.') != -1) document.getElementById('quantity').value = qnt.replace('.', ',');
		let st = document.getElementById('summ_total');
		st.value = qnt ? roundNumber(+(qnt)*price, 2) : 0;
		if(!hideFinPart){
			document.getElementById('poe_drp_price_val').innerHTML = price > 0 ? `${outSumm(roundNumber(price/100, 2))} руб./м.` : 'не задана';
			document.getElementById('poe_drp_summ_val').innerHTML = st.value && +(st.value) > 0 ? `${outSumm(roundNumber(st.value/100, 2))} руб.` : 'нет';
		}
	}
	
	function resetPOEOptions()
	{
		price = 0;
		drps = [];
		document.getElementById('summ_total').value = '';
		document.getElementById('poe_drp_ctgr_val').innerHTML = 'не задана';
		if(!hideFinPart){
			document.getElementById('poe_drp_price_val').innerHTML = 'не задана';
			document.getElementById('poe_drp_summ_val').innerHTML = 'нет';
		}
	}
	
	function loadSavedDrp()
	{
		console.log(JSON.stringify(savedDrp));
		let tmdd = document.getElementById('tmDD');
		for(var i=0;i<tmdd.options.length;i++){
			if(savedDrp['trade_mark'] == tmdd.options[i].text){
				tmdd.selectedIndex = i;
				chngTM(tmdd);
			}
		}
	}
	
	function validateEntry()
	{
		let outMsg = '';
		if(document.getElementById('draperyDD').selectedIndex == 0) outMsg = addToErrMsg(outMsg, 'Не выбрана ткань!');
		if(!document.getElementById('quantity').value) outMsg = addToErrMsg(outMsg, 'Не задано количество ткани!');
		if(outMsg.length > 0){
			let oMsgObj = {type: 'error', text: outMsg};
			return oMsgObj;
		}
		else return true;
	}
	
	function saveChanges()
	{
		let msgVldt = validateEntry();
		if(typeof(msgVldt) === 'boolean') return true;
		showMsg(msgVldt);
		return false;
	}
	
	function  extAPL()
	{
		let qMask = IMask(document.getElementById('quantity'), {mask: /^\d+[.,]?\d{0,2}$/});
		if(savedDrp) loadSavedDrp();
	}
	
</script>