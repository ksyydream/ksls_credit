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
    private $percetage = 0.4;
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

    public function get_img($ns_id){
        require_once (APPPATH . 'libraries/phpqrcode/phpqrcode.php');
        $qrpath  = '/Users/yangyang/www/ksls_credit/upload_files/share_img/1.png';
        $URL= 'http://'.$_SERVER['SERVER_NAME'] . '/mobile/mobile_get_company_detail?c_id=' . $ns_id;
        QRcode::png($URL, $qrpath, 3, 2);

        $this->createGoodsPng(APPPATH . "../assets/dzzs/i/zhengshu1.jpg", $qrpath);
    }

    private function createGoodsPng($bg, $qrpath) {
        //创建画布
        $im = imagecreatetruecolor(800, 1300);

        //填充画布背景色
        $color = imagecolorallocate($im, 255, 255, 255);
        imagefill($im, 0, 0, $color);

        //字体文件
        $font_file = APPPATH . "../assets/share/msyh.ttf";
        $font_file_bold = APPPATH . "../assets/share/msyh_bold.ttf";

        //设定字体的颜色
        $font_color = ImageColorAllocate ($im, 255, 255, 255);

        //背景图片
        list($g_w,$g_h) = getimagesize($bg);
        $bgImg = $this->createImageFromFile($bg);
        imagecopyresized($im, $bgImg, 0, 0, 0, 0, $g_w, $g_h, $g_w, $g_h);

        $img_ = APPPATH . "../assets/dzzs/i/gaizhang.png";
        list($code_w,$code_h) = getimagesize($img_);
        $mainImg = $this->createImageFromFile($img_);
        imagecopyresized($im, $mainImg, 220, 220, 0, 0, 360, 360, $code_w, $code_h);

        $title1 = $this->cn_row_substr('测试11', 1, 20);
        imagettftext($im, 16,0, 200, 130, $font_color, $font_file_bold, $title1[1]);

        imagettftext($im, 14,0, 220, 640, $font_color, $font_file, "原价111: " . 'market_price');
        imagettftext($im, 20,0, 335, 640, $font_color, $font_file_bold, '￥');
        imagettftext($im, 36,0, 360, 640, $font_color, $font_file_bold, "shop_price");

        $title2 = $this->cn_row_substr("goods_name11", 1, 15);
        imagettftext($im, 20,0, 170, 700, $font_color ,$font_file, $title2[1]);

        //二维码
        list($code_w,$code_h) = getimagesize($qrpath);
        $codeImg = $this->createImageFromFile($qrpath);
        imagecopyresized($im, $codeImg, 150, 910, 0, 0, 160, 160, $code_w, $code_h);

        imagepng ($im, APPPATH . "../upload_files/share_img/222.jpg");
//        Header("Content-Type: image/png");
//        imagepng ($im);

        $this->saveSmallImage(APPPATH . "../upload_files/share_img/222.jpg", APPPATH . "../upload_files/share_img/222_small.jpg");

        //释放空间
        imagedestroy($im);
        imagedestroy($bgImg);
        imagedestroy($mainImg);
        imagedestroy($codeImg);

        //$img_url = $this->getBaseUrl() . "/public/share/tmp/goods_{$goods_data['goods_id']}.jpg";
        //$img_url_small = $this->getBaseUrl() . "/public/share/tmp/goods_{$goods_data['goods_id']}_small.jpg";
        $this->ajaxReturn(['status' => 1, 'msg' => '获取成功', 'img_url' => '', 'img_url_small' => '', 'path' => '']);
    }

    private function saveSmallImage($oldPath, $newPath) {
        list($width, $height) = getimagesize($oldPath);
        $image = imagecreatefrompng($oldPath);
        $new_width = $width * $this->percetage;
        $new_height = $height * $this->percetage;
        $image_small = imagecreatetruecolor($new_width, $new_height);
        imagecopyresampled($image_small, $image,0,0,0,0, $new_width, $new_height, $width, $height);
        imagedestroy($image);

        imagepng($image_small, $newPath);
    }

    /**
     * 从图片文件创建Image资源
     * @param $file 图片文件，支持url
     * @return bool|resource    成功返回图片image资源，失败返回false
     */
    private function createImageFromFile($file){
        if(preg_match('/http(s)?:\/\//',$file)){
            $fileSuffix = $this->getNetworkImgType($file);
        } else {
            $fileSuffix = pathinfo($file, PATHINFO_EXTENSION);
        }
        if(!$fileSuffix) return false;
        switch ($fileSuffix){
            case 'jpeg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'jpg':
                $theImage = @imagecreatefromjpeg($file);
                break;
            case 'png':
                $theImage = @imagecreatefrompng($file);
                break;
            case 'gif':
                $theImage = @imagecreatefromgif($file);
                break;
            default:
                $theImage = @imagecreatefromstring(file_get_contents($file));
                break;
        }
        return $theImage;
    }

    /**
     * 获取网络图片类型
     * @param $url  网络图片url,支持不带后缀名url
     * @return bool
     */
    private function getNetworkImgType($url){
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $url); //设置需要获取的URL
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);//设置超时
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); //支持https
        curl_exec($ch);//执行curl会话
        $http_code = curl_getinfo($ch);//获取curl连接资源句柄信息
        curl_close($ch);//关闭资源连接
        if ($http_code['http_code'] == 200) {
            $theImgType = explode('/',$http_code['content_type']);
            if($theImgType[0] == 'image'){
                return $theImgType[1];
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    private function cn_row_substr($str,$row = 1,$number = 10,$suffix = true){
        $result = array();
        for ($r=1;$r<=$row;$r++){
            $result[$r] = '';
        }

        $str = trim($str);
        if(!$str) return $result;

        $theStrlen = strlen($str);

        //每行实际字节长度
        $oneRowNum = $number * 3;
        for($r=1;$r<=$row;$r++){
            if($r == $row and $theStrlen > $r * $oneRowNum and $suffix){
                $result[$r] = $this->mg_cn_substr($str,$oneRowNum-6,($r-1)* $oneRowNum).'...';
            }else{
                $result[$r] = $this->mg_cn_substr($str,$oneRowNum,($r-1)* $oneRowNum);
            }
            if($theStrlen < $r * $oneRowNum) break;
        }

        return $result;
    }

    private function mg_cn_substr($str,$len,$start = 0){
        $q_str = '';
        $q_strlen = ($start + $len)>strlen($str) ? strlen($str) : ($start + $len);

        //如果start不为起始位置，若起始位置为乱码就按照UTF-8编码获取新start
        if($start and json_encode(substr($str,$start,1)) === false){
            for($a=0;$a<3;$a++){
                $new_start = $start + $a;
                $m_str = substr($str,$new_start,3);
                if(json_encode($m_str) !== false) {
                    $start = $new_start;
                    break;
                }
            }
        }

        //切取内容
        for($i=$start;$i<$q_strlen;$i++){
            //ord()函数取得substr()的第一个字符的ASCII码，如果大于0xa0的话则是中文字符
            if(ord(substr($str,$i,1))>0xa0){
                $q_str .= substr($str,$i,3);
                $i+=2;
            }else{
                $q_str .= substr($str,$i,1);
            }
        }
        return $q_str;
    }

}
