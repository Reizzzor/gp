function validateLoginForm()
	{
		var loginInput, passInput;
		var outMsg = '';
		loginInput = document.getElementById('login');
		passInput = document.getElementById('password');
		if(loginInput.value.length == 0){
			loginInput.value = '';
			outMsg = addToErrMsg(outMsg, 'Не введён логин');
		}
		else if(loginInput.value.length < 5){
			loginInput.value = '';
			outMsg = addToErrMsg(outMsg, 'Неверный логин');
		}
		if(passInput.value.length == 0){
			passInput.value = '';
			outMsg = addToErrMsg(outMsg, 'Не введён пароль');
		}
		else if(passInput.value.length < 8){
			passInput.value = '';
			outMsg = addToErrMsg(outMsg, 'Неверный пароль');
		}
		if(outMsg.length > 0){
			var oMsgObj = {};
			oMsgObj.type = 'error';
			oMsgObj.text = outMsg;
			return oMsgObj;
		}
		else return true;
	}
	
function saveChanges()
	{
		var msgVldt = validateLoginForm();
		//var fDiv = document.getElementById('editUserForm');
		if(typeof(msgVldt) === 'boolean') return true;
		//fDiv.action = 'javasript:void(0);';
		showMsg(msgVldt);
		return false;
	}
	
function extAPL()
	{
		return false;
	}
