function countCM(arrCM, cmKey, cMode) //cMode - 'sum', 'max', 'wlen_max' или 'ecm_sum'
{
	let cRes = 0;
	if(cMode == 'wlen_max') cRes = '';
	for(var i=0;i<arrCM.length; i++){
		let crVal = cMode == 'wlen_max' ? arrCM[i][cmKey] : +(arrCM[i][cmKey]);
		if(cMode == 'max' && crVal > cRes) cRes = crVal;
		else if(cMode == 'wlen_max' && crVal.length > cRes.length) cRes = crVal; //т.е. возвращаем ЗНАЧЕНИЕ самого длинного элемента
		else if(cMode == 'sum') cRes += crVal;
		else if(cMode == 'ecm_sum') cRes += +(arrCM[i].quantity)*crVal;
	}
	if(cMode == 'wlen_max' && cRes.length < 6) cRes = '999999';
	return cRes;
}
	
function addCM(cmID, cmName, cmX, cmY, hasDecor)
{
	let objCM = {id: cmID, name: cmName, x: cmX, y: cmY, decor: hasDecor};
	if(isECM(objCM)) addECM(objCM);
	else chsnModules.push(objCM);
	checkCM();
}
	
function delCM(cmId, isExcp=false)
{
	if(!isExcp) chsnModules.splice(cmId, 1);
	else if(exmv[cmId].quantity > 1)  exmv[cmId]['quantity']--;
	else exmv.splice(cmId, 1);
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
	console.log(`chsnModules: ${JSON.stringify(chsnModules)}; exmv: ${JSON.stringify(exmv)}`);
	if(!cmStage) cmStage = acgraph.create('cm_graphics');
	else cmStage.removeChildren();
	lbLayer = cmStage.layer();
	let cmgDiv = document.getElementById('cm_graphics');
	cmvw = getCMView();
	cmSize = getCMSize();
	//console.log(JSON.stringify(cmSize));
	let scft = is_print ? sclCft : 1;
	cmStage.width(scft*cmSize.imgX);
	cmgDiv.style.width = scft*cmSize.imgX + 'px';
	cmStage.height(scft*cmSize.imgY);
	cmgDiv.style.height = scft*cmSize.imgY + 'px';
	let cmnCateg; 
	if(!is_print) cmnCateg = document.getElementById('common_ctgr_val').innerHTML;
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
			if(cmnCateg) ajaxPO('get_cm_price', {cm_id: crmd.id, cm_num: cmViewToArray(j, i), ctgr: cmnCateg, cm_type: 'cm'});
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
	let ecmLayer;
	let crntY = 0;//(cmSize.imgY - cmSize.excY)/2;
	//{fontFamily: "Arial, Helvetica, sans-serif", fontSize: "14px", fontWeight: 'bold', color: 'red',};
	ecmLayer = cmStage.layer();
	for(var i=0;i<exmv.length;i++){
		if(i>0) crntY += cmSize.mrgLbl;
		let crrECM = exmv[i];
		let emY = crrECM.y > cmSize.qSize ? crrECM.y : cmSize.qSize;
		let rct = new acgraph.math.Rect(0, crntY, cmSize.qSize + cmSize.excX + 5, 2*cmSize.excLblY + emY);
		acgraph.vector.primitives.roundedRect(ecmLayer, rct, 2, 2, 3, 3).stroke('grey');
		let moreLbl = ecmLayer.text(5, crntY, '+', {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "17px", fontWeight: 'bold', color: 'red'});
		lblCentrize(moreLbl, 5, crntY, cmSize.qSize, cmSize.excLblY);
		moreLbl.listen('click', function(e){addCM(crrECM.id, crrECM.name, crrECM.x, crrECM.y, crrECM.decor);});
		moreLbl.cursor('pointer');
		let nameLbl = ecmLayer.text(cmSize.qSize + 5, crntY, crrECM.name, {fontFamily: "Arial, Helvetica, sans-serif", fontSize: is_print ? "20px" : "14px"});
		lblCentrize(nameLbl, cmSize.qSize + 5, crntY, cmSize.excX, cmSize.excLblY);
		let dQY, dY;
		crntY += cmSize.excLblY;
		if(crrECM.y > cmSize.qSize){
			dQY = (crrECM.y - cmSize.qSize)/2;
			dY = 0;
		}	
		else{
			dY = (cmSize.qSize - crrECM.y)/2;
			dQY = 0;
		}
		drawCM(crrECM, 5 + cmSize.qSize + (cmSize.excX - crrECM.x)/2, crntY + dY, ecmLayer);
		rct = new acgraph.math.Rect(5, crntY + dQY, cmSize.qSize, cmSize.qSize);
		acgraph.vector.primitives.roundedRect(ecmLayer, rct, 2).stroke('grey');
		let qLbl = ecmLayer.text(5, crntY + dQY, crrECM.quantity,  {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "17px", fontWeight: 'bold', color: 'red'});
		lblCentrize(qLbl, 5, crntY + dQY, cmSize.qSize, cmSize.qSize);
		crntY += emY;
		let lessLbl = ecmLayer.text(5, crntY, '–', {fontFamily: "Arial, Helvetica, sans-serif", fontSize: "17px", fontWeight: 'bold', color: 'red'});
		lblCentrize(lessLbl, 5, crntY, cmSize.qSize, cmSize.excLblY);
		let iECM = i;
		lessLbl.listen('click', function(e){delCM(iECM, true);});
		lessLbl.cursor('pointer');
		if(cmnCateg) ajaxPO('get_cm_price', {cm_id: crrECM.id, cm_num: iECM, ctgr: cmnCateg, cm_type: 'ecm'});
		crntY += cmSize.excLblY;
	}
	if(exmv.length>0) ecmLayer.setPosition(cmSize.pExc.x, cmSize.pExc.y + (cmSize.imgY - cmSize.excY)/2);
}

function checkCM()
{
	let cm = document.getElementById('chosen_modules');
	let ocm = document.getElementById('open_chosen_modules');
	let ccm = document.getElementById('clear_modules_list');
	let dcr = document.getElementById('decor');
	if(chsnModules.length > 0 || exmv.length > 0){
		if(!cm.style.display || cm.style.display == 'none') cm.style.display = 'block';
		if(ocm.innerHTML  == 'Показать компановку') toggleChsnModules();
		if(!ccm.style.display || ccm.style.display == 'none') ccm.style.display = 'block';
		dcr.style.display = 'none';
		for(var i=0; i<chsnModules.length;i++){
			if(chsnModules[i].decor == 1){
				dcr.style.display = 'block';
				break;
			}
		}
		listCM();
	}
	else{
		if(cm.style.display != 'none') cm.style.display = 'none';
		if(ocm.innerHTML == 'Скрыть компановку') toggleChsnModules();
		if(ccm.style.display != 'none') ccm.style.display = 'none';
		cmnPrice = 0;
		if(!hideFinPart) document.getElementById('common_price_val').innerHTML = 'нет';
		countFinalPrice();
		dcr.style.display = 'none';
	}
	let ecml = [];
	for(var i=0;i<exmv.length;i++){
		for(var j=0;j<exmv[i].quantity;j++){
			ecml.push(exmv[i]);
		}
	}
	//console.log(`exmv: ${JSON.stringify(exmv)}; ecml: ${JSON.stringify(ecml)}`);
	document.getElementById('cm_list_hidden').value = JSON.stringify(chsnModules.concat(ecml));
	checkDecorTitle();
}

function clearCM()
{
	chsnModules = [];
	exmv = [];
	checkCM();
}

function drawCM(cModule, mX, mY, cLayer)
{
	let rpName, rpDD;
	if(!is_print){
		rpDD = document.getElementById('r_prodDD');
		rpName = rpDD.options[rpDD.selectedIndex].text;
	}
	else rpName = document.getElementById('rp_name').innerHTML;
	let rct;
	let back = 35;
	let cR = 8;
	if(rpName.indexOf('Кьянти') != -1 && cModule.name == 'ДКР'){
		rct = new acgraph.math.Rect(mX, mY, back, cModule.y);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		rct = new acgraph.math.Rect(mX + back, mY, cModule.x - 2*back, back);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		rct = new acgraph.math.Rect(mX + cModule.x - back, mY, back, cModule.y);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		cLayer.path().moveTo(mX+ back - cR, mY + cModule.y).lineTo(mX + cModule.x - back + cR, mY + cModule.y);
	}
	else if(rpName.indexOf('Лондон') != -1){
		if(cModule.name == 'ЛондонУГл'){
			rct = new acgraph.math.Rect(mX, mY, cModule.x, cModule.y/2);
			acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
			rct = new acgraph.math.Rect(mX, mY + cModule.y/2, cModule.x/2, cModule.y/2);
			acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		}
		else if(cModule.name == 'ЛондонУГп'){
			rct = new acgraph.math.Rect(mX, mY, cModule.x, cModule.y/2);
			acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
			rct = new acgraph.math.Rect(mX + cModule.x/2, mY + cModule.y/2, cModule.x/2, cModule.y/2);
			acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		}
	}
	else if(/^(\D?КР|ОТ|Д\d{2,3})/.test(cModule.name.toUpperCase())){
		rct = new acgraph.math.Rect(mX, mY, cModule.x, back);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		rct = new acgraph.math.Rect(mX, mY + back, cModule.x, cModule.y - back);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
	}
	else if(["УГЛ", "УГ.ЛЕВ.СКОШ.", "УГ.ЛЕВ.ПРЯМАЯ", "УГ ЛЕВ", "УГОЛ ЛЕВЫЙ", "УГЛОВАЯ (ЛЕВАЯ)", "УГ.ЛЕВ.ПР.", "УГ.ЛЕВ", "УГ.ЛЕВ.", "УГЛЕВ", "УГЛВ", "ЛУ"].includes(cModule.name.toUpperCase())){
		rct = new acgraph.math.Rect(mX, mY, cModule.x, back);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		rct = new acgraph.math.Rect(mX + cModule.x - back, mY + back, back, cModule.y - back);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);		
		cLayer.path().moveTo(mX, mY + back - cR).lineTo(mX, mY + cModule.y).lineTo(mX + cModule.x - back + cR, mY + cModule.y);
	}
	else if(["УГПР", "УГП", "УГ.ПР.СКОШ.", "УГ.ПР.ПРЯМАЯ", "УГ ПРАВ", "УГОЛ ПРАВЫЙ", "УГЛОВАЯ (ПРАВАЯ)", "УГ.ПРАВ.ПР", "УГ.ПРАВ", "УГ.ПР.", "УГПРАВ", "ПУ"].includes(cModule.name.toUpperCase())){
		rct = new acgraph.math.Rect(mX, mY, cModule.x - back, back);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);
		rct = new acgraph.math.Rect(mX + cModule.x - back, mY, back, cModule.y);
		acgraph.vector.primitives.roundedRect(cLayer, rct, cR);		
		cLayer.path().moveTo(mX, mY + back - cR).lineTo(mX, mY + cModule.y).lineTo(mX + cModule.x - back + cR, mY + cModule.y);
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
	let txtName = lbLayer.text(lbc.x, lbc.y + cmSize.lbPrice, crmd.name, {fontFamily: "Arial, Helvetica, sans-serif", fontSize: is_print ? "20px" : "14px"});
	lblCentrize(txtName, lbc.x, lbc.y + cmSize.lbPrice, cmSize.lblX, cmSize.lbName);
	if(!is_print) lbLayer.path().moveTo(lbc.x, navY).lineTo(lbc.x + cmSize.lblX, navY);
	let moveL, moveR, cmDel;
	let idCM = crntI;
	if(!is_print){
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
		cmDel = lbLayer.text(lbc.x + cmSize.lbNav, navY, 'X', navStyle);
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
	let lSum = j = 0;
	for(var j=0;j<cmvw.length;j++){
		if(n - lSum < cmvw[j].length) return {j: j, i: n-lSum};
		lSum += cmvw[j].length;
	}
	return false;
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
		lY = cmSize.lbl1[0] + (crntI+1)*lblOffset + crntI*cmSize.lblY;
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
	let lblX = roundNumber(1.3*acgraph.text(0, 0, countCM(savedCM, 'name', 'wlen_max'), {fontFamily: "Arial, Helvetica, sans-serif", fontSize: is_print ? "20px" : "14px"}).getWidth(), 3); 
	let lblY = 50;
	let mrgnL = 0.7*lblX;
	let mrgLbl = 20;
	let imgX = roundNumber(1.3*cmX, 3) > 250 ? roundNumber(1.3*cmX, 3) : 250;
	let imgY = roundNumber(1.5*lblY + 1.4*cmY, 3);
	if(cmvw.length > 1) imgX += 1.5*lblX;
	if(cmvw.length == 3) imgY += 1.5*lblY;
	let lbl0 = [0, imgX];
	let p0 = {x: roundNumber(mrgnL + cmX - countCM(cmvw[0], 'x', 'sum'), 3), y: 2*mrgLbl + lblY};
	let pRB = {x: p0.x + countCM(cmvw[0], 'x', 'sum'), y: p0.y};
	if(cmvw[0].length > 0) pRB.y += cmvw[0][cmvw[0].length-1].y;
	let rsltObjct = {cmX: cmX, cmY: cmY, lblX: lblX, lblY: lblY, imgX: imgX, imgY: imgY, mrgnL: mrgnL, mrgLbl: mrgLbl, p0: p0, pRB: pRB, lbl0: lbl0, lbPrice: 0.32*lblY, lbName: 0.4*lblY, lbNav: roundNumber(lblX/3, 3)};
	if(cmvw.length > 1){
		rsltObjct.p1 = {x: pRB.x - countCM(cmvw[1], 'y', 'max'), y: pRB.y};
		rsltObjct.pRB.y += countCM(cmvw[1], 'x', 'sum');
		rsltObjct.lbl1 = [p0.y, imgY];
	}
	if(cmvw.length > 2){
		rsltObjct.p2 = {x: pRB.x - cmvw[1][cmvw[1].length-1].y - countCM(cmvw[2], 'x', 'sum'), y: pRB.y - countCM(cmvw[2], 'y', 'max')};
		rsltObjct.lbl2 = [0, pRB.x];
	}
	if(exmv.length > 0){
		rsltObjct.qSize = 0.5*rsltObjct.lblX;
		rsltObjct.pExc = {x: rsltObjct.imgX, y: 0};
		rsltObjct.excLblY = roundNumber(1.2*acgraph.text(0, 0, exmv[0].name, {fontFamily: "Arial, Helvetica, sans-serif", fontSize: is_print ? "20px" : "14px"}).getHeight(), 3);
		rsltObjct.excX = 10 + roundNumber(countCM(exmv, 'x', 'max'), 3);
		rsltObjct.imgX += rsltObjct.qSize + rsltObjct.excX + 10;
		rsltObjct.excY = roundNumber(getECMBlockHeight(rsltObjct, exmv) - rsltObjct.mrgLbl, 3);
		if(rsltObjct.imgY < rsltObjct.excY + 50) rsltObjct.imgY = rsltObjct.excY + 50;
	}
	return rsltObjct;
}

function getCMView()//получаем cmvw из chsnModules
{
	let rsltVw = [[]];
	let crnCntr = 0;
	for(var i=0;i<chsnModules.length;i++){
		rsltVw[crnCntr].push(chsnModules[i]);
		let cornersArr = ["УГЛ", "УГ", "УГ.ЛЕВ.СКОШ.", "УГ.ЛЕВ.ПРЯМАЯ", "УГ ЛЕВ", "УГОЛ ЛЕВЫЙ", "УГЛОВАЯ (ЛЕВАЯ)", "УГ.ЛЕВ.ПР.", "УГ.ЛЕВ", "УГ.ЛЕВ.", "УГЛЕВ", "УГЛВ", "УГПР", "УГП", "УГ.ПР.СКОШ.", "УГ.ПР.ПРЯМАЯ", "УГ ПРАВ", "УГОЛ ПРАВЫЙ", "УГЛОВАЯ (ПРАВАЯ)", "УГ.ПРАВ.ПР", "УГ.ПРАВ", "УГПРАВ", "УГ.ПР."];
		if(cornersArr.includes(chsnModules[i].name.toUpperCase()) && crnCntr<2) crnCntr++;
		if(rsltVw.length - 1 < crnCntr) rsltVw.push([]);
	}
	return rsltVw;
}

function isECM(cm)
{
	if(/^(Б\/Я|ЯЩ)/.test(cm.name.toUpperCase())) return true;
	let rpName, rpDD;
	if(!is_print){
		rpDD = document.getElementById('r_prodDD');
		rpName = rpDD.options[rpDD.selectedIndex].text;
	}
	else rpName = document.getElementById('rp_name').innerHTML;
	if(rpName.indexOf('Кьянти') != -1  && /^(КП)/.test(cm.name.toUpperCase())) return true;
	if(rpName.indexOf('Лондон') != -1 && /^(ЛП)/.test(cm.name.toUpperCase())) return true;
	if(rpName.indexOf('Летти') != -1 && /^\d{2}[XХ]\d{2}/.test(cm.name.toUpperCase())) return true;
	if(rpName.indexOf('Милтон') != -1 && /^(ВАЛ\.)\d{2,}/.test(cm.name.toUpperCase())) return true;
	return false;
}

function parseSavedCM()
{
	console.log(JSON.stringify(savedCM));
	for(var i=0;i<savedCM.length;i++){
		if(isECM(savedCM[i])) addECM(savedCM[i]);
		else chsnModules.push(savedCM[i]);
	}
}

function addECM(cm)
{
	for(var i=0;i<exmv.length;i++){
		if(exmv[i].id == cm.id){
			exmv[i]['quantity']++;
			return true;
		}
	}
	let newExcm = cm;
	newExcm.quantity = 1;
	exmv.push(newExcm);
}

function getECMBlockHeight(sz, arrECM) 
{
	let bh = 0;
	for(var i=0;i<arrECM.length;i++){
		if(i>0)bh += sz.mrgLbl;
		bh += arrECM[i].y > sz.qSize ? arrECM[i].y : sz.qSize;
		bh += 2*sz.excLblY;
	}
	return bh;
}

function checkDecorTitle()
{
	let subDcr = ['decor', 'nails', 'stitching', 'golf_options'];
	let dcrTl = document.getElementById('decor_title');
	dcrTl.style.display = 'none';
	for(var i in subDcr){
		console.log(subDcr[i] + ': ' + document.getElementById(subDcr[i]).style.display);
		if(document.getElementById(subDcr[i]).style.display == 'block'){
			dcrTl.style.display = 'block';
			break;
		}
	}
}

function getScaleCft()
{
	let prntLen = 375;
	if(!cmSize) cmSize = getCMSize();
	if(cmSize.imgX > prntLen && cmSize.imgY > prntLen){
		var cftX = roundNumber(prntLen/cmSize.imgX, 3);
		var cftY = roundNumber(prntLen/cmSize.imgY, 3);
		return cftX > cftY ? cftX : cftY;
	}
	else if(cmSize.imgX > prntLen) return roundNumber(prntLen/cmSize.imgX, 3);
	else if(cmSize.imgY > prntLen) return roundNumber(prntLen/cmSize.imgY, 3);
	else return 1;
}