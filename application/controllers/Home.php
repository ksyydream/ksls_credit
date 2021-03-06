<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends Home_Controller {

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

    }

    public function test_cap(){
        //phpinfo();
        die();
        $company_cap_ = $this->session->userdata('company_cap');
        $agent_cap_ = $this->session->userdata('agent_cap');
        die(var_dump( $company_cap_ . '||' . $agent_cap_));
    }

    //前台首页
	public function index()
	{
		$this->display('homepage/index.html');
	}

    public function logout(){
        $this->session->sess_destroy();
        redirect('/home');
    }

    public function login()
    {
        $this->display('homepage/login.html');
    }

    public function get_company_cap(){
        $vals = array(
            //'word'      => 'Random word',
            'img_path'  => './upload/captcha/',
            'img_url'   => '/upload/captcha/',
            'img_width' => '120',
            'img_height'    => 30,
            'expiration'    => 7200,
            'word_length'   => 4,
            'font_size' => 18,
            'img_id'    => 'Imageid',
            'pool'      => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',

            // White background and border, black text and red grid
            'colors'    => array(
                'background' => array(255, 255, 255),
                'border' => array(255, 255, 255),
                'text' => array(0, 0, 0),
                'grid' => array(255, 40, 40)
            )
        );

        $rs = create_captcha($vals);
        $this->session->set_userdata(array('company_cap' => $rs['word']));
        
    }

    public function get_agent_cap(){
        $vals = array(
            //'word'      => 'Random word',
            'img_path'  => './upload/captcha/',
            'img_url'   => '/upload/captcha/',
            'img_width' => '120',
            'img_height'    => 30,
            'expiration'    => 7200,
            'word_length'   => 4,
            'font_size' => 18,
            'img_id'    => 'Imageid',
            'pool'      => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',

            // White background and border, black text and red grid
            'colors'    => array(
                'background' => array(255, 255, 255),
                'border' => array(255, 255, 255),
                'text' => array(0, 0, 0),
                'grid' => array(255, 40, 40)
            )
        );

        $rs = create_captcha($vals);
        $this->session->set_userdata(array('agent_cap' => $rs['word']));
        
    }

    //企业登录
    public function login_company(){
        $res = $this->company_model->login();
        $this->ajaxReturn($res);
    }

    //经纪人登录
    public function login_agent(){
        $res = $this->agent_model->login();
        $this->ajaxReturn($res);
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

    //企业详情
    public function company_detail($c_id){
    	$data = $this->home_model->get_company_detail($c_id);
		if(!$data)
			redirect('/');
		if($data['flag'] != 2)
			redirect('/');
    	$ns_list_1 = array();
    	$ns_list_2 = array();
    	$ns_list_other = array();
    	if($data['ns_list']){
    		if(isset($data['ns_list'][0]))
    			$ns_list_1 = $data['ns_list'][0];
    		if(isset($data['ns_list'][1]))
    			$ns_list_2 = $data['ns_list'][1];
    		if (count($data['ns_list']) > 2) {
    			$ns_list_other = $data['ns_list'];
    			unset($ns_list_other[1]);
    			unset($ns_list_other[0]);
    		}
    	}
    	$year_ = date('Y');
    	$year_list = array();
    	while ($year_ >= 2017) {
    		$year_list[] = $year_;
    		$year_--;
    	}
    	$this->assign('data', $data);
    	$this->assign('year_list', $year_list);
    	$this->assign('ns_list_1', $ns_list_1);
    	$this->assign('ns_list_2', $ns_list_2);
    	$this->assign('ns_list_other', $ns_list_other);
    	$this->display('homepage/company/com_page.html');
    }

    //企业详情中的 经纪人列表
    public function show_agent($page = 1){
        $data = $this->home_model->show_agent($page);
        $base_url = "/home/show_agent/";
        //getPageLink
        $pager = $this->pagination->getPageLink4home($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('homepage/company/show_agent.html');
    }

    //单独显示企业 持证经纪人
    public function show_agent_1($page = 1){
        $data = $this->home_model->show_agent($page, 1);
        $base_url = "/home/show_agent_1/";
        //getPageLink
        $pager = $this->pagination->getPageLink4home($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('homepage/company/show_agent_1.html');
    }
    //单独显示企业 从业经纪人
    public function show_agent_2($page = 1){
        $data = $this->home_model->show_agent($page, 2);
        $base_url = "/home/show_agent_2/";
        //getPageLink
        $pager = $this->pagination->getPageLink4home($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('homepage/company/show_agent_2.html');
    }
    //企业详情中的 企业事件列表
    public function show_company_record($page = 1){
        $data = $this->home_model->show_company_record($page);
        $base_url = "/home/show_company_record/";
        $pager = $this->pagination->getPageLink4home($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('homepage/company/show_event.html');
    }
    //显示从业人员信息
    public function show_employess_dialog(){
        $data = $this->home_model->get_agentByKey4add();
        if(!$data){
            echo -1;die();
        }
        if($data['work_type'] != 2){
            echo -2;die();
        }
        if($data['company_id'] != -1){
            echo -3;die();
        }
        $this->assign('data', $data);
        $this->display('homepage/company/show_employess_dialog.html');
    }

    //经纪人列表
    public function agent_list($page=1){
        $data = $this->home_model->agent_list($page);
        $base_url = "/home/agent_list/";
        $pager = $this->pagination->getPageLink($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('homepage/agent/person_list.html');
    }

    public function agent_detail($a_id){
    	$data = $this->home_model->get_agent_detail($a_id);
        $year_ = date('Y');
    	$year_list = array();
        while ($year_ >= 2017) {
            $year_list[] = $year_;
            $year_--;
        }
        $this->assign('data', $data);
        $this->assign('year_list', $year_list);
    	$this->display('homepage/agent/person_page.html');
    }

    //企业详情中的 企业事件列表
    public function show_agent_record($page = 1){
        $data = $this->home_model->show_agent_record($page);
        $base_url = "/home/show_agent_record/";
        $pager = $this->pagination->getPageLink($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('homepage/agent/show_event.html');
    }

    public function qr_code_raw($c_id)
    {
        require_once (APPPATH . 'libraries/phpqrcode/phpqrcode.php');
        QRcode::png('http://'.$_SERVER['SERVER_NAME'] . '/mobile/mobile_get_company_detail?c_id=' . $c_id);
    }

    public function qr_code_raw4agent($job_code)
    {
        require_once (APPPATH . 'libraries/phpqrcode/phpqrcode.php');
        QRcode::png('http://'.$_SERVER['SERVER_NAME'] . '/mobile/mobile_get_agent_detail?job_code=' . $job_code);
    }

    public function get_company_cert4api($company_id){
        $data = $this->home_model->get_last_cert($company_id);
        if(!$data){
            echo '为找到对应证书';
            die();
        }
        $this->assign('data', $data);
        $this->display('homepage/cert4api/dianzizhengshu.html');
    }

    public function get_agent_cert4api($agent_id){
        $data = $this->home_model->get_agent_cert($agent_id);
        if(!$data){
            echo '为找到对应证书';
            die();
        }
        $this->assign('data', $data);
        $show_person_ = '';
        if($data['person_img_list'])
            $show_person_ = $data['person_img_list'][0]['img'];
        $this->assign('show_person_', $show_person_);
        $this->display('homepage/cert4api/dianzizhengshu1.html');
    }
	
}
