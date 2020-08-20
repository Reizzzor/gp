<?php

class Orders_model extends LK_Model
{

	protected $user_id;
	protected $sln; //хранится sln_id для салона и массив из sln_id для управляющего

	public function __construct()
	{
		parent::__construct();
	}

//PRIVATE FUNCTIONS

	private function _set_po_category($in_data)
	{
		$id = is_array($in_data) ? $this->db->get_where('po_consist', $in_data)->row('id') : $in_data;
		$new_ctgr = $this->get_po_category($id);
		if ($new_ctgr === FALSE) $new_ctgr = NULL;
		$this->db->update('po_consist', array('category' => $new_ctgr), array('id' => $id));
	}

	private function _cleanup_drp_ids($poe_id)
	{
		$pre_order = $this->get_po_entry($poe_id);
		$drp_ids = array();
		for ($i = 0; $i <= 8; $i++) {
			if (!$i && $pre_order['drapery_type'] != 1) $drp_ids['drapery_id0'] = NULL;
			if ($i && $i <= 3 && $pre_order['drapery_type'] != 2) $drp_ids['drapery_id' . $i] = NULL;
			if ($i && $pre_order['drapery_type'] != 3) $drp_ids['ind_drapery_id' . $i] = NULL;
		}
		if (!empty($drp_ids)) $this->db->update('po_consist', $drp_ids, array('id' => $poe_id));
	}

	private function _remove_double_entry($po_id, $tab = 'po_consist')
	{
		$query = $this->db->select('id, stamp')->where('po_id', $po_id)->order_by('stamp DESC')->get($tab);
		if (!$query->num_rows()) return FALSE;
		$dblz = $query->result_array();
		$ep_now = getdate()[0];
		foreach ($dblz as $i) {
			if ($ep_now - strtotime($i['stamp']) < 5) $this->db->where('id', $i['id'])->delete($tab);
		}
	}

	private function _set_total_summ($po_id)
	{
		$summ_total = 0;
		$preorder = $this->get_preorders($po_id)[0];
		$po_consist = $this->get_po_consist($po_id);
		$poe_drp = $this->get_po_consist($po_id, TRUE);
		foreach ([$po_consist, $poe_drp] as $j) {
			foreach ($j as $i) {
				if ($i['summ_total']) $summ_total += (int)$i['summ_total'];
			}
		}
		if ($preorder['count_dlvr']) {
			foreach (['summ_up', 'summ_dlvr'] as $i) {
				if (isset($preorder[$i])) $summ_total += $preorder[$i];
			}
		}
		$this->db->where('id', $po_id)->update('pre_orders', array('summ_total' => $summ_total));
	}

	private function _is_action($id_val, $id_type = 'id')
	{
		$coll_ext_id = $this->db->get_where('rp_drapery', array($id_type => $id_val))->row('coll_ext_id');
		if (!$coll_ext_id) return FALSE;
		$query = $this->db->get_where('action_drp', array('coll_ext_id' => $coll_ext_id));
		return $query->num_rows() ? TRUE : FALSE;
	}

	private function _action_drp_price()
	{
		return $this->db->get_where('rp_drapery', array('category' => 0))->row('price');
	}

	private function _action_collections($rp_id, $id_type = 'id')
	{
		$rp_ext_id = $id_type == 'ext_id' ? $rp_id : $this->db->get_where('r_prod', array($id_type => $rp_id))->row('ext_id');
		return $this->db->select('trade_mark, collection')->get_where('action_drp', array('rp_ext_id' => $rp_ext_id))->result_array();
	}

	private function _get_client_id($data)
	{
		$cl_data = array('surname' => $data['cstmr_surname'], 'name1' => $data['cstmr_name1'], 'phone1' => $data['phone1']);
		if (isset($data['cstmr_name2'])) $cl_data['name2'] = $data['cstmr_name2'];
		if (isset($data['email'])) $cl_data['email'] = $data['email'];
		$this->db->reset_query();
		$query = $this->db->where('phone1', $cl_data['phone1'])->get('clients')->row();
		if (isset($query->id)) {
			$client_id = $query->id;
			$this->db->where('id', $client_id)->update('clients', $cl_data);
		} else {
			$this->db->insert('clients', $cl_data);
			$client_id = $this->db->get_where('clients', $cl_data)->row('id');
		}
		return $client_id;
	}

//PUBLIC FUNCTIONS

//разные GET
	public function get_orders($params = NULL, $strg = FALSE)
	{
		$id_where = array('id_type' => 'sln_id', 'id' => $strg ? $this->storage_ids('sln') : $this->sln);
		if (!$params) $upd_params = array($id_where);
		elseif (array_key_exists('id_type', $params)) $upd_params = array($params, $id_where);
		else $upd_params = array_merge($params, array($id_where));
		if (!is_array($this->sln)) {
			$upd_params[] = array('id_type' => 'kagent_id', 'id' => $strg ? $this->storage_ids('kagent') : $this->get_salons($this->sln)['kagent_id']);
			if ($this->get_salons($this->sln)['hide_archive']) $upd_params[] = array('id_type' => 'order_status_id !=', 'id' => 10);
		}
		return parent::get_orders($upd_params);
	}

	public function get_userinfo()
	{
		return $this->get_users($this->user_id);
	}

	public function get_rp($params = NULL)
	{
		if ($params) {
			foreach ($params as $k => $vl) {
				$this->db->where($k, $vl);
			}
		}
		return $this->db->order_by('name', 'ASC')->get('r_prod')->result_array();
	}

	public function get_po_options($poe_id)
	{
		$res = array('code' => 0);
		$pe = $this->get_po_entry($poe_id);
		foreach (['nails', 'decor'] as $i) {
			if ($pe[$i . '_id']) {
				$this->db->where('ext_id', $pe[$i . '_id']);
				if ($i == 'decor') $this->db->where('rp_ext_id', $pe['r_prod_id']);
				$res[$i . '_id'] = $this->db->get('rp_' . $i)->row('id');
			}
		}
		if ($this->check_cm_decor(explode(',', substr($pe['extra_modules'], 1, -1)))) $res['modules_decor'] = TRUE;
		return $res;
	}

	public function get_rp_options($r_id = NULL)
	{
		if (!$r_id) return array('code' => 25);
		$res = $this->db->get_where('r_prod', array('id' => $r_id))->row_array();
		$res['discount'] = $this->get_discount($res['ext_id']);
		$price = $this->db->where('category IS NULL', NULL, FALSE)->get_where('rp_prices', array('rp_ext_id' => $res['ext_id']))->row('price');
		if ($price) $res['price'] = $price / 100;
		else {
			$query = $this->db->select('drp1, drp2, drp3, drp4, drp5, drp6')->where('rp_ext_id', $res['ext_id'])->get('rp_drp');
			if ($query->num_rows()) $res['drp_names'] = $query->row_array();
		}
		foreach (['modules', 'decor', 'nails'] as $i) {
			if ($i == 'nails') {
				if (!$res['has_nails']) continue;
				$this->db->select('id, name');
			}
			if ($i == 'modules') $this->db->select('id, ext_id, name, full_name, rp_ext_id, length, width, own_decor')->where('length >', 0)->where('width >', 0);
			if ($i != 'nails') $this->db->where('rp_ext_id', $res['ext_id']);
			$rr = $this->db->order_by('name ASC')->get('rp_' . $i)->result_array();
			if (!empty($rr)) $res[$i] = $rr;
		}
		$res['code'] = 0;
		return $res;
	}

	public function get_discount($rp_ext_id)
	{
		$qd = $this->db->select_max('discount')->get_where('discounts', array('rp_ext_id' => $rp_ext_id))->row('discount');
		return $qd ? $qd : 0;
	}

	public function get_rp_id($rp_ext_id)
	{
		$rp_id = $this->db->where('ext_id', $rp_ext_id)->get('r_prod')->row('id');
		return $rp_id ? $rp_id : NULL;
	}

	public function get_extra_modules($poe_id, $encode_res = TRUE)
	{
		$emr = explode(',', substr($this->get_po_entry($poe_id)['extra_modules'], 1, -1));
		if (!isset($emr[0]) || !$emr[0]) $emr = array();
		$em = array();
		foreach ($emr as $i) {
			$qr = $this->db->get_where('rp_modules', array('ext_id' => $i))->row_array();
			$em[] = array('id' => $qr['id'], 'name' => $qr['name'], 'x' => $qr['length'], 'y' => $qr['width'], 'decor' => $qr['own_decor']);
		}
		return $encode_res ? json_encode($em) : $em;

	}

	public function get_module_price($module_id, $ctgr)
	{
		$res = $this->db->get_where('rp_modules', array('id' => $module_id))->row_array();
		return isset($res['price' . $ctgr]) ? $res['price' . $ctgr] / 100 : 0;

	}

	public function get_drp4order($tm, $coll)
	{
		$drp = $this->get_rp_drapery('drp', array('trade_mark' => $tm, 'collection' => $coll));
		foreach ($drp as $k => $vl) {
			if (!$this->_is_action($vl['id'])) continue;
			$drp[$k]['category'] = 0;
			$drp[$k]['ctgr_name'] = '0';
			$drp[$k]['price'] = $this->_action_drp_price();
		}
		return $drp;

	}

	public function get_action_rp($coll_ext_id)
	{
		$rp_raw = $this->db->get_where('action_drp', array('coll_ext_id' => $coll_ext_id))->result_array();
		$rp_res = [];
		foreach ($rp_raw as $i) {
			$rp_res[] = $this->db->get_where('r_prod', array('ext_id' => $i['rp_ext_id']))->row('id');
		}
		return $rp_res;
	}

	public function get_drp_tm()
	{
		return $this->db->select('DISTINCT trade_mark', FALSE)->order_by('1 ASC')->get('rp_drapery')->result_array();
	}

	public function get_preorders($po_val = NULL, $mode = 'id', $strg = FALSE)
	{
		if ($this->sln) {
			if ($strg) $this->db->where(array('sln_id' => $this->storage_ids('sln'), 'kagent_id' => $this->storage_ids('kagent')));
			else {
				$kagent_id = $this->get_salons($this->sln)['kagent_id'];
				$this->db->where(array('sln_id' => $this->sln, 'kagent_id' => $kagent_id));
			}
		}
		if ($po_val || $mode != 'id') $this->db->where($mode, $po_val);
		else $this->db->select('po.*, SUM(p.summa) as debt')->join('prepayment p', 'po.id = p.id_pre_orders', 'left')->group_by('po.id');
		return $this->db->order_by('po.id', 'DESC')->get('pre_orders po')->result_array();
	}

	public function get_po_entry($id_val = NULL, $mode = 'id')
	{
		if ($id_val) $this->db->where($mode, $id_val);
		$q_res = $this->db->get('po_consist');
		if ($q_res->num_rows()) return $q_res->num_rows() > 1 ? $q_res->result_array() : $q_res->row_array();
		return FALSE;
	}

	public function get_poe_drp($id_val = NULL, $mode = 'id')
	{
		if ($id_val) $this->db->where($mode, $id_val);
		$q_res = $this->db->get('po_drapery');
		if ($q_res->num_rows()) return $q_res->num_rows() > 1 ? $q_res->result_array() : $q_res->row_array();
		return FALSE;
	}

	public function get_po_category($in_data, $rp_ext_id = NULL) //на вход - po_id(будет высчитывать как максю категорию срекди исп-х тканей) либо массив вида [...{id_type:id}...](вернёт максимальную категорию из перечисленных)
	{
		$drp_ids = array();
		if (!is_array($in_data)) {
			$pre_order = $this->get_po_entry($in_data);
			switch ($pre_order['drapery_type']) {
				case '1':
					if (isset($pre_order['drapery_id0']) && $pre_order['drapery_id0']) $drp_ids[] = array('ext_id' => $pre_order['drapery_id0']);
					break;
				case '2':
					for ($i = 1; $i <= 6; $i++) {
						if (isset($pre_order['drapery_id' . $i]) && $pre_order['drapery_id' . $i]) $drp_ids[] = array('ext_id' => $pre_order['drapery_id' . $i]);
					}
					break;
				case '3':
					for ($i = 1; $i <= 8; $i++) {
						if (isset($pre_order['ind_drapery_id' . $i]) && $pre_order['ind_drapery_id' . $i]) $drp_ids[] = array('ext_id' => $pre_order['ind_drapery_id' . $i]);
					}
					break;
			}
			if (empty($drp_ids)) $this->db->where('id', $pre_order['id'])->update('po_consist', array('drapery_type' => NULL));
			return !empty($drp_ids) ? $this->get_po_category($drp_ids, $pre_order['r_prod_id']) : FALSE;
		} else {
			$action = [];
			$non_action = [];
			foreach ($in_data as $i) {
				$coll_ext_id = $this->db->get_where('rp_drapery', $i)->row('coll_ext_id');
				if ($coll_ext_id && $rp_ext_id && in_array($this->get_rp_id($rp_ext_id), $this->get_action_rp($coll_ext_id))) $action[] = $i;
				else $non_action[] = $i;
			}
			if (empty($non_action)) return empty($action) ? FALSE : 0;
			$this->db->select('MAX(category) AS max_ctgr', FALSE);
			$ff = TRUE;
			foreach ($non_action as $i) {
				foreach ($i as $k => $vl) {
					if ($ff) {
						$this->db->where($k, $vl);
						$ff = FALSE;
					} else $this->db->or_where($k, $vl);
				}
			}
			$qmc = $this->db->get('rp_drapery')->row('max_ctgr');
			if ($qmc) return $qmc;
			return empty($action) ? FALSE : 0;
		}
	}

	public function get_rp_exceptions($id, $id_type = 'rp_ext_id')
	{
		$out_item = $id_type == 'rp_ext_id' ? 'coll_ext_id' : 'rp_ext_id';
		$q_res = $this->db->select('DISTINCT ' . $out_item, FALSE)->get_where('rp_exceptions', array($id_type => $id))->result_array();
		$res = array();
		foreach ($q_res as $i) {
			$res[] = $i[$out_item];
		}
		return $res;
	}

	public function get_drp_ctgrs()
	{
		$q_res = $this->db->select('DISTINCT category, ctgr_name', FALSE)->order_by('category', 'ASC')->get('rp_drapery')->result();
		$res = array();
		foreach ($q_res as $i) {
			$res[(int)$i->category] = (int)$i->category ? $i->ctgr_name : '0'; //Категория ШИК убирается ЗДЕСЬ(пока Григорий не поправит выгрузку)
		}
		return $res;
	}

	public function get_rp_price($rp_id, $ctgr)
	{
		$qp = $this->db->get_where('rp_prices', array('rp_ext_id' => $this->get_rp_options($rp_id)['ext_id'], 'category' => $ctgr))->row('price');
		return $qp ? $qp / 100 : 0;
	}

	public function get_sln_address($sln_id = NULL)
	{
		return $this->db->get_where('salons', array('id' => $sln_id ? $sln_id : $this->sln))->row('address');
	}

	public function get_address($rpo_id)
	{
		$res = array('code' => 0);
		$q_res = $this->db->get_where('pre_orders', array('id' => $rpo_id))->row_array();
		foreach (['email', 'phone1', 'phone2', 'cstmr_surname', 'cstmr_name1', 'cstmr_name2', 'dlvr_type', 'dlvr_city', 'dlvr_street', 'dlvr_house', 'dlvr_flat', 'dlvr_porch', 'dlvr_stage', 'dlvr_dmphn', 'has_lift', 'dlvr_comment'] as $i) {
			$res[$i] = $q_res[$i] != '0' ? $q_res[$i] : '';
		}
		return $res;
	}

	public function get_last_preorder_id($kagent_id)
	{
		return $this->db->select('id')->order_by('id', 'DESC')->get_where('pre_orders', array('sln_id' => $this->sln, 'kagent_id' => $kagent_id))->row('id');
	}

	public function get_sln_stuff()
	{
		$q_res = $this->db->where('sln_id', $this->sln)->order_by('id', 'ASC')->get('sln_managers')->result_array();
		$res = array('val0' => 'Выбрать');
		foreach ($q_res as $i) {
			$res['val' . $i['id']] = $i['name'];
		}
		return $res;
	}

	public function get_seller($seller_id)
	{
		return $this->db->get_where('sln_managers', array('id' => $seller_id))->row('name');
	}

	public function get_po_models($po_id)
	{
		$rp_arr = $this->db->select('r_prod_id')->where('po_id', $po_id)->order_by('stamp', 'ASC')->get('po_consist')->result();
		$drp_arr = $this->db->select('drp_ext_id')->where('po_id', $po_id)->order_by('stamp', 'ASC')->get('po_drapery')->result();
		$rslt_arr = array();
		foreach ($rp_arr as $i) {
			$qname = $this->db->select('name')->where('ext_id', $i->r_prod_id)->get('r_prod')->row('name');
			if ($qname) $rslt_arr[] = $qname;
		}
		foreach ($drp_arr as $i) {
			$qname = $this->db->select('name')->where('ext_id', $i->drp_ext_id)->get('rp_drapery')->row('name');
			if ($qname) $rslt_arr[] = $qname;
		}
		if (empty($rslt_arr)) return '';
		if (count($rslt_arr) == 1) return $rslt_arr[0];
		$rslt = implode(', ', $rslt_arr);
		return strlen($rslt) > 150 ? substr($rslt, 0, 147) . '...' : $rslt;
	}

	public function get_po_consist($po_id, $is_drp = FALSE)
	{
		return $this->db->where('po_id', $po_id)->order_by('stamp', 'DESC')->get($is_drp ? 'po_drapery' : 'po_consist')->result_array();
	}
//$query = $this->db->select('o.*, po.summ_total ,SUM(p.summa) as debt')->join('prepayment p', 'o.order_id = p.order_id', 'left')->join('pre_orders po', 'po.sln_order_id = o.sln_order_id', 'left')->group_by('o.id, po.summ_total')->order_by('o.order_id', 'DESC')->get('orders o');

	public function get_po_prepayment($po_id, $is_drp = FALSE)
	{
		return $this->db
			->select('t_p.name, p.summa, p.created_at, po.summ_total')
			->where('p.id_pre_orders', $po_id)
			->or_where('p.order_id', $po_id)
			->order_by('p.created_at')
			->join('type_prepayment t_p', 'p.id_type_prepayment = t_p.id')
			->join('orders o', 'o.order_id = p.order_id', 'left')
			->join('pre_orders po', 'po.sln_order_id = o.sln_order_id', 'left')
			->get('prepayment p')
			->result_array();
	}

	public function get_type_prepayment()
	{
		return $this->db->get('type_prepayment')->result_array();
	}

	public function get_next_soid()
	{
		if (is_array($this->sln)) return FALSE;
		$max_soid = $this->db->where('id', $this->sln)->get('salons')->row('max_soid');
		$prfx = $this->db->where('id', $this->sln)->get('salons')->row('prefix');
		if (!$prfx && !$max_soid) return FALSE;
		$prfx_reg = $prfx ? '(' . $prfx . ')' : '';
		$where_arr = array('sln_id' => $this->sln);
		/*if($this->sln != $this->storage_ids('sln')) */
		$po_max = $this->db->where('sln_order_id RLIKE "^' . $prfx_reg . '[0-9]{1,5}$"', NULL, FALSE)->get_where('pre_orders', $where_arr)->result_array();
		//else $po_max = $this->db->where('sln_order_id RLIKE "^'.$prfx_reg.'[0-9]{1,5}$"', NULL, FALSE)->get('storage')->result_array();
		foreach ($po_max as $i) {
			$curr_id = (int)preg_replace('/\D/', '', $i['sln_order_id']);
			if ($curr_id && $curr_id > $max_soid) $max_soid = $curr_id;
		}
		$po_max = $this->db->where('sln_order_id RLIKE "^' . $prfx_reg . '[0-9]{1,5}$"', NULL, FALSE)->get_where('orders', $where_arr)->result_array();
		foreach ($po_max as $i) {
			$curr_id = (int)preg_replace('/\D/', '', $i['sln_order_id']);
			if ($curr_id && $curr_id > $max_soid) $max_soid = $curr_id;
		}
		return $prfx . strval(++$max_soid);
	}

	public function get_poe_extra($poe_id)
	{
		$rslt = array();
		$poe = $this->db->get_where('po_consist', array('id' => $poe_id))->row_array();
		if ($poe['decor_id']) {
			$qname = $this->db->get_where('rp_decor', array('ext_id' => $poe['decor_id']))->row('name');
			if ($qname) $rslt[] = ['Декор: ', $qname];
		}
		if ($poe['nails_id']) {
			$qname = $this->db->get_where('rp_nails', array('ext_id' => $poe['nails_id']))->row('name');
			if ($qname) $rslt[] = ['Гвозди: ', $qname];
		}
		if ($poe['corner']) $rslt[] = ['Угол: ', $poe['corner'] == 'L' ? 'левый' : 'правый'];
		if ($poe['stitching']) $rslt[] = ['Отстрочка: ', $poe['stitching']];
		if ($poe['armrest']) $rslt[] = ['Подлокотник: ', $poe['armrest']];
		if ($poe['pillows']) $rslt[] = ['Количество подушек: ', $poe['pillows']];
		return $rslt;
	}

	public function get_reserved() //возвр-м array('sln'=>[], 'other'=>[]), где [] - массив соотв-х uniq_id, для менеджера надо написать отдельно
	{
		$raw_rslt = $this->db->where('printing', FALSE)->get('reserved')->result_array();
		$res = array('sln' => [], 'other' => []);
		foreach ($raw_rslt as $i) {
			$res[$this->sln == $i['sln_id'] ? 'sln' : 'other'][] = $i['order_ext_id'];
		}
		return $res;
	}

	public function get_reserved_item($id, $id_type = 'id')
	{
		$query = $this->db->where($id_type, $id)->get('reserved');
		return $query->num_rows() ? $query->row_array() : FALSE;
	}

//остальные функции

	public function non_discount_price($poe) //возвращает значение в копейках
	{
		$query = $this->db->where('category IS NULL', NULL, FALSE)->get_where('rp_prices', array('rp_ext_id' => $poe['r_prod_id']));
		if ($query->num_rows()) return $query->row('price');
		$modules = $this->get_extra_modules($poe['id'], FALSE);
		if (empty($modules)) {
			$price = $this->db->get_where('rp_prices', array('rp_ext_id' => $poe['r_prod_id'], 'category' => $poe['category']))->row('price');
			return $price ? $price : 0;
		}
		$price = 0;
		foreach ($modules as $i) {
			$m_price = $this->db->get_where('rp_modules', array('id' => $i['id']))->row('price' . $poe['category']);
			if ($m_price) $price += $m_price;
		}
		return $price;
	}

	public function check_sln_order_id($so_id)
	{
		if (!$so_id) return TRUE;
		if ($this->db->get_where('pre_orders', array('sln_order_id' => $so_id, 'sln_id' => $this->sln))->num_rows() || $this->db->get_where('orders', array('sln_order_id' => $so_id, 'sln_id' => $this->sln))->num_rows()) return FALSE;
		return TRUE;
	}

	public function check_cm_decor($cm_arr)
	{
		if (!is_array($cm_arr) || empty($cm_arr)) return FALSE;
		if ($this->db->where_in('ext_id', $cm_arr)->get_where('rp_modules', array('own_decor' => TRUE))->num_rows()) return TRUE;
		return FALSE;
	}

	public function set_user_id($uid)
	{
		$this->user_id = $uid;
		if ($this->get_userinfo()['user_type'] === 'salon') $this->sln = $this->get_salons($this->user_id, 'user_id')['id'];
	}

	public function update_po_entry($po_data)
	{
		//var_dump($po_data);
		$this->_remove_double_entry($po_data['po_id']);
		$col_arr = ['id', 'r_prodDD'];
		for ($i = 0; $i <= 8; $i++) {
			if ($i <= 6) $col_arr[] = 'draperyDD' . $i;
			if ($i) $col_arr = array_merge($col_arr, ['ind_draperyDD' . $i, 'ind_comment' . $i]);
		}
		$cm = array();
		$r_prod = $this->db->where('id', $po_data['r_prodDD'])->get('r_prod')->row_array();
		$data = array('extra_modules' => '{}', 'r_prod_id' => $r_prod['ext_id']);
		foreach ($po_data as $k => $vl) {
			if (in_array($k, ['decorDD', 'nailsDD'])) $data[substr($k, 0, -2) . '_id'] = (int)$vl ? $this->db->where('id', $vl)->get('rp_' . substr($k, 0, -2))->row('ext_id') : NULL;
			elseif ($k == 'drapery_radio') {
				$data['drapery_type'] = $vl;
				switch ($vl) {
					case '1':
						$data['drapery_id0'] = $this->db->get_where('rp_drapery', array('id' => $po_data['draperyDD0']))->row('ext_id');
						break;
					case '2':
						for ($i = 1; $i <= 6; $i++) {
							if (!isset($po_data['draperyDD' . $i])) $data['drapery_id' . $i] = NULL;
							else $data['drapery_id' . $i] = $this->db->get_where('rp_drapery', array('id' => $po_data['draperyDD' . $i]))->row('ext_id');
						}
						break;
					case '3':
						for ($i = 1; $i <= 8; $i++) {
							$data['ind_drapery_id' . $i] = $this->db->get_where('rp_drapery', array('id' => $po_data['ind_draperyDD' . $i]))->row('ext_id');
							$data['ind_comment' . $i] = $po_data['ind_comment' . $i];
						}
						break;
				}
			} elseif ($k == 'cm_list_hidden') {
				if (!$vl) continue;
				$cmr = json_decode($vl, TRUE);
				foreach ($cmr as $i) {
					$cm[] = $this->db->where('id', $i['id'])->get('rp_modules')->row('ext_id');
				}
				if (!empty($cm)) $data['extra_modules'] = '{' . implode(',', $cm) . '}';
			} elseif ($k == 'xtra_discount_val') $data['xtra_discount'] = $vl;
			elseif ($k == 'cornersDD') {
				if ((int)$vl) $data['corner'] = $vl == 1 ? 'L' : 'R';
				else $data['corner'] = NULL;
			} elseif ($k == 'stitchingDD') {
				switch ((int)$vl) {
					case 0:
						$data['stitching'] = NULL;
						break;
					case 1:
						$data['stitching'] = 'В тон';
						break;
					case 2:
						$data['stitching'] = 'Контрастная светлая';
						break;
					case 3:
						$data['stitching'] = 'Контрастная тёмная';
						break;
				}
			} elseif ($k == 'armrestDD') {
				if (!isset($r_prod['is_golf']) || !$r_prod['is_golf'] || (int)$vl == 0) $data['armrest'] = NULL;
				else $data['armrest'] = (int)$vl == 1 ? 'Прямой' : 'Скошенный';
			} elseif ($k == 'pillowsDD') $data['pillows'] = isset($r_prod['is_golf']) && $r_prod['is_golf'] && (int)$vl ? $vl : NULL;
			elseif ($k == 'summ_total') $data[$k] = (int)$vl * 100;
			elseif (!in_array($k, $col_arr)) $data[$k] = $vl;
		}
		if (!$data['r_prod_id'] || (!$this->check_cm_decor($cm) && $this->db->where('rp_ext_id', $data['r_prod_id'])->get('rp_modules')->num_rows())) $data['decor_id'] = NULL;
		if (!isset($po_data['id'])) $this->db->insert('po_consist', $data);
		else $this->db->where('id', $po_data['id'])->update('po_consist', $data);
		$id = isset($po_data['id']) ? $po_data['id'] : $this->db->order_by('id', 'DESC')->get_where('po_consist', $data)->row('id');
		$this->_cleanup_drp_ids($id);
		$this->_set_po_category($id);
		$this->_set_total_summ($po_data['po_id']);
	}

	public function update_poe_drp($po_data)
	{
		$this->_remove_double_entry($po_data['po_id'], 'po_drapery');
		$drp = $this->db->where('id', $po_data['draperyDD'])->get('rp_drapery')->row_array();
		if ($this->_is_action($drp['id'])) $drp['ctgr_name'] = '0';
		$data = array('drp_ext_id' => $drp['ext_id'], 'ctgr_name' => $drp['ctgr_name']);
		foreach ($po_data as $k => $vl) {
			if (in_array($k, ['id', 'draperyDD'])) continue;
			$data[$k] = $k == 'quantity' ? (int)($vl * 100) : $vl;
		}
		if (!isset($po_data['id'])) $this->db->insert('po_drapery', $data);
		else $this->db->where('id', $po_data['id'])->update('po_drapery', $data);
		$this->_set_total_summ($po_data['po_id']);
	}

	public function del_po_entry($id_val, $mode = 'id', $is_poe_drp = FALSE)
	{
		if (!is_array($id_val)) $this->db->where($mode, $id_val)->delete($is_poe_drp ? 'po_drapery' : 'po_consist');
		else $this->db->where_in($mode, $id_val)->delete($is_poe_drp ? 'po_drapery' : 'po_consist');
		return 0;
	}

	public function insert_prepayment($prepayment_data)
	{
		if (isset($prepayment_data['pre_summa']) && isset($prepayment_data['type_prepayment'])) {

			$data = [];
			foreach ($prepayment_data as $k => $vl) {
				switch ($k) {
					case 'pre_summa':
						$data['summa'] = (int)$prepayment_data['pre_summa'] * 100;
						break;
					case 'type_prepayment':
						$data['id_type_prepayment'] = $prepayment_data['type_prepayment'];
						break;
					case 'id':
						$data['id_pre_orders'] = $prepayment_data['id'];
						break;
					case 'id_order':
						$data['order_id'] = $prepayment_data['id_order'];
						break;
				}
			}
//			var_dump($data);
			$this->db->insert('prepayment', $data);
		}
	}

	public function update_preorder($po_data)
	{
		//var_dump($po_data);
		$col_arr = ['po_id'];
		$data = array();
		foreach ($po_data as $k => $vl) {
			if (in_array($k, ['summ_up', 'summ_dlvr'])) $data[$k] = (int)$vl * 100;
			elseif ($k == 'sellerDD') $data['seller_id'] = $vl;
			elseif ($k != 'po_id') $data[$k] = $vl;
		}
		if (!isset($po_data['po_id'])) $this->db->insert('pre_orders', $data);
		else $this->db->where('id', $po_data['po_id'])->update('pre_orders', $data);
		$id = isset($po_data['po_id']) ? $po_data['po_id'] : $this->get_last_preorder_id($data['kagent_id']);
		$this->_set_total_summ($id);
		if (isset($data['phone1']) && $data['phone1']) $this->db->where('id', $id)->update('pre_orders', array('client_id' => $this->_get_client_id($data)));
	}

	public function upld_preorders($p_id = NULL)
	{
		if ($p_id) $this->db->where('id', $p_id)->set('stamp', 'CURRENT_TIMESTAMP', FALSE)->update('pre_orders', array('is_uploading' => TRUE));
		else {
			$po_arr = $this->get_preorders($p_id);
			foreach ($po_arr as $i) {
				$this->db->where('id', $i['id'])->set('stamp', 'CURRENT_TIMESTAMP', FALSE)->update('pre_orders', array('is_uploading' => TRUE));
			}
		}
		return 0;
	}

	public function del_preorders($p_id = NULL)
	{
		if ($p_id) {
			$this->db->where('id', $p_id)->delete('pre_orders');
			$this->db->where('po_id', $p_id)->delete('po_consist');
			$this->db->where('po_id', $p_id)->delete('po_drapery');
		} else {
			$po_arr = $this->get_preorders($p_id);
			foreach ($po_arr as $i) {
				$this->db->where('id', $i['id'])->delete('pre_orders');
				$this->db->where('po_id', $i['id'])->delete('po_consist');
				$this->db->where('po_id', $i['id'])->delete('po_drapery');
			}
		}
		return 0;
	}

	public function reserve($r_data, $is_strg = FALSE, $print = FALSE)
	{
		$this->db->where('order_ext_id', $r_data['order_ext_id'])->delete('reserved');
		if (isset($r_data['phone1'])) $r_data['client_id'] = $this->_get_client_id($r_data);
		if ($print) $r_data['printing'] = TRUE;
		else {
			$r_data['printing'] = FALSE;
			if (!$is_strg) $r_data['is_confirming'] = TRUE;
		}
		$this->db->insert('reserved', $r_data);
	}

}
