<script>
var orderId;

function ajaxRsrv(mthd, fID=null) //методы: rp_options, po_options
{
	var xhr = new XMLHttpRequest();
	xhr.open('POST', "<?=site_url('pohandler')?>", true);
	let qParams = 'method=' + mthd;
	let currVal;
	let inpVals = ['cstmr_surname', 'cstmr_name1', 'cstmr_name2', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_corp', 'dlvr_porch', 'dlvr_flat', 'dlvr_dmphn', 'dlvr_stage', 
			'phone1', 'phone2', 'email', 'summ_dlvr', 'dlvr_comment', 'summ_up', 'has_lift', 'count_dlvr', 'rsrv_is_example', 'dscnt_retail'];
	let dlvrTypes = ['salon', 'client', 'pickup'];			
	switch(mthd){
		case 'pre_save':
			for(var i in inpVals){
				currVal = document.getElementById(inpVals[i]) ? document.getElementById(inpVals[i]).value : 'NONE';
				if(!currVal) currVal = 'NONE';
				qParams += '&' + inpVals[i] + '=' + currVal;
				
			}
			for(i in dlvrTypes){
				if(document.getElementById(`dlvr_${dlvrTypes[i]}`).checked) qParams += '&dlvr_type=' + dlvrTypes[i];
			}
			qParams += '&order_id=' + orderId;
			console.log(qParams);
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
		RsrvRespHandler(jr);
	}
	xhr.send(qParams);
}

function RsrvRespHandler(rpObj)
{
	switch(rpObj.method){
		case 'pre_save':
			document.getElementById('print_link').click();
			break;
	}
}

function validateDlvr()
{
	return true;
	let outMsg = '';
	if(!document.getElementById('sln_order_id').value) outMsg = addToErrMsg(outMsg, 'Необходимо ввести номер заказа');
	return outMsg ? {type: 'error', text: outMsg} : true;
}
	
function checkDlvr()
{
	let msgVldt = validateDlvr();
	if(typeof(msgVldt) === 'boolean') return true;
	showMsg(msgVldt);
	return false;
}
	
function showDlvr()
{
	let dc = document.getElementById('dlvr_content');
	if(dc.style.display == 'none') dc.style.display = 'block';
	else dc.style.display = 'none';
}

function dropDlvr()
{
	document.getElementById('hidden_bg').style.display = 'none';
	document.getElementById('hidden_dlvr').style.display = 'none';
	document.getElementById('dlvr_salon').checked = true;
	document.getElementById('dlvr_address').style.display = 'none';
	clearDlvr();
}

function clearDlvr()
{
	let clntDlvr = 	document.getElementsByClassName('client_dlvr');
	for(var i in clntDlvr){
		if(['has_lift', 'count_dlvr'].includes(clntDlvr[i].id)){
			clntDlvr[i].checked = false;
			cbSetValue(clntDlvr[i].id);
		}
		else clntDlvr[i].value = '';
	}
}

function dlvrHandle(dlvr)
{
	document.getElementById(`dlvr_${dlvr.value}`).checked = true;
	clearDlvr();
	document.getElementById('dlvr_address').style.display = (dlvr.value == 'client') ? 'block' : 'none'; 
}

function setEL()
{
	var hdnOn = 0;
	let dDiv = document.getElementById('dlvr_dialog');
	let wDiv = document.getElementById('hidden_dlvr');
	document.addEventListener("keyup", function(event){
		if(event.key === 'Escape' && wDiv.style.display == 'block'){
			hdnOn = 0;
			dropDlvr();
		}
	});
	document.addEventListener("click", function(event){
		if((!dDiv.contains(event.target)) && wDiv.style.display == 'block'){
			if(hdnOn > 0){
				hdnOn = 0;
				dropDlvr();
			}
			else hdnOn++;
		}
	});
	document.getElementById('undo_hdn_dlvr').onclick = function(event){
		hdnOn = 0;
		dropDlvr();
	};
	
}

function dlvrValidate()
{
	let outMsg = '';
	if(document.getElementById('blank_upld').files.length == 0) outMsg = addToErrMsg(outMsg, 'Не загружен бланк заказа');
	return outMsg ? {type: 'error', text: outMsg} : true;
}

function dlvrConfirm()
{
	let msgVldt = dlvrValidate();
	if(typeof(msgVldt) === 'boolean') return confirm('Подтвердите оформление заказа');
	showMsg(msgVldt);
	return false;
}

function extAPL()
{
	orderId = +(window.location.href.slice(window.location.href.lastIndexOf('/') + 1));
	//setEL();
	let ciArr = ['name1', 'name2', 'surname'];
	for(var i in ciArr){
		document.getElementById(`cstmr_${ciArr[i]}`).addEventListener("input", function() {
			this.value = this.value[0].toUpperCase() + this.value.slice(1);
		});
	}
	let pIn = document.getElementsByClassName('phone_input');
	let pMasks = [];
	for(var i=0; i<pIn.length; i++){
		pMasks[i] = IMask(pIn[i], {mask: '+{7}(000)000-00-00'});
	}	
}

</script>