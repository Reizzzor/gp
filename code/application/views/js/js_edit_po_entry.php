<script>
	var justLoaded = false;
	var busyDD = []; //ID DD, которые заполняются при помощи ajax-запросов и нуждаются в последующей обработке
	var poId, savedDrps, cmStage, lbLayer, cmvw, cmSize, poEntry, strId, drpNames;
	var mpLink = "<?=$mp_link?>";
	var drpCtgrs = JSON.parse(<?="'".json_encode($drp_ctgrs)."'"?>);
	var drri = <?=isset($drapery_type) ? "'".$drapery_type."'" : "'1'" ?>;
	var is_print = false;
	<?php 
		if(isset($po_id)) echo "poId = $po_id;\n";
		if(isset($storage_id)) echo "justLoaded = true;\nstrId = $storage_id;\n";
		if(isset($poe_id)) echo "justLoaded = true;\npoEntry = $poe_id;\n";
		if(isset($saved_drps)) echo "savedDrps = JSON.parse('".$saved_drps."');\n";
	?>
	var hideFinPart = <?=$hide_fin_part ? 'true' : 'false' ?>;
	var cmWaiters = xDscnt = discount = cmnPrice = 0;
	var drpNum = 3;
	var rpArr = ['decor', 'nails', 'modules'];
	var chsnModules = []; //массив выбранных модулей, заполняется объектами вида {id:id, name:name, x:length, y:width}
	var savedCM = [];
	var exmv = [];
	<?php if(isset($cm_list)) echo "savedCM = JSON.parse('".$cm_list."');\n"; ?>
	if(savedCM.length>0 && !savedCM[0].id) savedCM = [];
	
	function ajaxPO(mthd, fID) //методы: rp_options, po_options
	{
		var xhr = new XMLHttpRequest();
		xhr.open('POST', "<?=site_url('pohandler')?>", true);
		let qParams = 'method=' + mthd;
		switch(mthd){
			case 'rp_options':
				qParams += '&rp_id=' + fID;
				break;
			case 'po_options': 
				qParams += poEntry ? '&po_id=' + fID : '&strg_id=' + fID;
				break;
			case 'get_drp_coll': 
				qParams += '&dd_id=' + fID.dd.id + '&tm_name=' + fID.tmName;
				if(Object.keys(fID).includes('rp_id')) qParams += '&rp_id=' + fID.rp_id;
				break;
			case 'get_drapery': 
				qParams += '&dd_id=' + fID.dd.id + '&tm_name=' + fID.tmName + '&coll_name=' + fID.collName;
				if(Object.keys(fID).includes('rp_id')) qParams += '&rp_id=' + fID.rp_id;
				break;
			case 'get_drp_ctgr':
				qParams += '&drp_id=' + fID.drp_id + '&drp_dd=' + fID.drp_dd;
				break;
			case 'get_cm_price':
				qParams += '&module_id=' + fID.cm_id + '&ctgr=' + getCtgrIndex(fID.ctgr) + '&cm_num=' + fID.cm_num + '&cm_type=' + fID.cm_type; //cm_type: 'cm', 'ecm'
				cmWaiters++;
				break;
			case 'get_rp_price':
				qParams += '&rp_id=' + fID.rp_id + '&ctgr=' + getCtgrIndex(fID.ctgr);
				break;
		}
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange = function() {
			if(this.readyState != 4) return;
			if(this.status != 200){
				console.log(qParams);
				showMsg({type: 'error', text: 'Ошибка: ' + (this.status ? this.statusText : 'запрос не удался')});
				return;
			}
			//console.log(this.responseText);
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
	
	function PORespHandler(rpObj)
	{
		let crdr;
		switch(rpObj.method){
			case 'rp_options':
				console.log(JSON.stringify(rpObj));
				discount = poId ? +rpObj.discount : 0;
				drpNum = +rpObj.drapery_num;
				drpNames = null;
				document.getElementById('discount_val').innerHTML = discount + xDscnt; 
				for(var i in rpArr){   //decor, nails, modules
					var fDD = [];
					let cDiv = document.getElementById(rpArr[i]);
					if(Object.keys(rpObj).includes(rpArr[i])){
						if(rpArr[i] != 'modules'){
							for(var j in rpObj[rpArr[i]]){
								fDD.push({id:+(rpObj[rpArr[i]][j].id), name:rpObj[rpArr[i]][j].name});
							}
							fillOptions(document.getElementById(rpArr[i] + 'DD'), fDD);
						}
						else{
							var mlIH = '';
							for(var j in rpObj['modules']){
								let crmd = rpObj['modules'][j];
								let ock = `onClick="addCM(${crmd.id}, '${crmd.name}', ${crmd['length']}, ${crmd['width']}, ${crmd.own_decor});"`;
								let avmName = `<div>${crmd.full_name ? crmd.full_name : crmd.name}</div>`;
								let avmPic = `<img src="${mpLink}${crmd.ext_id}.png">`;
								let divOpen = `<div class="av_module" id="avm${crmd.id}" ${ock}>`; 
								mlIH += divOpen + avmName + avmPic + '</div>' ;
							}
							document.getElementById('av_modules_list').innerHTML = mlIH;
						}
						if(!(Object.keys(rpObj).includes('modules') && rpArr[i] == 'decor')) cDiv.style.display = 'block';
					}
					else{
						if(['decor', 'nails'].includes(rpArr[i])) fillOptions(document.getElementById(rpArr[i] + 'DD'), []);
						else if(rpArr[i] == 'modules') document.getElementById('av_modules_list').innerHTML = '';
						if(cDiv.style.display == 'block') cDiv.style.display = 'none';
					} 
				}
				if(rpObj.has_corner == '1'){
					document.getElementById('corners').style.display = 'block';
					if(!justLoaded) document.getElementById('cornersDD').selectedIndex = 0;
				} 
				else document.getElementById('corners').style.display = 'none';
				if(rpObj.has_stitching == '1'){
					document.getElementById('stitching').style.display = 'block';
					if(!justLoaded) document.getElementById('stitchingDD').selectedIndex = 0;
				}
				else document.getElementById('stitching').style.display = 'none';
				if(rpObj.is_golf == '1'){
					document.getElementById('golf_options').style.display = 'block';
					if(!justLoaded){
						document.getElementById('armrestDD').selectedIndex = 0;
						document.getElementById('pillowsDD').selectedIndex = 0;
					}
				}
				else document.getElementById('golf_options').style.display = 'none';
				document.getElementById('quantity').readOnly = rpObj.nmcl_group == 'Подушки' ? false : true;
				document.getElementById('pic_dt2').src = Object.keys(rpObj).includes('src_num') ? rpObj.src_num : '';
				document.getElementById('pic_dt3').src = Object.keys(rpObj).includes('src_unnum') ? rpObj.src_unnum : '';
				if(Object.keys(rpObj).includes('price')){
					document.getElementById('drapery').style.display = 'none';
					cmnPrice = roundNumber(+rpObj.price, 2);
					if(!hideFinPart) document.getElementById('common_price_val').innerHTML = cmnPrice + 'руб.';
					countFinalPrice();
				}
				else{
					document.getElementById('drapery').style.display = 'block';
					if(Object.keys(rpObj).includes('drp_names')) drpNames = rpObj['drp_names'];
				}
				checkDecorTitle();
				if(justLoaded) ajaxPO('po_options', poEntry ? poEntry : strId);
				else if(document.getElementById('modules').style.display == 'block') clearCM();
				break;
			case 'po_options':
				var rk = Object.keys(rpObj);
				console.log(JSON.stringify(rk));
				if(rk.includes('nails_id')) setDDValue('nailsDD', rpObj['nails_id']);
				if(justLoaded){
					if(chsnModules.length > 0) checkCM();	
					justLoaded = false;
				}
				if(rk.includes('decor_id') || rk.includes('modules_decor')) document.getElementById('decor').style.display = 'block';
				if(rk.includes('decor_id')) setDDValue('decorDD', rpObj['decor_id']);
				checkDecorTitle();
				break;
			case 'get_drp_coll':
				var fDD = [];
				crdr = getDrpStuff(rpObj.dd_id); 
				for(var i=0;i<rpObj.coll.length;i++){
					fDD.push({id: i+1, name: rpObj.coll[i]});
				}
				fillOptions(crdr.collDD, fDD);
				if(busyDD.includes(crdr.collDD.id)){
					busyDD.splice(busyDD.indexOf(crdr.collDD.id), 1);
					for(var i=0;i<savedDrps.length;i++){
						if(savedDrps[i].idType == crdr.idType){
							for(var j=0;j<crdr.collDD.options.length;j++){
								if(savedDrps[i].collName == crdr.collDD.options[j].text){
									busyDD.push(crdr.drpDD.id);
									crdr.collDD.selectedIndex = j;
									dddChange(crdr.collDD);
									break;
								}
							}
							break;
						}
					}
					
				}
				crdr.collDiv.style.display = 'block';
				break;
			case 'get_drapery':			
				var fDD = [];
				crdr = getDrpStuff(rpObj.dd_id);
				for(var i=0;i<rpObj.drp.length;i++){
					fDD.push({id: rpObj.drp[i].id, name: rpObj.drp[i].name});
				}
				fillOptions(crdr.drpDD, fDD);
				if(busyDD.includes(crdr.drpDD.id)){
					busyDD.splice(busyDD.indexOf(crdr.drpDD.id), 1);
					for(var i=0;i<savedDrps.length;i++){
						if(savedDrps[i].idType == crdr.idType){
							for(var j=0;j<crdr.drpDD.options.length;j++){
								if('val' + savedDrps[i].id == crdr.drpDD.options[j].value){
									crdr.drpDD.selectedIndex = j;
									setCategory(crdr.drpDD);
									break;
								}
							}
							break;	
						}
					}
				}
				crdr.drpDiv.style.display = 'block';
				break;
			case 'get_drp_ctgr':
				document.getElementById(rpObj.drp_dd + '_ctgr_val').innerHTML = rpObj.action.includes(getSelectedValue('r_prodDD').substring(3)) ? '0' : drpCtgrs[rpObj.drp_ctgr];
				document.getElementById(rpObj.drp_dd + '_category').style.display = 'table-cell';
				setCommonCtgr();
				break;
			case 'get_cm_price':
				if(rpObj.cm_type == 'cm'){
					if(rpObj.cm_id == chsnModules[+(rpObj.cm_num)].id){
						chsnModules[+(rpObj.cm_num)].price = rpObj.cm_price;
						cmWaiters--;
					}
				}
				else if(rpObj.cm_type == 'ecm'){
					if(exmv[+(rpObj.cm_num)].id == rpObj.cm_id){
						exmv[+(rpObj.cm_num)].price = rpObj.cm_price;
						cmWaiters--;
					}
				}
				if(cmWaiters == 0){
					cmnPrice = roundNumber(countCM(chsnModules, 'price', 'sum'), 2); 
					if(exmv.length) cmnPrice += roundNumber(countCM(exmv, 'price', 'ecm_sum'), 2);
					if(!hideFinPart){
						cmvw = getCMView();
						cmSize = getCMSize();
						var prcStyle = {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "11px", color: 'red', fontWeight: 'bold'};
						for(i=0; i<chsnModules.length; i++){
							let lbi = cmArrayToView(i);
							let lbc = getLblCoords(lbi.j, lbi.i);
							let outPrice = outSumm(chsnModules[i].price)!== '0' ? outSumm(chsnModules[i].price) : '';
							let txtPrc = lbLayer.text(lbc.x, lbc.y, outPrice, prcStyle);
							lblCentrize(txtPrc, lbc.x, lbc.y, cmSize.lblX, cmSize.lbPrice);
						}
						for(i=0; i<exmv.length; i++){
							let emY = exmv[i].y > cmSize.qSize ? exmv[i].y : cmSize.qSize;
							let emPrcY = cmSize.pExc.y + (cmSize.imgY - cmSize.excY)/2 + getECMBlockHeight(cmSize, exmv.slice(0, i)) + cmSize.excLblY + emY;
							if(i>0) emPrcY += + cmSize.mrgLbl;
							let emPrcX = cmSize.pExc.x + 5 + cmSize.qSize;
							let outPrice = outSumm(exmv[i].price)!== '0' ? outSumm(exmv[i].price*exmv[i].quantity) : '';
							let txtPrc = lbLayer.text(emPrcX, emPrcY, outPrice, prcStyle);
							lblCentrize(txtPrc, emPrcX, emPrcY, cmSize.excX, cmSize.excLblY);
						}
						document.getElementById('common_price_val').innerHTML = cmnPrice > 0 ? outSumm(cmnPrice) + 'руб.' : 'нет';
					}
					countFinalPrice();
				}
				break;
			case 'get_rp_price':
				cmnPrice = roundNumber(+rpObj.price, 2);
				if(!hideFinPart) document.getElementById('common_price_val').innerHTML = cmnPrice > 0 ? outSumm(cmnPrice) + 'руб.' : 'нет';
				countFinalPrice();
				break;
		}
	}
		
	function toggleChsnModules()
	{
		let cmg = document.getElementById('cm_graphics');
		let ocm = document.getElementById('open_chosen_modules');
		if(!cmg.style.display || cmg.style.display == 'none'){
			cmg.style.display = 'block';
			ocm.innerHTML = 'Скрыть компановку';
		}
		else{
			cmg.style.display = 'none';
			ocm.innerHTML = 'Показать компановку';
		}
	}
	
	function setOptions()
	{
		if(!justLoaded){
			drrHandle({value: drri});
			xDscnt = 0;
			document.getElementById('xtra_discount_val').selectedIndex = 0;
			document.getElementById('quantity').value = '1';
		}
		if(document.getElementById('r_prodDD').selectedIndex == 0){
			discount = 0;
			drpNum = 0;
			drpNames = [];
			document.getElementById('discount_val').innerHTML = 0;
			cmnPrice = 0;
			var ddList = ['decor', 'nails', 'stitching', 'corners', 'armrest', 'pillows'];
			for(var i in ddList){
				let iDD = document.getElementById(ddList[i] + 'DD');
				iDD.selectedIndex = 0;
				if(['decor', 'nails'].includes(ddList[i])){
					fillOptions(iDD, []);
					document.getElementById(ddList[i]).style.display = 'none';
				} 
			}
			if(document.getElementById('av_modules_list').innerHTML){
				document.getElementById('av_modules_list').innerHTML = '';
				clearCM();
				document.getElementById('modules').style.display = 'none';
			}
			document.getElementById('golf_options').style.display = 'none';
			document.getElementById('pic_dt2').src = '';
			document.getElementById('pic_dt3').src = '';
			if(!hideFinPart) document.getElementById('common_price_val').innerHTML = 'нет';
			countFinalPrice();
		}
		else ajaxPO('rp_options', +(getSelectedValue('r_prodDD').substring(3)));
		
	}
		
	function drrHandle(drr)
	{
		let dt1 = document.getElementById('dt1');
		let dt2 = document.getElementById('dt2');
		let dt3 = document.getElementById('dt3');
		document.getElementById(`drapery_radio${drr.value}`).checked = true;	
		switch(drr.value){
			case '1':
				dt1.style.display = 'inline';
				dt2.style.display = 'none';
				dt3.style.display = 'none';
				break;
			case '2':
				dt1.style.display = 'none';
				dt2.style.display = 'inline';
				dt3.style.display = 'none';
				var d2Arr = dt2.getElementsByClassName('d2');
				for(var i=0;i<6;i++){
					let d2TMDD = document.getElementById(`tmDD${i+1}`).options[0];
					if(drpNames){
						var currDrpName = drpNames[`drp${i+1}`];
						if(!currDrpName) d2Arr[i].style.display = 'none';
						else{
							d2TMDD.text = currDrpName.length > 1 ? `(Ткань ${i+1}: ${currDrpName})` : `(Ткань ${i+1}: Выбрать торг. марку)`;
							d2Arr[i].style.display = 'table-row';
						}
					}
					else{
						d2TMDD.text = `(Ткань ${i+1}: Выбрать торг. марку)`;
						d2Arr[i].style.display = (i < drpNum) ? 'table-row' : 'none';
					}
				}
				break;
			case '3':
				dt1.style.display = 'none';
				dt2.style.display = 'none';
				dt3.style.display = 'inline';
				break;
		}
		if(!justLoaded) resetDraperyDDs();
		setCommonCtgr();
	}
	
	
	function dddChange(cDD)
	{
		let crdr = getDrpStuff(cDD.id);
		if(cDD.id.indexOf('tm') != -1){
			crdr.collDiv.style.display = 'none';
			crdr.collDD.selectedIndex = 0;
		}
		crdr.drpDiv.style.display = 'none';
		crdr.drpDD.selectedIndex = 0;
		crdr.ctgDiv.style.display = 'none';
		crdr.ctgSpan.innerHTML = '';
		if(cDD.selectedIndex == 0) setCommonCtgr();
		else if(cDD.id.indexOf('tm') != -1) ajaxPO('get_drp_coll', {dd: crdr.collDD, tmName: cDD.options[cDD.selectedIndex].text});
		else if(cDD.id.indexOf('coll') != -1) ajaxPO('get_drapery', {dd: crdr.drpDD, collName: cDD.options[cDD.selectedIndex].text, tmName: crdr.tmDD.options[crdr.tmDD.selectedIndex].text, rp_id: +(getSelectedValue('r_prodDD').substring(3))});
	}
	
	function nstrdChngHandler()
	{
		if(document.getElementById('is_nonstandard').checked){
			document.getElementById('n_discount').style.display = 'table-cell';
			document.getElementById('discount').style.display = 'none';
			document.getElementById('xtra_discount').style.display = 'none';
		}
		else{
			document.getElementById('n_discount').style.display = 'none';
			document.getElementById('discount').style.display = 'table-cell';
			document.getElementById('xtra_discount').style.display = 'table-cell';			
		}
		cbSetValue('is_nonstandard');
		countFinalPrice();
	}
	
	
	function setCategory(drpDD)
	{
		if(drpDD.selectedIndex>0) ajaxPO('get_drp_ctgr', {drp_id: getSelectedValue(drpDD.id).substring(3), drp_dd: drpDD.id});
		else{
			document.getElementById(drpDD.id + '_category').style.display = 'none';
			document.getElementById(drpDD.id + '_ctgr_val').innerHTML = '';
			setCommonCtgr();
		}
	}
	
	function setCommonCtgr()
	{
		let ctgrValues = document.getElementById('dt' + getDraperyType()).getElementsByClassName('drp_ctgr_val');
		var maxCtgr = -1;
		for(i=0; i<ctgrValues.length;i++){
			let currCtgr = ctgrValues[i].innerHTML ? +getCtgrIndex(ctgrValues[i].innerHTML) : -1;
			if(currCtgr > maxCtgr) maxCtgr = currCtgr;
		}
		document.getElementById('common_ctgr_val').innerHTML = maxCtgr > -1 ? drpCtgrs[maxCtgr] : 'не выбрана';
		document.getElementById('discount_val').innerHTML = maxCtgr > 0 ? discount + xDscnt : xDscnt;
		if(chsnModules.length>0){
			if(document.getElementById('cm_graphics').style.display != 'none') listCM();
		}
		else if(maxCtgr > -1) ajaxPO('get_rp_price', {ctgr: drpCtgrs[maxCtgr], rp_id: +(getSelectedValue('r_prodDD').substring(3))});
	}
	
	function getCtgrIndex(ctgrName)
	{
		for(var i in drpCtgrs){
			if(drpCtgrs[i] == ctgrName) return i;	
		}
		return -1;
	}
	
	function getDraperyType()
	{
		for(var i=1; i<=3;i++){
			let cdtDiv = document.getElementById('dt' + i);
			if(cdtDiv.style.display && cdtDiv.style.display != 'none') return i;
		}
	}
	
	function getDrpStuff(anyID) //drpIDType или ID любого полходящего DD
	{
		let nmbr = parseInt(anyID.replace(/\D+/g,""));
		let prfx = anyID.indexOf('ind_') != -1 ? 'ind_' : '';
		let tm = document.getElementById(prfx + 'tm' + nmbr);
		let tmd = document.getElementById(prfx + 'tmDD' + nmbr);
		let cl = document.getElementById(prfx + 'coll' + nmbr);
		let cld = document.getElementById(prfx + 'collDD' + nmbr);
		let dr = document.getElementById(prfx + 'drapery' + nmbr);
		let drd = document.getElementById(prfx + 'draperyDD' + nmbr);
		let ctd = document.getElementById(prfx + 'draperyDD' + nmbr + '_category');
		let cts = document.getElementById(prfx + 'draperyDD' + nmbr + '_ctgr_val');
		let idt = prfx + 'drapery_id' + nmbr;
		let dsObj = {tmDiv: tm, tmDD: tmd, collDiv: cl, collDD: cld, drpDiv: dr, drpDD: drd, ctgDiv: ctd, ctgSpan: cts, idType: idt};
		if(prfx) dsObj['cmnt'] = document.getElementById(prfx + 'comment' + nmbr);
		return dsObj;
	}
	
	function setDrpOptions(drpObj)
	{
		let crdr = getDrpStuff(drpObj.idType);
		for(var i=0;i<crdr.tmDD.options.length;i++){
			if(crdr.tmDD.options[i].text == drpObj.tmName){
				busyDD.push(crdr.collDD.id);
				crdr.tmDD.selectedIndex = i;
				dddChange(crdr.tmDD);
				break;
			}
		}
	}
	
	function resetDraperyDDs()
	{
		let drpRoot = document.getElementById('drapery');
		let dd2off = drpRoot.getElementsByTagName('select');
		let ddParent, crdr;
		for(var i=1;i<=8;i++){
			document.getElementById('ind_comment' + i).value = '';
		}
		for(var i=0;i<dd2off.length;i++){
			dd2off[i].selectedIndex = 0;
			ddParent = dd2off[i].parentElement;
			if(ddParent.id.indexOf('tm') == -1) ddParent.style.display = 'none';
			crdr = getDrpStuff(dd2off[i].id);
			if(crdr.ctgSpan.innerHTML){
				crdr.ctgSpan.innerHTML = '';
				setCommonCtgr();
			}
		}
		cmnPrice = 0;
		if(!hideFinPart) document.getElementById('common_price_val').innerHTML = 'нет';
		countFinalPrice();
	}
	
	function setXtraDiscount()
	{
		if(document.getElementById('xtra_discount').style.display != 'none') xDscnt = document.getElementById('xtra_discount_val').selectedIndex;
		let dVal = xDscnt;
		if(discount && document.getElementById('common_ctgr_val').innerHTML != '0' && !document.getElementById('is_example').checked) dVal += discount;
		document.getElementById('discount_val').innerHTML = dVal < 100 ? dVal : 99;
		if(document.getElementById('modules').style.display == 'block') checkCM();
		countFinalPrice();
	}
	
	function setNstrdDiscount(isMarkup=false)
	{
		if(isMarkup) document.getElementById('nstrd_discount').selectedIndex = 0;
		else document.getElementById('nstrd_markup').selectedIndex = 0;
		countFinalPrice();
	}
	
	function countFinalPrice()
	{
		if(cmnPrice == 0){
			if(!hideFinPart) document.getElementById('final_price_val').innerHTML = 'нет';
			document.getElementById('summ_total').value = '';
			return false;
		}
		let cPrice = cmnPrice;
		if(document.getElementById('rp_quantity').style.display != 'none' && parseInt(document.getElementById('quantity').value, 10) > 1) cPrice *= parseInt(document.getElementById('quantity').value, 10);
		if(!document.getElementById('is_nonstandard').checked) cPrice *= 1 - +(document.getElementById('discount_val').innerHTML)/100;
		else{
			if(document.getElementById('nstrd_discount').selectedIndex > 0) cPrice *= 1 - document.getElementById('nstrd_discount').selectedIndex/100;
			else cPrice *= 1 + document.getElementById('nstrd_markup').selectedIndex/100;
		}
		if(!hideFinPart) document.getElementById('final_price_val').innerHTML = cPrice > 0 ? outSumm(roundNumber(cPrice, 2)) + 'руб.' : 'нет';
		document.getElementById('summ_total').value = roundNumber(cPrice, 2);
	}
	
	function extAPL()
	{
		if(savedCM && savedCM.length > 0){
			for(var i=0;i<savedCM.length;i++){
				savedCM[i].x = +(savedCM[i].x);
				savedCM[i].y = +(savedCM[i].y);
			}
			parseSavedCM();
		}
		drrHandle({value: drri});
		if(document.getElementById('r_prodDD').selectedIndex>0) setOptions();
		if(savedDrps && savedDrps.length>0){
			for(var i=0;i<savedDrps.length;i++){
				setDrpOptions(savedDrps[i]);
			}
		}
		if(document.getElementById('is_nonstandard').checked) nstrdChngHandler();
		else if(document.getElementById('xtra_discount_val').value != 'val0'){
			xDscnt = document.getElementById('xtra_discount_val').selectedIndex;
			setXtraDiscount();
		}
		checkDecorTitle();
	}
</script>
