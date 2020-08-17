<?php
require_once(dirname(__FILE__) . "/Orders.php");
class Store extends Orders {
	public function __construct()
	{
		parent::__construct();
		$this->data['menu_list'] = array(anchor('store', '<i class="fa fa-check-square" aria-hidden="true"></i>&nbsp;Продажа образцов'), anchor($this->user_target.'/drapery', '<i class="fas fa-file" aria-hidden="true"></i>&nbsp;Разбивка тканей'));
		$this->data['cmpny_name'] = 'МЕНЕДЖЕР GPARK';
		$this->data['title'] = 'Кабинет менеджера';
	}
	
	private function _get_saved_drps($order)
	{
		$res = array();
		switch($order['drapery_type']){
			case '1':
				if($order['drapery_id0']){
					$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$order['drapery_id0']))[0];
					$res[] = array('id'=>$drp['id'], 'idType'=>'drapery_id0', 'tmName'=>$drp['trade_mark'], 'collName'=>$drp['collection'], 'category'=>$drp['category']);
				}
				break;
			case '2':
				for($i=1;$i<=6;$i++){
					if($order['drapery_id'.$i]){
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$order['drapery_id'.$i]))[0];
						$res[] = array('id'=>$drp['id'], 'idType'=>'drapery_id'.$i, 'tmName'=>$drp['trade_mark'], 'collName'=>$drp['collection'], 'category'=>$drp['category']);
					}	
				}
				break;
			case '3':
				for($i=1;$i<=8;$i++){
					if($order['ind_drapery_id'.$i]){
						$drp = $this->model->get_rp_drapery(NULL, array('ext_id'=>$order['ind_drapery_id'.$i]))[0];
						$res[] = array('id'=>$drp['id'], 'idType'=>'ind_drapery_id'.$i, 'tmName'=>$drp['trade_mark'], 'collName'=>$drp['collection'], 'category'=>$drp['category'], 'comment'=>$order['ind_comment'.$i]);
					}	
				}
				break;
		}
		return json_encode($res);
	}
	
	public function index($ords_id = 0)
	{
		$po_list = $this->model->get_confirming();
		$po_rows = [];
		foreach($po_list as $i){
			$order = $this->model->get_order($i['order_ext_id']);
			$sln = $this->model->get_salons($i['sln_id'])['name'];
			$kagent = $this->model->get_kagents($i['kagent_id'])['name'];
			$po_date = $this->date_format(explode(' ', $i['stamp'])[0]);
			$edit = anchor(site_url('store/edit/'.$i['id']), 'Подтверждение');
			$del = anchor(site_url('store/delete/'.$i['id']), 'Удалить');
			$upld = anchor(site_url('store/upload/'.$i['id']), 'Отправить на фабрику');
			$po_rows[] = array($order['order_id'], $order['sln_order_id'], $order['nomenclature'], $order['quantity'], $sln, $kagent, $po_date, $edit, $del, $upld);
		}
		$rc_tbl = empty($po_rows) ? NULL : $this->make_sticky_table('rsrv_confirm_table', ["№", "№ в салоне", "Номенклатура",  "Кол-во", "Салон", "Контрагент", "Дата", '&nbsp;', '&nbsp;', '&nbsp;'], $po_rows, 200);
		$pg_title = heading($rc_tbl ? 'Список заказов для подтверждения' : 'Заказы для подтверждения отсутствуют', 2); 
		$this->data['js'] = $this->load->view('js/js_order_info', array('table_id'=>'rsrv_confirm_table'), TRUE);
		$this->data['content'] = $pg_title.$rc_tbl;
		$this->load_lk_view();		
	}
	
	public function reserved()
	{
		$strg = $this->model->get_storage();
		$str_rows = [];
		foreach($strg as $i){
			$ri = $this->model->get_reserved_item($i['uniq_id'], 'order_ext_id');
			$r_st = $ri ? "Резерв: {$this->date_format($ri['stamp'])} ".$this->model->get_salons($ri['sln_id'])['name'] : 'Доступен';
			$str_rows[] = array_merge($this->make_storage_row($i), array($r_st));
		}
		if(!empty($str_rows)) $this->data['js'] = $this->load->view('js/js_order_info', array('url_info'=>site_url('store').'/', 'table_id'=>'storage_table'), TRUE);
		$this->show_storage($this->make_sticky_table('storage_table', ["ID", "Номенклатура", "Комплектация", "Кол-во", "Статус", '&nbsp;'], $str_rows, 140));
	}

	public function edit_order($rsrv_id) //id из таблицы reserved
	{
		$rsrv = $this->model->get_reserved_item($rsrv_id);
		$order = $this->model->get_order($rsrv['order_ext_id']);
		if($order['sln_order_id']){ 
			$po = $this->model->get_preorders($order['sln_order_id'], 'sln_order_id'); //переписать с учетом Store в Orders_model
			$ostrg = isset($po[0]) ? $this->model->get_po_entry($po[0]['id'], 'po_id') : NULL; //аналогично
		}
		else $ostrg = NULL;
		if($ostrg){
			$ostrg['sln_order_id'] = $order['sln_order_id'];
			$ostrg['order_ext_id'] = $order['uniq_id'];
		}
		$data_arr = array('dlv_info'=>TRUE, 'basic_info'=> $ostrg ? $this->basic_storage_info($ostrg) : $this->basic_order_info($order, TRUE), 'rsrv_id'=>$rsrv_id);
		foreach(['dlvr_type', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_flat', 'dlvr_stage', 'dlvr_porch', 'dlvr_dmphn', 'has_lift', 'phone1', 'phone2', 'email', 'dlvr_comment', 'is_example', 'dscnt_retail', 'dscnt_whole'] as $i){
			if(!$rsrv[$i]) continue;
			if($i == 'dlvr_house' &&  $rsrv['dlvr_corp']) $data_arr['dlvr_house'] = $rsrv['dlvr_house'].(ctype_digit($rsrv['dlvr_corp'][0]) ? 'стр' : '').$rsrv['dlvr_corp'];
			else $data_arr[$i] = in_array($i, ['phone1', 'phone2']) ? $this->phone_format($rsrv[$i]) : $rsrv[$i];
		}
		if($rsrv['dlvr_type'] == 'salon') $data_arr['sln_address'] = $this->model->get_sln_address($rsrv['sln_id']);
		$customer = '';
		foreach(['surname', 'name1', 'name2'] as $i){
			if($rsrv['cstmr_'.$i]) $customer .= $rsrv['cstmr_'.$i] . ' ';
		}
		$data_arr['customer'] = strlen($customer) ? substr($customer, 0, -1) : '';
		$this->data['content'] = $this->load->view('orders/show_reserve', $data_arr, TRUE);
		$this->load_lk_view();
	}
		
	public function submit_order($rsrv_id)
	{
		$val_arr = ['dscnt_retail', 'dscnt_whole'];
		$submit_data = array();
		$p_arr = $this->input->post();
		foreach($val_arr as $i){
			if(isset($p_arr[$i])) $submit_data[$i] = $p_arr[$i];
		}
		$this->model->submit_order($rsrv_id, $submit_data);
		set_cookie('popup_msg', (isset($msg_text) && $msg_text) ? $msg_text : 'Изменения успешно внесены');
		$this->index();
	}
	
	public function delete_order($order_id)
	{
		$this->model->delete_order($order_id);
		set_cookie('popup_msg', 'Заказ удалён');
		$this->index();
	}
	
	public function upload_order($order_id)
	{
		$this->model->upload_order($order_id);
		set_cookie('popup_msg', 'Заказ отправлен ра фабрику');
		$this->index();
	}	
	
}