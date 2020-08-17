<?php
	class Pages extends CI_Controller {

	public function view($page = 'home')
	{
			if ( ! file_exists(APPPATH.'/views/pages/'.$page.'.php'))
			{
					// Упс, у нас нет такой страницы!
					show_404();
			}

			$data['title'] = ucfirst($page); // Capitalize the first letter

			$this->load->view('templates/header', $data);
			$this->load->view('pages/'.$page, $data);
			$this->load->view('templates/footer', $data);
	}
}

