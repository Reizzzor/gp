<script>
	function afterPageLoad()
	{
		extAPL();
		document.addEventListener('keydown', function (event) {
			if (event.keyCode === 13 && event.target.nodeName === 'INPUT' && window.location.href.indexOf('log') == -1) {
				var form = event.target.form;
				var index = Array.prototype.indexOf.call(form, event.target);
				form.elements[index + 1].focus();
				event.preventDefault();
			}
		});
		var srvMsg = getMsg();		
		if(typeof(srvMsg) == 'object') showMsg(srvMsg);
		else deleteCookie('popup_msg');
	}
	
	window.onload = afterPageLoad();
</script>
