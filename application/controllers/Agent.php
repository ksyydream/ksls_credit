<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Agent extends Home_Controller {

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
		if(!$this->agent_id){
			home_err_return();
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
		$agent_info = $this->agent_model->get_detail4self($this->agent_id);
		$company_list = $this->company_model->get_company4track(array(2));
		$this->assign('company_list',$company_list);
		$this->assign('agent_info',$agent_info);
    	$this->display('homepage/agent/add_track.html');
	}

	public function save_apply(){
		$res = $this->agent_model->save_apply($this->agent_id);
		$this->ajaxReturn($res);
	}

	//人事申请列表
	public function track_list($page=1){
		$data = $this->agent_model->track_list($page);
		$base_url = "/agent/track_list/";
		$pager = $this->pagination->getPageLink($base_url, $data['total_rows'], $data['limit']);
		$this->assign('pager', $pager);
		$this->assign('page', $page);
		$this->assign('data', $data);
		$this->display('homepage/agent/track_list.html');
	}
	
}
