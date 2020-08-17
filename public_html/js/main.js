// возвращает cookie с именем name, если есть, если нет, то undefined
function getCookie(name) {
  var matches = document.cookie.match(new RegExp(
    "(?:^|; )" + name.replace(/([\.$?*|{}\(\)\[\]\\\/\+^])/g, '\\$1') + "=([^;]*)"
  ));
  return matches ? decodeURIComponent(matches[1]) : undefined;
}

// устанавливает cookie с именем name и значением value
// options - объект с свойствами cookie (expires, path, domain, secure)
function setCookie(name, value, options) {
  options = options || {};

  var expires = options.expires;

  if (typeof expires == "number" && expires) {
    var d = new Date();
    d.setTime(d.getTime() + expires * 1000);
    expires = options.expires = d;
  }
  if (expires && expires.toUTCString) {
    options.expires = expires.toUTCString();
  }

  value = encodeURIComponent(value);

  var updatedCookie = name + "=" + value;

  for (var propName in options) {
    updatedCookie += "; " + propName;
    var propValue = options[propName];
    if (propValue !== true) {
      updatedCookie += "=" + propValue;
    }
  }

  document.cookie = updatedCookie;
}

// удаляет cookie с именем name
function deleteCookie(name) {
	setCookie(name, "", {expires: -1});
}	

function cookiesRequest(mthd, argObj=null)
{
	let xhr = new XMLHttpRequest();
	console.log(getCookie('ch_path'));
	xhr.open('POST', getCookie('ch_path'), true);
	let qParams = 'method=' + mthd;
	if(mthd == 'screen_size') qParams += '&scr_x=' + argObj.scr_x + '&scr_y=' + argObj.scr_y;
	else if(mthd == 'toggle_submenu') qParams += '&menu_id=' + argObj.menu_id + '&state=' + argObj.state;
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
		cookiesResponse(jr);
	}
	xhr.send(qParams);
}

function cookiesResponse(cr)
{
	console.log(JSON.stringify(cr));
	if(cr.method == 'screen_size'){
		setCookie('scr_y', cr.scr_y, {expires: 3600});
		setCookie('scr_x', cr.scr_x, {expires: 3600});
		window.location.reload();
	}
	//else if(cr.method == 'toggle_submenu') setCookie('show_sbm' + cr.menu_id, cr.state, {expires: 3600});
}

function getSelectedValue(ddId)
{
	let dd = document.getElementById(ddId);
	return dd.options[dd.selectedIndex].value;
}

function fillOptions(dd2fill, optsArr) //заполняемый DD, массив значений вида [{id: +(id), name: 'name'}, {}...]
{
	let rsltObj = {'id': 0}; //возвращаем инфу о выбранном элементе, в случае, если выбранным делаем не val0 - допишем инфу в новые поля
	let slctdVal = +(getSelectedValue(dd2fill.id).substring(3)); 
	while(dd2fill.options.length > 1){
		dd2fill.options[1] = null;
	}
	for(i in optsArr){
		dd2fill.options[dd2fill.options.length] = new Option(optsArr[i].name, 'val' + optsArr[i].id);
		if(slctdVal == optsArr[i].id) rsltObj = Object.assign({}, Object.assign(optsArr[i], {'sIndex': dd2fill.options.length - 1}));		
	}
	if(rsltObj.sIndex) dd2fill.selectedIndex = rsltObj.sIndex;
	else if(dd2fill.id == 'slnDD') document.getElementById('slnInput').value = '';
	else if(dd2fill.id == 'ka4slnDD'){
		document.getElementById('ka4slnInput').value = '';
		document.getElementById('k4sINN').value = '';
	}
	return rsltObj;
}

function setDDValue(ddid2set, val2set)
{
	let dd2set = document.getElementById(ddid2set);
	for(var i=0; i<dd2set.options.length; i++){
		if(dd2set.options[i].value.substring(3) == val2set){
			dd2set.selectedIndex = i;
			break;
		}
	}
}

function roundNumber(a, n)
{
	return +(a.toFixed(n));
}

function toggleSubMenu(smNum)
{
	let smShow;
	let sm = document.getElementById('submenu' + smNum);
	if(!sm.style.display || sm.style.display == 'none'){
		smShow = '1';
		sm.style.display = 'block';
	} 
	else{ 
		smShow = '0';
		sm.style.display = 'none';
	}
	cookiesRequest('toggle_submenu', {menu_id: smNum, state: smShow});
}

function toggleDrpTm(n)
{
	let tbl = document.getElementById('drp_colls' + n);
	tbl.style.display = (tbl.style.display == 'none') ? 'table' : 'none';
}
				
function getMsg()//from cookie
{
	let msgRaw = getCookie('popup_msg');
	if(!msgRaw) return 0;
	msgRaw = msgRaw.replace( /\+/g, " ");
	deleteCookie('popup_msg');
	return msgRaw.substr(0,2) === '!!' ? {type: 'error', text: msgRaw.substr(2)} : {type: 'info', text: msgRaw};
	
}
		
function hideMsg(hmd)
{
	hmd.style.display = 'none';
	hmd.innerHTML = '';
}
		
function showMsg(msgo)
{
	let msgDiv = document.getElementById('message');
	msgDiv.className = msgo.type;
	msgDiv.innerHTML = msgo.text;
	msgDiv.style.display = 'block';
	setTimeout(hideMsg, 5000, msgDiv);		
}

function addToErrMsg(em, ea)
{
    if(em  && ea ) return (em + "\n" + ea);
    else return ea ? ea : '';
}

function cbSetValue(cbId)
{
	let cb = document.getElementById(cbId);
	cb.value = cb.checked ? 1 : 0;
}


function setCellsWidth(tRows)
{
	let cNum = tRows[0].cells.length;
	for(var i = 0; i < cNum; i++){
		var wTH = tRows[0].cells[i].clientWidth - 10;
		var wTD = tRows[0].cells[i].clientWidth - 10;
		if (wTH > wTD) tRows[1].cells[i].style.width = wTH + "px";
		else tRows[0].cells[i].style.width = wTD + "px";
	}
}
	
function resizeRows(rTbl)
{
	let rI = 1;
	while(rTbl.rows[rI].style.display == 'none')
	{
		rI++;
	}
	let scrlW = rTbl.tHead.clientWidth  - rTbl.tBodies[0].clientWidth;
	let colNum = rTbl.rows[0].cells.length;
	for(var i = 0; i < colNum; i++){
		var clW = rTbl.rows[0].cells[i].clientWidth - 10;
		if(i == (colNum - 1)) clW -= scrlW;
		rTbl.rows[rI].cells[i].style.width = clW + "px";
	}
}

function soidCheck(){
	let soid = document.getElementById('sln_order_id');
	while(soid.value.indexOf('.') !== -1){
		soid.value = soid.value.replace('.', '');
	}
}

function outSumm(summ)
{
	let inpt = String(summ).split('.');
	let res = '';
	let iLen = inpt[0].length;
	if(iLen <= 3) res = inpt[0];
	else{
		for(var i=0; i<iLen; i++){	
			if(i>0 && iLen%3 == i%3) res += ' ';
			res += inpt[0][i];
		}
	}
	return inpt.length > 1 ? res + ',' + inpt[1] : res;
}

function tabOnEnter() {
        if (event.keyCode==13) 
        {
            event.keyCode=9; 
	    return event.keyCode;
        }
}