<html>
	<!--<meta name="viewport" content="width=device-width, initial-scale=1"> -->
	<head>
		<?=$css_link?>
		<?=$ext_js?>
		<title>Авторизация</title>
	</head>
	<body>
		<div class="container">
			<div class="imgcontainer">
				<a href="http://geniuspark.ru/" target="_blank"> <img src="http://geniuspark.ru/images/Logo_gp2_5v9z-r3.jpg" /> </a> </div>
			<div class='login_form'>
				<lable><p>Вход в личный кабинет</p></lable>
				<?=form_open('login/submit','method="post" id="loginForm" onSubmit="var okF=saveChanges(); return okF;"')?>
				<input type="text" name="username" id="login" placeholder="Логин" /><br /><br />
				<input type="password" name="password" id="password" placeholder="Пароль" /><br /><br />
				<input type="submit" value=" Войти " name="submit" />	
				<?=form_close('<br />')?>
			</div>
			<div id="message" style="display: none"></div>
		</div>		
	</body>
	<?=$js_onload?>
</html>
