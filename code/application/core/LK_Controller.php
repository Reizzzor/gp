<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '/usr/share/php/PHPMailer/src/Exception.php';
require '/usr/share/php/PHPMailer/src/PHPMailer.php';
require '/usr/share/php/PHPMailer/src/SMTP.php';

class LK_Controller extends CI_Controller {

	protected $targets = array('admin' => 'admin', 'kagent' => 'orders', 'salon' => 'orders', 'manager' => 'manager', 'store'=>'store');
	protected $model;
	protected $data = array();
	protected $ext_js = ['main.js', 'pre.js'];
	
	public function __construct()
	{
		parent::__construct();
		$this->load->library('session');
		$this->load->helper(array('cookie', 'url', 'html', 'form'));
		$this->data['css_link2'] = link_tag('css/main.css');
		$this->data['css_link'] = link_tag('css/all.css');
		$this->data['pdf_dir'] = '/var/www/lk/pdf';
		$this->data['js'] = $this->load->view('js/js_default', NULL, TRUE);
		$this->data['js_onload'] = $this->load->view('js/js_onload', NULL, TRUE);
		$this->data['a_logout'] = anchor('logout', '<span class="fas fa-sign-out-alt"></span>Выйти');
		$this->output->set_header('Cache-Control: max-age=43200');
	}
	
	protected function has_pdf($order_id)
	{
		$pdf_arr = array_diff(scandir($this->data['pdf_dir']), array('.','..'));
		foreach($pdf_arr as $item){
			if((float)$order_id === (float)substr($item, 0, -4)) return $this->data['pdf_dir'].'/'.$item;
		}
		return 0;
	}
	
	protected function check_user_type($usrtype)
	{
		if(!$this->session->has_userdata('user_type')) redirect('logout', 'location');	
		if($this->session->user_type !== $usrtype) redirect('logout', 'location');
		if(!get_cookie('scr_y')) set_cookie('scr_y', 900);
		if(!get_cookie('scr_x')) set_cookie('scr_x', 1600);
		if(!$this->session->has_userdata('show_sbm1')) $this->session->show_sbm1 = '0';
		if(!$this->session->has_userdata('show_sbm2')) $this->session->show_sbm2 = '0';
		$this->data['user_type'] = $usrtype;
		$this->user_target = $this->targets[$usrtype];
		set_cookie('ch_path', site_url($this->user_target.'/cookies_handler'));
		$model_name = $this->user_target.'_model';
		$this->load->model($model_name);
		$this->model = $this->{$model_name};
		$this->data['o_statuses'] = $this->model->get_order_statuses();
		if($this->session->has_userdata('shown_msg')){
			$this->session->unset_userdata('shown_msg');
			delete_cookie('popup_msg');
		}
	}
	
	protected function make_user_button($id, $name, $content, $onclick)
	{
		return '<div class="usr_btn" id="'.$id.'">'.form_button(array('name'=>$name, 'content'=>$content, 'onClick'=>$onclick)).'</div>';
	}	
	
	protected function date_format($date_str)
	{
		return $date_str[0] === '2' ? date('d-m-Y', strtotime(substr($date_str, 0, 10))) : '';
	}
	
	protected function phone_format($phone_str)
	{
		return $phone_str && strlen($phone_str) == 11 ? '+'.$phone_str[0].'('.substr($phone_str, 1, 3).')'.substr($phone_str, 4, 3).'-'.substr($phone_str, 7, 2).'-'.substr($phone_str, -2) : '';
	}
	
	protected function load_lk_view()
	{
		if(get_cookie('popup_msg')) $this->session->shown_msg = get_cookie('popup_msg');
		$this->data['ext_js'] = $this->get_ext_js();
		$this->data['searchlink'] = $this->user_target.'/search/';
		$this->load->view('templates/lk-template', $this->data);	
	}

	protected function get_yes_or_no($bool_var)	
	{
		return $bool_var ? 'Да' : 'Нет';
	}
	
	protected function out_summ($summ)
	{
		$inpt = explode('.', strval($summ));
		$res = '';
		if(strlen($inpt[0]) <= 3) $res = $inpt[0];
		else{
			for($i=0;$i<strlen($inpt[0]);$i++){
				if($i && strlen($inpt[0])%3 == $i%3) $res .= ' ';
				$res .= $inpt[0][$i];
			}
		}
		if(isset($inpt[1]) && $inpt[1]) $res .= ','.$inpt[1];
		return $res;
	}
	
	protected function get_ext_js()
	{
		$res_js = '';
		foreach($this->ext_js as $i){
			$res_js .= '<script src="'.site_url("js/$i").'"></script>';
		}
		return $res_js;
	}

	protected function get_order_status($ordstatus_id)
	{
		foreach($this->data['o_statuses'] as $o_s){
			if($o_s['id'] === $ordstatus_id) return $o_s['name'];
		}
		return FALSE;
	}
	
	protected function make_sticky_table($tbl_id, $th_arr, $rows, $y_offset=NULL)
	{
		if(empty($rows)) return NULL;
		$this->load->library('table');
		$this->table->set_heading($th_arr);
		foreach($rows as $i){
			$this->table->add_row($i);
		}
		$tbl_template = array('table_open' => '<table class="table_resize" id="'.$tbl_id.'">');
		if($y_offset !== NULL) $tbl_template['tbody_open'] = '<tbody style="max-height: '.((int)get_cookie('scr_y') - $y_offset).'px">';
		$this->table->set_template($tbl_template);
		return $this->table->generate();
	}
	
	protected function make_orders_table($orders, $t_name = 'Информация о заказах: ')
	{	
		$ords_rows = [];
		foreach($orders as $order){
			#'sln_order_id', 'order_id', 'nomenclature', 'charact', 'quantity', 'complect', 'ready_stamp', 'order_status', 'pay', 'is_podium', 'not_standart', 'kept_stamp', 'out_stamp', 'customer', 'phone1', 'phone2', 'address', 'comment', 'sln_name', 'kagent_name', 'kagent_inn'		
			$order_status = $this->get_order_status($order['order_status_id']);
			$drapery = $this->model->get_drapery($order['uniq_id'], 'uniq_id');
			if(!empty($drapery)){
				$dr_item = $drapery[0];
				$has_drp =  $this->get_yes_or_no($dr_item['is_available']);
				$drp_stamp = $this->date_format($dr_item['stamp']);
			}
			else{
				$has_drp = '';
				$drp_stamp = '';
			}
			$ords_rows[] = [$order['sln_order_id'], $order['order_id'], $order['nomenclature'], $order['quantity'], $order_status, $this->get_yes_or_no($order['pay']), $this->get_yes_or_no($order['is_podium']), $this->date_format($order['kept_stamp']), $this->date_format($order['ready_stamp']), $this->date_format($order['out_stamp']), $has_drp, $drp_stamp];
		}
		$ords_tbl = $this->make_sticky_table('orders_table', ["№ в салоне", "Фабр. №", "Номенклатура", "Кол-во", "Статус", "Оплата", "Подиум. образец", "Дата принятия", "Дата готовности", "Дата отгрузки", "Наличие ткани", "Дата прихода"], $ords_rows, 145);
		if($ords_tbl) $this->data['js'] = $this->load->view('js/js_order_info', array('url_info'=>site_url($this->data['orders_url']).'/', 'table_id'=>'orders_table'), TRUE);
		if(isset($this->data['links']) && $this->data['links']) $t_name .= $this->data['links'];
		$this->data['content'] = heading($t_name, 2).$ords_tbl;		
		$this->load_lk_view();
	}

	protected function make_study_menu()
	{
		 $this->data['menu_list'][] = anchor('', '<i class="fas fa-graduation-cap" aria-hidden="true"></i>&nbsp;Обучение', 'onClick="toggleSubMenu(2); return false;"');
		 $submenu = array(anchor($this->user_target.'/userguide', 'Внесение заказов'), anchor($this->user_target.'/testing', 'Тестирование'));
		 $sbm_raw = $this->model->get_ssbm();
		 foreach($sbm_raw as $i){
			if(isset($i['new'])) $i['name'] .= ' ('.$i['new'].')';
			$submenu[] = anchor($this->user_target.'/howto/'.$i['id'], $i['name']);
		 }
		 $this->data['menu_list'][] = ul($submenu, array('id'=>'submenu2', 'style'=>"display: {$this->submenu_display_mode('show_sbm2')}"));
	}
	
	protected function submenu_display_mode($ud_name)
	{
		return $this->session->{$ud_name} == '1' ? 'block' : 'none';
	}

	protected function preview_link($full_link)
	{
		$ea = explode('/', $full_link);
		$c = count($ea) - 1;
		$r = '';
		for($i=0;$i<$c;$i++){
			$r.=$ea[$i].'/';
		}
		return $r.'previews/'.$ea[$c];
	}
	
	protected function youtube_link($code, $mode='preview')
	{
		switch($mode){
			case 'preview': return 'https://img.youtube.com/vi/'.$code.'/mqdefault.jpg';
			case 'embed': return 'https://www.youtube.com/embed/'.$code;
			case 'reverse':
				if(strpos($code, '/') === FALSE){
					$src = explode('=', $code);
					return $src[count($src)-1];
				}
				$src = explode('?', $code);
				if(strpos($src[1], '&') === FALSE) return substr($src[1], 2);
				$ss = explode('&', $src[1]);
				foreach($ss as $i){
					if(substr($i, 0, 2) == 'v=') return substr($i, 2);
				}
		}	
	}
	
	protected function del_tree($dir) { 
		$files = array_diff(scandir($dir), array('.','..')); 
		foreach ($files as $file) { 
		      (is_dir("$dir/$file")) ? $this->del_tree("$dir/$file") : unlink("$dir/$file"); 
		} 
		return rmdir($dir); 
	}
	
	protected function show_storage($strg_table=NULL)
	{
		$this->data['content'] = heading($strg_table ? 'Склад:' : 'Доступные для резервирования заказы отсутствуют', 2).$strg_table;
		$this->load_lk_view();
	}
	
	protected function basic_order_info($order, $i_mode=NULL)
	{
		$this->load->library('table');
		$tb_tmpl = array('table_open'=>'<table class="order_info">');
		if($i_mode) $order['i_mode'] = $i_mode;
		$order_id = $order['order_id'];
		$order['user_target'] = $this->user_target;
		$order['out_stamp'] = $this->date_format($order['out_stamp']);
		$order['order_status'] = $this->get_order_status($order['order_status_id']);
		$this->table->set_template($tb_tmpl);
		$this->table->set_heading("№ в салоне", "Фабр. №", "Номенклатура", "Хар-ка номенклатуры", "Кол-во", "Комплектация", "Дата готовности", "Статус"); //по просьбе Валентины убрал отображение скидки здесь
		$this->table->add_row($order['sln_order_id'], $order_id, $order['nomenclature'], $order['charact'], $order['quantity'], $order['complect'], $this->date_format($order['ready_stamp']), $order['order_status']);
		$order['oi_table'] = $this->table->generate();
		$this->table->clear();
		$drapery = $this->model->get_drapery($order['uniq_id'], 'uniq_id');
		if(!empty($drapery)){
			$this->table->set_template($tb_tmpl);
			$this->table->set_heading('Наименование ткани', 'Наличие', 'Дата прихода', 'Поставщик');
			foreach($drapery as $dr_item){
				$this->table->add_row($dr_item['name'], $this->get_yes_or_no($dr_item['is_available']), $this->date_format($dr_item['stamp']), $dr_item['producer']);
			}
			$order['d_table'] = $this->table->generate();
		}
		else $order['d_table'] = NULL;
		$order['is_paid'] = $this->get_yes_or_no($order['pay']);
		foreach(array_keys($order) as $i){
			if(!$order[$i] && !in_array($i, ['is_podium', 'not_standart', 'comment'])) unset($order[$i]);
		}
		return $this->load->view('templates/order_basic_info', $order, TRUE);
	}
	
	protected function basic_storage_info($sti)
	{
		$this->load->library('table');
		$order = $this->model->get_orders(array('id_type'=>'uniq_id', 'id'=>$sti['order_ext_id']), TRUE);
		$sti['i_mode'] = TRUE;
		$r_prod = $this->model->get_rp(array('ext_id'=>$sti['r_prod_id']));
		if(!empty($r_prod)) $r_prod = $this->model->get_rp_options($r_prod[0]['id']);
		$this->table->set_template(array('table_open'=>'<table class="order_info" id="o_info">'));
		$this->table->set_heading("№ в салоне", "Фабр. №", "Номенклатура", "Категория", "Кол-во", "Комплектация", "Дата");
		$this->table->add_row($sti['sln_order_id'], $order['order_id'], $order['nomenclature'], $sti['category'], $sti['quantity'], $order['complect'], $this->date_format($sti['stamp']));
		$sti['oi_table'] = $this->table->generate();
		if($sti['drapery_type']){
			$this->table->clear();
			switch($sti['drapery_type']){
				case '1':
					if(!$sti['drapery_id0']) break;
					$this->table->set_template(array('table_open'=>'<table class="order_info" id="drp_info">'));
					$sti['d_type'] = 'всё изделие - в одной ткани';
					$this->table->set_heading("Наименование ткани", "Коллекция", "Торговая марка", "Категория");
					$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$sti['drapery_id0']))[0];
					$this->table->add_row($drp['name'], $drp['collection'], $drp['trade_mark'], $drp['ctgr_name']);
					break;
				case '2':
					$this->table->set_template(array('table_open'=>'<table class="order_info" id="drp_info">'));
					$sti['d_type'] = 'в разных тканях';
					$this->table->set_heading("№", "Наименование ткани", "Коллекция", "Торговая марка", "Категория");
					if(isset($r_prod['drp_names'])){
						for($i=1;$i<=6;$i++){
							if(!$sti['drapery_id'.$i] || !$r_prod['drp_names']['drp'.$i]) continue;
							$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$sti['drapery_id'.$i]))[0];
							$drp_no = $i.(strlen($r_prod['drp_names']['drp'.$i]) > 1 ? "({$r_prod['drp_names']['drp'.$i]})" : '');
							$this->table->add_row($drp_no, $drp['name'], $drp['collection'], $drp['trade_mark'], $drp['ctgr_name']);
						}
					}
					else{
						$dt2_list=["Подушки", "Сиденья", "Спинка, царга", "Кант"];
						for($i=1;$i<=$r_prod['drapery_num'];$i++){
							if(!$sti['drapery_id'.$i]) continue;
							$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$sti['drapery_id'.$i]))[0];
							$drp_no = $i.($i>count($dt2_list) ? '' : "({$dt2_list[$i-1]})");
							$this->table->add_row($drp_no, $drp['name'], $drp['collection'], $drp['trade_mark'], $drp['ctgr_name']);
						}
					}
					break;
				case '3':
					$this->table->set_template(array('table_open'=>'<table class="order_info" id="drp_info">'));
					$sti['d_type'] = 'индивидуальный';
					$this->table->set_heading("№", "Наименование ткани", "Коллекция", "Торговая марка", "Категория", "Комментарий по ткани");
					for($i=1;$i<=8;$i++){
						if(!$sti['ind_drapery_id'.$i]) continue;
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$sti['ind_drapery_id'.$i]))[0];
						$this->table->add_row($i, $drp['name'], $drp['collection'], $drp['trade_mark'], $drp['ctgr_name'], $sti['ind_comment'.$i]);
					}
					break;
			}
		}
		$drp_tbl = $this->table->generate();
		if(isset($drp_tbl) && $drp_tbl) $sti['d_table'] = $drp_tbl;
		if(isset($sti['is_nonstandard'])) $sti['not_standart'] = $sti['is_nonstandard'];
		//if(isset($sti['is_example'])) $sti['is_podium'] = $sti['is_example'];
		if(isset($sti['nstrd_descr'])) $sti['comment'] = $sti['nstrd_descr'];
		return $this->load->view('templates/order_basic_info', $sti, TRUE);
	}
	
	protected function order_info($ord)
	{
		$order = array('basic_info'=>$this->basic_order_info($ord), 'bill_disabled'=>($this->session->has_userdata('email') || $this->session->has_userdata('emails')) && $this->has_pdf($ord['order_id']) ? FALSE : TRUE);
		$this->data['content'] = $this->load->view('templates/oinfo_view', $order, TRUE);
		$this->load_lk_view();
	}
	
	protected function make_storage_row($ord)
	{
		$ctgr = $this->model->non_storage_ctgr($ord['charact']); //preg_replace('/\D/', '', $ord['charact']);
		$o_st = $this->get_order_status($ord['order_status_id']);
		return [$ord['order_id'], $ord['sln_order_id'], $ord['nomenclature'], $ord['complect'], $ord['quantity'], $o_st];
	}
	
	protected function non_storage_cost($rp, $compl, $ctg)
	{
		if(isset($rp['price'])) return $rp['price'];
		if(!is_numeric($ctg)) return NULL;
		if(isset($rp['modules'])){
			if(!$compl) return NULL;
			$c_list = explode(',', $compl);
			$cost = 0;
			foreach($c_list as $i){
				if(strpos($i, '-') === FALSE) continue;
				$m_arr = explode('-', $i);
				$m_arr[0] = preg_replace('/\s/', '', $m_arr[0]);
				if($m_arr[0] == 'ЛУ') $m_arr[0] = 'ЛондонУГл';
				foreach($rp['modules'] as $j){
					if($j['name'] == $m_arr[0]){
						$m_price = $this->model->get_module_price($j['id'], $ctg);
						$cost  += $m_price*(int)$m_arr[1];
						break;
					}
				}
			}
		}
		else $cost = $this->model->get_rp_price($rp['id'], $ctg);
		return $cost ? $cost : NULL;
	}
	
	protected function pgnt_table($tbl_rows, $base_url, $sgmt_num, $pg=0)
	{
		$this->load->library('pagination');
		$config['total_rows'] = count($tbl_rows);
		$config['per_page'] = 100;
		if(($config['total_rows'] > $config['per_page']) || $pg){
			$config['base_url'] = base_url($base_url);
			$config['uri_segment'] = $sgmt_num;
			$config['first_link'] = ' Первая ';
			$config['last_link'] = ' Последняя ';
			$config['prev_link'] = ' &lt; ';
			$config['next_link'] = ' &gt; ';
			$config['cur_tag_open'] = '<span id="active_page"> ';
			$config['cur_tag_close'] = ' </span>';
			$config['num_tag_open'] = ' ';
			$config['num_tag_close'] = ' ';
			$this->pagination->initialize($config);
			$this->data['links'] = $this->pagination->create_links();
			$pgnd_rows = array();
			$ei = $pg + $config['per_page'];
			for($i=$pg;$i<$ei;$i++){
				if(!isset($tbl_rows[$i])) break;
				$pgnd_rows[] = $tbl_rows[$i];
			}
		}
		else $pgnd_rows = $tbl_rows;
		return $pgnd_rows;
	}
	 
//public functions
	
	public function show_docs($a)
	{
		$docs = $this->model->get_db_list('docs', ['aggr_id',$a]);
		$vw_arr = array();
		if(!empty($docs)){
			$this->load->library('table');
			foreach($docs as $i){
				$ex_src = explode('.', $i['src']);
				/*if($ex_src[1] == 'pdf'){
					$is_pdf = TRUE;
					$on_click = 'onClick="viewPDF(\''.site_url($i['link']).'\'); return false;"';
					$dwnld = anchor('', $i['name'], $on_click);
				}
				else $dwnld = anchor($i['link'], $i['name'], 'download');*/
				$dwnld = anchor($i['link'], $i['name']);
				$this->table->add_row($dwnld, '[Добавлен '.$i['added'].']');
			}
			$this->table->set_template(array('table_open'=>'<table id="docs_table" class="invisible_table">'));
			$dt = $this->table->generate();
		}
		if(isset($is_pdf)){
			$this->ext_js = array_merge($this->ext_js, ['player_api.js', 'img_viewer.js']);
			$this->data['js'] = $this->load->view('js/js_tutorials_viewer', NULL, TRUE);
		}
		if(isset($dt)){
			$vw_arr['docs_table'] = $dt;
			$vw_arr['viewer'] = $this->load->view('templates/hidden_viewer', NULL, TRUE);
		}
		$this->data['content'] = $this->load->view('templates/docs_view', $vw_arr, TRUE);
		$this->load_lk_view();
	}
	
	public function show_imgs($a)
	{
		$imgs_arr = $this->model->get_db_list('imgs', ['aggr_id',$a]);
		$imgs = array();
		foreach($imgs_arr as $i){
			$imgs[] = '<div class="img_preview"><p class="img_pr">'.anchor('', img($this->preview_link($i['link'])), 'onClick="viewImg(\''.site_url($i['link']).'\'); return false;"').'</p><p class="img_name">'.$i['name'].'</p></div>';
		}
		$this->ext_js[] = 'img_viewer.js';
		$this->data['js'] = $this->load->view('js/js_tutorials_viewer', NULL, TRUE);
		$vw_arr = array('category'=>$this->model->pathman($a)['category'], 'imgs'=>$imgs);
		if(!empty($imgs)) $vw_arr['viewer'] = $this->load->view('templates/hidden_viewer', array('is_pic'=>TRUE), TRUE);
		$this->data['content'] = $this->load->view('templates/imgs_view', $vw_arr, TRUE);
		$this->load_lk_view();
	}
	
	public function show_video($a)
	{
		$v_arr = $this->model->get_db_list('video', ['aggr_id',$a]);
		$videos = array();
		foreach($v_arr as $i){
			$videos[] = '<span class="video_preview"><p class="v_pr">'.anchor('', img($this->youtube_link($i['src'])), 'onClick="viewVideo(\''.$this->youtube_link($i['src'], 'embed').'\'); return false;"').'</p><p class="v_name">'.$i['name'].'</p></span>';
		}
		$this->ext_js = array_merge($this->ext_js, ['player_api.js', 'img_viewer.js']);
		$this->data['js'] = $this->load->view('js/js_tutorials_viewer', NULL, TRUE);
		$vw_arr = array('category'=>$this->model->pathman($a)['category'], 'videos'=>$videos);
		if(!empty($videos)) $vw_arr['viewer'] = $this->load->view('templates/hidden_viewer', NULL, TRUE);
		$this->data['content'] = $this->load->view('templates/video_view', $vw_arr, TRUE);
		$this->load_lk_view();
	}
	
	public function howto($a)
	{
		$this->data['content'] = $this->load->view('templates/view_howto', array('pv'=>$this->model->get_ssbm($a), 'ss_link'=>$this->user_target.'/show_'), TRUE);
		$this->load_lk_view();
	}
	
	public function show_404()
	{
		$this->data['content'] = $this->load->view('templates/view404', NULL, TRUE);
		$this->output->set_status_header('404');
		$this->load_lk_view();
	}
	
	public function send_bill($order_id)
	{
		$email = new PHPMailer();
		$email->CharSet = "UTF-8";
		$email->setFrom('no-reply@lk.geniuspark.ru', 'PDF Счёт');
		$email->Name = 'PDF Bill';
		$subj = 'Счёт по заказу №'.$order_id;
		$email->Subject = substr($subj, -2) === '.0' ? substr($subj, 0, -2) : $subj;
		$mail_body = "Добрый день!\nСчёт по заказу $order_id";
		$email->Body = substr($subj, -2) === '.0' ? substr($mail_body, 0, -2).' - во вложении.' : "$mail_body - во вложении.";
		$email->addAddress($this->session->email);
		$attach = $this->has_pdf($order_id);
		$email->addAttachment($attach);
		$send_res = $email->Send();
		set_cookie('popup_msg', $send_res != '1' ? '!!'.$send_res : 'Сообщение успешно отправлено');
		$this->order_info($order_id);
	}
		
	public function drapery_layout()
	{
		$this->data['content'] = $this->load->view('orders/drapery_layout', array('tm_list'=>$this->model->get_drapery_layout(), 'arch_prefix'=>$this->targets[$this->data['user_type']]), TRUE);
		$this->load_lk_view();
	}
	
	public function drp_layout_archive()
	{
		$arch_files = scandir('/var/www/lk/public_html/drp_layout/archive');
		$arch_list = [];
		foreach($arch_files as $i){
			if(preg_match('/drp_wcoll/', $i)) $arch_list[] = array('name'=>$i, 'stamp'=>substr($i, 9, 10));
		}
		if(!empty($arch_list)){
			$arch_len = count($arch_list) - 1;
			$sort_flag = TRUE;
			while($sort_flag){
				$sort_flag = FALSE;
				for($i=0;$i<$arch_len;$i++){
					if(strtotime($arch_list[$i]['stamp']) < strtotime($arch_list[$i+1]['stamp'])){
						$tmp = $arch_list[$i+1];
						$arch_list[$i+1] = $arch_list[$i];
						$arch_list[$i] = $tmp;
						$sort_flag = TRUE;
					}
				}
			}
		}
		$this->data['content'] = $this->load->view('orders/drp_layout_archive', array('arch_list'=>$arch_list, 'arch_prefix'=>$this->targets[$this->data['user_type']]), TRUE);
		$this->load_lk_view();
	}
	
	public function storage()
	{
		$this->show_storage(NULL);
	}
	
	public function userguide()
	{
		$this->data['content'] = $this->load->view('templates/user_guide', NULL, TRUE);
		$this->load_lk_view();
	}
	
	public function testing()
	{
		$this->data['content'] = $this->load->view('templates/test_guide', NULL, TRUE);
		$this->load_lk_view();
	}
	
	public function search()
	{
		$srch = $this->input->post('search');
		if(!$srch){
			$this->index();
			return TRUE;
		} 
		$sln_id = isset($this->data['s_info']['id']) ? $this->data['s_info']['id'] : NULL;
		$res_search = $this->model->search($srch, $sln_id);
		if(!empty($res_search)) $this->make_orders_table($res_search);
		else{
			$this->data['content'] = heading("No results for $srch", 2);
			$this->load_lk_view();
		}
	}
	
	public function cookies_handler()
	{
		$p_arr = $this->input->post();
		if(!isset($p_arr['method'])) $resp = array('code'=>20, 'message'=>'Отсутствует обязательный параметр "method" в запросе');
		elseif($p_arr['method'] == 'screen_size'){
			set_cookie('scr_x', $p_arr['scr_x']);
			set_cookie('scr_y', $p_arr['scr_y']);
			$resp = array('code'=>0, 'scr_x'=>$p_arr['scr_x'], 'scr_y'=>$p_arr['scr_y']);
		}
		elseif($p_arr['method'] == 'toggle_submenu'){
			//set_cookie('show_sbm'.$p_arr['menu_id'], $p_arr['state']);
			$this->session->{'show_sbm'.$p_arr['menu_id']} = $p_arr['state'];
			$resp = array('code'=>0, 'menu_id'=>$p_arr['menu_id'], 'state'=>$p_arr['state']);
		}
		else $resp = array('code'=>21, 'message'=>'Неизвестное или неправильное имя метода');
		if($resp['code'] == 0) $resp['method'] = $p_arr['method'];
		echo json_encode($resp);
	}
}
