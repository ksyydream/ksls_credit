<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company extends Home_Controller {

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
		if(!$this->company_id){
			home_err_return();
        }
       
    }

    public function company_cancel_agent(){
    	$res = $this->company_model->company_cancel_agent($this->company_id);
    	$this->ajaxReturn($res);
    }

	public function change_pwd(){
		$this->display('homepage/company/change_pwd.html');
	}

	public function save_pwd(){
		$res = $this->company_model->save_pwd($this->company_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/home/logout'));
        }else{
            $this->show_message($res['msg']);
        }
	}

	public function get_cert($ns_id){
		$data = $this->company_model->get_cert($ns_id, $this->company_id);
		if(!$data)
			redirect('/home/index');
		$this->assign('data', $data);
		$this->display('homepage/company/dianzizhengshu.html');
	}
	
}
