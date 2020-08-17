<?php

class Admin extends LK_Controller {

	protected $usr_roles = array('admin' => 'Администратор', 'kagent' => 'Контрагент', 'salon' => 'Салон', 'manager'=>'Управляющий', 'store'=>'Склад');

	public function __construct()
	{
		parent::__construct();
		$this->check_user_type('admin');
		$this->data['title'] = 'Административный кабинет';		
		$this->data['cmpny_name'] = 'Административный кабинет';
		$this->data['user_name'] = '[login:'.$this->session->login .']';
		if($this->session->user_id != 79){
			$this->data['menu_list'] = array(anchor('admin/users', '<i class="fa fa-address-book"></i>&nbsp;Пользователи'),
		                                 anchor('admin/orders', '<i class="fa fa-edit"></i>&nbsp;Редактор заказов'));
			$this->data['orders_url'] = 'admin/orders';
		}
		else $this->data['menu_list'] = array();
		$this->_make_upload_sbm();
		$this->make_study_menu();		
		$this->data['menu_list'] = array_merge($this->data['menu_list'], [anchor('admin/drapery', '<i class="fas fa-file" aria-hidden="true"></i>&nbsp;Разбивка тканей'), anchor('admin/storage', '<i class="fas fa-warehouse" aria-hidden="true"></i>&nbsp;Склад')]);
	}	

//private functions

	private function _make_upload_sbm()
	{
		$submenu = array();
		$submenu[] = anchor('admin/docs_edit', 'Документы');
		$submenu[] = anchor('admin/imgs_edit', 'Изображения');
		$submenu[] = anchor('admin/video_edit', 'Видео');
		$this->data['menu_list'][] = anchor('', '<i class="fas fa-tasks" aria-hidden="true"></i>&nbsp;Редактор учебных материалов', 'onClick="toggleSubMenu(1); return false;"');
		$this->data['menu_list'][] = ul($submenu, array('id'=>'submenu1', 'style'=>"display: {$this->submenu_display_mode('show_sbm1')}"));
	}
	
	private function _make_dd_menu($mode, $fltr=NULL)
	{
		$got_array = $this->model->get_alter_items($mode, $fltr);
		$res_array = array('val0'=>'Выбрать');
		foreach($got_array as $i){
			$res_array['val'.$i['id']] = $i['name'];
		}
		return $res_array;
	}
	
	private function _get_user_settings($u_id)
	{
		$u_settings = array();
		$usr_info = $this->model->get_users($u_id);
		$u_settings['is_active'] = $usr_info['is_active'] ? 1 : 0;
		foreach(['login', 'user_type', 'email'] as $i){
			$u_settings[$i] = $usr_info[$i];
		}
		if($usr_info['user_type'] === 'salon'){
			$salon = $this->model->get_salons($u_id, 'user_id');
			if(!empty($salon)){
				$u_settings['slnInput'] = $salon['name'];
				$u_settings['sln_id'] = $salon['id'];
				$u_settings['address'] = $salon['address'];
				$u_settings['prefix'] = $salon['prefix'];
				$u_settings['phone'] = $salon['phone'];
				$u_settings['mailto'] = $salon['mailto'];
				foreach(['hide_archive', 'show_preorders', 'hide_fin_part'] as $i){
					$u_settings[$i] = $salon[$i] ? 1 : 0;
				}
			}
			if($salon['kagent_id']){ 
				$kagent = $this->model->get_kagents($salon['kagent_id']);
				if(!empty($kagent)){
					$u_settings['ka4slnInput'] = $kagent['name'];
					$u_settings['k4sINN'] = $kagent['inn'];
					$u_settings['kagent_id'] = $kagent['id'];
				}
			}
		}
		$mngr_info = $this->model->get_mngr_info($u_id);
		$u_settings['emails'] = $mngr_info['emails'];
		$u_settings['sln_ids'] = $mngr_info['sln_ids'];
		return $u_settings;
	}
	
	private function _correct_upld($db, $vals_arr)
	{
		$srch_res = $this->model->search_file_id($db, $vals_arr);
		if(!$srch_res || !is_file($vals_arr['link']) || (isset($srch_res['dir_path']) && !is_dir($srch_res['dir_path']))){
			if(file_exists($vals_arr['link'])) unlink($vals_arr['link']);
			return 6;
		}
		$ex_src = explode('.', $srch_res['src']);
		$ex_link = $srch_res['dir_path'].$srch_res['src'];
		$new_src = $srch_res['aggr_id'].'_'.$srch_res['id'].'.'.$ex_src[1];
		$new_link = $srch_res['dir_path'].$new_src;
		rename($ex_link, $new_link);
		if(strstr($vals_arr['link'], 'imgs')) rename($this->preview_link($ex_link), $this->preview_link($new_link));
		$this->model->upd_tutorial($db, $srch_res['id'], array('src'=>$new_src));
		return 0;
	}
		
	private function _ctgr_dd_params($upld_type)
	{
		$p_arr = array('val0'=>'Выберите раздел');
		$aa = $this->model->pathman();
		foreach($aa as $i){
			if(strrpos($i['path_dir'], $upld_type) === FALSE) continue;
			$p_arr['val'.$i['id']] = $i['category'];
		}
		return $p_arr;
	}
	
	private function _make_preview($orig, $nh=65)   //указывать пут к оригиналу
	{
		$p_name = $this->preview_link($orig);
		if(file_exists($p_name)) unlink($p_name);
		//echo $p_name.'<br />';
		$config = array();
		$config['image_library'] = 'gd2';
		$config['source_image'] = $orig;
		$config['new_image'] = $p_name;
		$config['height'] = $nh;
		$config['maintain_ratio'] = TRUE;
		$this->load->library('image_lib', $config);
		$ir = $this->image_lib->resize();
		if(!$ir) $this->image_lib->display_errors();
	}
	
	private function _make_video_list($va)
	{
		$this->ext_js = array_merge($this->ext_js, ['player_api.js', 'img_viewer.js']);
		$vl = '<div id="video2edit">';
		foreach($va as $i){
			$descr = '<div id="doc_'.$i['id'].'">'.$i['name'].'</div>';
			$added = "<div>[Добавлен {$i['added']}]</div>";
			$pos = $this->model->get_dseqs('video', $i['aggr_id']) - $i['dspl_id'];
			$ctgr = "<div>{$this->model->pathman($i['aggr_id'])['category']}(#<span id=\"dspl_{$i['id']}\">$pos</span>)</div>";
			$edit = anchor('', 'Редактировать', "onClick=\"editUpld({$i['id']}, 'val{$i['aggr_id']}'); return false;\"");
			$vl.= "<div id=\"link_{$i['id']}\">".anchor('', img($this->youtube_link($i['src'])), "onClick=\"viewVideo('{$this->youtube_link($i['src'], 'embed')}'); return false;\"");
			$vl.= "<div class=\"video_info1\">{$descr}{$edit}</div><div class=\"video_info2\">{$added}{$ctgr}</div></div>";
		}
		return $vl.'</div>';
	}

	private function _upld_edit($v_arr)
	{
		$js_upld_params = array('del_url'=>site_url('admin/del_'.$v_arr['u_type']).'/', 'upd_url'=>site_url('admin/'.$v_arr['u_type'].'_submit').'/', 'dseqs_str'=>"'".json_encode($this->model->get_dseqs($v_arr['u_type']))."'");
		$this->data['js'] = $this->load->view('js/js_upldedit', $js_upld_params, TRUE);	
		$v_arr['dd_arr'] = $this->_ctgr_dd_params($v_arr['u_type']);
		$this->data['content'] = $this->load->view('admin/howto_editor/docs_edit', $v_arr, TRUE);
		$this->load_lk_view();
	}
	
	private function _upld_submit($v_arr)
	{
		$this->lang->load('upload_lang', 'russian');
		$name = $this->input->post('upld_file_name');
		$aggr_id = substr($this->input->post('ctgr_dd'), 3);
		$d_pos = substr($this->input->post('dspl_dd'), 3); // это не dspl_id, а порядковый номер 
		$to_upd = array();
		$config['allowed_types'] = $v_arr['f_types'];
		$config['overwrite'] = TRUE;
		if(!isset($v_arr['upld_id'])){	//передаём d_pos если он не дефолтный
			$fn_arr = $this->model->get_filesrc($aggr_id, $v_arr['u_type']);
			$config['upload_path'] = 'tutorials/'.$fn_arr[0];
			$config['file_name'] = $aggr_id.'_'.$fn_arr[1];
			$to_upd = array('aggr_id'=>$aggr_id, 'name'=>$name, 'src'=>$config['file_name']);
			if($d_pos != 1) $to_upd['d_pos'] = $d_pos; 
		}
		else{ ////передаём d_pos если он поменялся
			$upld_arr = $this->model->get_tutorial($v_arr['u_type'], $v_arr['upld_id']);
			if($name != $upld_arr['name']) $to_upd['name'] = $name;
			if($aggr_id != $upld_arr['aggr_id']){
				 $new_path = 'tutorials/'.$this->model->pathman($aggr_id)['path_dir'];				
				 $to_upd['aggr_id'] = $aggr_id;
				 $ex_src = explode('.', $upld_arr['src']);
				 $to_upd['src'] = $aggr_id.'_'.$v_arr['upld_id'].'.'.$ex_src[1];
				 $config['file_name'] = $to_upd['src'];
				 $config['upload_path'] = $new_path;
				 $to_upd['d_pos'] = $d_pos; 
			}
			else{
				$dseq = $this->model->get_dseqs($v_arr['u_type'], $aggr_id);
				if($upld_arr['dspl_id'] != $dseq - $d_pos) $to_upd['d_pos'] = $d_pos;
				$config['upload_path'] = $upld_arr['dir_path'];
				$config['file_name'] = $upld_arr['src'];
			}
		}
		if($_FILES['fileUpload']['error'] == 4){
			if(!empty($to_upd)){
				if(isset($to_upd['src'])){
					$new_link = $new_path.$to_upd['src'];
					rename($upld_arr['link'], $new_link);
					if($v_arr['u_type'] == 'imgs') rename($this->preview_link($upld_arr['link']), $this->preview_link($new_link));
				} 
				$upd_res = $this->model->upd_tutorial($v_arr['u_type'], $v_arr['upld_id'], $to_upd);
				if(!$upd_res) $upl_msg = 'Данные обновлены';
			}
			if(isset($upl_msg)) set_cookie('popup_msg', $upl_msg);
			return false;
		}
		$this->load->library('upload', $config);
		$u_res = $this->upload->do_upload('fileUpload');
		if(!$u_res) $upl_msg = '!!Ошибка при загрузке: '.$this->upload->display_errors();
		else{
			$upd_res = 0;
			if($v_arr['u_type'] == 'imgs') $this->_make_preview($this->upload->data('full_path'));
			if(!isset($v_arr['upld_id'])){
				$to_upd['src'] = $this->upload->data('file_name');
				$upd_res = $this->model->add_tutorial($v_arr['u_type'], $to_upd, $fn_arr[1]);
				if($upd_res == 9){
					 $to_upd['link'] = $this->upload->data('full_path');
					 $corr_res = $this->_correct_upld($v_arr['u_type'], $to_upd);
					 if(!$corr_res) $upd_res = 0;
				}
			}
			elseif(!empty($to_upd)){
				if(isset($to_upd['src'])) unlink($upld_arr['link']);
				$upd_res = $this->model->upd_tutorial($v_arr['u_type'], $v_arr['upld_id'], $to_upd);
			}
			$upl_msg = $upd_res ? '!!Ошибка при загрузке' : 'Загрузка успешно завершена';
		}
		set_cookie('popup_msg', $upl_msg);
	}
	
//Обработчики AJAX
	public function sln_info_handler() //параметры передаются в POST, method - обязательный
	{
		$resp = array('code'=>0);
		$p_arr = $this->input->post();
		if(!isset($p_arr['method'])) $resp = array('code'=>20, 'message'=>'Отсутствует обязательный параметр "method" в запросе');
		else{
			if($p_arr['method'] == 'kagents'){
				$id_type = 'sln_id';
				if(isset($p_arr[$id_type])){
					$sln = $this->model->get_salons($p_arr[$id_type]);
					if(!empty($sln)) $resp['sln_name'] = $sln['name'];
				}
			} 
			elseif($p_arr['method'] == 'salons'){
				$id_type = 'kagent_id';
				if(isset($p_arr[$id_type])){
					$ka = $this->model->get_kagents($p_arr[$id_type]);
					if(!empty($ka)){
						$resp['ka_name'] = $ka['name'];
						$resp['inn'] = $ka['inn'];
					} 
				}
			}
			elseif($p_arr['method'] == 'get_sln_stuff') $resp['stuff'] = $this->model->get_sln_stuff($p_arr['sln_id']);
			elseif($p_arr['method'] == 'del_sln_stuff'){
				$this->model->del_sln_stuff($p_arr['id']);
				$resp['stuff'] = $this->model->get_sln_stuff($p_arr['sln_id']);
			}
			elseif($p_arr['method'] == 'add_sln_stuff'){
				$this->model->add_sln_stuff($p_arr['sln_id'], urldecode($p_arr['name']));
				$resp['stuff'] = $this->model->get_sln_stuff($p_arr['sln_id']);
			}
			else $resp = array('code'=>21, 'message'=>'Неизвестное или неправильное имя метода');
			$resp['method'] = $p_arr['method'];
			if(isset($id_type) && in_array($p_arr['method'], ['kagents', 'salons'])){
				if(!isset($p_arr[$id_type])) $p_arr[$id_type] = NULL;
				$resp[$p_arr['method']] = $this->model->get_alter_items($p_arr['method'], $p_arr[$id_type]);
			}
		}
		echo json_encode($resp);
	}	
	
//public functions

	public function index()
	{
		if($this->session->user_id != 79) $this->users();
		else{
			$this->data['content'] = heading('Административный кабинет тренера', 2);
			$this->load_lk_view();	
		}
	}

	public function orders($page = 0)
	{
		$orders_raw = $this->model->get_orders();
		$orders = $this->pgnt_table($orders_raw, 'admin/orders/page', 4, $page);
		$this->make_orders_table($orders);
	}

	public function users()
	{
		$this->data['js'] = $this->load->view('js/js_order_info', array('url_info'=>site_url('admin/users/edit').'/', 'table_id'=>'users_table'), TRUE);
		$users = $this->model->get_users();
		$usr_rows = [];
		foreach($users as $user){
			$u_role = $this->usr_roles[$user['user_type']];
			$u_active = $this->get_yes_or_no($user['is_active']);
			$user['name'] = $user['user_type'] === 'salon' ? $this->model->get_salons($user['id'], 'user_id')['name'] : ''; 
			if(!isset($user['email'])) $user['email'] = '';
			$usr_rows[] = [$user['id'], $user['name'], $user['login'], $u_role, $user['email'], $u_active];
		}
		$cu_btn = $this->make_user_button('right', 'createuser', 'Создать нового пользователя', 'location.href=\'/admin/users/create\';');
		$usr_table = $this->make_sticky_table('users_table', ["Id", "Название", "Логин", "Роль", "E-mail", "Включен"], $usr_rows, 143);
		$this->data['content'] = $this->load->view('admin/users', array('crb'=>$cu_btn, 'usr_table'=>$usr_table), TRUE);
		$this->load_lk_view();		
	}

	public function edit_user($uid)
	{
		if($this->session->has_userdata('user_settings')) $this->session->unset_userdata('user_settings');
		$user_settings = $this->_get_user_settings($uid);
		$this->session->user_settings = $user_settings;
		$user_settings['id'] = $uid;
		$this->data['user_id'] = $uid;
		$this->data['is_create'] = FALSE;
		$user_settings['is_create'] = FALSE;
		$user_settings['labels'] = array('Роль пользователя:', 'Включен:', 'Логин:', 'E-mail пользователя:', 'Пароль:', '');
		$user_settings['user_options'] = array(
			$this->usr_roles,
			array('name' => 'is_active', 'id' => 'is_active', 'checked' => (int)$user_settings['is_active'], 'value' => $user_settings['is_active'], 'onChange' => 'cbSetValue(\'is_active\')'),
			array('name' => "login", 'id' => "login", 'value' => $user_settings['login']),
			array('name' => 'email', 'id' => 'email', 'value' => $user_settings['email']),
			array('name' => "pass1", 'id' => "pass1"),
			array('name' => "pass2", 'id' => "pass2"));
		$this->data['sln_emails_str'] = "'".json_encode($this->model->get_sln_emails())."'";
		if(!empty($user_settings['emails'])) $this->data['mng_emails_str'] = "'".json_encode($user_settings['emails'])."'";
		if(!empty($user_settings['sln_ids'])) $this->data['mng_salons_str'] = "'".json_encode($user_settings['sln_ids'])."'";
		$user_settings['salons'] = $this->_make_dd_menu('salons', isset($user_settings['kagent_id']) ? $user_settings['kagent_id'] : NULL);
		$user_settings['kagents'] = $this->_make_dd_menu('kagents', isset($user_settings['sln_id']) ? $user_settings['sln_id'] : NULL);
		$this->ext_js[] = 'phone_mask.js';
		$this->data['js'] = $this->load->view('js/js_edit_user', $this->data, TRUE);
		$this->data['content'] = $this->load->view('admin/useredit', $user_settings, TRUE);
		$this->load_lk_view();		
	}
	
	public function create_user()
	{
		$mngr_emails = $this->model->get_sln_emails();
		$user_info = array();
		$this->data['is_create'] = TRUE;
		$user_info['labels'] = array('Роль пользователя:', 'Включен:', 'Логин:', 'E-mail пользователя:', 'Пароль:', '');
		$user_info['user_options'] = array(
			$this->usr_roles,
			array('name' => 'is_active', 'id' => 'is_active', 'checked' => TRUE, 'value'=> 1, 'onchange' => 'cbSetValue(\'is_active\')'),
			array('name' => "login", 'id' => "login", 'placeholder'=>"Придумайте логин"),
			array('name' => 'email', 'id' => 'email', 'placeholder'=>"example@geniuspark.ru"),
			array('name' => "pass1", 'id' => "pass1", 'placeholder'=>"Придумайте пароль"),
			array('name' => "pass2", 'id' => "pass2", 'placeholder'=>"Пароль ещё раз"));
		$this->data['sln_emails_str'] = "'".json_encode($mngr_emails)."'";
		$user_info['salons'] = $this->_make_dd_menu('salons');
		$user_info['kagents'] = $this->_make_dd_menu('kagents');
		$user_info['is_create'] = TRUE;
		$this->ext_js[] = 'phone_mask.js';
		$this->data['js'] = $this->load->view('js/js_edit_user', $this->data, TRUE);
		$this->data['content'] = $this->load->view('admin/useredit', $user_info, TRUE);
		$this->load_lk_view();		
	}
	
	public function submit_user($uid = NULL)
	{
		$val_arr = ['user_type', 'login', 'is_active'];
		$submit_data = array();
		$p_arr = $this->input->post();
		if($p_arr['user_type'] != 'manager') $val_arr[] = 'email';
		else{
			$p_arr['mngr_hidden'] = json_decode($p_arr['mngr_hidden'], TRUE);
			foreach(['sln_ids', 'emails'] as $i){
				$submit_data[$i] = $p_arr['mngr_hidden'][$i];
			}
		} 
		if($p_arr['user_type'] == 'salon'){
			$val_arr = array_merge($val_arr, ['slnInput', 'ka4slnInput', 'hide_archive', 'address', 'prefix', 'phone', 'mailto', 'show_preorders', 'hide_fin_part']);
			foreach(['hide_archive', 'show_preorders', 'hide_fin_part'] as $i){
				if(!isset($p_arr[$i])) $p_arr[$i]=0;
			}
			$new_phone = isset($p_arr['phone']) ? preg_replace('/\D/', '', $p_arr['phone']) : NULL;
			$p_arr['phone'] = $new_phone ? (int)$new_phone : NULL;
		} 
		if(!isset($p_arr['is_active'])) $p_arr['is_active']=0;
		if($p_arr['pass2']) $submit_data['pass'] = $p_arr['pass2'];
		if($uid){
			if(!$this->session->has_userdata('user_settings')){
				set_cookie('popup_msg', '!!Не удалось сохранить изменения');
				$this->users();
				return false; 				
			}
			$last_set = $this->session->user_settings;
			$this->session->unset_userdata('user_settings');
			$submit_data['id'] = $uid;
			foreach($val_arr as $i){
				if(!isset($last_set[$i])) $last_set[$i] = NULL;
				if(!isset($p_arr[$i])) continue;
				if($p_arr[$i] != $last_set[$i]) $submit_data[$i] = $p_arr[$i];
			}
		}
		else{
			$user = $this->model->get_users($p_arr['login'], 'login');
			if(!empty($user)){
				set_cookie('popup_msg', '!!Невозможно создать пользователя! Пользователь с таким логином уже существует.');
				$this->create_user();
				return false; //'ExistingUserCreationError';
			}
			else{
				foreach($val_arr as $vl){
					$submit_data[$vl] = $p_arr[$vl];
				}
			}
		}
		if(count(array_keys($submit_data))>1) $this->model->update_user($submit_data);
		set_cookie('popup_msg', 'Изменения успешно внесены');
		$this->users();
	}

	public function delete_user($uid)
	{
		$this->model->delete_user($uid);
		set_cookie('popup_msg', 'Пользователь был успешно удалён');
		$this->users();
	}
	
	public function order_info($order_id)
	{
		$order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id));
		$email = $this->model->get_sln_emails($order['sln_id'])['email'];
		if($email) $this->session->email = $email;
		else $this->session->unset_userdata['email'];
		parent::order_info($order);
	}

	public function docs_edit()
	{
		$vw_arr = array('u_type'=>'docs', 'u_name'=>'документ', 'u_name1'=>'документа', 'u_name2'=>'документов', 'list_hdr'=>'Список загруженных файлов');
		$docs = $this->model->get_db_list('docs');
		if(!empty($docs)){
			$this->load->library('table');
			foreach($docs as $i){
				$ex_src = explode('.', $i['src']);
				if($ex_src[1] == 'pdf'){
					$is_pdf = TRUE;
					$on_click = "onClick=\"viewPDF('".site_url($i['link'])."'); return false;\"";
					$dwnld = anchor('', 'Открыть(PDF)', $on_click);
				}
				else $dwnld = anchor($i['link'], 'Скачать('.$ex_src[1].')', 'download');
				$descr = '<span class="doc_name" id="doc_'.$i['id'].'">'.$i['name'].'</span>';
				$added = '[Добавлен '.$i['added'].']';
				$pos = $this->model->get_dseqs('docs', $i['aggr_id']) - $i['dspl_id'];
				$ctgr = 'Категория: '.$this->model->pathman($i['aggr_id'])['category'].'(#<span id="dspl_'.$i['id'].'">'.$pos.'</span>)';
				$edit = anchor('', 'Редактировать', 'onClick="editUpld('.$i['id'].', \'val'.$i['aggr_id'].'\'); return false;"');
				$this->table->add_row($dwnld, $descr, $added, $ctgr, $edit);
			}
			$this->table->set_template(array('table_open'=>'<table id="docs_table" class="invisible_table">'));
			$dtbl = $this->table->generate();
		}
		if(isset($is_pdf)) $this->ext_js = array_merge($this->ext_js, ['pdfobject.js', 'img_viewer.js']);
		if(isset($dtbl)){
			$vw_arr['upld_list'] = $dtbl;
			$vw_arr['viewer'] = $this->load->view('templates/hidden_viewer', NULL, TRUE);
		}
		$this->_upld_edit($vw_arr);
	}

	public function imgs_edit()
	{
		$vw_arr = array('u_type'=>'imgs', 'u_name'=>'изображение', 'u_name1'=>'изображения', 'u_name2'=>'изображений', 'list_hdr'=>'Список загруженных файлов');
		$imgs = $this->model->get_db_list('imgs');
		if(!empty($imgs)){
			$this->ext_js[] = 'img_viewer.js';
			$i_list = '<div id="imgs2edit">';
			foreach($imgs as $i){
				$ii = array('i_type'=>'imgs', 'id'=>$i['id'], 'name'=>$i['name'], 'added'=>$i['added'], 'aggr_id'=>$i['aggr_id'], 'link'=>$i['link'], 'pre_link'=>$this->preview_link($i['link']));
				$ii['pos'] = $this->model->get_dseqs('imgs', $i['aggr_id']) - $i['dspl_id'];
				$ii['ctgr'] = $this->model->pathman($i['aggr_id'])['category'];
				$i_list .= $this->load->view('admin/howto_editor/item_edit', $ii, TRUE);
			}
			$vw_arr['upld_list'] = $i_list.'</div>';
			$vw_arr['viewer'] = $this->load->view('templates/hidden_viewer', array('is_pic'=>TRUE), TRUE);
		}
		$this->_upld_edit($vw_arr);
	}
	
	public function video_edit()
	{
		$vw_arr = array('u_type'=>'video', 'u_name'=>'видео', 'u_name1'=>'видео', 'u_name2'=>'видео', 'list_hdr'=>'Список загруженных видео');
		$videos = $this->model->get_db_list('video');
		if(!empty($videos) && is_array($videos)){
			$vw_arr['upld_list'] = $this->_make_video_list($videos);
			$vw_arr['viewer'] = $this->load->view('templates/hidden_viewer', NULL, TRUE);
		}		
		$this->_upld_edit($vw_arr);
	}
	
	public function docs_submit($doc_id=NULL)
	{
		$vw_arr = array('u_type'=>'docs', 'f_types'=>'txt|doc|docx|pdf');
		if($doc_id) $vw_arr['upld_id'] = $doc_id;
		$this->_upld_submit($vw_arr);
		$this->docs_edit();
	}
	
	public function imgs_submit($doc_id=NULL)
	{
		$vw_arr = array('u_type'=>'imgs', 'f_types'=>'jpg|jpeg|png');
		if($doc_id) $vw_arr['upld_id'] = $doc_id;
		$this->_upld_submit($vw_arr);
		$this->imgs_edit();
	}

	public function video_submit($v_id=NULL)
	{
		$name = $this->input->post('upld_file_name');
		$src = $this->youtube_link($this->input->post('fileUpload'), 'reverse');
		$aggr_id = substr($this->input->post('ctgr_dd'), 3);
		$d_pos = substr($this->input->post('dspl_dd'), 3);
		if(!$v_id){
			$to_upd = array('aggr_id'=>$aggr_id, 'name'=>$name, 'src'=>$src);
			if($d_pos != 1) $to_upd['d_pos'] = $d_pos;
			$this->model->add_tutorial('video', $to_upd);
			$upl_msg = 'Загрузка успешно завершена';
		} 
		else{
			$to_upd = array();
			$upld_arr = $this->model->get_tutorial('video', $v_id);
			if($name != $upld_arr['name']) $to_upd['name'] = $name;
			if($aggr_id != $upld_arr['aggr_id']){
				$to_upd['aggr_id'] = $aggr_id;
				$to_upd['d_pos'] = $d_pos;
			}
			elseif($upld_arr['dspl_id'] != $this->model->get_dseqs('video', $aggr_id) - $d_pos) $to_upd['d_pos'] = $d_pos;
			if($src != $upld_arr['src']) $to_upd['src'] = $src;
			if(!empty($to_upd)) $this->model->upd_tutorial('video', $v_id, $to_upd);
			$upl_msg = 'Данные обновлены';
		}
		set_cookie('popup_msg', $upl_msg);
		$this->video_edit();
	}
	
	
	public function del_docs($id_to_del)
	{
		$doc2del = $this->model->get_tutorial('docs', $id_to_del);
		set_cookie('popup_msg', unlink($doc2del['link']) && !($this->model->del_tutorial('docs', $id_to_del)) ? 'Файл был успешно удалён' : '!!При удалении документа возникла ошибка');
		$this->docs_edit();
	}
	
	public function del_imgs($id_to_del)
	{
		$doc2del = $this->model->get_tutorial('imgs', $id_to_del);
		unlink($this->preview_link($doc2del['link']));
		set_cookie('popup_msg', unlink($doc2del['link']) && !($this->model->del_tutorial('imgs', $id_to_del)) ? 'Файл был успешно удалён' : '!!При удалении документа возникла ошибка');
		$this->imgs_edit();
	}
	
	public function del_video($id_to_del)
	{
		set_cookie('popup_msg', $this->model->del_tutorial('video', $id_to_del) ? '!!При удалении видео возникла ошибка' : 'Видео было успешно удалёно');
		$this->video_edit();
	}
	
	public function storage()
	{
		$this->show_storage(NULL);
	}
}
