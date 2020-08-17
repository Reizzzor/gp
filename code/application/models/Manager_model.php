<?php
require_once(dirname(__FILE__) . "/Orders_model.php");

class Manager_model extends Orders_Model{
	public function __construct()
	{
		parent::__construct();
	}

	public function set_user_id($uid)
	{
		parent::set_user_id($uid);
		$this->sln = $this->get_mngr_info($uid)['sln_ids'];
	}
}
?>