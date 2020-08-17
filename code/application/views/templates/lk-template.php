<html>
	<head>
		<?=$css_link?>
		<?=$css_link2?>
		<?=$ext_js?>
		<?=$js?>
		<title><?=$title?></title>
	</head>
	<?php if(!isset($cmpny_name)) $cmpny_name = '';?>
	<body>
		<div id='container'>
			<div id='header'>
				<div id='cmpny_name'><?=$cmpny_name?></div>
				<div id='search_form'>
					<?php echo form_open("$searchlink");?>
						<input type='text' name='search' id='search' placeholder="&nbsp;Поиск" />
						<!--<input type="image" src= alt="Поиск" />-->
						<!--<input type="submit" value="Поиск" name="submit" /> -->
						<?php $sbtn_params = array('name'=>'submit', 'type'=>'submit', 'content'=>'<i class="fa fa-search"></i>');
						      echo form_button($sbtn_params);?>
					<?=form_close()?>
				</div>
				<div id='user_exit'><?=$user_name?> <?=$a_logout?></div><br />
			</div>
			<div class='menu' id='main_menu'>
				<?php foreach($menu_list as $menu_item): ?>
					<p><?=$menu_item?></p>
				<?php endforeach; ?>
				<div id="contacts">
					<div>Тех. поддержка Личного Кабинета:</div>
					<div>e-mail: <span>lk@geniuspark.ru</span></div>
					<div>тел: <span>+7(925)310-64-11</span></div>
					<div>С 10:00 до 20:00(МСК) ежедневно</div>
				</div>
			</div>
			<div id='workflow'>
				<?=$content?>
			</div>
			<div id='message' class='info' style='display: none'></div>
		</div>
	</body> 
	<?=$js_onload?>
</html>

