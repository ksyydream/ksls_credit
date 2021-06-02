<?php
defined('BASEPATH') OR exit('No direct script access allowed');
require_once "Api_controller.php";
class Outside_api extends Api_controller {

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
	public function __construct(){
		parent::__construct();
		$this->load->model('outside_model');
		$free_action = array('login');
		$action = $this->uri->segment(2);
		if(!in_array($action, $free_action)){
			$token = $this->get_header_token();
			if(!$token){
				$this->ajaxReturn(array('status' => -1, 'msg' => 'token缺失!', "result" => ''));
			}
			$api_id_ = $this->get_token_uid($token,"API"); //可以不验证
			$check_re = $this->outside_model->check_token($token, $api_id_);
			if($check_re['status'] < 0){
				$this->ajaxReturn($check_re);
			}
		}
	}

	//公司人员登录
	public function login(){
		$rs = $this->outside_model->login();
		if($rs['status'] == 1){
			$API_id = $rs['result']['id'];
			$token = $this->set_token_uid($API_id,'API');
			$this->outside_model->update_api_tt($API_id,$token);
			$rs['result'] = array('token' => $token);
		}
		$this->ajaxReturn($rs);
	}

	public function get_c(){
		$rs = $this->outside_model->get_c();
		$this->ajaxReturn($rs);
	}

	public function get_a(){
		$rs = $this->outside_model->get_a();
		$this->ajaxReturn($rs);
	}
}
