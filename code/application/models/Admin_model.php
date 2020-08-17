<?php
class Admin_model extends LK_Model {

	public function __construct()
    {
		parent::__construct();
    }

	private function _cleanup($u_id, $tab)
	{
		$this->db->where('user_id', $u_id)->update($tab, array('user_id'=>NULL));
	}
	
	private function _move_dspl_ids($db, $aggr_id, $dspl_id, $is_up=FALSE)	
	{
		$ea = $this->get_db_list($db, ['aggr_id', $aggr_id]);
		foreach($ea as $i){
			if($i['dspl_id'] < $dspl_id) break;
			if($is_up) $upd_dspl = $i['dspl_id']+1;
			else $upd_dspl = $i['dspl_id']-1;
			$this->db->where('id', $i['id'])->update($db, array('dspl_id'=>$upd_dspl));
		}
	}
	
	public function delete_user($uid)
	{
		$user = $this->get_users($uid);
		if(empty($user)) return 0;
		$this->db->where('id', $uid)->delete('auth');
		if($user['user_type'] == 'salon') $this->_cleanup($uid, $user['user_type'].'s');
		if($user['user_type'] == 'manager') $this->db->delete('managers', array('mngr_id'=>$uid));
		return 0;
	}
	
	public function update_user($u_data) 
	{
		if(!isset($u_data['id'])){
			$data = array('login'=>$u_data['login'], 'pswrd_hash'=>password_hash($u_data['pass'], PASSWORD_DEFAULT), 'user_type'=>$u_data['user_type'], 'is_active'=>$u_data['is_active']);
			if($u_data['user_type'] != 'manager') $data['email'] = $u_data['email'];
			$this->db->insert('auth', $data);
		}
		else{
			$data = array();
			if(isset($u_data['pass'])){
				$p_hash = $this->get_users($u_data['id'])['pswrd_hash'];
				if((!password_verify($u_data['pass'], $p_hash))||(password_needs_rehash($p_hash, PASSWORD_DEFAULT))) $data['pswrd_hash']=password_hash($u_data['pass'], PASSWORD_DEFAULT);
			}
			foreach(['user_type', 'login', 'email', 'is_active'] as $k){
				if(isset($u_data[$k])) $data[$k] = $u_data[$k];
			}
			if(!empty($data)) $this->db->where('id', $u_data['id'])->update('auth', $data);
			if(isset($u_data['user_type']) && $u_data['user_type']!=='salon') $this->_cleanup($u_data['id'], 'salons');
		}
		if(isset($u_data['id']))$user_id = $u_data['id'];
		else $user_id=$this->get_users($u_data['login'], 'login')['id'];
		if(isset($u_data['slnInput'])){
			$this->_cleanup($user_id, 'salons');
			$this->db->where('name', $u_data['slnInput'])->update('salons', array('user_id' => $user_id));
		}
		if(isset($u_data['ka4slnInput'])){
			$kagent_id = $this->get_kagents($u_data['ka4slnInput'], 'name')['id'];
			$this->db->where('user_id', $user_id)->update('salons', array('kagent_id' => $kagent_id));
		}
		if($this->get_users($user_id)['user_type'] == 'salon'){
			$sln_update = array();
			foreach(['hide_archive', 'address', 'prefix', 'phone', 'mailto', 'show_preorders', 'hide_fin_part'] as $i){
				if(isset($u_data[$i])) $sln_update[$i] = $u_data[$i];
			}
			if(!empty($sln_update)) $this->db->where('user_id', $user_id)->update('salons', $sln_update);	
		}
		if($this->get_users($user_id)['user_type'] == 'manager'){
			$new_salons = $u_data['sln_ids'];
			$new_emails = $u_data['emails'];
			if(!isset($u_data['id'])){
				foreach($new_salons as $i){
					$this->db->insert('managers', array('mngr_id'=>$user_id, 'sln_id'=>$i));
				}
				foreach($new_emails as $i){
					$this->db->insert('managers', array('mngr_id'=>$user_id, 'email'=>$i));
				}
			}
			else{
				$mngr_curr = $this->get_mngr_info($user_id);
				foreach($new_salons as $i){
					$s_pos = array_search($i, $mngr_curr['sln_ids']);
					if(is_numeric($s_pos)) array_splice($mngr_curr['sln_ids'], $s_pos, 1);
					else $this->db->insert('managers', array('mngr_id'=>$user_id, 'sln_id'=>$i));
				}
				if(!empty($mngr_curr['sln_ids'])){
					foreach($mngr_curr['sln_ids'] as $i){
						$this->db->delete('managers', array('sln_id'=>$i, 'mngr_id'=>$user_id));
					}
				} 
				foreach($new_emails as $i){
					$e_pos = array_search($i, $mngr_curr['emails']);
					if(is_numeric($e_pos)) array_splice($mngr_curr['emails'], $e_pos, 1);
					else $this->db->insert('managers', array('mngr_id'=>$user_id, 'email'=>$i));	
				}
				if(!empty($mngr_curr['emails'])){
					foreach($mngr_curr['emails'] as $i){
						$this->db->delete('managers', array('mngr_id'=>$user_id, 'email'=>$i));
					}
				}
			}
		}
	}
	
    public function del_tutorial($db_name, $id)
    {
	if(!$this->check_howto_db($db_name)) return 100;
	$t = $this->get_tutorial($db_name, $id);
	$this->_move_dspl_ids($db_name, $t['aggr_id'], $t['dspl_id'], FALSE);
	$this->db->where('id', $id)->delete($db_name);
	return 0;
    }
    
    public function add_tutorial($db_name, $v_arr, $id_chk=NULL)
    {
	if(!$this->check_howto_db($db_name)) return 100;
	$dseq = $this->get_dseqs($db_name, $v_arr['aggr_id']);
	if(isset($v_arr['d_pos'])){
		$dspl_id = $dseq - $v_arr['d_pos'] + 1;
		$this->_move_dspl_ids($db_name, $v_arr['aggr_id'], $dspl_id, TRUE);
		unset($v_arr['d_pos']);
	}
	if(!isset($dspl_id)) $dspl_id = $dseq;
	$this->db->set('dspl_id', $dspl_id);
	if(!in_array('added', array_keys($v_arr))) $this->db->set('added', 'CURRENT_DATE', FALSE);
	$this->db->insert($db_name, $v_arr);
	if($id_chk){
		$chk_arr = $this->db->reset_query()->where('id', $id_chk)->get($db_name)->row_array();
		if(empty($chk_arr)) return 9;
		foreach($v_arr as $k=>$v){
			if($chk_arr[$k] != $v) return 9;
		}
	}
	return 0;
    }
    
    public function upd_tutorial($db_name, $f_id, $v_arr)
    {
	if(!$this->check_howto_db($db_name)) return 100;
	if(isset($v_arr['d_pos'])){
		$d_pos = $v_arr['d_pos'];
		unset($v_arr['d_pos']);
		$t = $this->get_tutorial($db_name, $f_id);
		if(isset($v_arr['aggr_id'])) $aggr_id = $v_arr['aggr_id'];
		else $aggr_id = $t['aggr_id'];
		$d = $this->get_dseqs($db_name, $aggr_id);
		if(isset($v_arr['aggr_id'])) $d++;
		$dspl_id = $d - $d_pos;
		$this->_move_dspl_ids($db_name, $t['aggr_id'], $t['dspl_id'], FALSE);
		$this->_move_dspl_ids($db_name, $aggr_id, $dspl_id, TRUE);
		$this->db->set('dspl_id', $dspl_id);
	}
	$this->db->where('id', $f_id)->update($db_name, $v_arr);
	return 0;
    }
    
    public function get_filesrc($a_id, $db_name)
    {
	if(!$this->check_howto_db($db_name)) return 100;
	$new_id = $this->db->select_max('id', 'max_id')->get($db_name)->row('max_id') + 1;
	return array($this->pathman($a_id)['path_dir'], $new_id);
    }

    public function get_dseqs($db, $aa=NULL)
    {
    	$this->db->distinct()->select('aggr_id, dspl_id_seq(aggr_id) AS dseq', FALSE);
    	if(is_array($aa)) $this->db->where_in('aggr_id', $aa);
    	elseif($aa) $this->db->where('aggr_id', $aa);
    	$query = $this->db->get($db);
    	if(!is_array($aa) && $aa) return $query->row('dseq');
    	else return $query->result_array();
    }
    
    public function get_sln_emails($sln_id=NULL)
    {
    	$this->db->select('auth.id AS user_id, salons.id AS sln_id, email', FALSE)->from('auth')->join('salons', 'auth.id = user_id', 'left');
	if($sln_id) $this->db->where('salons.id', $sln_id);
    	$query = $this->db->get();
	if($sln_id) return $query->row_array();
    	return $query->result_array();
    }
    
    public function get_alter_kagents($sln_id)
    {
	$r_arr = [];
	$q_res = $this->db->distinct()->select('kagent_id')->get_where('orders', array('sln_id'=>$sln_id))->result_array();
	foreach($q_res as $i){
		$r_arr[] = $i['kagent_id'];
    	}
	return $r_arr;
    }
    
    public function get_alter_items($item, $id=NULL) //$item - 'salons'/'kagents'. Возвращает салоны, соотв-е kagent_id, либо к/агентов, соотв-х sln_id в виде массива пар вида {'id'=>id, 'name'=>name}
    {
	if($id){
		if($item == 'kagents'){
			$id_select = 'kagent_id';
			$id_where = 'sln_id';
			
		}
		else{
			$id_select = 'sln_id';
			$id_where = 'kagent_id';
		}
		$q_res = $this->db->distinct()->select($id_select)->get_where('orders', array($id_where=>$id))->result();
		$r_arr = array();
		foreach($q_res as $i){
			$r_arr[] = $i->{$id_select};
		}	
	}
	$this->db->select('id, name');
	if(isset($r_arr) && !empty($r_arr)) $this->db->where_in('id', $r_arr);
	return $this->db->get($item)->result_array();
    }
    
    public function get_sln_stuff($sln_id)
    {
	return $this->db->select('id, name')->where('sln_id', $sln_id)->where('is_active', TRUE)->order_by('id', 'ASC')->get('sln_managers')->result_array();
    }
    
    public function del_sln_stuff($id)
    {
	$this->db->where('id', $id)->set('is_active', FALSE)->update('sln_managers');
    }
    public function add_sln_stuff($sln_id, $name)
    {
	$this->db->insert('sln_managers', array('sln_id'=>$sln_id, 'name'=>$name));
    }
	
}
