<?php
	class Login extends CI_Controller {

				protected $targets = array('admin' => 'admin', 'kagent' => 'orders', 'salon' => 'orders', 'manager'=>'manager', 'store'=>'store');		
				protected $lf_params = array();

        public function __construct()
        {
            parent::__construct();
			$this->load->helper(array('form', 'url', 'html', 'cookie'));
			$this->load->library('form_validation');
			$this->load->library('session');
			$this->load->model('login_model'); //!!!
			$this->mdl = $this->login_model;
			$this->lf_params['css_link'] = link_tag('css/login.css');
			$this->lf_params['ext_js'] = $this->_ext_js();
			$this->lf_params['js_onload'] = $this->load->view('js/js_onload', NULL, TRUE);
        }

		private function _check_logined()
		{
			if($this->session->has_userdata('user_type')){
				$trgt = $this->targets[$this->session->user_type];
				redirect($trgt, 'location');
			}
			else{
				if(isset($_SESSION)) session_destroy();
				session_start();
				$this->session->set_userdata('on_auth', TRUE);
			}
		}
		
		private function _ext_js()
		{
			$res_js = '';
			foreach(['main.js', 'login.js'] as $i){
				$res_js .= '<script src="'.site_url("js/$i").'"></script>';
			}
			return $res_js;
		}

		public function index()
		{
			if( ! $this->session->has_userdata('on_auth')) $this->_check_logined();
			$this->load->view('login/form', $this->lf_params);
		}

		public function submit()
		{
			$submit_data = array('usr_name' => $this->input->post('username'), 'pswrd' => trim($this->input->post('password')));
			$user_info = $this->mdl->get_auth_info($submit_data['usr_name']); //!!!
			if(!$user_info){
				set_cookie('popup_msg', '!!Неверный логин');
				$this->load->view('login/form', $this->lf_params);
				return 0;
			}
			if(!$user_info->is_active){
				set_cookie('popup_msg', '!!Доступ в ЛК заблокирован');
				$this->load->view('login/form', $this->lf_params);
				return 0;
			}
			if(password_verify($submit_data['pswrd'], $user_info->pswrd_hash)){
				if(password_needs_rehash($user_info->pswrd_hash, PASSWORD_DEFAULT)){
					$new_hash =  password_hash($submit_data['pswrd'], PASSWORD_DEFAULT);
					$this->login_model->update_hash($user_info->id, $new_hash);
				}
			}
			else{
				set_cookie('popup_msg', '!!Неправильный пароль');
				$this->load->view('login/form', $this->lf_params);
				return 0;				
			}	
			$this->session->unset_userdata('on_auth');
			$this->session->set_userdata(array('user_id' => $user_info->id, 'user_type' => $user_info->user_type, 'login'=>$user_info->login));
			$this->load->helper('cookie');
			set_cookie('popup_msg', 'Вы зашли в партнёрский кабинет как '.$user_info->login);
			redirect($this->targets[$user_info->user_type], 'location');
		}
		
		public function logout()
		{
			if(isset($_SESSION)) session_destroy();
			$sbm_arr = [1, 2];
			foreach($sbm_arr as $i){
				if(get_cookie('show_sbm'.$i)) delete_cookie('show_sbm'.$i);
			}
			$this->load->view('login/form', $this->lf_params);
		}
}
