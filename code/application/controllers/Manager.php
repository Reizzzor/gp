<?php
require_once(dirname(__FILE__) . "/Orders.php");
class Manager extends Orders {
	public function __construct()
	{
		parent::__construct();
		$this->_make_main_menu();
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
		$this->data['menu_list'] = array(anchor('', '<i class="fas fa-shopping-cart" aria-hidden="true"></i>&nbsp;Заказы', 'onClick="toggleSubMenu(1); return false;"'), ul($submenu, $ul_params), anchor($this->user_target.'/status/10', '<i class="fas fa-file-archive" aria-hidden="true"></i>&nbsp;Архив заказов'), anchor($this->user_target.'/podium', '<i class="fas fa-store-alt" aria-hidden="true"></i>&nbsp;Образцы'));		
		$this->make_study_menu();
		$this->data['menu_list'][] = anchor('manager/drapery', '<i class="fas fa-file" aria-hidden="true"></i>&nbsp;Разбивка тканей');		
	}	
	
	protected function make_orders_table($orders, $t_name = 'Информация о заказах: ')
	{	
		$order_info = array('url_info'=>site_url('manager/info').'/', 'table_id'=>'orders_table');
		$salons_filter = array();
		$kagents_filter = array();
		$sln_ids = $this->model->get_mngr_info($this->session->user_id)['sln_ids'];
		$sln_raw = $this->model->get_salons($sln_ids);
		if(!isset($sln_raw['id'])){
			foreach($sln_raw as $i){
				$salons_filter['val'.$i['id']] = $i['name'];
				if(!in_array('val'.$i['kagent_id'], array_keys($kagents_filter)) && $i['kagent_id']) $kagents_filter['val'.$i['kagent_id']] = $this->model->get_kagents($i['kagent_id'])['name'];
			}	
		}
		else{
			$salons_filter['val'.$sln_raw['id']] = $sln_raw['name'];
			if($sln_raw['kagent_id']) $kagents_filter['val'.$sln_raw['kagent_id']] = $this->model->get_kagents($sln_raw['kagent_id'])['name'];
		}
		$order_info['s_fltr_str'] = "'".json_encode($salons_filter)."'";
		$order_info['k_fltr_str'] = "'".json_encode($kagents_filter)."'";
		$this->data['js'] = $this->load->view('js/js_order_info', $order_info, TRUE);
		$ord_rows = [];
		foreach($orders as $order){
			$ord_rows[] = array($order['sln_order_id'], $order['order_id'], $order['nomenclature'], $order['quantity'], $this->get_order_status($order['order_status_id']), $this->get_yes_or_no($order['pay']),
					$this->get_yes_or_no($order['is_podium']), $this->date_format($order['kept_stamp']), $this->date_format($order['ready_stamp']), $this->date_format($order['out_stamp']),
					$this->model->get_salons($order['sln_id'])['name'], $this->model->get_kagents($order['kagent_id'])['name']);
		}
		$ord_tbl = $this->make_sticky_table('orders_table', ["№ в салоне", "Фабр. №", "Номенклатура", "Кол-во", "Статус", "Оплата", "Подиум. образец", "Дата принятия", "Дата готовности", "Дата отгрузки", "Салон", "Контрагент"], $ord_rows, 140);
		if(isset($this->data['links']) && $this->data['links']) $t_name .= $this->data['links'];
		$this->data['content'] = $this->load->view('orders/mngr_table', array('tab_title'=>$t_name, 'ord_tbl'=>$ord_tbl), TRUE);		
		$this->load_lk_view();
	}
	
	public function index($ord_id = 0)
	{
		$this->orders($ord_id, 0);
	}
	
	public function orders($ords_id = 0, $page = 0)
	{
		
		$tt = $ords_id ? 'Заказы в статусе "'.$this->get_order_status($ords_id).'"' : 'Информация о заказах: ';
		$orders_raw = $ords_id ? $this->model->get_orders(array('id_type'=>'order_status_id', 'id'=>$ords_id)) : $this->model->get_orders();
		if(!empty($orders_raw)){
			if(array_key_exists('order_id', $orders_raw)) $orders_raw = array($orders_raw);
			$usr_orders = $this->pgnt_table($orders_raw, "manager/status/$ords_id/page", 5, $page);
			$this->make_orders_table($usr_orders, $tt);
		}
		else{
			$this->data['content'] = heading('Заказы не найдены', 2);
			$this->load_lk_view();
		}
	}
	
	public function send_bill($order_id)
	{
		$i = (int)substr($this->input->post('emailsDD'), 3);
		$emails = $this->session->emails;
		if(!$emails) return FALSE;
		$this->session->unset_userdata['emails'];
		$this->session->email = $emails[$i];
		parent::send_bill($order_id);
	}
}

?>