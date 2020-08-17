<script>
	var justLoaded = false;
	var busyDD = []; //ID DD, которые заполняются при помощи ajax-запросов и нуждаются в последующей обработке
	var poId;
	<?php if(isset($po_id)) echo "poId = $po_id;\n justLoaded = true;"; ?>
	var consistSumm = <?=isset($consist_summ) ? $consist_summ : 0 ?>;
	var dlvri = <?=isset($dlvr_type) ? "'".$dlvr_type."'" : "'salon'" ?>;
	
	function ajaxPO(mthd, fID) //методы: rp_options, po_options
	{
		var xhr = new XMLHttpRequest();
		xhr.open('POST', "<?=site_url('pohandler')?>", true);
		let qParams = 'method=' + mthd;
		switch(mthd){
			case 'del_xtra_file':
				qParams += '&f_name=' + fID + '&po_id=' + poId;
				break;
			case 'get_address':
				qParams += '&rpo_id=' + fID;
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
		switch(rpObj.method){
			case 'del_xtra_file':
				if(/\d+_\d+_dlvr_list\.pdf/.test(rpObj.deleted_file)){
					document.getElementById('xtra_filelist').innerHTML = '';
					document.getElementById('xtra_filelist_title').innerHTML = 'Доставочный лист не загружен';
					document.getElementById('xtra_file_upld').style.display = 'inline-block';
				} 
				else{
					var entryID = rpObj.deleted_file.slice(rpObj.deleted_file.lastIndexOf('_') + 1, -4);
					if(rpObj.deleted_file.indexOf('drp') != -1) entryID = `_drp${entryID}`;
					document.getElementById('curr_xfile' + entryID).innerHTML = '';
					document.getElementById('xtra_file' + entryID).style.display = 'inline-block';
				}
				showMsg({type: 'info', text: `Файл ${rpObj.deleted_file} был успешно удалён`});
				break;
			case 'get_address':
				console.log(JSON.stringify(rpObj));
				break;
		}
	}
	
	function dlvrHandle(dlvr)
	{
		document.getElementById(`dlvr_${dlvr.value}`).checked = true;
		dlvri = dlvr.value;
		let adr = document.getElementById('dlvr_address');
		if(dlvr.value == 'client') adr.style.display = 'block'; 
		else{
			var clntDlvr = 	document.getElementsByClassName('client_dlvr');
			for(var i in clntDlvr){
				if(['has_lift', 'count_dlvr'].includes(clntDlvr[i].id)){
					clntDlvr[i].checked = false;
					cbSetValue(clntDlvr[i].id);
				}
				else if(clntDlvr[i].id == 'dlvr_comment') continue;
				else clntDlvr[i].value = '';
			}
			adr.style.display = 'none';
		}
	}
	
	function countFinalPrice()
	{
		if(!document.getElementById('preorder_summ').innerHTML) return 0;
		let newSumm = consistSumm;
		if(document.getElementById('count_dlvr').checked){
			if(parseFloat(document.getElementById('summ_up').value)) newSumm += parseFloat(document.getElementById('summ_up').value);
			if(parseFloat(document.getElementById('summ_dlvr').value)) newSumm += parseFloat(document.getElementById('summ_dlvr').value);	
		}
		document.getElementById('po_summ_val').innerHTML = outSumm(newSumm);
	}

	
	function fillAddress()
	{
		let confirmFill = false;
		if(document.getElementById('rsoidDD').selectedIndex > 0) confirmFill = confirm('Использовать адрес из связанного заказа?');
		if(confirmFill) ajaxPO('get_address', getSelectedValue('rsoidDD').substring(3)); 
	}
	
	function addPOEntry()
	{
		document.getElementById('adding_poe').value = 'ap';
		document.getElementById('editPreorderForm').submit();
		
	}
	
	function editPOEntry(poeID)
	{
		document.getElementById('adding_poe').value = 'ep' + poeID;
		document.getElementById('editPreorderForm').submit();
		
	}
	
	function delPOEntry(poeID)
	{
		document.getElementById('adding_poe').value = 'dp' + poeID;
		document.getElementById('editPreorderForm').submit();
		
	}
	
	function addPOEDrapery()
	{
		document.getElementById('adding_poe').value = 'ad';
		document.getElementById('editPreorderForm').submit();
		
	}
	
	function editPOEDrapery(poeID)
	{
		document.getElementById('adding_poe').value = 'ed' + poeID;
		document.getElementById('editPreorderForm').submit();
		
	}
	
	function delPOEDrapery(poeID)
	{
		document.getElementById('adding_poe').value = 'dd' + poeID;
		document.getElementById('editPreorderForm').submit();
		
	}
	
	function validatePreOrder()
	{
		let outMsg = '';
		if(!document.getElementById('sln_order_id').value) outMsg = addToErrMsg(outMsg, 'Необходимо ввести номер заказа');
/*		let inputs = document.querySelectorAll('input[type="file"]');
		for(var i=0;i<inputs.length;i++){
			if(inputs[i].files.length == 0 && inputs[i].style.display != 'none'){
				console.log(`${inputs[i].id}: ${inputs[i].style.display}`);
				outMsg = addToErrMsg(outMsg, 'Не загружен(-ы) бланк(-и)');
				break;
			} 
		}*/
		return outMsg ? {type: 'error', text: outMsg} : true;
	}
	
	function saveChanges()
	{
		let msgVldt = validatePreOrder();
		if(typeof(msgVldt) === 'boolean') return true;
		showMsg(msgVldt);
		return false;
	}
	
	function xfUpldHandle(xf)
	{
		let xft = document.getElementById('xtra_filelist_title');
		xft.style.display = xf.value ? 'none' : 'block';
	}
	
	function extAPL()
	{
		let ciArr = ['name1', 'name2', 'surname'];
		for(var i in ciArr){
			document.getElementById(`cstmr_${ciArr[i]}`).addEventListener("input", function() {
				this.value = this.value[0].toUpperCase() + this.value.slice(1);
			})
		}
		let t = document.getElementById('po_consist_table');
		let clH = document.body.clientHeight;
		if(t){	
			if((clH - 60 ) < t.offsetHeight) t.tHead.style.position = 'sticky';
			resizeRows(t);
		}
		dlvrHandle({value: dlvri});
		let pIn = document.getElementsByClassName('phone_input');
		let pMasks = [];
		for(var i=0; i<pIn.length; i++){
			pMasks[i] = IMask(pIn[i], {mask: '+{7}(000)000-00-00'});
		}
		var soidMask = IMask(document.getElementById('sln_order_id'), {mask: /^[а-яА-Я0-9]+$/});
		justLoaded = false;
	}
</script>