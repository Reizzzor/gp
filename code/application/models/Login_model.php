<?php
class Login_model extends CI_Model {

        public function __construct()
        {
                $this->load->database();
        }

		public function get_auth_info($login)
		{
			return $this->db->get_where('auth', array('login'=>$login))->row();
		}
	
		public function update_hash($user_id, $hash)
		{
			$this->db->set('pswrd_hash', $hash);
			$this->db->replace('auth');
		}
}
