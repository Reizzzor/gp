<?php
require_once(dirname(__FILE__) . "/Orders_model.php");

class Store_model extends Orders_Model{
	public function __construct()
	{
		parent::__construct();
	}

	public function get_confirming()
	{
		return $this->db->get_where('reserved', array('is_confirming'=>TRUE, 'is_uploading'=>FALSE))->result_array();
	}

	public function get_order($uniq_id)
	{
		return $this->db->where('uniq_id',$uniq_id)->get('orders')->row_array();
	}

	public function submit_order($id, $data)
	{
		$this->db->where('id', $id)->update('reserved', $data);
		return 0;
	}
	
	public function delete_order($order_id)
	{
		$this->db->where('id', $order_id)->delete('reserved');
		return 0;
	}
	
	public function upload_order($order_id)
	{
		$this->db->where('id', $order_id)->update('reserved', array('is_uploading'=>TRUE));
		return 0;
	}
	

}
?>