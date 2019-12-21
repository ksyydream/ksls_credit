<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent extends MY_Controller {

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

	public $agent_id = null;
	public function __construct()
    {
        parent::__construct();
        $this->load->model('agent_model');
        $agent_id = $this->session->userdata('agent_id');
		//die(var_dump($agent_id));
        if($agent_id){
			$data = $this->agent_model->get_detail($agent_id);
			if($data && $data['flag'] == 2){
				$this->assign('data', $data);
				$this->agent_id = $agent_id;
			}
		}
		if(!$this->agent_id){
		 if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            {
                $err_ = $this->manager_model->fun_fail('操作异常！请重新登录！');
                $this->ajaxReturn($err_);
            }
            else {
                 redirect('/home/logout');
            }
        }
       
    }

    //经纪人编辑个人信息页面
	public function person_change()
	{
    	$this->display('homepage/agent/person_change.html');
	}

	public function save_info(){
		$res = $this->agent_model->save_info($this->agent_id);
        $this->ajaxReturn($res);
	}

	//经纪人人事申请页面
	public function add_track()
	{
    	$this->display('homepage/agent/add_track.html');
	}
	
}
