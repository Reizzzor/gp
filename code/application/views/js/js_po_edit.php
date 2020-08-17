<script>
	var justLoaded = false;
	var busyDD = []; //ID DD, которые заполняются при помощи ajax-запросов и нуждаются в последующей обработке
	var poId, savedDrps, cmStage, lbLayer, cmvw, cmSize;
	var mpLink = "<?=$mp_link?>";
	var drpCtgrs = JSON.parse(<?="'".json_encode($drp_ctgrs)."'"?>);
	var dlvri = <?=isset($dlvr_type) ? "'".$dlvr_type."'" : "'salon'" ?>;
	var drri = <?=isset($drapery_type) ? "'".$drapery_type."'" : "'1'" ?>; 
	<?php 
		if(isset($po_id)) echo "justLoaded = true;\npoId = $po_id;\n";
		if(isset($saved_drps)) echo "savedDrps = JSON.parse('".$saved_drps."');\n";
	?>
	var isFirstPage = true;
	var cmWaiters = 0;
	var xDscnt = 0;
	var discount = 0;
	var drpNum = 3;
	var rpArr = ['decor', 'nails', 'modules'];
	var chsnModules = []; //массив выбранных модулей, заполняется объектами вида {id:id, name:name, x:length, y:width}
	<?php if(isset($cm_list)) echo "chsnModules = JSON.parse('".$cm_list."');\n"; ?>
	if(chsnModules.length>0 && !chsnModules[0].id) chsnModules = [];
	
	function POChangePage()
	{
		let pName = document.getElementById('page_name');
		if(isFirstPage){
			pName.innerHTML = 'доставке';
			document.getElementById('page1').style.display = 'none';
			document.getElementById('page2').style.display = 'block';
			document.getElementById('chngPage').innerHTML = '<< Инф-я о заказе';
			dlvrHandle({value: dlvri});
			isFirstPage = false;
		}
		else{
			pName.innerHTML = 'заказе';
			document.getElementById('page1').style.display = 'block';
			document.getElementById('page2').style.display = 'none';
			document.getElementById('chngPage').innerHTML = 'Инф-я о доставке >>';
			isFirstPage = true;
		}
	}
	
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
				qParams += '&po_id=' + fID;
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
				qParams += '&module_id=' + fID.cm_id + '&ctgr=' + drpCtgrs.indexOf(fID.ctgr) + '&cm_num=' + fID.cm_num;
				cmWaiters++;
				break;
			case 'get_rp_price':
				qParams += '&rp_id=' + fID.rp_id + '&ctgr=' + drpCtgrs.indexOf(fID.ctgr);
				break;
			case 'del_xtra_file':
				qParams += '&f_name=' + fID + '&po_id=' + poId;
				break;
		}
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange = function() {
			if(this.readyState != 4) return;
			if(this.status != 200){
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
				discount = +rpObj.discount;
				drpNum = +rpObj.drapery_num;
				document.getElementById('discount_val').innerHTML = discount + xDscnt; 
				for(var i in rpArr){
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
								let ock = `onClick="addCM(${crmd.id}, '${crmd.name}', ${crmd['length']}, ${crmd['width']});"`;
								let avmName = `<div>${crmd.name}</div>`;
								let avmPic = `<img src="${mpLink}${crmd.ext_id}.png">`;
								let divOpen = `<div class="av_module" id="avm${crmd.id}" ${ock}>`; 
								mlIH += divOpen + avmName + avmPic + '</div>' ;
							}
							document.getElementById('av_modules_list').innerHTML = mlIH;
						}
						if(cDiv.style.display == 'none') cDiv.style.display = 'block';
					}
					else{
						if(['decor', 'nails'].includes(rpArr[i])) fillOptions(document.getElementById(rpArr[i] + 'DD'), []);
						else if(rpArr[i] == 'modules') document.getElementById('av_modules_list').innerHTML = '';
						if(cDiv.style.display == 'block') cDiv.style.display = 'none';
					} 
				}
				if(rpObj.has_corner == '1'){
					document.getElementById('corners').style.display = 'block';
					if(!justLoaded) document.getElementById('corners').selectedIndex = 0;
				} 
				else document.getElementById('corners').style.display = 'none';
				if(rpObj.has_stitching == '1'){
					document.getElementById('stitching').style.display = 'block';
					if(!justLoaded) document.getElementById('stitching').selectedIndex = 0;
				}
				else document.getElementById('stitching').style.display = 'none';
				if(justLoaded) ajaxPO('po_options', poId);
				else clearCM(); 
				break;
			case 'po_options':
				var rk = Object.keys(rpObj);
				for(var i in rk){
					if(['decor_id', 'nails_id'].includes(rk[i])) setDDValue(rk[i].slice(0, -3) + 'DD', rpObj[rk[i]]);
				}
				if(justLoaded){
					if(chsnModules.length > 0) checkCM();	
					justLoaded = false;
				}
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
									crdr.ctgSpan.innerHTML = drpCtgrs[savedDrps[i].category];
									crdr.ctgDiv.style.display = 'table-cell';
									setCommonCtgr();
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
				document.getElementById(rpObj.drp_dd + '_ctgr_val').innerHTML = drpCtgrs[rpObj.drp_ctgr];
				document.getElementById(rpObj.drp_dd + '_category').style.display = 'table-cell';
				setCommonCtgr();
				break;
			case 'get_cm_price':
				for(var i=0; i<chsnModules.length; i++){
					if(rpObj.cm_id == chsnModules[i].id && rpObj.cm_num == i){
						chsnModules[i].price = rpObj.cm_price;
						cmWaiters--;
						break;
					}
				}
				if(cmWaiters == 0){
					cmvw = getCMView();
					cmSize = getCMSize();
					//console.log('chsnModules: %s; cmvw: %s; cmSize: %s', JSON.stringify(chsnModules), JSON.stringify(cmvw), JSON.stringify(cmSize));
					var prcStyle = {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "11px", color: 'red', fontWeight: 'bold'};
					for(i=0; i<chsnModules.length; i++){
						let lbi = cmArrayToView(i);
						let lbc = getLblCoords(lbi.j, lbi.i);
						let txtPrc = lbLayer.text(lbc.x, lbc.y, chsnModules[i].price, prcStyle);
						lblCentrize(txtPrc, lbc.x, lbc.y, cmSize.lblX, cmSize.lbPrice);
					}
					var cmnPrice = countCM(chsnModules, 'price', 'sum');
					document.getElementById('common_price_val').innerHTML = cmnPrice > 0 ? cmnPrice + 'руб.' : 'нет';
					document.getElementById('final_price_val').innerHTML = cmnPrice > 0 ? Math.round(cmnPrice*(1 - (+(document.getElementById('discount_val').innerHTML)/100))) + 'руб.' : 'нет';
					document.getElementById('summ_total').value = document.getElementById('final_price_val').innerHTML;
					
				}
				break;
			case 'get_rp_price':
				document.getElementById('common_price_val').innerHTML = +rpObj.price > 0 ? rpObj.price + 'руб.' : 'нет';
				document.getElementById('final_price_val').innerHTML = +rpObj.price > 0 ? Math.round((1 - (+(document.getElementById('discount_val').innerHTML)/100))*(+rpObj.price)) + 'руб.' : 'нет';
				document.getElementById('summ_total').value = document.getElementById('final_price_val').innerHTML;
				break;
			case 'del_xtra_file':
				var xflIH = '';
				var xfList = Object.values(rpObj.xf_list);
				if(xfList.length>0){
					for(var i=0;i<xfList.length;i++){
						let spnName = `<span class="xtra_file_name">${xfList[i]}</span>`; 
						let spnDel = `<span class="xtra_file_del" onClick="ajaxPO('del_xtra_file', '${xfList[i]}');">Удалить</span>`;
						xflIH += '<div>' + spnName + spnDel + '</div>';
					}
				}
				if(!xflIH) document.getElementById('xtra_filelist_title').innerHTML = 'По данному заказу не выгружено ни одного файла';
				document.getElementById('xtra_filelist').innerHTML = xflIH;
				showMsg({type: 'info', text: `Файл ${rpObj.deleted_file} был успешно удалён`});
				break;
		}
	}
	
	function countCM(arrCM, cmKey, cMode) //cMode - 'sum', 'max' или 'wlen_max'
	{
		let cRes = 0;
		if(cMode == 'wlen_max') cRes = '';
		for(var i=0;i<arrCM.length; i++){
			let crVal = arrCM[i][cmKey];
			if(cMode == 'max' && +(crVal) > cRes) cRes = +(crVal);
			else if(cMode == 'wlen_max' && crVal.length > cRes.length) cRes = crVal;
			else if(cMode == 'sum') cRes += +(crVal);
		}
		if(cMode == 'wlen_max' && cRes.length < 6) cRes = '999999';
		return cRes;
	}
	
	function addCM(cmID, cmName, cmX, cmY)
	{
		chsnModules.push({id: cmID, name: cmName, x: cmX, y: cmY});
		checkCM();
	}
	
	function delCM(cmId)
	{
		chsnModules.splice(cmId, 1);
		checkCM();
	}
	
	function moveCM(cmId, course='r')
	{
		let cm1 = chsnModules[cmId];
		let i2 = cmId + 1;
		if(course == 'l') i2 = cmId - 1; 
		chsnModules[cmId] = chsnModules[i2];
		chsnModules[i2] = cm1;
		checkCM();
	}
	
	function listCM()
	{
		if(!cmStage) cmStage = acgraph.create('cm_graphics');
		else cmStage.removeChildren();
		lbLayer = cmStage.layer();
		let cmgDiv = document.getElementById('cm_graphics');
		cmvw = getCMView();
		cmSize = getCMSize();
		console.log('chsnModules: %s; cmvw: %s; cmSize: %s', JSON.stringify(chsnModules), JSON.stringify(cmvw), JSON.stringify(cmSize));
		cmStage.width(cmSize.imgX);
		cmgDiv.style.width = cmSize.imgX + 'px';
		cmStage.height(cmSize.imgY);
		cmgDiv.style.height = cmSize.imgY + 'px';
		for(var j=0;j<cmvw.length;j++){
			let cmLayer = cmStage.layer();
			let mdlX = mdlY = 0;
			for(var i=0;i<cmvw[j].length;i++){
				let crmd = cmvw[j][i];
				drawCM(crmd, mdlX, mdlY, cmLayer);
				let lbi = drawCMLabel(j, i);
				let outX, outY;
				if(j==0){
					outX = cmSize.p0.x + 0.5*crmd.x + countCM(cmvw[0].slice(0, i), 'x', 'sum');
					outY = cmSize.p0.y;
				}
				else if(j==1){
					outX = cmSize.pRB.x;
					outY = cmSize.p1.y + 0.5*crmd.x + countCM(cmvw[1].slice(0, i), 'x', 'sum');
				}
				else{
					outX = cmSize.pRB.x - cmvw[1][cmvw[1].length-1].y - 0.5*crmd.x - countCM(cmvw[2].slice(0, i), 'x', 'sum');
					outY = cmSize.pRB.y;
				}
				lbLayer.path().moveTo(outX, outY).lineTo(lbi.x, lbi.y);
				let cmnCateg = document.getElementById('common_ctgr_val').innerHTML;
				if(cmnCateg) ajaxPO('get_cm_price', {cm_id: crmd.id, cm_num: cmViewToArray(j, i), ctgr: cmnCateg});
				mdlX += crmd.x;
			}
			if(j==0) cmLayer.setPosition(cmSize.p0.x, cmSize.p0.y);
			else if(j==1){
				cmLayer.rotate(90, 0, countCM(cmvw[1], 'y', 'max'));
				cmLayer.setPosition(cmSize.p1.x, cmSize.p1.y);
			}
			else{
				cmLayer.rotate(180, countCM(cmvw[2], 'x', 'sum')/2, countCM(cmvw[2], 'y', 'max')/2);
				cmLayer.setPosition(cmSize.p2.x, cmSize.p2.y);
			}
		}
	}
	
	function checkCM()
	{
		let cm = document.getElementById('chosen_modules');
		let ocm = document.getElementById('open_chosen_modules');
		let ccm = document.getElementById('clear_modules_list');
		//console.log('chsnModules.length: %d; cm: %s; ocm: %s', chsnModules.length, cm.style.display, ocm.innerHTML);
		if(chsnModules.length > 0){
			if(!cm.style.display || cm.style.display == 'none') cm.style.display = 'block';
			if(ocm.innerHTML  == 'Показать компановку') toggleChsnModules();
			if(!ccm.style.display || ccm.style.display == 'none') ccm.style.display = 'block';
			listCM();
		}
		else{
			if(cm.style.display != 'none') cm.style.display = 'none';
			if(ocm.innerHTML == 'Скрыть компановку') toggleChsnModules();
			if(ccm.style.display != 'none') ccm.style.display = 'none';
			document.getElementById('common_price_val').innerHTML = 'нет';
			document.getElementById('final_price_val').innerHTML = 'нет';
		}
		document.getElementById('cm_list_hidden').value = JSON.stringify(chsnModules);
	}
	
	function clearCM()
	{
		chsnModules = [];
		checkCM();
	}
	
	function drawCM(cModule, mX, mY, cLayer)
	{
		let rct;
		let back = 35;
		let cR = 8;
		if(/^(\D?КР|ОТ)\d{2,3}/.test(cModule.name)){
			rct = new acgraph.math.Rect(mX, mY, cModule.x, back);
			acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
			rct = new acgraph.math.Rect(mX, mY + back, cModule.x, cModule.y - back);
			acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		}
		else if(/^(Угл)(Л|П)/.test(cModule.name)){
			if(cModule.name == 'УглЛ'){
				rct = new acgraph.math.Rect(mX, mY, cModule.x, back);
				acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
				rct = new acgraph.math.Rect(mX + cModule.x - back, mY + back, back, cModule.y - back);
				acgraph.vector.primitives.roundedRect(cLayer, rct, cR);		
				cLayer.path().moveTo(mX, mY + back - cR).lineTo(mX, mY + cModule.y).lineTo(mX + cModule.x - back + cR, mY + cModule.y);
			}
			else{
				rct = new acgraph.math.Rect(mX, mY, cModule.x - back, back);
				acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
				rct = new acgraph.math.Rect(mX + cModule.x - back, mY, back, cModule.y);
				acgraph.vector.primitives.roundedRect(cLayer, rct, cR);		
				cLayer.path().moveTo(mX, mY + back - cR).lineTo(mX, mY + cModule.y).lineTo(mX + cModule.x - back + cR, mY + cModule.y);
			}
		}
		else{
			rct = new acgraph.math.Rect(mX, mY, cModule.x, cModule.y);
			acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		} 
	}
	
	function drawCMLabel(crntJ, crntI)
	{
		let crmd = cmvw[crntJ][crntI];
		let lbc = getLblCoords(crntJ, crntI);
		let navY = lbc.y + cmSize.lbPrice + cmSize.lbName;
		let navStyle = {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "11px", fontWeight: 'bold'};
		let rct = new acgraph.math.Rect(lbc.x, lbc.y, cmSize.lblX, cmSize.lblY);
		acgraph.vector.primitives.roundedRect(lbLayer, rct, 3, 3, 5, 5);
		let txtName = lbLayer.text(lbc.x, lbc.y + cmSize.lbPrice, crmd.name, {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "14px"});
		lblCentrize(txtName, lbc.x, lbc.y + cmSize.lbPrice, cmSize.lblX, cmSize.lbName);
		lbLayer.path().moveTo(lbc.x, navY).lineTo(lbc.x + cmSize.lblX, navY);
		let moveL, moveR;
		let idCM = crntI; 
		for(j=0;j<crntJ;j++){
			idCM += cmvw[j].length;
		}
		if(crntJ>0 || crntI>0){
			if(crntJ == 2){
				moveL = lbLayer.text(lbc.x + 2*cmSize.lbNav, navY, '>', navStyle);
				lblCentrize(moveL, lbc.x + 2*cmSize.lbNav, navY, cmSize.lbNav, lbc.y + cmSize.lblY - navY);
			}
			else{
				if(crntJ == 0) moveL = lbLayer.text(lbc.x, navY, '<', navStyle);
				else moveL = lbLayer.text(lbc.x, navY, '^', navStyle);
				lblCentrize(moveL, lbc.x, navY, cmSize.lbNav, lbc.y + cmSize.lblY - navY);
			}
			moveL.listen('click', function(e){moveCM(idCM, 'l');});
			moveL.cursor('pointer');
		}
		let cmDel = lbLayer.text(lbc.x + cmSize.lbNav, navY, 'X', navStyle);
		lblCentrize(cmDel, lbc.x + cmSize.lbNav, navY, cmSize.lbNav, lbc.y + cmSize.lblY - navY);
		cmDel.listen('click', function(e){delCM(idCM);});
		cmDel.cursor('pointer');
		if(crntJ != cmvw.length-1 || crntI != cmvw[crntJ].length-1){
			if(crntJ == 2){
				moveR = lbLayer.text(lbc.x, navY, '<', navStyle);
				lblCentrize(moveR, lbc.x, navY, cmSize.lbNav, lbc.y + cmSize.lblY - navY);
			}
			else{
				if(crntJ == 0)	moveR = lbLayer.text(lbc.x + 2*cmSize.lbNav, navY, '>', navStyle);
				else moveR = lbLayer.text(lbc.x + 2*cmSize.lbNav, navY, 'v', navStyle);
				lblCentrize(moveR, lbc.x + 2*cmSize.lbNav, navY, cmSize.lbNav, lbc.y + cmSize.lblY - navY);
			}
			moveR.listen('click', function(e){moveCM(idCM);});
			moveR.cursor('pointer');
		}
		return {x: lbc.inX, y: lbc.inY};
	}
	
	function lblCentrize(cLbl, dTX, dTY, dX, dY)
	{
		//console.log(dTX, dTY, dX, dY, cLbl.text());
		cLbl.y(dTY + 0.5*(dY - cLbl.getHeight()));
		cLbl.x(dTX + 0.5*(dX - cLbl.getWidth()));
	}
	
	function cmArrayToView(n)
	{
		let lSum = 0;
		for(var j=0;j<cmvw.length;j++){
			if(n - lSum < cmvw[j].length) return {j: j, i: n-lSum};
			else lSum += cmvw[j].length;
		}
	}
	
	function cmViewToArray(cJ, cI)
	{
		let rI = cI;
		for(var j=0;j<cJ;j++){
			rI += cmvw[j].length;
		}	
		return rI;
	}
	
	function getLblCoords(crntJ, crntI)
	{
		let lX, lY, inX, inY; 
		let lblOffset = cmSize['lbl'+crntJ][1] - cmSize['lbl'+crntJ][0];
		if(crntJ == 1) lblOffset = roundNumber((lblOffset - cmvw[1].length*cmSize.lblY)/(cmvw[1].length + 1), 3);
		else lblOffset = roundNumber((lblOffset - cmvw[crntJ].length*cmSize.lblX)/(cmvw[crntJ].length + 1), 3);
		if(crntJ == 0){
			lX = (crntI+1)*lblOffset + crntI*cmSize.lblX;
			lY = cmSize.mrgLbl;
			inX = lX + cmSize.lblX/2;
			inY = lY + cmSize.lblY;
		}
		else if(crntJ == 1){
			lX = cmSize.mrgnL + cmSize.cmX + cmSize.mrgLbl;   
			lY = (crntI+1)*lblOffset + crntI*cmSize.lblY;
			inX = lX;
			inY = lY + cmSize.lblY/2;
		}
		else{
			lX = cmSize.pRB.x - (crntI+1)*(lblOffset + cmSize.lblX);
			lY = cmSize.pRB.y + cmSize.mrgLbl;
			inX = lX + cmSize.lblX/2;
			inY = lY;
		}
		return {x: lX, y: lY, inX: inX, inY: inY};
	}
	
	function getCMSize()
	{
		let cmX = countCM(cmvw[0], 'x', 'sum');
		let cmY = countCM(cmvw[0], 'y', 'max');
		if(cmvw.length == 3 && cmX < countCM(cmvw[2], 'x', 'sum') + cmvw[1][cmvw[1].length-1].y) cmX = countCM(cmvw[2], 'x', 'sum') + cmvw[1][cmvw[1].length-1].y;
		if(cmvw.length > 1 && cmY < countCM(cmvw[1], 'x', 'sum') + cmvw[0][cmvw[0].length-1].y) cmY = countCM(cmvw[1], 'x', 'sum') + cmvw[0][cmvw[0].length-1].y;
		let lblX = roundNumber(1.3*acgraph.text(0, 0, countCM(chsnModules, 'name', 'wlen_max'), {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "14px"}).getWidth(), 3); 
		let lblY = 50;
		let mrgnL = 0.7*lblX;
		let mrgLbl = 20;
		let imgX = roundNumber(1.3*cmX, 3) > 250 ? roundNumber(1.3*cmX, 3) : 250;
		let imgY = 1.5*lblY + 1.4*cmY;
		if(cmvw.length > 1) imgX += 1.5*lblX;
		if(cmvw.length == 3) imgY += 1.5*lblY;
		let lbl0 = [0, imgX];
		let p0 = {x: roundNumber(mrgnL + cmX - countCM(cmvw[0], 'x', 'sum'), 3), y: 2*mrgLbl + lblY};
		let pRB = {x: p0.x + countCM(cmvw[0], 'x', 'sum'), y: p0.y + cmvw[0][cmvw[0].length-1].y};
		let p1, p2, lbl1, lbl2;
		if(cmvw.length > 1){
			p1 = {x: pRB.x - countCM(cmvw[1], 'y', 'max'), y: pRB.y};
			pRB.y += countCM(cmvw[1], 'x', 'sum');
			lbl1 = [p1.y, imgY];
		}
		if(cmvw.length > 2){
			p2 = {x: pRB.x - cmvw[1][cmvw[1].length-1].y - countCM(cmvw[2], 'x', 'sum'), y: pRB.y - countCM(cmvw[2], 'y', 'max')};
			lbl2 = [0, pRB.x];
		}
		let rsltObjct = {cmX: cmX, cmY: cmY, lblX: lblX, lblY: lblY, imgX: imgX, imgY: imgY, mrgnL: mrgnL, mrgLbl: mrgLbl, p0: p0, pRB: pRB, lbl0: lbl0, lbPrice: 0.32*lblY, lbName: 0.4*lblY, lbNav: roundNumber(lblX/3, 3)};
		if(p1){
			rsltObjct['p1'] = p1;
			rsltObjct['lbl1'] = lbl1;
			
		} 
		if(p2){
			rsltObjct['p2'] = p2;
			rsltObjct['lbl2'] = lbl2;
		} 
		return rsltObjct;
	}
	
	function getCMView()
	{
		let rsltVw = [[]];
		let crnCntr = 0;
		for(var i=0;i<chsnModules.length;i++){
			rsltVw[crnCntr].push(chsnModules[i]);
			if(/^(Угл)(Л|П)/.test(chsnModules[i].name) && crnCntr<2) crnCntr++;
			if(rsltVw.length - 1 < crnCntr) rsltVw.push([]);
		}
		return rsltVw;
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
			document.getElementById('xtra_discount_val').style.display = 'none';
		}
		let rId = +(getSelectedValue('r_prodDD').substring(3));
		ajaxPO('rp_options', rId);
	}
		
	function drrHandle(drr)
	{
		let dt1 = document.getElementById('dt1');
		let dt2 = document.getElementById('dt2');
		let dt3 = document.getElementById('dt3');
		switch(drr.value){
			case '1':
				dt1.style.display = 'table-cell';
				dt2.style.display = 'none';
				dt3.style.display = 'none';
				break;
			case '2':
				dt1.style.display = 'none';
				dt2.style.display = 'table-cell';
				dt3.style.display = 'none';
				var d2Arr = dt2.getElementsByClassName('d2');
				for(var i=3;i<6;i++){
					d2Arr[i].style.display = (i < drpNum) ? 'table-row' : 'none';
				}
				break;
			case '3':
				dt1.style.display = 'none';
				dt2.style.display = 'none';
				dt3.style.display = 'table-cell';
				break;
		}
		if(!justLoaded) resetDraperyDDs();
		setCommonCtgr();
	}
	
	function dlvrHandle(dlvr)
	{
		if(dlvri != dlvr.value) dlvri = dlvr.value;
		let adr = document.getElementById('dlvr_address');
		if(dlvr.value == 'client') adr.style.display = 'block'; 
		else{
			var clntDlvr = 	document.getElementsByClassName('client_dlvr');
			for(var i in clntDlvr){
				if(['has_lift', 'count_dlvr'].includes(clntDlvr[i].id)){
					clntDlvr[i].checked = false;
					cbSetValue(clntDlvr[i].id);
				}
				else clntDlvr[i].value = '';
			}
			adr.style.display = 'none';
		}
	}
	
	function dddChange(cDD)
	{
		let crdr = getDrpStuff(cDD.id);
		if(cDD.id.indexOf('tm') != -1){
			if(cDD.selectedIndex == 0){	
				crdr.collDiv.style.display = 'none';
				crdr.drpDiv.style.display = 'none';
				crdr.ctgDiv.style.display = 'none';
				crdr.ctgSpan.innerHTML = '';
				setCommonCtgr();
			}
			else ajaxPO('get_drp_coll', {dd: crdr.collDD, tmName: cDD.options[cDD.selectedIndex].text});
		}
		else if(cDD.id.indexOf('coll') != -1){
			if(cDD.selectedIndex == 0){
				crdr.drpDiv.style.display = 'none';
				crdr.ctgDiv.style.display = 'none';
				crdr.ctgSpan.innerHTML = '';
				setCommonCtgr();
			} 
			else ajaxPO('get_drapery', {dd: crdr.drpDD, collName: cDD.options[cDD.selectedIndex].text, tmName: crdr.tmDD.options[crdr.tmDD.selectedIndex].text, rp_id: +(getSelectedValue('r_prodDD').substring(3))});
		}
	}
	
	function nstrdChngHandler()
	{
		let nstrd = document.getElementById('nstrd_descr');
		if(document.getElementById('is_nonstandard').checked) nstrd.style.display = 'block';
		else{
			nstrd.value = '';
			nstrd.style.display = 'none';
		}
		cbSetValue('is_nonstandard');
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
		for(var i=0; i<ctgrValues.length;i++){
			let currCtgr = (ctgrValues[i].innerHTML) ? drpCtgrs.indexOf(ctgrValues[i].innerHTML) : -1;
			if(currCtgr > maxCtgr)  maxCtgr = currCtgr;
		}
		document.getElementById('common_ctgr_val').innerHTML = maxCtgr > -1 ? drpCtgrs[maxCtgr] : 'не выбрана';
		if(chsnModules.length>0){
			if(document.getElementById('cm_graphics').style.display != 'none') listCM();
		}
		else if(maxCtgr > -1) ajaxPO('get_rp_price', {ctgr: drpCtgrs[maxCtgr], rp_id: +(getSelectedValue('r_prodDD').substring(3))});
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
		let prfx = '';
		if(anyID.indexOf('ind_') != -1) prfx = 'ind_';
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
	}
	function toggleXtraDiscount()
	{
		let xdDD = document.getElementById('xtra_discount_val');
		if(xdDD.style.display == 'inline'){
			xdDD.style.display = 'none';
			xdDD.value = 'val0';
		}
		else xdDD.style.display = 'inline';
		xDscnt = 0;
		setXtraDiscount();
	}
	
	function setXtraDiscount()
	{
		if(document.getElementById('xtra_discount_val').style.display == 'inline') xDscnt = document.getElementById('xtra_discount_val').selectedIndex;
		document.getElementById('discount_val').innerHTML = discount + xDscnt < 100 ? discount + xDscnt : 99;
		checkCM();
		
	}
	
	function extAPL()
	{
		if(document.getElementById('is_nonstandard').checked) document.getElementById('nstrd_descr').style.display = 'block';
		if(chsnModules && chsnModules.length > 0){
			for(var i=0;i<chsnModules.length;i++){
				chsnModules[i].x = +(chsnModules[i].x);
				chsnModules[i].y = +(chsnModules[i].y);
			}
		}
		drrHandle({value: drri});
		if(document.getElementById('r_prodDD').selectedIndex>0) setOptions();
		if(savedDrps && savedDrps.length>0){
			for(var i=0;i<savedDrps.length;i++){
				setDrpOptions(savedDrps[i]);
			}
		}
		if(document.getElementById('xtra_discount_val').value != 'val0'){
			document.getElementById('xtra_discount_val').style.display = 'inline';
			xDscnt = document.getElementById('xtra_discount_val').selectedIndex;
			setXtraDiscount();
		}
	}
</script>