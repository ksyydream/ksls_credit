<?php
if (! defined('BASEPATH'))
    exit('No direct script access allowed');
/**
 * 扩展业务控制器
 *
 * @package		app
 * @subpackage	Libraries
 * @category	controller
 * @author      yaobin
 *        
 */
class MY_Controller extends CI_Controller
{
	public $wx_success = array('status' => 1, 'msg' => '', 'result' => array());
	public $wx_fail = array('status' => -1, 'msg' => '操作失败!', 'result' => array());
    public function __construct ()
    {
        parent::__construct();
		$this->load->model('sys_model');
        ini_set('date.timezone','Asia/Shanghai');
        $this->cismarty->assign('base_url',base_url());//url路径
		//if(!@file_get_contents('./uploadfiles/profile/'.$user_pic)){
		//	$user_pic='user_photo.gif';
		//}

    }
    
	//重载smarty方法assign
	public function assign($key,$val) {  
        $this->cismarty->assign($key,$val);  
    }  
    
	//重载smarty方法display
    public function display($html) {
        $this->cismarty->display($html);  
    }

	public function ajaxReturn($data){
		exit(json_encode($data, JSON_UNESCAPED_UNICODE));
	}
    /**
     * 获取产品菜单的树状结构
     **/
    public function subtree($arr,$id=0,$lev=1)
    {
    	static $subs = array();
    	foreach($arr as $v){
    		if((int)$v['parent_id']==$id){
    		    $v['lev'] = $lev;
    		    $subs[]=$v;
    		    $this->subtree($arr,$v['id'],$lev+1);
    		}
    	}
    	return $subs;
    }

	/**
	 * 提示信息
	 * @param varchar $message 提示信息
	 * @param varchar $url 跳转页面，如果为空则后退
	 * @param int $type 提示类型，1是自动关闭的提示框，2是错误提示框
	 **/
	public function show_message($message,$url=null,$type=1){
		if($url){
			$js = "location.href='".$url."';";
		}else{
			$js = "history.back();";
		}

		if($type=='1'){
			echo "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
				<html xmlns='http://www.w3.org/1999/xhtml'>
				<head>
				<meta name=\"viewport\" content=\"width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no\">
				<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
				<title>".$message."</title>
				<script src='".base_url()."assets/js/jquery.min.js'></script>
				<link rel='stylesheet' href='".base_url()."assets/css/easydialog.css'>
				</head>
				<body>
				<script src='".base_url()."assets/js/easydialog.min.js'></script>
				<script>
				var callFn = function(){
				  ".$js."
				};
				easyDialog.open({
					container : {
						content : '".$message."'
					},
					autoClose : 1200,
					callback : callFn

				});

				</script>
				</body>
				</html>";
		}
		exit;
	}
    
    /**
     * 中国正常GCJ02坐标---->百度地图BD09坐标
     * 腾讯地图用的也是GCJ02坐标
     * @param double $lat 纬度
     * @param double $lng 经度
     */
    function Convert_GCJ02_To_BD09($lat,$lng){
    	$x_pi = 3.14159265358979324 * 3000.0 / 180.0;
    	$x = $lng;
    	$y = $lat;
    	$z =sqrt($x * $x + $y * $y) + 0.00002 * sin($y * $x_pi);
    	$theta = atan2($y, $x) + 0.000003 * cos($x * $x_pi);
    	$lng = $z * cos($theta) + 0.0065;
    	$lat = $z * sin($theta) + 0.006;
    	return array('lng'=>$lng,'lat'=>$lat);
    }
    
    
    private function createNonceStr($length = 16) {
    	$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    	$str = "";
    	for ($i = 0; $i < $length; $i++) {
    		$str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
    	}
    	return $str;
    }
    
    function getSignPackage($ticket) {
    	$jsapiTicket = $ticket['ticket'];
    	$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
    	$url = "$protocol$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
    	$timestamp = time();
    	$nonceStr = $this->createNonceStr();
    	$string = "jsapi_ticket=$jsapiTicket&noncestr=$nonceStr&timestamp=$timestamp&url=$url";
    	$signature = sha1($string);
    	$signPackage = array(
    			"appId"     => APP_ID,
    			"nonceStr"  => $nonceStr,
    			"timestamp" => $timestamp,
    			"url"       => $url,
    			"signature" => $signature,
    			"rawString" => $string
    	);
    	return $signPackage;
    }

}

/**
 * 前台扩展业务控制器
 *
 * @package		app
 * @subpackage	Libraries
 * @category	controller
 * @author      yangyang
 *
 */
class Home_Controller extends MY_Controller{
	public $agent_id = null;
	public $company_id = null;
	public $is_whow_agent_cert_ = -1; //经纪人证书权限 1代表有,-1代表没有
	public function __construct ()
	{
		parent::__construct();
		$this->agent_id = null;
		$this->company_id = null;
		$this->load->model('home_model');
		$this->load->model('agent_model');
		$this->load->model('company_model');
		$uncheck_action = array('logout', 'login', 'login_agent', 'get_company_cert4api', 'get_agent_cert4api');
		$this->assign('user_flag_', '');
		$action_ = $this->router->fetch_method();
		if(!in_array($action_, $uncheck_action)){
			$agent_id = $this->session->userdata('agent_id');
			if($agent_id){
				$data = $this->home_model->get_agent_flag($agent_id);
				if($data && $data['flag'] == 2){
					$this->assign('user_flag_', 'agent');
					$this->assign('hear_show_', $data['name']);
					$this->agent_id = $agent_id;
					$this->session->unset_userdata('company_id');
					$this->session->unset_userdata('company_info');
				}else{
					home_err_return();
				}
			}

			$company_id = $this->session->userdata('company_id');
			if($company_id){
				$data = $this->home_model->get_company_flag($company_id);
				if($data && $data['flag'] == 2){
					$this->assign('user_flag_', 'company');
					$this->assign('hear_show_', $data['company_name']);
					$this->company_id = $company_id;
				}else{
					home_err_return();
				}
			}
		}
		$this->assign('a_id_', $this->agent_id);
		$this->assign('c_id_', $this->company_id);

		//检查经纪人证书权限
		if($this->agent_id){
			$agent_detail = $this->agent_model->get_detail4power($this->agent_id);
			if($agent_detail && strlen($agent_detail['job_code']) == 6 && in_array(substr($agent_detail['job_code'],0,2), array('20','19', '21'))) {
				$this->is_whow_agent_cert_ = 1;
			}
		}
		$this->assign('is_whow_agent_cert_', $this->is_whow_agent_cert_);
	}
}

/* End of file MY_Controller.php */
/* Location: ./application/core/MY_Controller.php */