<script>
	var tmpEmail = "";
	var isFillingDD = false;
	var mngrReq = false;
	var dd, salons, slnEmails, mngEmails, mngSalons;
	<?php foreach(array("sln_emails_str"=>"slnEmails", "mng_emails_str"=>"mngEmails", "mng_salons_str"=>"mngSalons") as $k=>$vl): ?>
			<?=$vl?> = <?=isset(${$k}) ? "JSON.parse(${$k})" : "[]"?>;
	<?php endforeach; ?>
	
//вспомогательные функции	
	
	function slnInfoRespHandler(ro)
	{
		if(ro.method == 'salons'){
			if(mngrReq){
				var inEmail = document.getElementById('email');
				salons = ro.salons;
				fillMngSlnDD();
				document.getElementById('active_salons').innerHTML = getMngrItems('salons');
				document.getElementById('active_emails').innerHTML = getMngrItems('emails');
				tmpEmail = inEmail.value;
				inEmail.value = 'см. Информацию об управляющем';
				inEmail.readOnly = true;
				if(/^\S+@\S+\.\S+$/.test(tmpEmail) && !mngEmails.includes(tmpEmail)){
					mngEmails.push(tmpEmail);
					document.getElementById('active_emails').innerHTML = getMngrItems('emails');
				}
				mngrReq = false;
			}
			else{
				if(ro.ka_name){
					document.getElementById('ka4slnInput').value = ro.ka_name;
					document.getElementById('k4sINN').value = ro.inn;
				}
				fillOptions(document.getElementById('slnDD'), ro.salons);
			}
		}
		else if(ro.method == 'kagents'){
			if(ro.sln_name) document.getElementById('slnInput').value = ro.sln_name;
			fillOptions(document.getElementById('ka4slnDD'), ro.kagents);
		}
		else if(ro.method == 'get_sln_stuff') listSlnStuff(ro.stuff);
		else if(ro.method == 'del_sln_stuff'){
			listSlnStuff(ro.stuff);
			showMsg({type: 'info', text: 'Запись удалена'});
			
		}
		else if(ro.method == 'add_sln_stuff'){
			listSlnStuff(ro.stuff);
			showMsg({type: 'info', text: 'Запись добавлена'});
		}
	}
	
	function slnInfoRequest(mthd, fID=null)
	{
		let xhr = new XMLHttpRequest();
		xhr.open('POST', "<?=site_url('slninfohandler')?>", true);
		let qParams = 'method=' + mthd;
		if(mthd == 'salons'){
			if(fID) qParams += '&kagent_id=' + fID;
		}
		else if(mthd == 'kagents'){
			if(fID) qParams += '&sln_id=' + fID;
		}
		else if(mthd == 'get_sln_stuff') qParams += '&sln_id=' + getSelectedValue('slnDD').substring(3);
		else if(mthd == 'del_sln_stuff') qParams += '&sln_id=' + getSelectedValue('slnDD').substring(3) + '&id=' + fID;
		else if(mthd == 'add_sln_stuff') qParams += '&sln_id=' + getSelectedValue('slnDD').substring(3) + '&name=' + encodeURI(fID);
		xhr.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
		xhr.onreadystatechange = function() {
			if (this.readyState != 4) return;
			if (this.status != 200) {
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
			slnInfoRespHandler(jr);
		}
		xhr.send(qParams);
	}
	
//функции-обработчики событий

	function fillSlnFields(ddId)
	{
		if(isFillingDD) return 111;
		isFillingDD = true;
		let reqMthd = (ddId == 'slnDD') ? 'kagents' : 'salons';
		let selectedId = +(getSelectedValue(ddId).substring(3));
		let k4sInput = document.getElementById('ka4slnInput');	
		let slnInput = document.getElementById('slnInput');
		let inn = document.getElementById('k4sINN');
		if(selectedId == 0){
			if(reqMthd == 'salons'){
				k4sInput.value = '';
				inn.value = '';
			}
			else if(reqMthd == 'kagents') slnInput.value = '';
			slnInfoRequest(reqMthd);
		}
		else slnInfoRequest(reqMthd, selectedId);
		isFillingDD = false;
	}
	
	function uTypeSetVisible()
	{
		let uType = getSelectedValue('utype_select');
		let slnDiv = document.getElementById('sln');
		let kaDiv = document.getElementById('ka');
		let mgDiv = document.getElementById('manager');
		let inEmail = document.getElementById('email');
		switch(uType){
			case 'store':
			case 'admin':
				slnDiv.style.display = 'none';
				kaDiv.style.display = 'none';
				mgDiv.style.display = 'none';
				if(inEmail.readOnly){
					inEmail.readOnly = false;
					inEmail.value = tmpEmail;
				}
				break;
			case 'salon':
				kaDiv.style.display = 'none';
				mgDiv.style.display = 'none';
				if(inEmail.readOnly){
					inEmail.readOnly = false;
					inEmail.value = tmpEmail;
				}
				slnInfoRequest('get_sln_stuff');
				slnDiv.style.display = 'block';
				break;
			case 'kagent':
				slnDiv.style.display = 'none';
				kaDiv.style.display = 'block';
				mgDiv.style.display = 'none';
				break;
			case 'manager':
				slnDiv.style.display = 'none';
				kaDiv.style.display = 'none';
				mngrReq = true;
				slnInfoRequest('salons');
				mgDiv.style.display = 'block';
				break;
		}
	}
	
//Обработчики списка сотрудников салона

	function listSlnStuff(stList)
	{
		let slDiv = document.getElementById('sln_stuff');
		if(stList.length == 0) slDiv.innerHTML ='<span class="mngr_empty_msg">Не добавлено ни одного сотрудника</span>'
		else{
			slDiv.innerHTML = '';
			for(var i=0; i<stList.length; i++){
				let divId = 'sln_stuff_' + stList[i].id;
				let spanName = '<span class="mngr_item_name">' + stList[i].name + '</span>';
				let spanDel = `<span class="mngr_item_del" onClick="slnInfoRequest('del_sln_stuff', ${stList[i].id}); return false;">Удалить</span>`;
				slDiv.innerHTML += `<div id="${divId}">${spanName}${spanDel}</div>`;
			}
		}
	}
	
	function addSlnStuff()
	{
		let addInpt = document.getElementById('add_sln_stuff');
		slnInfoRequest('add_sln_stuff', addInpt.value);
		addInpt.value = '';
	}
	
//Функции валидации
	
	function validateUserEditForm()
	{
		let outMsg = '';
		let loginInput = document.getElementById('login');
		let pass1 = document.getElementById('pass1');
		let pass2 = document.getElementById('pass2');
		let email = document.getElementById('email');
		let uType = getSelectedValue('utype_select');
		if(loginInput.value.length < 5){
			loginInput.value = '';
			outMsg = addToErrMsg(outMsg, 'Слишком короткий логин! Минимальная длина логина должна составлять 5 символов.');
		}
		if(pass1.value && pass1.value != pass2.value){
			pass1.value = '';
			pass2.value = '';
			outMsg = addToErrMsg(outMsg, 'Введённые пароли не совпадают!');
		} 
		else if(pass1.value && pass1.value.length < 8){
			pass1.value = '';
			pass2.value = '';
			outMsg = addToErrMsg(outMsg, 'Слишком короткий пароль! Минимальная длина пароля должна составлять 8 символов.');
		}
		if(email.value && !(/^\S+@\S+\.\S+$/.test(email.value)) && uType !== 'manager'){
			email.value = '';
			outMsg = addToErrMsg(outMsg, 'Введённый e-mail - неправильный!');
		}
		if(uType === 'salon'){
			var innInput = document.getElementById('k4sINN');
			if(innInput.value && !(/\d{8,15}/.test(innInput.value))){
				innInput.value = '';
				outMsg = addToErrMsg(outMsg, 'ИНН должен включать от 8 до 15 символов и состоять только из цифр.');
			}	
		}
		if(uType === 'manager'){
			if(mngSalons.length<1) outMsg = addToErrMsg(outMsg, 'Необходимо выбрать хотя бы 1 салон.');
			if(mngEmails.length<1) outMsg = addToErrMsg(outMsg, 'Необходимо задать хотя бы 1 e-mail.');
			document.getElementById('mngr_hidden').value = JSON.stringify({emails: mngEmails, sln_ids: mngSalons});
		}
		return outMsg ? {type: 'error', text: outMsg} : true;
	}
	
	function saveChanges()
	{
		let msgVldt = validateUserEditForm();
		if(typeof(msgVldt) === 'boolean') return true;
		showMsg(msgVldt);
		return false;
	}

	
//обработчики юзеров типа "Менеджер"
	
	function mngrAddSalon()
	{
		let mngSlnDD = document.getElementById('mngr_sln_dd');
		let selectedValue = getSelectedValue('mngr_sln_dd');
		let selectedId = +(selectedValue.substring(3));
		if(selectedId>0){
			for(var i=0; i<salons.length; i++){
				if(salons[i].id == selectedId){
					mngSalons.push(selectedId);
					break;
				}
			}
			document.getElementById('active_salons').innerHTML = getMngrItems('salons');
			fillMngSlnDD();
			mngSlnDD.selectedIndex = 0;
			for(var i=0;i<slnEmails.length;i++){
				if(slnEmails[i].sln_id == selectedId && !mngEmails.includes(slnEmails[i].email)){
					mngEmails.push(slnEmails[i].email);	
					document.getElementById('active_emails').innerHTML = getMngrItems('emails');
					break;
				}
			}
		}
	}
	
	function mngrAddEmail()
	{
		let email2add = document.getElementById('mngr_add_email').value;
		if(!(/^\S+@\S+\.\S+$/.test(email2add))) showMsg({type:'error', text:'Введён некорректный E-mail'});
		else{
			if(mngEmails.includes(email2add)) showMsg({type:'error', text:'Введённый E-mail уже внесён в список'});
			else{
				mngEmails.push(email2add);
				document.getElementById('active_emails').innerHTML = getMngrItems('emails');
			}
			document.getElementById('mngr_add_email').value = '';
		}
	}
	
	function getMngrItems(dType) //dType: salons/emails; returns innerHTML for active_salons/active_emails
	{
		let dArr = dType == 'salons' ? mngSalons : mngEmails;
		let itemName = dType == 'salons' ? 'салон' : 'e-mail';
		if(!dArr.length) return '<span class="mngr_empty_msg">Не добавлено ни одного ' + itemName + 'а</span>';
		let divIH = '';
		for(var i=0; i<dArr.length; i++){
			if(dType == 'salons'){
				var divId = 'mngr_sln_' + dArr[i];
				for(var j=0; j<salons.length; j++){
					if(salons[j].id == dArr[i]){
						var spanName = '<span class="mngr_item_name">' + salons[j].name + '</span>';
						break;
					}
				}			
				
			}
			else{
				var divId = 'mngr_email_' + i;
				var spanName = '<span class="mngr_item_name">' + dArr[i] + '</span>';
			}
			var spanDel = '<span class="mngr_item_del" onClick="delMngrItem(\''  + divId + '\'); return false;">Удалить</span>';
			divIH += '<div id="' + divId + '">' + spanName + spanDel + '</div>';
		}
		return divIH;
	}
	
	function delMngrItem(div2del)
	{
		let id2del = +(div2del.slice(div2del.lastIndexOf('_') + 1)); //в случае salons - sln_id, emails- номер элемента в массиве mngEmails
		let itemType = div2del.includes('sln') ? 'salons' : 'emails';
		if(itemType == 'emails') mngEmails.splice(id2del,1);
		else{
			for(var i=0; i<mngSalons.length; i++){
				if(mngSalons[i] == id2del){
					mngSalons.splice(i,1);
					for(var j=0;j<slnEmails.length;j++){
						if(slnEmails[j].sln_id == id2del){
							for(var k=0;k<mngEmails.length;k++){
								if(slnEmails[j].email == mngEmails[k]){
									var email2del = k;
									break;
								}
							}
							break;
						}
					}
					if(email2del >= 0){
						var dFl = false;
						for(var j=0;j<slnEmails.length;j++){
							if(mngSalons.includes(slnEmails[j].sln_id) && (slnEmails[j].sln_id != id2del) && (slnEmails[j].email == mngEmails[email2del])){
								dFl= true;
								break;
							}
						}
						if(!dFl) { //delMngrItem('mngr_email_' + email2del);
							if(confirm("Удалить также привязанный к салону электронный адрес?")) delMngrItem('mngr_email_' + email2del);
						} 
					}	
					break;
				}
			}
			fillMngSlnDD();
		}
		document.getElementById('active_' + itemType).innerHTML = getMngrItems(itemType);
	}
	
	function fillMngSlnDD()
	{
		let msd = document.getElementById('mngr_sln_dd');
		if(msd.options.length>1){
			while(msd.options.length>1){
				msd.options[1] = null;
			}
		}
		for(var i=0; i<salons.length; i++){
			var msFlag = true;
			for(var j=0;j<mngSalons.length;j++){
				if(salons[i].id == mngSalons[j]){
					msFlag = false;
					break;
				}
			}
			if(msFlag) msd.options[msd.options.length] = new Option(salons[i].name, 'val' + salons[i].id);
		}
	}
	
	function extAPL()
	{
		cbSetValue('is_active');
		uTypeSetVisible();
		let pMask = IMask(document.getElementById('phone'), {mask: '+{7}(000)000-00-00'});	
		return true;
	}

</script>