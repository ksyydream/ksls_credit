<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Mobile extends MY_Controller {

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


    //扫描二维码获取信息
    public function mobile_get_agent_detail(){

		$data = $this->home_model->mobile_get_agent_detail();
		$this->assign('data', $data);
		$year_ = date('Y');
		$year_list = array();
		while ($year_ >= 2019) {
			$year_list[] = $year_;
			$year_--;
		}
		$this->assign('year_list', $year_list);

		$show_person_ = '';
		if(isset($data['person_img_list']) && $data['person_img_list'])
			$show_person_ = $data['person_img_list'][0]['img'];
		$this->assign('show_person_', $show_person_);
		$this->display('mobile/html/saff-info.html');
        //$this->display('mobile/html/person_page.html');
    }

	//企业扫描二维码获取信息
	public function mobile_get_company_detail(){
		$data = $this->home_model->mobile_get_company_detail();
		$this->assign('data', $data);
		$year_ = date('Y');
		$year_list = array();
		while ($year_ >= 2019) {
			$year_list[] = $year_;
			$year_--;
		}
		$this->assign('year_list', $year_list);
		$this->display('mobile/html/company-info.html');
	}

	//获取经纪人 事件和轨迹
	public function saff_load(){
		$res = $this->home_model->saff_load();
		$this->ajaxReturn($res);
	}

	//获取企业 事件
	public function company_r_load(){
		$res = $this->home_model->company_r_load();
		$this->ajaxReturn($res);
	}

	//获取企业 经纪人
	public function company_a_load(){
		$res = $this->home_model->company_a_load();
		$this->ajaxReturn($res);
	}
	
}
