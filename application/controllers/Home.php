<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */

	public function __construct()
    {
        parent::__construct();
        $this->load->model('home_model');
       
    }

	public function index()
	{
		$this->display('homepage/index.html');
	}

	//企业列表
	public function company_list($page=1){
        $data = $this->home_model->company_list($page);
        $base_url = "/home/company_list/";
        $pager = $this->pagination->getPageLink4home($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('homepage/company/com_list.html');
    }


	public function login()
	{
		$this->display('homepage/login.html');
	}
}
