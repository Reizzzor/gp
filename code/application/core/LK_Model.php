<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class LK_Model extends CI_Model {
	
	public function __construct()
	{
		parent::__construct();
		$this->load->database();
	}
	
	protected function get_aggr_id($pd)
	{
		$res = $this->db->where('path_dir', $pd)->get('learning')->row();
		return isset($res->id) ? $res->id : 0;
	}
	
	private function _count_new($db, $aggr_id=NULL){ //при указанном aggr_id возвращает число записей в таблице db, которым менее 10 дней, иначе - массив вида 'aggr_id'=>COUNT(*)
		$this->db->where('(CURRENT_DATE - added) <= 10', NULL, FALSE);
		return $aggr_id ? $this->db->where('aggr_id', $aggr_id)->count_all_results($db) : $this->db->select('ssi_id, COUNT(src) AS vol', FALSE)->join('learning', 'learning.id = aggr_id', 'left')->group_by('ssi_id')->get($db)->result_array();
	}
	
	private function _clean_reserve()
	{
		$str_ids = $this->storage_ids();
		$this->db->query("DELETE FROM reserved WHERE order_ext_id NOT IN (SELECT uniq_id FROM orders WHERE sln_id = ? AND kagent_id = ?)", array($str_ids['sln_id'], $str_ids['kagent_id']));
	}
	
	protected function check_howto_db($db_name)
	{
		return in_array($db_name, ['docs', 'imgs', 'video']) ? 1 : 0;
	}
	
	public function storage_ids($id_type=NULL) //'sln'/'kagent'
	{
		$kagent_id = $this->db->get_where('kagents', array('name'=>'РЕЗЕРВ'))->row('id');
		$sln_id  = $this->db->get_where('salons', array('kagent_id'=>$kagent_id, 'name'=>'Склад'))->row('id');
		if(!$id_type) return array('kagent_id'=>$kagent_id, 'sln_id'=>$sln_id);
		return $id_type == 'sln' ? $sln_id : $kagent_id;
	}
	
	public function pathman($a=NULL)
	{
		return $a ? $this->db->where(is_numeric($a) ? 'id' : 'path_dir', $a)->get('learning')->row_array() : $this->db->get('learning')->result_array();
	}
	
	public function search_file_id($db, $arr_vals)
	{
		if(!$this->check_howto_db($db)) return 100;
		foreach(array('name', 'aggr_id') as $i){
			if(in_array($i, array_keys($arr_vals))) $this->db->where($i, $arr_vals[$i]);
		}
		$query = $this->db->get($db);
		if(!$query->num_rows()) return 0;
		$sf_res = $query->row_array();
		$sf_res['dir_path'] = 'tutorials/'.$this->pathman($sf_res['aggr_id'])['path_dir']; 
		$sf_res['link'] = $sf_res['dir_path'].$sf_res['src']; 
		return $sf_res;
	}
	
	public function non_storage_ctgr($chr)
	{
		if(strripos($chr, 'Категория') === FALSE) return NULL;
		$pre_ctg = iconv_substr($chr, 9);
		if(is_numeric($pre_ctg)) return $pre_ctg;
		$query = $this->db->where('ctgr_name', $pre_ctg)->get('rp_drapery');
		return $query->num_rows() ? $query->row('category') : NULL;
	}
	
	public function search($sr, $sln_id = NULL)
	{
		$this->db->like('CAST(sln_order_id AS CHAR(10))', $sr);
		if($sln_id) $this->db->where('sln_id', $sln_id);
		$res = $this->db->get('orders')->result_array();
		if(is_numeric($sr)){
			$this->db->like('CAST(order_id AS CHAR(10))', $sr);
			if($sln_id) $this->db->where('sln_id', $sln_id);
			$sres = $this->db->get('orders')->result_array();
			$res = array_merge($res, $sres);
		}
		else{
			$tbls = ['nomenclature', 'customer'];
			foreach($tbls as $i){
				$this->db->like($i, $sr);
				if($sln_id) $this->db->where('sln_id', $sln_id);
				$sres = $this->db->get('orders')->result_array();
				$res = array_merge($res, $sres);
			}
		}
		return $res;
	}
	
	public function is_reserved($id, $id_type='order_ext_id') //FALSE, если не зарезервирован, иначе - sln_id
	{
		$query = $this->db->where($id_type, $id)->where('printing', FALSE)->get('reserved');
		return $query->num_rows() ? $query->row('sln_id') : FALSE;	
	}
	
	public function get_orders($params = NULL, $extra=FALSE)
	{
		if($params){
			if(array_key_exists('id_type', $params)){
				if(!is_array($params['id'])) $this->db->where('o.' . $params['id_type'], $params['id']);
				else $this->db->where_in('o.' . $params['id_type'], $params['id']);
			}
			else{
				foreach($params as $i){
					if(!is_array($i['id'])) $this->db->where('o.' . $i['id_type'], $i['id']);
					else $this->db->where_in('o.' . $i['id_type'], $i['id']);
				}
			}
		}
		$query = $this->db->select('o.*, po.summ_total ,SUM(p.summa) as debt')->join('prepayment p', 'o.order_id = p.order_id', 'left')->join('pre_orders po', 'po.sln_order_id = o.sln_order_id', 'left')->group_by('o.id, po.summ_total')->order_by('o.order_id', 'DESC')->get('orders o');
		return $query->num_rows()>1 ? $query->result_array() : $query->row_array();
	}

	public function get_order_statuses()
	{
		return $this->db->get('order_statuses')->result_array();	
	}
	
	public function get_users($u_val = NULL, $mode = 'id')
	{
		if($u_val) $this->db->where($mode, $u_val);		
		else $this->db->order_by('login', 'ASC');
		$query = $this->db->get('auth');
		if($query->num_rows()>1) return $query->result_array();
		$res = $query->row_array();
		if($res['user_type'] === 'manager'){
			$m_info = $this->get_mngr_info($res['id']);
			$res['sln_ids'] = $m_info['sln_ids'];
			$res['emails'] = $m_info['emails'];
		}
		return $res;
	}
	
	public function get_salons($sln_val = NULL, $mode = 'id')
	{
		if(!$sln_val) return $this->db->get('salons')->result_array();		
		if(is_numeric($sln_val)) $this->db->where($mode, $sln_val);
		else $this->db->where_in($mode, $sln_val);
		$query = $this->db->get('salons');
		return $query->num_rows()>1 ? $query->result_array() : $query->row_array();
	}	
	
	public function get_kagents($ka_val = NULL, $mode = 'id')
	{
		if(!$ka_val) return $this->db->get('kagents')->result_array();		
		if(is_numeric($ka_val)) $this->db->where($mode, $ka_val);
		else $this->db->where_in($mode, $ka_val);
		$query = $this->db->get('kagents');
		return $query->num_rows()>1 ? $query->result_array() : $query->row_array();
	}
	
	public function get_mngr_info($m_id)
	{
		$rr = $this->db->where('mngr_id', $m_id)->get('managers')->result_array();
		$emails = array();
		$sln_ids = array();
		foreach($rr as $i){
			if($i['sln_id']) $sln_ids[] = $i['sln_id'];
			if($i['email']) $emails[] = $i['email'];
		}
		return array('emails'=>$emails, 'sln_ids'=>$sln_ids);
	}
	
	public function get_drapery($dr_val = NULL, $mode = 'id')
	{
		if($dr_val) $this->db->where($mode, $dr_val);
		return $this->db->get('drapery')->result_array(); 
	}
	
	public function get_email($user_id)
	{
		$res = $this->db->select('email')->where('id', $user_id)->get('auth')->row('email');
		return $res ? $res : 0;
	}
		
	public function get_drapery_layout()
	{
		$res = array();
		$tm_res = $this->db->select('DISTINCT trade_mark', FALSE)->get('rp_collections')->result();
		foreach($tm_res as $i){
			$res[] = array('trade_mark'=>$i->trade_mark, 'collections'=>$this->db->select('collection, category, exceptions')->get_where('rp_collections', array('trade_mark'=>$i->trade_mark))->result_array());
		}
		return $res;
	}
	
	public function get_rp_drapery($mode=NULL, $params=NULL)
	{
		if($mode=='tm') $this->db->select('DISTINCT trade_mark', FALSE);
		elseif($mode=='coll') $this->db->select('DISTINCT collection', FALSE);
		else $this->db->select('name, id, category, ctgr_name, trade_mark, collection, price, coll_ext_id');
		if($params && is_array($params)){
			foreach($params as $k=>$vl){
				$this->db->where($k, $vl);
			}
		}
		return $this->db->where('is_prohibited', FALSE)->order_by("1 ASC")->get('rp_drapery')->result_array();
	}
	
	public function get_storage()
	{
		$st_ids = $this->storage_ids();
		return $this->db->where(array('sln_id'=>$st_ids['sln_id'], 'kagent_id'=>$st_ids['kagent_id'], 'order_status_id'=>5))->order_by('order_id', 'DESC')->order_by('nomenclature', 'ASC')->get('orders')->result_array();
	}

	public function get_storage_order($soid) 
	{
		$st_ids = $this->storage_ids();
		$query = $this->db->get_where('pre_orders', array('sln_order_id'=>$soid, 'sln_id'=>$st_ids['sln_id'], 'kagent_id'=>$st_ids['kagent_id']));
		if(!$query->num_rows()) return FALSE;
		$po_id = $query->row('id');
		$query = $this->db->where('po_id', $po_id)->get('po_consist');
		return $query->num_rows() ? $query->row_array() : FALSE;
	}
	
	public function get_ssbm($s_id=NULL) //при указании $s_id получаем $content_arr для view_howto, иначе - массив значений для меню Обучение
	{
		if($s_id){
			$res = $this->db->where('id', $s_id)->get('howto')->row_array();
			if(empty($res)) return FALSE;
			$sbmi = array('title'=>$res['title'], 'subsections'=>array());
			$res = $this->db->where('ssi_id', $s_id)->get('learning')->result_array();
			foreach($res as $i){
				$ss_title = explode('/', $i['category'])[1];
				$ni = $this->_count_new($i['type'], $i['id']);
				if($ni) $ss_title .= ' ['.$ni.']';
				$sbmi['subsections'][] = array('type'=>$i['type'], 'aggr_id'=>$i['id'], 'title'=>$ss_title);
			}
		}
		else{
			$sbmi = $this->db->select('id, name')->order_by('id')->get('howto')->result_array();
			foreach(['docs', 'imgs', 'video'] as $dbase){
				$new_arr = $this->_count_new($dbase);
				foreach($new_arr as $i){
					if($i['vol']){
						foreach($sbmi as $j=>$vl){
							if($vl['id'] == $i['ssi_id']){
								if(!isset($vl['new'])) $sbmi[$j]['new'] = 0;
								$sbmi[$j]['new'] += $i['vol'];
								break;
							}
						}
					}
				}
			}
		}
		return $sbmi;
	}		

	public function get_db_list($db, $fltr=NULL) //$fltr - массив вида ['aggr_id', 5]
	{
		if(!$this->check_howto_db($db)) return 100;
		if($fltr) $this->db->where($fltr[0], $fltr[1])->order_by('dspl_id', 'DESC');
		$res = $this->db->get($db)->result_array();
		$fin_arr = [];
		foreach($res as $i){
			$i['link'] = 'tutorials/'.$this->pathman($i['aggr_id'])['path_dir'].$i['src'];
			$fin_arr[] = $i;
		}
		return $fin_arr;
	}
	
	public function get_files_list($sm_item, $f_type)
	{
		$aggr_id = $this->get_aggr_id($f_type.'/'.$sm_item.'/');
		return $aggr_id ? $this->get_db_list($f_type, array('aggr_id', $aggr_id)) : 4;
	}
	
	public function get_tutorial($db, $id)
	{
		if(!$this->check_howto_db($db)) return 100;
		$res = $this->db->where('id', $id)->get($db)->row_array();
		$res['dir_path'] = 'tutorials/'.$this->pathman($res['aggr_id'])['path_dir'];
		$res['link'] = $res['dir_path'].$res['src'];
		return $res;
	}
	
	public function get_ctgr_name($ctgr)
	{
		$query = $this->db->select('DISTINCT ctgr_name', FALSE)->get_where('rp_drapery', array('category'=>$ctgr));
		return $query->num_rows() ? $query->row('ctgr_name') : FALSE;
	}
}
