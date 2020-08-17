<?php 
	if(isset($ext_script)) echo "<script src=\"$ext_script\"></script>\n"; 
?>
<script>
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
  setCookie(name, "", {
    expires: -1
  })
}	

function getSelectedValue(ddId)
{
	dd = document.getElementById(ddId);
	for(i = 0; i < dd.options.length; i++){
		if(dd.options[i].selected) return dd.options[i].value;
	}
}


function toggleSubMenu(smNum)
{
	var smShow;
	var sm = document.getElementById('submenu' + smNum);
	if(!sm.style.display || sm.style.display == 'none'){
		smShow = 1;
		sm.style.display = 'block';
	} 
	else{ 
		smShow = 0;
		sm.style.display = 'none';
	}
	setCookie('show_sbm' + smNum, smShow, {expires: 3600});
}
	
function viewPDF(fileLink)
	{
		var viewParams = {
			pdfOpenParams: {
				view: "FitV",
				pagemode: "thumbs"
			}
		}
		PDFObject.embed(fileLink, document.getElementById('viewer_show'), viewParams);
		document.getElementById('viewer').style.display = 'block';
		return true;
	}
	
function hideDiv(h_id)
	{
		document.getElementById(h_id).style.display = 'none';
		document.getElementById(h_id + '_close').style.display = 'none';
		return true;
		
	}
			
function getMsg()//from cookie
{
	var msgRaw = (getCookie('popup_msg')).replace( /\+/g, " ");
	if(msgRaw){
		deleteCookie('popup_msg');
		var msgObj = {};
		if(msgRaw.substr(0,2) === '!!'){
			msgObj.type = 'error';
			msgObj.text = msgRaw.substr(2);
		}
		else{
			msgObj.type = 'info';
			msgObj.text = msgRaw;
		}
		return msgObj;
	}
	return 0;
}
		
function hideMsg(hmd)
{
	hmd.style.display = 'none';
	hmd.innerHTML = '';
}
		
function showMsg(msgo)
{
	var msgDiv = document.getElementById('message');
	if(msgDiv.className != msgo.type) msgDiv.className = msgo.type;
	msgDiv.innerHTML = msgo.text;
	msgDiv.style.display = 'block';
	setTimeout(hideMsg, 5000, msgDiv);		
}

function addToErrMsg(em, ea)
{
    if(em.length > 0 && ea.length > 0) return (em + "<br />" + ea);
    else if(ea.length > 0) return ea;
    else return '';
}

						
var y = getCookie('scr_y');
var real_y = window.innerHeight;
if(!y || y != real_y){
	y = setCookie('scr_y', real_y, {expires: 3600});
	if(window.location.pathname.indexOf('login') == -1) location.reload();
}				
</script>