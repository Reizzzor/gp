<?php

class Orders extends LK_Controller {

	private $crnt_usr = array();

	public function __construct()
	{
		parent::__construct();
		$ut = in_array($this->session->user_type, ['salon', 'manager', 'store']) ? $this->session->user_type : NULL;
		$this->check_user_type($ut);
		$this->model->set_user_id($this->session->user_id);
		$u_info = $this->model->get_userinfo();
		if(!$u_info['is_active']) redirect('logout', 'location');
		$this->data['u_info'] = $u_info;
		$this->data['orders_url'] = 'orders/info';
		if($ut === 'salon'){
			$s_info = $this->model->get_salons($u_info['id'], 'user_id');
			$this->data['s_info'] = $s_info;
			$k_info = $this->model->get_kagents($s_info['kagent_id']);
			$this->data['k_info'] = $k_info;
			$this->data['title'] = 'Кабинет партнера';
			if(isset($k_info['name'])) $this->data['cmpny_name'] = $k_info['name'];
			if($s_info['name']) $this->data['user_name'] = $s_info['name'];
			$this->_make_main_menu();
		}
		elseif($ut === 'manager') $this->data['title'] = 'Кабинет управляющего';
		if(!(isset($this->data['user_name']) && $this->data['user_name'])) $this->data['user_name'] = '[login:'.$u_info['login'].']';
		if(!(isset($this->data['cmpny_name']) && $this->data['cmpny_name']) && isset($this->data['title'])) $this->data['cmpny_name'] = $this->data['title'];
	}
	
	private function _make_main_menu()
	{
		$submenu = array(anchor($this->user_target.'/status/0', 'Все'));
		$sbm_order = [1, 0, 2, 3, 5, 4, 6, 7, 8, 10, 11]; 
		foreach($sbm_order as $i){
			$sbmi_link = $this->user_target.'/status/'.$this->data['o_statuses'][$i]['id'];
			$submenu[] = anchor($sbmi_link, $this->data['o_statuses'][$i]['name']);
		}
		$sbm_display = $this->submenu_display_mode('show_sbm1');
		$ul_params = array('id'=>'submenu1', 'style'=>"display: $sbm_display");
		$this->data['menu_list'] = array(anchor('', '<i class="fas fa-shopping-cart" aria-hidden="true"></i>&nbsp;Заказы', 'onClick="toggleSubMenu(1); return false;"'), ul($submenu, $ul_params), anchor($this->user_target.'/podium', '<i class="fas fa-store-alt" aria-hidden="true"></i>&nbsp;Образцы'));		
		if(!$this->data['s_info']['hide_archive']) array_splice($this->data['menu_list'], 2, 0, anchor($this->user_target.'/status/10', '<i class="fas fa-file-archive" aria-hidden="true"></i>&nbsp;Архив заказов'));
		$this->make_study_menu();
		if($this->data['s_info']['show_preorders']) array_splice($this->data['menu_list'], 0, 0, anchor('preorders', '<i class="fas fa-plus-circle" aria-hidden="true"></i>&nbsp;Новый заказ'));
		$this->data['menu_list'][] = anchor($this->user_target.'/drapery', '<i class="fas fa-file" aria-hidden="true"></i>&nbsp;Разбивка тканей');
		if(!$this->data['s_info']['hide_fin_part']) $this->data['menu_list'][] = anchor('storage', '<i class="fas fa-warehouse" aria-hidden="true"></i>&nbsp;Склад');
	}
	
	private function _get_saved_drps($pre_order, $is_poe_drp=FALSE)
	{
		if($is_poe_drp) return	json_encode($this->model->get_rp_drapery(NULL, array('ext_id'=>$pre_order['drp_ext_id']))[0]);
		$res = array();
		switch($pre_order['drapery_type']){
			case '1':
				if($pre_order['drapery_id0']){
					$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$pre_order['drapery_id0']))[0];
					$res[] = array('id'=>$drp['id'], 'idType'=>'drapery_id0', 'tmName'=>$drp['trade_mark'], 'collName'=>$drp['collection'], 'category'=>$drp['category']);
				}
				break;
			case '2':
				for($i=1;$i<=6;$i++){
					if($pre_order['drapery_id'.$i]){
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$pre_order['drapery_id'.$i]))[0];
						$res[] = array('id'=>$drp['id'], 'idType'=>'drapery_id'.$i, 'tmName'=>$drp['trade_mark'], 'collName'=>$drp['collection'], 'category'=>$drp['category']);
					}	
				}
				break;
			case '3':
				for($i=1;$i<=8;$i++){
					if($pre_order['ind_drapery_id'.$i]){
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$pre_order['ind_drapery_id'.$i]))[0];
						$res[] = array('id'=>$drp['id'], 'idType'=>'ind_drapery_id'.$i, 'tmName'=>$drp['trade_mark'], 'collName'=>$drp['collection'], 'category'=>$drp['category'], 'comment'=>$pre_order['ind_comment'.$i]);
					}	
				}
				break;
		}
		return json_encode($res);
	}
	
	private function _make_rsoid_dd($po_id=NULL)
	{
		$res = array('val0'=>'(Нет)');
		$soid_raw = $this->model->get_preorders(NULL, 'root_sln_order_id');
		foreach($soid_raw as $i){
			if(!$po_id || $i['id'] != $po_id) $res['val'.$i['id']] = $i['sln_order_id'];
		}
		return $res;
	}
	
	protected function show_po_entry($poe_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$po_entry = $this->model->get_po_entry($poe_id);
		if($this->model->get_preorders($po_entry['po_id'])[0]['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$poe_values = $this->model->get_rp_options($this->model->get_rp_id($po_entry['r_prod_id']));
		$src_pics_path = '/var/www/lk/public_html/pics_drp/';
		if($po_entry['drapery_type'] == '2') $poe_values['rp_pic_src'] = file_exists($src_pics_path."numbered/{$po_entry['r_prod_id']}.jpg") ? site_url("pics_drp/numbered/{$po_entry['r_prod_id']}.jpg") : '';
		else $poe_values['rp_pic_src'] = file_exists($src_pics_path."unnumbered/{$po_entry['r_prod_id']}.jpg") ? site_url("pics_drp/unnumbered/{$po_entry['r_prod_id']}.jpg") : '';
		if($po_entry['extra_modules'] != '{}') $poe_values['modules'] = $this->model->get_extra_modules($poe_id, FALSE);
		$poe_values['extra'] = $this->model->get_poe_extra($poe_id);
		$poe_values['quantity'] = $po_entry['quantity'];
		$ctgr_name = $this->model->get_ctgr_name($po_entry['category']);
		if($ctgr_name !== FALSE) $poe_values['category'] = $ctgr_name;
		$poe_values['is_example'] = $po_entry['is_example'];
		$poe_values['is_nonstandard'] = $po_entry['is_nonstandard'];
		$poe_values['comment'] = $po_entry['nstrd_descr'];
		if(!$this->data['s_info']['hide_fin_part']){
			$nd_price = $this->model->non_discount_price($po_entry)*$po_entry['quantity'];
			if($nd_price){
				$poe_values['dscnt'] = round(100*($nd_price - $po_entry['summ_total'])/$nd_price, 1).'%';
				$poe_values['nd_summ'] = $this->out_summ($nd_price/100).'руб.';
			}
			$poe_values['summ'] = $this->out_summ($po_entry['summ_total']/100).'руб.';
		}
		if($po_entry['drapery_type']){
			$this->load->library('table');
			$this->table->set_heading(($po_entry['drapery_type'] == '3' ? 'Индивидуальный' : 'Стандартный').' крой', 'Ткань/кожа', 'Поставщик', 'Категория');
			if($po_entry['drapery_type'] == '3'){
				for($i=1;$i<=8;$i++){
					if(!$po_entry["ind_drapery_id$i"]) continue;
					$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["ind_drapery_id$i"]))[0];
					$this->table->add_row($po_entry["ind_comment$i"], $drapery['name'], $drapery['trade_mark'], $drapery['ctgr_name']);
				}
			}
			elseif($po_entry['drapery_type'] == '2'){
				if(isset($poe_values['drp_names'])){
					for($i=1;$i<=6;$i++){
						if(!$po_entry['drapery_id'.$i] || !$poe_values['drp_names']['drp'.$i]) continue;
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry['drapery_id'.$i]))[0];
						$drp_no = 'Ткань №'.$i.(strlen($poe_values['drp_names']['drp'.$i]) > 1 ? "({$poe_values['drp_names']['drp'.$i]})" : '');
						$this->table->add_row($drp_no, $drp['name'], $drp['trade_mark'], $drp['ctgr_name']);
					}
				}
				else{
					$dt2_list=["Подушки", "Сиденья", "Спинка, царга", "Кант"];
					for($i=1;$i<=6;$i++){
						if(!$po_entry["drapery_id$i"]) continue;
						$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["drapery_id$i"]))[0];
						$this->table->add_row("Ткань №$i".($i>count($dt2_list) ? '' : '('.$dt2_list[$i-1].')'), $drapery['name'], $drapery['trade_mark'], $drapery['ctgr_name']);
					}
				}
			}
			else{
				$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["drapery_id0"]))[0];
				$this->table->add_row('Всё изделие в одной ткани', $drapery['name'], $drapery['trade_mark'], $drapery['ctgr_name']);
			}
			$this->table->set_template(array('table_open'=>'<table class="po_drapery_table">'));
			$poe_values['drapery_table'] = $this->table->generate();
		}
		else $poe_values['drapery_table'] = '';
		return $this->load->view('orders/show_po_entry', $poe_values, TRUE);
	}

	protected function show_drp_entry($poe_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$po_entry = $this->model->get_poe_drp($poe_id);
		if($this->model->get_preorders($po_entry['po_id'])[0]['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry['drp_ext_id']))[0];
		$drp['quantity'] = $po_entry['quantity']/100;
		if(!$this->data['s_info']['hide_fin_part']) $drp['summ'] = $this->out_summ($po_entry['summ_total']/100).' руб';
		return $this->load->view('orders/show_poe_drp', $drp, TRUE);
	}	
	
	public function index($ords_id = 0)
	{
		$tt = $ords_id ? 'Заказы в статусе "'.$this->get_order_status($ords_id).'"' : 'Все заказы';
		$usr_orders = $ords_id ? $this->model->get_orders(array('id_type'=>'order_status_id', 'id'=>$ords_id)) : $this->model->get_orders(array('id_type'=>'order_status_id', 'id'=>[1, 2, 3, 4, 5, 6, 7, 8, 9, 12]));
		if(!empty($usr_orders)){
			if(array_key_exists('order_id', $usr_orders)) $usr_orders = array($usr_orders);
			$this->make_orders_table($usr_orders, $tt);
		}
		else{
			$this->data['content'] = heading('Заказы не найдены', 2);
			$this->load_lk_view();
		}
	}
	
	public function podium()
	{
		$podium_orders = $this->model->get_orders([array('id_type'=>'order_status_id !=', 'id'=>10), array('id_type'=>'is_podium', 'id'=>TRUE)]);
		if(!empty($podium_orders)){
			$str_rows = [];
			foreach($podium_orders as $i){
				$ri = $this->model->get_reserved_item($i['uniq_id'], 'order_ext_id');
				if(!$ri) $r_st = 'Доступен';
				else $r_st = $ri['is_uploading'] ? 'Зарезервирован '.$this->date_format($ri['stamp']) : 'Отправлен на подтверждение';
				$str_rows[] = array_merge($this->make_storage_row($i), array($r_st));
			}
			if(!empty($str_rows)) $this->data['js'] = $this->load->view('js/js_order_info', array('url_info'=>site_url('reserve').'/', 'table_id'=>'storage_table'), TRUE);
			$p_tbl = $this->make_sticky_table('storage_table', ["Фабр-й №", "№ в салоне", "Номенклатура", "Комплектация", "Количество", "Статус", '&nbsp;'], $str_rows, 145);
		} 
		$this->data['content'] = isset($p_tbl) ? heading('Подиумные образцы', 2).$p_tbl : heading('Подиумные образцы отсутствуют', 2);
		$this->load_lk_view();
	}
	
	public function order_info($order_id)
	{
		$order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id));
		if($this->session->user_type != 'manager'){
			$email = $this->model->get_email($this->session->user_id);
			if($email) $this->session->email = $email;
			else $this->session->unset_userdata('email');
		}
		else $this->session->emails = $this->model->get_mngr_info($this->session->user_id)['emails'];
		parent::order_info($order);
	}
	
	public function pre_orders()
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$po_list = $this->model->get_preorders();
		foreach($po_list as $i){
			if(!$i['cstmr_surname'] && !$i['cstmr_name1'] && !$i['phone1'] && $i['dlvr_type'] == 'salon' && !$this->model->get_po_consist($i['id']) && !$this->model->get_po_consist($i['id'], TRUE)) $this->delete_pre_orders($i['id'], TRUE);
		}
		$po_list = $this->model->get_preorders();
		$po_rows = [];
		foreach($po_list as $i){
			if($i['received']) continue;
			$cstmr = $i['cstmr_surname'];
			for($j=1;$j<=2;$j++){
				if($i['cstmr_name'.$j])$cstmr .= ' '.$i['cstmr_name'.$j];
			}
			$phone = $i['phone1'] ? $i['phone1'] : NULL;
			if(!$phone && $i['phone2']) $phone = $i['phone2'];
			if(!$i['is_uploading']){
				if(!$i['needs_redacting']) $edit = anchor(site_url('preorders/'.$i['id']), (int)get_cookie('scr_x') > 1700 ? 'Редактировать' : 'Редакт-ть');
				else $edit = anchor(site_url('preorders/'.$i['id']), ((int)get_cookie('scr_x') > 1700 ? 'Редактировать' : 'Редакт-ть').'(Требуются исправления)');
				$del = anchor(site_url('preorders/delete/'.$i['id']), 'Удалить');
				$upld = anchor(site_url('preorders/upload/'.$i['id']), ((int)get_cookie('scr_x') > 1700 ? 'Отправить' : 'Отпр-ть').' на фабрику');
				$po_date = '';
			}
			else{
				$edit = anchor(site_url('preorders/show/'.$i['id']), 'Просмотр');
				$del = '';
				$upld = 'Отправлен';
				$po_date = $this->date_format(explode(' ', $i['stamp'])[0]);
			}
			$po_rows[] = [$i['sln_order_id'], $this->model->get_po_models($i['id']), $cstmr, $phone, $i['seller_id'] ? $this->model->get_seller($i['seller_id']) : '', !$this->data['s_info']['hide_fin_part'] && $i['summ_total'] ? ($this->out_summ($i['summ_total']/100)).' руб.' : '-', $po_date, $edit, $del, $upld];
		}
		$this->data['js'] = $this->load->view('js/js_order_info', array('table_id'=>'preorders_table'), TRUE);
		$this->data['content'] = $this->load->view('orders/preorders_list', array('po_list'=>$this->make_sticky_table('preorders_table', ["№ в салоне", "Состав", "Заказчик", "Телефон", "Продавец", "Стоимость", "Дата", '&nbsp;', '&nbsp;', '&nbsp;'], $po_rows, 200)), TRUE);
		$this->load_lk_view();
	}
	
	public function new_pre_order()
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();	
		$this->data['js'] = $this->load->view('js/js_preorder_edit', array('mp_link'=>site_url('module_previews').'/', 'drp_ctgrs'=>$this->model->get_drp_ctgrs()), TRUE);
		$this->ext_js[] = 'phone_mask.js';
		$dlvr = $this->load->view('templates/dlvr_edit', array('sln_address'=>$this->model->get_sln_address()), TRUE);
		$this->data['content'] = $this->load->view('orders/preorder_edit', array('sln_stuff'=>$this->model->get_sln_stuff(), 'hide_fin_part'=>$this->data['s_info']['hide_fin_part'], 'dlvr'=>$dlvr,
					'next_soid'=>$this->model->get_next_soid()), TRUE);
		$this->load_lk_view();
	}
	
	public function edit_pre_order($po_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		if(!isset($this->model->get_preorders($po_id)[0])) $this->pre_orders();
		$pr_data = array('sln_stuff'=>$this->model->get_sln_stuff(), 'po'=>$this->model->get_preorders($po_id)[0], 'hide_fin_part'=>$this->data['s_info']['hide_fin_part'], 'next_soid'=>$this->model->get_next_soid());
		if($pr_data['po']['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$po_consist = $this->model->get_po_consist($po_id);
		$poe_drp = $this->model->get_po_consist($po_id, TRUE);
		$user_id = $this->data['u_info']['id'];
		$soid = $pr_data['po']['sln_order_id'];
		$xtra_path = "/var/preorders_xtra/u$user_id/";
		$consist_summ = $pr_data['po']['summ_total']/100;
		if($pr_data['po']['count_dlvr'] && $pr_data['po']['summ_dlvr']) $consist_summ -= $pr_data['po']['summ_dlvr']/100;
		if($pr_data['po']['count_dlvr'] && $pr_data['po']['summ_up']) $consist_summ -= $pr_data['po']['summ_up']/100;
		$po_rows = [];
		foreach($po_consist as $k=>$vl){
			$mdl_name = $vl['r_prod_id'] ? $this->model->get_rp(array('ext_id'=>$vl['r_prod_id']))[0]['name'] : '';
			if(!$this->data['s_info']['hide_fin_part']){
				$discount = (int)$vl['xtra_discount'];
				if($vl['r_prod_id'] && $vl['category'] != 0 && !$vl['is_example']) $discount += $this->model->get_discount($vl['r_prod_id']);
				$discount = "{$discount}%";	
			}
			else $discount = '-';
			$edit = anchor('', (int)get_cookie('scr_x') > 1700 ? 'Редактировать' : 'Редакт-ть', 'onClick="editPOEntry('.$vl['id'].'); return false;"');
			$del = anchor('', 'Удалить', 'onClick="delPOEntry('.$vl['id'].'); return false;"');
			$xfile_name = $user_id."_$po_id".'_'.$vl['id'].'.pdf';
			$xfile_name = "{$user_id}_{$po_id}_{$vl['id']}.pdf";
			$xfile_descr = file_exists($xtra_path.$xfile_name) ? '<span class="xtra_file_name">'.$xfile_name.' </span><span class="xtra_file_del" onClick="ajaxPO(\'del_xtra_file\', \''.$xfile_name.'\');">Удалить </span>' : '';
			$xf_upld_params = array('id'=>'xtra_file'.$vl['id'], 'name'=>'xtra_file'.$vl['id']);
			if($xfile_descr) $xf_upld_params['style'] = 'display:none';
			$xtra_file = '<span id="curr_xfile'.$vl['id'].'">'."$xfile_descr</span>".form_upload($xf_upld_params);
			$summ = !$this->data['s_info']['hide_fin_part'] && $vl['summ_total']/100 ? $this->out_summ($vl['summ_total']/100).' руб.' : '-';
			$print_blank = anchor(site_url('print/poe/'.$vl['id']), 'Печать', 'target="_blank"');
			$po_rows[] = array($k+1, $mdl_name, $vl['quantity'], $summ, $discount, $this->date_format(explode(' ', $vl['stamp'])[0]), $edit, $del, $print_blank, $xtra_file);
		}
		foreach($poe_drp as $k=>$vl){
			$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$vl['drp_ext_id']))[0];
			$discount = !$this->data['s_info']['hide_fin_part'] ? '0%' : '-';
			$edit = anchor('', (int)get_cookie('scr_x') > 1700 ? 'Редактировать' : 'Редакт-ть', 'onClick="editPOEDrapery('.$vl['id'].'); return false;"');
			$del = anchor('', 'Удалить', 'onClick="delPOEDrapery('.$vl['id'].'); return false;"');
			$xfile_name = "{$user_id}_{$po_id}_drp_{$vl['id']}.pdf";
			$summ = !$this->data['s_info']['hide_fin_part'] && $vl['summ_total']/100 ? $this->out_summ($vl['summ_total']/100).' руб.' : '-';
			$xfile_descr = file_exists($xtra_path.$xfile_name) ? '<span class="xtra_file_name">'.$xfile_name.' </span><span class="xtra_file_del" onClick="ajaxPO(\'del_xtra_file\', \''.$xfile_name.'\');">Удалить </span>' : '';
			$xf_upld_params = array('id'=>'xtra_file_drp'.$vl['id'], 'name'=>'xtra_file_drp'.$vl['id']);
			if($xfile_descr) $xf_upld_params['style'] = 'display:none';
			$xtra_file = '<span id="curr_xfile_drp'.$vl['id'].'">'."$xfile_descr</span>".form_upload($xf_upld_params);
			$po_rows[] = array('тк'.($k+1), $drp['name'], ($vl['quantity']/100).'м', $summ, $discount, $this->date_format(explode(' ', $vl['stamp'])[0]), $edit, $del, '', $xtra_file);
		}
		$pr_data['consist_table'] = $this->make_sticky_table('po_consist_table', ["№", "Модель", "Кол-во", "Стоимость", "Скидка", "Дата", '&nbsp;', '&nbsp;', 'Бланк заказа',  "Доп. файл"], $po_rows);
		$pr_data['summ_total'] = $this->out_summ($consist_summ);
		$this->ext_js[] = 'phone_mask.js';
		$this->data['js'] = $this->load->view('js/js_preorder_edit', array('po_id'=>$po_id, 'dlvr_type'=>$pr_data['po']['dlvr_type'], 'consist_summ'=>$consist_summ), TRUE);
		$dlvr_list_fname = $user_id."_$po_id".'_dlvr_list.pdf';
		if(file_exists($xtra_path.$dlvr_list_fname)) $pr_data['dlvr_list'] = $dlvr_list_fname;
		$dlvr_data = array_merge($pr_data['po'], array('sln_address'=>$this->model->get_sln_address()));
		$pr_data['dlvr'] = $this->load->view('templates/dlvr_edit', $dlvr_data, TRUE);
		$this->data['content'] = $this->load->view('orders/preorder_edit', $pr_data, TRUE);
		$this->load_lk_view();
	}
	
	public function show_pre_order($po_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		if(!isset($this->model->get_preorders($po_id)[0])) $this->pre_orders();
		$po = $this->model->get_preorders($po_id)[0];
		if($po['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$po_consist = $this->model->get_po_consist($po_id);
		$poe_drp = $this->model->get_po_consist($po_id, TRUE);
		if($po['count_dlvr'] && $po['summ_dlvr']) $consist_summ -= $po['summ_dlvr']/100;
		if($po['count_dlvr'] && $po['summ_up']) $consist_summ -= $po['summ_up']/100;
		$po_rows = [];
		foreach($po_consist as $i){
			$po_rows[] = $this->show_po_entry($i['id']);
		}
		foreach($poe_drp as $i){
			$po_rows[] = $this->show_drp_entry($i['id']);
		}
		$po['sln_address'] = $this->model->get_sln_address();
		if($po['seller_id']) $po['seller'] = $this->model->get_seller($po['seller_id']);
		$po['hide_fin_part'] = $this->data['s_info']['hide_fin_part'];
		$po['rows'] = $po_rows;
		$this->data['content'] = $this->load->view('orders/preorder_show', $po, TRUE);
		$this->load_lk_view();
	}
	
	public function save_pre_order($po_id = NULL)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		if($po_id){
			$preorder = $this->model->get_preorders($po_id)[0];
			if($preorder['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		}
		$val_arr = ['sln_order_id', 'cstmr_surname', 'cstmr_name1', 'cstmr_name2', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_corp', 'dlvr_porch', 'dlvr_flat', 'dlvr_dmphn', 'dlvr_stage', 'has_lift', 
			'phone1', 'phone2', 'email', 'summ_dlvr', 'count_dlvr', 'dlvr_type', 'dlvr_comment', 'sellerDD', 'summ_up'];
		$submit_data = array('sln_id'=>$this->data['s_info']['id'], 'kagent_id'=>$this->data['s_info']['kagent_id']);
		$p_arr = $this->input->post();
		if($p_arr['sln_order_id']){
			$curr_po = $this->model->get_preorders($p_arr['sln_order_id'], 'sln_order_id');
			if(!empty($curr_po) && (!$po_id || ($po_id && (count($curr_po) > 1 || $curr_po[0]['id'] != $po_id)))){
				set_cookie('popup_msg', '!!Заказ с таким номером уже существует');
				$this->pre_orders();
			}
		}
		foreach(['has_lift', 'count_dlvr'] as $i){
			if(!isset($p_arr[$i])) $p_arr[$i] = 0;
		}
		if(isset($p_arr['sellerDD'])) $p_arr['sellerDD'] =  substr($p_arr['sellerDD'], 3);
		foreach(['phone1', 'phone2'] as $i){
			if(isset($p_arr[$i])){
				$new_phone = preg_replace('/\D/', '', $p_arr[$i]);
				$p_arr[$i] = $new_phone ? (int)$new_phone : NULL;
			}
		}
		if($po_id) $submit_data['po_id'] = $po_id;
		foreach($val_arr as $i){
			if(isset($p_arr[$i])) $submit_data[$i] = $p_arr[$i];
		}
		if(count(array_keys($submit_data))>3) $this->model->update_preorder($submit_data);
		if(!$po_id) $po_id = $this->model->get_last_preorder_id($submit_data['kagent_id']);
		$xtra_path = '/var/preorders_xtra/';
		$usr_fldr = 'u'.$this->data['u_info']['id'].'/';
		$fname_prefix = $this->data['u_info']['id']."_$po_id".'_';
		$upload_config = array('overwrite'=>TRUE, 'upload_path'=>$xtra_path.$usr_fldr, 'allowed_types'=>'pdf');
		$this->load->library('upload');
		foreach($_FILES as $k=>$vl){
			if(!$vl['size']) continue;
			if(!file_exists($xtra_path.$usr_fldr)) mkdir($xtra_path.$usr_fldr);
			if($k == 'xtra_file_upld') $upload_config['file_name'] = $fname_prefix.'dlvr_list.pdf';
			else{
				$fname_offset = strpos($k, 'drp') === FALSE ? 9 : 13;
				$drp_sign = strpos($k, 'drp') === FALSE ? '' : 'drp_';
				$upload_config['file_name'] = $fname_prefix.$drp_sign.substr($k, $fname_offset).'.pdf';
			}
			$full_fname = $upload_config['upload_path'].$upload_config['file_name'];
			if(file_exists($full_fname)) unlink($full_fname);
			$this->upload->initialize($upload_config);
			$u_res = $this->upload->do_upload($k);
			if(!$u_res) $msg_text = '!!Ошибка при загрузке: '.$this->upload->display_errors();
		}
		if($p_arr['adding_poe'] == 'n'){
			set_cookie('popup_msg', (isset($msg_text) && $msg_text) ? $msg_text : 'Изменения успешно внесены');
			$this->pre_orders();
		}
		if($p_arr['adding_poe'] == 'ap') $this->new_po_entry($po_id);
		if(substr($p_arr['adding_poe'], 0, 2) == 'ep') $this->edit_po_entry(substr($p_arr['adding_poe'], 2));
		if(substr($p_arr['adding_poe'], 0, 2) == 'dp') $this->del_po_entry(substr($p_arr['adding_poe'], 2));
		if($p_arr['adding_poe'] == 'ad') $this->new_poe_drp($po_id);
		if(substr($p_arr['adding_poe'], 0, 2) == 'ed') $this->edit_poe_drp(substr($p_arr['adding_poe'], 2));
		if(substr($p_arr['adding_poe'], 0, 2) == 'dd') $this->del_po_entry(substr($p_arr['adding_poe'], 2), TRUE);
		
	}
	
	public function delete_pre_orders($po_id=NULL, $quiet=FALSE)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$u_path = '/var/preorders_xtra/u'.$this->data['u_info']['id'];
		if(file_exists($u_path) && is_dir($u_path)){
			$xf_list = array_diff(scandir($u_path), array('.','..'));
			foreach ($xf_list as $file){
				if($po_id && !preg_match("/_$po_id".'_/', $file)) continue;
				unlink("$u_path/$file");
			}
		}
		$dres = $this->model->del_preorders($po_id);
		if(!$quiet){
			if(!$dres) $msg_text = $po_id ? 'Заказ был успешно удалён' : 'Заказы были успешно удалены';
			else $msg_text = $po_id ? '!!При удалении заказа возникла ошибка' : '!!При удалении заказов возникла ошибка';
			set_cookie('popup_msg', $msg_text);
			$this->pre_orders();
		}
	}	
		
	public function upload_pre_orders($po_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$preorder = $this->model->get_preorders($po_id)[0];
		if($preorder['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$po_consist = $this->model->get_po_consist($po_id);
		$poe_drp = $this->model->get_po_consist($po_id, TRUE);
		$user_id = $this->data['u_info']['id'];
		$xtra_path = "/var/preorders_xtra/u$user_id/";
		foreach($po_consist as $i){
			$xfile_name = "{$user_id}_{$po_id}_{$i['id']}.pdf";
			if(!file_exists($xtra_path.$xfile_name)){
				set_cookie('popup_msg', '!!Не загружен(-ы) бланк(-и)');	
				$this->pre_orders();
				return FALSE;
			}
		}
		foreach($poe_drp as $i){
			$xfile_name = "{$user_id}_{$po_id}_drp_{$i['id']}.pdf";
			if(!file_exists($xtra_path.$xfile_name)){
				set_cookie('popup_msg', '!!Не загружен(-ы) бланк(-и)');	
				$this->pre_orders();
				return FALSE;
			}
		}
		$u_res = $this->model->upld_preorders($po_id);
		if(!$u_res) $msg_text = $po_id ? 'Заказ был успешно выгружен' : 'Заказы были успешно выгружены'; 
		else $msg_text = $po_id ? '!!При выгрузке заказа возникла ошибка' : '!!При выгрузке заказов возникла ошибка';
		set_cookie('popup_msg', $msg_text);
		$this->pre_orders();
	}
		
	public function pre_orders_handler()
	{
		$p_arr = $this->input->post();
		if(!isset($p_arr['method'])) $resp = array('code'=>20, 'message'=>'Отсутствует обязательный параметр "method" в запросе');
		else{
			switch($p_arr['method']){
				case 'rp_options':
					$resp = $this->model->get_rp_options($p_arr['rp_id']);
					if(file_exists('/var/www/lk/public_html/pics_drp/numbered/'.$resp['ext_id'].'.jpg')) $resp['src_num'] = site_url('pics_drp/numbered/'.$resp['ext_id'].'.jpg');
					if(file_exists('/var/www/lk/public_html/pics_drp/unnumbered/'.$resp['ext_id'].'.jpg')) $resp['src_unnum'] = site_url('pics_drp/unnumbered/'.$resp['ext_id'].'.jpg');;
					break;
				case 'po_options':
					$resp = isset($p_arr['po_id']) ? $this->model->get_po_options($p_arr['po_id']) : $this->model->get_strg_options($p_arr['strg_id']);
					break;
				case 'get_drp_coll':
					$coll = array();
					$coll_raw = $this->model->get_rp_drapery('coll', array('trade_mark'=>$p_arr['tm_name']));
					$excs = isset($p_arr['rp_id']) ? $this->model->get_rp_exceptions($p_arr['rp_id']) : array();
					foreach($coll_raw as $i){
						if(empty($excs) || !in_array($i['ext_id'], $excs)) $coll[] = $i['collection'];
					}
					$resp = array('dd_id'=>$p_arr['dd_id'], 'coll'=>$coll, 'code'=>0);
					break;
				case 'get_drapery':
					$drp = array();
					$drp_raw = $this->model->get_rp_drapery('drp', array('trade_mark'=>$p_arr['tm_name'], 'collection'=>$p_arr['coll_name']));
					foreach($drp_raw as $i){
						$drp[] = array('id'=>$i['id'], 'name'=>$i['name']);
					}
					$resp = array('dd_id'=>$p_arr['dd_id'], 'drp'=>$drp, 'code'=>0);
					break;
				case 'get_drp_ctgr':
					$drp = $this->model->get_rp_drapery(NULL, array('id'=>$p_arr['drp_id']))[0];
					$resp = array('code'=>0, 'drp_dd'=>$p_arr['drp_dd'], 'drp_ctgr'=>$drp['category'], 'action'=>$this->model->get_action_rp($drp['coll_ext_id']));
					break;
				case 'get_cm_price':
					$cm_price = $this->model->get_module_price($p_arr['module_id'], $p_arr['ctgr']);
					$resp = array('code'=>0, 'cm_id'=>$p_arr['module_id'], 'cm_price'=>$cm_price, 'cm_num'=>$p_arr['cm_num'], 'cm_type'=>$p_arr['cm_type']);
					break;
				case 'get_rp_price':
					$resp = array('price'=>$this->model->get_rp_price($p_arr['rp_id'], $p_arr['ctgr']), 'code'=>0);
					break;
				case 'del_xtra_file':
					$xtra_path = '/var/preorders_xtra/u'.$this->data['u_info']['id'].'/';
					if(file_exists($xtra_path.$p_arr['f_name'])) unlink($xtra_path.$p_arr['f_name']);
					$resp = array('code'=>0, 'deleted_file'=>$p_arr['f_name']);
					break;
				case 'get_address':
					$resp = $this->model->get_address($p_arr['rpo_id']);
					break;
				case 'pre_save':
					$order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$p_arr['order_id']), TRUE);
					$strg = $order ? TRUE : FALSE;
					if(!$order) $order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$p_arr['order_id']));
					$val_arr = ['cstmr_surname', 'cstmr_name1', 'cstmr_name2', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_corp', 'dlvr_porch', 'dlvr_flat', 'dlvr_dmphn', 'dlvr_stage', 'has_lift', 
						'phone1', 'phone2', 'email', 'summ_dlvr', 'count_dlvr', 'dlvr_type', 'dlvr_comment', 'summ_up'];
					$submit_data = array('sln_id'=>$this->data['s_info']['id'], 'kagent_id'=>$this->data['s_info']['kagent_id'], 'order_ext_id'=>$order['uniq_id'], 'is_example'=>(isset($p_arr['rsrv_is_example']) && $p_arr['rsrv_is_example'] != 'NONE') ? $p_arr['rsrv_is_example'] : 0);
					foreach(['has_lift', 'count_dlvr'] as $i){
						if(!isset($p_arr[$i]) || $p_arr[$i] == 'NONE') $p_arr[$i] = 0;
					}
					foreach(['phone1', 'phone2'] as $i){
						if(isset($p_arr[$i]) && $p_arr[$i] != 'NONE'){
							$new_phone = preg_replace('/\D/', '', $p_arr[$i]);
							$p_arr[$i] = $new_phone ? (int)$new_phone : NULL;
						}
					}
					foreach($val_arr as $i){
						if(!isset($p_arr[$i]) || $p_arr[$i] == 'NONE') continue;
						$submit_data[$i] = in_array($i, ['summ_up', 'summ_dlvr']) ? 100*(int)$p_arr[$i] : $p_arr[$i];
					}
					$submit_data['dscnt_retail'] = (isset($p_arr['dscnt_retail']) && $p_arr['dscnt_retail'] != 'NONE') ? (int)substr($p_arr['dscnt_retail'], 3) : 0;
					$submit_data['dscnt_whole'] = $submit_data['dscnt_retail']/2;
					$this->model->reserve($submit_data, $strg, TRUE);
					$resp = array('code'=>0);
					break;
				default:
					$resp = array('code'=>21, 'message'=>'Неизвестное или неправильное имя метода');		
			}
		}
		if($resp['code'] == 0) $resp['method'] = $p_arr['method'];
		echo json_encode($resp);
	}
	
	public function poe_drp_handler()
	{
		$p_arr = $this->input->post();
		if(!isset($p_arr['method'])) $resp = array('code'=>20, 'message'=>'Отсутствует обязательный параметр "method" в запросе');
		else{
			switch($p_arr['method']){
				case 'get_drp_coll':
					$coll = array();
					$coll_raw = $this->model->get_rp_drapery('coll', array('trade_mark'=>$p_arr['tm_name']));
					foreach($coll_raw as $i){
						$coll[] = $i['collection'];
					}
					$resp = array('code'=>0, 'coll'=>$coll);
					break;
				case 'get_drapery':
					$resp = array('code'=>0, 'drp'=>$this->model->get_drp4order($p_arr['tm_name'], $p_arr['coll_name']));
					break;
				default:
					$resp = array('code'=>21, 'message'=>'Неизвестное или неправильное имя метода');
			}
		}
		if($resp['code'] == 0) $resp['method'] = $p_arr['method'];
		echo json_encode($resp);		
	}

	
	public function new_po_entry($po_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		if($this->model->get_preorders($po_id)[0]['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$this->data['js'] = $this->load->view('js/js_edit_po_entry', array('mp_link'=>site_url('module_previews').'/', 'drp_ctgrs'=>$this->model->get_drp_ctgrs(), 'po_id'=>$po_id, 'hide_fin_part'=>$this->data['s_info']['hide_fin_part']), TRUE);
		$this->ext_js = array_merge($this->ext_js, ['GraphicsJS.js', 'rp_modules.js']);
		$this->data['content'] = $this->load->view('orders/edit_po_entry', array('r_prod'=>$this->model->get_rp(), 'rp_drapery'=>$this->model->get_rp_drapery('tm'), 'po_id'=>$po_id, 'hide_fin_part'=>$this->data['s_info']['hide_fin_part']), TRUE);
		$this->load_lk_view();
	}
	
	public function new_poe_drp($po_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		if($this->model->get_preorders($po_id)[0]['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$this->data['js'] = $this->load->view('js/js_poe_drp', array('po_id'=>$po_id, 'hide_fin_part'=>$this->data['s_info']['hide_fin_part']), TRUE);
		$this->ext_js[] = 'phone_mask.js';
		$this->data['content'] = $this->load->view('orders/edit_poe_drp', array('rp_drapery'=>$this->model->get_rp_drapery('tm'), 'po_id'=>$po_id, 'hide_fin_part'=>$this->data['s_info']['hide_fin_part']), TRUE);
		$this->load_lk_view();
	}
	
	public function edit_po_entry($poe_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$po_entry = $this->model->get_po_entry($poe_id);
		if($this->model->get_preorders($po_entry['po_id'])[0]['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$pr_data = array('r_prod'=>$this->model->get_rp(), 'rp_drapery'=>$this->model->get_rp_drapery('tm'), 'po'=>$po_entry, 'hide_fin_part'=>$this->data['s_info']['hide_fin_part']);
		$cm_list = $this->model->get_extra_modules($poe_id);
		$this->ext_js = array_merge($this->ext_js, ['GraphicsJS.js', 'rp_modules.js']);
		$this->data['js'] = $this->load->view('js/js_edit_po_entry', array('poe_id'=>$poe_id, 'po_id'=>$po_entry['po_id'], 'cm_list'=>$cm_list, 'drapery_type'=>$po_entry['drapery_type'], 
				'saved_drps'=>$this->_get_saved_drps($po_entry), 'mp_link'=>site_url('module_previews').'/', 'drp_ctgrs'=>$this->model->get_drp_ctgrs(), 'hide_fin_part'=>$this->data['s_info']['hide_fin_part']), TRUE);
		if($po_entry['r_prod_id']) $pr_data['model_id'] = $this->model->get_rp_id($po_entry['r_prod_id']);
		$this->data['content'] = $this->load->view('orders/edit_po_entry', $pr_data, TRUE);
		$this->load_lk_view();
	}
	
	public function edit_poe_drp($poe_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$po_entry = $this->model->get_poe_drp($poe_id);
		if($this->model->get_preorders($po_entry['po_id'])[0]['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$pr_data = array('rp_drapery'=>$this->model->get_rp_drapery('tm'), 'po'=>$po_entry, 'hide_fin_part'=>$this->data['s_info']['hide_fin_part']);
		$this->data['js'] = $this->load->view('js/js_poe_drp', array('poe_id'=>$poe_id, 'po_id'=>$po_entry['po_id'], 'hide_fin_part'=>$this->data['s_info']['hide_fin_part'], 'saved_drp'=>$this->_get_saved_drps($po_entry, TRUE)), TRUE);
		$this->ext_js[] = 'phone_mask.js';
		$this->data['content'] = $this->load->view('orders/edit_poe_drp', $pr_data, TRUE);
		$this->load_lk_view();
	}
	
	public function del_po_entry($poe_id, $is_drp=FALSE)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$po_entry = !$is_drp ? $this->model->get_po_entry($poe_id) : $this->model->get_poe_drp($poe_id);
		$po_id = $po_entry['po_id'];
		$preorder = $this->model->get_preorders($po_id)[0];
		if($preorder['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$user_id = $this->data['u_info']['id'];
		$xtra_file = !$is_drp ? "/var/preorders_xtra/u$user_id/{$user_id}_{$po_id}_{$poe_id}.pdf" : "/var/preorders_xtra/u$user_id/{$user_id}_{$po_id}_drp_{$poe_id}.pdf";
		if(file_exists($xtra_file)) unlink($xtra_file);
		$msg_text = $this->model->del_po_entry($poe_id, 'id', $is_drp) ? '!!При удалении заказа возникла ошибка' : 'Заказ был успешно удалён';
		set_cookie('popup_msg', $msg_text);
		$this->edit_pre_order($po_entry['po_id']);
	}

	
	public function save_po_entry($po_id, $poe_id = NULL)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$preorder = $this->model->get_preorders($po_id)[0];
		if($preorder['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();		
		$val_arr = ['is_example', 'is_nonstandard', 'nstrd_descr', 'r_prodDD', 'decorDD', 'nailsDD', 'stitchingDD', 'cornersDD', 'armrestDD', 'pillowsDD', 'cm_list_hidden', 'drapery_radio', 'xtra_discount_val', 'summ_total', 'quantity'];
		$submit_data = array('po_id'=>$po_id);
		$p_arr = $this->input->post();
		foreach(['is_example', 'is_nonstandard'] as $i){
			if(!isset($p_arr[$i])) $p_arr[$i] = 0;
		}
		switch($p_arr['drapery_radio']){
			case '1':
				$val_arr[] = 'draperyDD0';
				break;
			case '2':
				for($i=1;$i<=6;$i++){
					$val_arr[] = 'draperyDD'.$i;
				}
				break;
			case '3':
				for($i=1;$i<=8;$i++){
					$val_arr = array_merge($val_arr, ['ind_draperyDD'.$i, 'ind_comment'.$i]);
				}
				break;
		}
		$dd_ids = ['r_prodDD', 'decorDD', 'nailsDD', 'stitchingDD', 'cornersDD', 'armrestDD', 'pillowsDD', 'xtra_discount_val'];
		for($i=0;$i<=8;$i++){
			if($i<=6) $dd_ids[] = 'draperyDD'.$i;
			if($i) $dd_ids[] = 'ind_draperyDD'.$i;
		}
		foreach($dd_ids as $i){
			if(isset($p_arr[$i]) && in_array($i, $val_arr)) $p_arr[$i] = substr($p_arr[$i], 3);
		}
		if($p_arr['is_nonstandard']) $p_arr['xtra_discount_val'] = ($p_arr['nstrd_markup'] != 'val0') ? -1*(int)substr($p_arr['nstrd_markup'], 3) : (int)substr($p_arr['nstrd_discount'], 3);
		if($poe_id) $submit_data['id'] = $poe_id;
		foreach($val_arr as $i){
			if(isset($p_arr[$i])) $submit_data[$i] = $p_arr[$i];
		}
		if(count(array_keys($submit_data))>1) $this->model->update_po_entry($submit_data);
		set_cookie('popup_msg', (isset($msg_text) && $msg_text) ? $msg_text : 'Изменения успешно внесены');
		$this->edit_pre_order($po_id);
	}
	
	public function save_poe_drp($po_id, $poe_id = NULL)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$preorder = $this->model->get_preorders($po_id)[0];
		if($preorder['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();		
		$val_arr = ['nstrd_descr', 'draperyDD', 'summ_total', 'quantity'];
		$submit_data = array('po_id'=>$po_id);
		if($poe_id) $submit_data['id'] = $poe_id;
		$p_arr = $this->input->post();
		foreach($val_arr as $i){
			if($i == 'draperyDD') $submit_data[$i] = substr($p_arr[$i], 3);
			elseif($i == 'quantity') $submit_data[$i] = (float)str_replace(',', '.', $p_arr[$i]);
			else $submit_data[$i] = $p_arr[$i];
		}
		$this->model->update_poe_drp($submit_data);
		set_cookie('popup_msg', (isset($msg_text) && $msg_text) ? $msg_text : 'Изменения успешно внесены');
		$this->edit_pre_order($po_id);
	}
	
	public function print_po_entry($poe_id)
	{
		if($this->session->user_type != 'salon' || !$this->data['s_info']['show_preorders']) $this->index();
		$po_entry = $this->model->get_po_entry($poe_id);
		$preorder = $this->model->get_preorders($po_entry['po_id'])[0];
		if($preorder['sln_id'] != $this->data['s_info']['id']) $this->pre_orders();
		$poe_values = array();
		foreach(['sln_order_id', 'dlvr_type', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_flat', 'dlvr_stage', 'dlvr_porch', 'dlvr_dmphn', 'has_lift', 'phone1', 'phone2', 'email'] as $i){
			if(!$preorder[$i]) continue;
			if($i == 'dlvr_house' &&  $preorder['dlvr_corp']) $poe_values['dlvr_house'] = $preorder['dlvr_house'].(ctype_digit($preorder['dlvr_corp'][0]) ? 'стр' : '').$preorder['dlvr_corp'];
			else $poe_values[$i] = in_array($i, ['phone1', 'phone2']) ? $this->phone_format($preorder[$i]) : $preorder[$i];
		}
		if(isset($this->data['s_info']['name'])) $poe_values['salon'] = $this->data['s_info']['name'];
		if(isset($this->data['k_info']['name'])) $poe_values['kagent'] = $this->data['k_info']['name'];
		if(isset($this->data['s_info']['mailto'])) $poe_values['sln_mailto'] = $this->data['s_info']['mailto'];
		if(isset($this->data['s_info']['phone'])) $poe_values['sln_phone'] = $this->phone_format($this->data['s_info']['phone']);
		$poe_values['sln_address'] = isset($this->data['s_info']['address']) ? $this->data['s_info']['address'] : '';
		$customer = '';
		foreach(['surname', 'name1', 'name2'] as $i){
			if($preorder['cstmr_'.$i]) $customer .= $preorder['cstmr_'.$i] . ' ';
		}
		$poe_values['customer'] = substr($customer, 0, -1);
		$poe_values['poe_date'] = $this->date_format(explode(' ', $po_entry['stamp'])[0]);
		foreach(['is_example', 'is_nonstandard', 'category', 'quantity'] as $i){
			if($po_entry[$i]) $poe_values[$i] = $po_entry[$i];
		}
		$rp_options = $this->model->get_rp_options($this->model->get_rp_id($po_entry['r_prod_id']));
		$poe_values['rp_name'] = $rp_options['name'];
		$src_pics_path = '/var/www/lk/public_html/pics_drp/';
		if($po_entry['drapery_type'] == '2') $poe_values['rp_pic_src'] = file_exists($src_pics_path."numbered/{$po_entry['r_prod_id']}.jpg") ? site_url("pics_drp/numbered/{$po_entry['r_prod_id']}.jpg") : '';
		else $poe_values['rp_pic_src'] = file_exists($src_pics_path."unnumbered/{$po_entry['r_prod_id']}.jpg") ? site_url("pics_drp/unnumbered/{$po_entry['r_prod_id']}.jpg") : '';
		if($po_entry['extra_modules'] != '{}') $poe_values['modules'] = $this->model->get_extra_modules($poe_id, FALSE);
		$poe_values['extra'] = $this->model->get_poe_extra($poe_id);
		if($po_entry['drapery_type']){
			$this->load->library('table');
			$this->table->set_heading(($po_entry['drapery_type'] == '3' ? 'Индивидуальный' : 'Стандартный').' крой', 'Ткань/кожа', 'Коллекция', 'Поставщик', 'Категория', 'Расход');
			if($po_entry['drapery_type'] == '3'){
				for($i=1;$i<=8;$i++){
					if(!$po_entry["ind_drapery_id$i"]) continue;
					$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["ind_drapery_id$i"]))[0];
					$this->table->add_row($po_entry["ind_comment$i"], $drapery['name'], $drapery['collection'], $drapery['trade_mark'], $drapery['ctgr_name'], '');
				}
			}
			elseif($po_entry['drapery_type'] == '2'){
				if(isset($rp_options['drp_names'])){
					for($i=1;$i<=6;$i++){
						if(!$po_entry['drapery_id'.$i] || !$rp_options['drp_names']['drp'.$i]) continue;
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry['drapery_id'.$i]))[0];
						$drp_no = 'Ткань №'.$i.(strlen($rp_options['drp_names']['drp'.$i]) > 1 ? "({$rp_options['drp_names']['drp'.$i]})" : '');
						$this->table->add_row($drp_no, $drp['name'], $drp['collection'], $drp['trade_mark'], $drp['ctgr_name'], '');
					}
				}
				else{
					$dt2_list=["Подушки", "Сиденья", "Спинка, царга", "Кант"];
					for($i=1;$i<=6;$i++){
						if(!$po_entry["drapery_id$i"]) continue;
						$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["drapery_id$i"]))[0];
						$this->table->add_row("Ткань №$i".($i>count($dt2_list) ? '' : '('.$dt2_list[$i-1].')'), $drapery['name'], $drapery['collection'], $drapery['trade_mark'], $drapery['ctgr_name'], '');
					}
				}
			}
			else{
				$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["drapery_id0"]))[0];
				$this->table->add_row('Всё изделие в одной ткани', $drapery['name'], $drapery['collection'], $drapery['trade_mark'], $drapery['ctgr_name'], '');
			}
			$this->table->set_template(array('table_open'=>'<table id="drapery_table">'));
			$poe_values['drapery_table'] = $this->table->generate();
		}
		else $poe_values['drapery_table'] = '';
		$poe_values['cm_list'] = $this->model->get_extra_modules($poe_id);
		$this->load->view('orders/print_po_entry', $poe_values);
	}

	public function storage()
	{
		$strg = $this->model->get_storage();
		$rsrv = $this->model->get_reserved();
		$str_rows = [];
		foreach($strg as $i){
			if(in_array($i['uniq_id'], $rsrv['other'])) continue;
			$ri = $this->model->get_reserved_item($i['uniq_id'], 'order_ext_id');
			$r_st = in_array($i['uniq_id'], $rsrv['sln']) ? 'Зарезервирован '.$this->date_format($ri['stamp']) : 'Доступен';
			$str_rows[] = array_merge($this->make_storage_row($i), array($r_st));
		}
		if(!empty($str_rows)) $this->data['js'] = $this->load->view('js/js_order_info', array('url_info'=>site_url('reserve').'/', 'table_id'=>'storage_table'), TRUE);
		$this->show_storage($this->make_sticky_table('storage_table', ["Фабр-й №", "№ в салоне", "Номенклатура", "Комплектация", "Количество", "Статус", '&nbsp;'], $str_rows, 145));
	}
	
	public function reserve($order_id) //в зависимости от того, зарезервирован ли заказ, будем выводить add_reserve либо show_reserve
	{
		$order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id), TRUE);
		if($order){
			$rs_id = $this->model->is_reserved($order['uniq_id'], 'order_ext_id');
			if($rs_id && $rs_id != $this->data['s_info']['id']) $this->storage();
			if(!$rs_id) $this->add_reserve($order, TRUE);
			else $this->show_reserve($order);
		}
		else $order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id));
		if(!$order) $this->index();
		$rs_id = $this->model->is_reserved($order['uniq_id'], 'order_ext_id');
		if(!$rs_id) $this->add_reserve($order);
		elseif($rs_id == $this->data['s_info']['id']) $this->show_reserve($order);
		else $this->podium();
	}
	
	public function add_reserve($o_arr, $strg=FALSE)
	{
		$this->ext_js[] = 'phone_mask.js';
		$this->data['js'] = $this->load->view('js/js_add_reserve', NULL, TRUE);
		$dlvr = $this->load->view('templates/dlvr_edit', array('sln_address'=>$this->model->get_sln_address(), 'storage'=>$strg, 'is_podium'=>!$strg, 'is_rsrv'=>TRUE), TRUE);
		if(!$o_arr['sln_order_id']) $ostrg = NULL;
		else{
			if($strg) $ostrg = $this->model->get_storage_order($o_arr['sln_order_id']);
			else{
				$po = $this->model->get_preorders($o_arr['sln_order_id'], 'sln_order_id');
				$ostrg = isset($po[0]) ? $this->model->get_po_entry($po[0]['id'], 'po_id') : NULL;
			}
		}
		if($ostrg){
			$ostrg['sln_order_id'] = $o_arr['sln_order_id'];
			$ostrg['order_ext_id'] = $o_arr['uniq_id'];
		}
		if($this->data['s_info']['hide_fin_part']) $price_tbl = NULL;
		else{
			$this->load->library('table');
			$this->table->set_template(array('table_open'=>'<table class="order_info" id="price_info">'));
			$this->table->set_heading("Цена", "Скидка", "Цена с учётом скидки");
			$r_prod = $this->model->get_rp(array('name'=>$o_arr['nomenclature']))[0];
			$r_prod = $this->model->get_rp_options($r_prod['id']);
			if(!$ostrg){
				$ctgr = $this->model->non_storage_ctgr($o_arr['charact']);
				$price1 = $this->non_storage_cost($r_prod, $o_arr['complect'], $ctgr)*$o_arr['quantity'];
				$discount = $ctgr != 0 ? $r_prod['discount'] : 0;
			}
			else{
				if(isset($r_prod['price'])) $price1 = $r_prod['price'];
				elseif(isset($r_prod['modules'])){
					$m_arr = $this->model->get_extra_modules($ostrg['id'], FALSE);
					$price1 = 0;
					foreach($m_arr as $i){
						$price1 += $this->model->get_module_price($i['id'], $ostrg['category']);
					}
				}
				else $price1 = $this->model->get_rp_price($r_prod['id'], $ostrg['category']);
				$price1 *= $ostrg['quantity'];
				$discount = $ostrg['category'] != 0 ? $r_prod['discount'] + $ostrg['xtra_discount'] : $ostrg['xtra_discount'];
			}
			if($price1 > 0){
				$price2 = 0.01*(int)($price1*(100-$discount));
				$discount = $discount > 0 ? "$discount%" : '-';
				$this->table->add_row($this->out_summ($price1).' руб.', $discount, $this->out_summ($price2).' руб.');
				$price_tbl = $this->table->generate();
			}
			else{
				$this->table->clear();
				$price_tbl = NULL;
			}
			
		}
		if($ostrg) $ostrg['price_tbl'] = $price_tbl;
		$o_arr['price_tbl'] = $price_tbl;
		$basic_info = $ostrg ? $this->basic_storage_info($ostrg) : $this->basic_order_info($o_arr, TRUE);
		$email = $this->model->get_email($this->session->user_id);
		if($email) $this->session->email = $email;
		else $this->session->unset_userdata('email');
		$this->data['content'] = $this->load->view('orders/add_reserve', array('basic_info'=>$basic_info, 'dlvr'=>$dlvr, 'order_id'=>$o_arr['order_id'], 'strg'=>$strg, 'bill_disabled'=>($this->session->has_userdata('email') || $this->session->has_userdata('emails')) && $this->has_pdf($o_arr['order_id']) ? FALSE : TRUE, 'blank_disabled'=> $ostrg ? FALSE : TRUE), TRUE);
		$this->load_lk_view();
	}
	
	public function show_reserve($order, $dlv_info=TRUE)
	{
		$st_ids = $this->model->storage_ids();
		if(!$order['sln_order_id']) $ostrg = NULL;
		else{
			if($order['sln_id'] == $st_ids['sln_id'] && $order['kagent_id'] == $st_ids['kagent_id']) $ostrg = $this->model->get_storage_order($order['sln_order_id']);
			else{
				$po = $this->model->get_preorders($order['sln_order_id'], 'sln_order_id');
				$ostrg = isset($po[0]) ? $this->model->get_po_entry($po[0]['id'], 'po_id') : NULL;
			}
		}
		if($ostrg){
			$ostrg['sln_order_id'] = $order['sln_order_id'];
			$ostrg['order_ext_id'] = $order['uniq_id'];
		}
		$data_arr = array('dlv_info'=>$dlv_info, 'basic_info'=> $ostrg ? $this->basic_storage_info($ostrg) : $this->basic_order_info($order, TRUE));
		$rsrv = $this->model->get_reserved_item($order['uniq_id'], 'order_ext_id');
		foreach(['dlvr_type', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_flat', 'dlvr_stage', 'dlvr_porch', 'dlvr_dmphn', 'has_lift', 'phone1', 'phone2', 'email', 'dlvr_comment', 'is_example', 'dscnt_retail'] as $i){
			if(!$rsrv[$i]) continue;
			if($i == 'dlvr_house' &&  $rsrv['dlvr_corp']) $data_arr['dlvr_house'] = $rsrv['dlvr_house'].(ctype_digit($rsrv['dlvr_corp'][0]) ? 'стр' : '').$rsrv['dlvr_corp'];
			else $data_arr[$i] = in_array($i, ['phone1', 'phone2']) ? $this->phone_format($rsrv[$i]) : $rsrv[$i];
		}
		if($rsrv['dlvr_type'] == 'salon') $data_arr['sln_address'] = $this->model->get_sln_address($this->session->user_type == 'salon' ? NULL : $rsrv['sln_id']);
		$customer = '';
		foreach(['surname', 'name1', 'name2'] as $i){
			if($rsrv['cstmr_'.$i]) $customer .= $rsrv['cstmr_'.$i] . ' ';
		}
		$data_arr['customer'] = strlen($customer) ? substr($customer, 0, -1) : '';
		$this->data['content'] = $this->load->view('orders/show_reserve', $data_arr, TRUE);
		$this->load_lk_view();
	}
	
	public function submit_reserve($order_id)
	{
		if($this->session->user_type != 'salon') $this->index();
		$order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id), TRUE);
		$strg = $order ? TRUE : FALSE;
		if(!$order) $order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id));
		if(!$order){
			set_cookie('popup_msg', '!!Ошибка резервирования: заказ не найден');
			$this->index();
		}
		if($this->model->is_reserved($order['uniq_id'])){
			set_cookie('popup_msg', '!!Ошибка резервирования: заказ уже был зарезервирован ранее');
			if($strg) $this->storage();
			else $this->podium();
		}
		$p_arr = $this->input->post();
		$val_arr = ['cstmr_surname', 'cstmr_name1', 'cstmr_name2', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_corp', 'dlvr_porch', 'dlvr_flat', 'dlvr_dmphn', 'dlvr_stage', 'email', 'summ_dlvr', 'dlvr_type', 'dlvr_comment', 'summ_up'];
		$submit_data = array('sln_id'=>$this->data['s_info']['id'], 'kagent_id'=>$this->data['s_info']['kagent_id'], 'order_ext_id'=>$order['uniq_id'], 'is_example'=>isset($p_arr['rsrv_is_example']) ? $p_arr['rsrv_is_example'] : 0);
		foreach(['has_lift', 'count_dlvr'] as $i){
			if(!isset($p_arr[$i])) $p_arr[$i] = 0;
		}
		foreach(['phone1', 'phone2'] as $i){
			if(isset($p_arr[$i])){
				$new_phone = preg_replace('/\D/', '', $p_arr[$i]);
				$p_arr[$i] = $new_phone ? (int)$new_phone : NULL;
			}
		}
		foreach($val_arr as $i){
			if(!isset($p_arr[$i])) continue;
			$submit_data[$i] = in_array($i, ['summ_up', 'summ_dlvr']) ? 100*(int)$p_arr[$i] : $p_arr[$i];
		}
		$submit_data['dscnt_retail'] = isset($p_arr['dscnt_retail']) ? (int)substr($p_arr['dscnt_retail'], 3) : 0;
		$submit_data['dscnt_whole'] = $submit_data['dscnt_retail']/2;
		$this->model->reserve($submit_data, $strg);
		$pdf_path = '/var/preorders_xtra/podium/';
		$upload_config = array('overwrite'=>TRUE, 'upload_path'=>$pdf_path, 'allowed_types'=>'pdf');
		$this->load->library('upload');
		foreach($_FILES as $k=>$vl){
			if(!$vl['size']) continue;
			$upload_config['file_name'] = $k == 'dlvr_upld' ? $order_id.'-dlvr.pdf' : $order_id.'.pdf';
			$this->upload->initialize($upload_config);
			$u_res = $this->upload->do_upload($k);
			if(!$u_res) $msg_text = '!!Ошибка при загрузке: '.$this->upload->display_errors();
		}
		$pdf_list = scandir($pdf_path);
		foreach($pdf_list as $i){
			if(preg_match('/_/', $i)) rename($pdf_path.$i, $pdf_path.str_replace('_', '.', $i));
		}
		set_cookie('popup_msg', (isset($msg_text) && $msg_text) ? $msg_text : 'Заказ зарезервирован');
		if($strg) $this->storage();
		else $this->podium();
	}
	
	public function print_blank($order_id)
	{
		if($this->session->user_type != 'salon') $this->index();
		$order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id), TRUE);
		$strg = $order ? TRUE : FALSE;
		if(!$order) $order = $this->model->get_orders(array('id_type'=>'order_id', 'id'=>$order_id));
		if(!$order){
			set_cookie('popup_msg', '!!Ошибка: заказ не найден');
			$this->index();
			return FALSE;
		}
		if($this->model->is_reserved($order['uniq_id'])){
			set_cookie('popup_msg', '!!Ошибка: заказ уже был зарезервирован ранее');
			if($strg) $this->storage();
			else $this->podium();
			return FALSE;
		}
		$rsrv = $this->model->get_reserved_item($order['uniq_id'], 'order_ext_id');
		$val_arr = ['cstmr_surname', 'cstmr_name1', 'cstmr_name2', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_corp', 'dlvr_porch', 'dlvr_flat', 'dlvr_dmphn', 'dlvr_stage', 'has_lift', 
			'phone1', 'phone2', 'email', 'summ_dlvr', 'count_dlvr', 'dlvr_type', 'dlvr_comment', 'summ_up'];
		$preorder = $this->model->get_preorders($order['sln_order_id'], 'sln_order_id', $strg)[0];
		//var_dump($preorder);
		$po_consist = $this->model->get_po_consist($preorder['id']);
		foreach($po_consist as $i){
			$mdl_name = $this->model->get_rp(array('ext_id'=>$i['r_prod_id']))[0]['name'];
			if($mdl_name == $order['nomenclature']){
				$poe_id = $i['id'];
				break;
			}
		}
		$po_entry = $this->model->get_po_entry($poe_id);
		$poe_values = array('order_id'=>$order_id, 'sln_order_id'=>$order['sln_order_id']);
		foreach(['dlvr_type', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_flat', 'dlvr_stage', 'dlvr_porch', 'dlvr_dmphn', 'has_lift', 'phone1', 'phone2', 'email'] as $i){
			if(!$rsrv[$i]) continue;
			if($i == 'dlvr_house' &&  $rsrv['dlvr_corp']) $poe_values['dlvr_house'] = $rsrv['dlvr_house'].(ctype_digit($rsrv['dlvr_corp'][0]) ? 'стр' : '').$rsrv['dlvr_corp'];
			else $poe_values[$i] = in_array($i, ['phone1', 'phone2']) ? $this->phone_format($rsrv[$i]) : $rsrv[$i];
		}
		if(isset($this->data['s_info']['name'])) $poe_values['salon'] = $this->data['s_info']['name'];
		if(isset($this->data['k_info']['name'])) $poe_values['kagent'] = $this->data['k_info']['name'];
		if(isset($this->data['s_info']['mailto'])) $poe_values['sln_mailto'] = $this->data['s_info']['mailto'];
		if(isset($this->data['s_info']['phone'])) $poe_values['sln_phone'] = $this->phone_format($this->data['s_info']['phone']);
		$poe_values['sln_address'] = isset($this->data['s_info']['address']) ? $this->data['s_info']['address'] : '';
		$customer = '';
		foreach(['surname', 'name1', 'name2'] as $i){
			if($rsrv['cstmr_'.$i]) $customer .= $preorder['cstmr_'.$i] . ' ';
		}
		$poe_values['customer'] = substr($customer, 0, -1);
		$poe_values['poe_date'] = $this->date_format(explode(' ', $po_entry['stamp'])[0]);
		foreach(['is_example', 'is_nonstandard', 'category', 'quantity'] as $i){
			if($po_entry[$i]) $poe_values[$i] = $po_entry[$i];
		}
		$rp_options = $this->model->get_rp_options($this->model->get_rp_id($po_entry['r_prod_id']));
		$poe_values['rp_name'] = $rp_options['name'];
		$src_pics_path = '/var/www/lk/public_html/pics_drp/';
		if($po_entry['drapery_type'] == '2') $poe_values['rp_pic_src'] = file_exists($src_pics_path."numbered/{$po_entry['r_prod_id']}.jpg") ? site_url("pics_drp/numbered/{$po_entry['r_prod_id']}.jpg") : '';
		else $poe_values['rp_pic_src'] = file_exists($src_pics_path."unnumbered/{$po_entry['r_prod_id']}.jpg") ? site_url("pics_drp/unnumbered/{$po_entry['r_prod_id']}.jpg") : '';
		if($po_entry['extra_modules'] != '{}') $poe_values['modules'] = $this->model->get_extra_modules($poe_id, FALSE);
		$poe_values['extra'] = $this->model->get_poe_extra($poe_id);
		if($po_entry['drapery_type']){
			$this->load->library('table');
			$this->table->set_heading(($po_entry['drapery_type'] == '3' ? 'Индивидуальный' : 'Стандартный').' крой', 'Ткань/кожа', 'Коллекция', 'Поставщик', 'Категория', 'Расход');
			if($po_entry['drapery_type'] == '3'){
				for($i=1;$i<=8;$i++){
					if(!$po_entry["ind_drapery_id$i"]) continue;
					$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["ind_drapery_id$i"]))[0];
					$this->table->add_row($po_entry["ind_comment$i"], $drapery['name'], $drapery['collection'], $drapery['trade_mark'], $drapery['ctgr_name'], '');
				}
			}
			elseif($po_entry['drapery_type'] == '2'){
				if(isset($rp_options['drp_names'])){
					for($i=1;$i<=6;$i++){
						if(!$po_entry['drapery_id'.$i] || !$rp_options['drp_names']['drp'.$i]) continue;
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry['drapery_id'.$i]))[0];
						$drp_no = 'Ткань №'.$i.(strlen($rp_options['drp_names']['drp'.$i]) > 1 ? "({$rp_options['drp_names']['drp'.$i]})" : '');
						$this->table->add_row($drp_no, $drp['name'], $drp['collection'], $drp['trade_mark'], $drp['ctgr_name'], '');
					}
				}
				else{
					$dt2_list=["Подушки", "Сиденья", "Спинка, царга", "Кант"];
					for($i=1;$i<=6;$i++){
						if(!$po_entry["drapery_id$i"]) continue;
						$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["drapery_id$i"]))[0];
						$this->table->add_row("Ткань №$i".($i>count($dt2_list) ? '' : '('.$dt2_list[$i-1].')'), $drapery['name'], $drapery['collection'], $drapery['trade_mark'], $drapery['ctgr_name'], '');
					}
				}
			}
			else{
				$drapery = $this->model->get_rp_drapery(NULL, array('ext_id'=>$po_entry["drapery_id0"]))[0];
				$this->table->add_row('Всё изделие в одной ткани', $drapery['name'], $drapery['collection'], $drapery['trade_mark'], $drapery['ctgr_name'], '');
			}
			$this->table->set_template(array('table_open'=>'<table id="drapery_table">'));
			$poe_values['drapery_table'] = $this->table->generate();
		}
		else $poe_values['drapery_table'] = '';
		$poe_values['cm_list'] = $this->model->get_extra_modules($poe_id);
		$this->load->view('orders/print_po_entry', $poe_values);
	}
}
