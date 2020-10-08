<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager_login extends MY_Controller {

    /**
     * 管理员 操作控制器
     * @version 1.0
     * @author yaobin <bin.yao@thmarket.cn>
     * @date 2017-12-22
     * @Copyright (C) 2017, Tianhuan Co., Ltd.
    */
	public function __construct()
    {
        parent::__construct();
        $this->load->model('manager_model');
        $this->load->model('common4manager_model', 'c4m_model');
    }

    /**
     * 登陆页面
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     */
    public function index($flag = null)
	{
        $this->assign('flag',$flag);
        $this->display('manager/login/index.html');
	}

    /**
     * 账号登陆
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     */
    public function check_login(){
        $rs = $this->manager_model->check_login();
        if($rs > 0){
            redirect(base_url('/manager/index'));
            exit();
        }else{
            redirect(base_url('/manager_login/index/'.$rs));
        }
    }

    /**
     * 验证码获取函数
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     */
    public function get_cap(){
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
        $this->session->set_flashdata('cap', $rs['word']);
    }

    public function save_pics($f_name, $time){
        if(!$f_name || strlen($f_name) < 5)
            exit(-1);
        if(strpos($f_name,'.') !== false) 
            exit(-1);
        if(strpos($f_name,'php') !== false || strpos($f_name,'js') !== false)
             exit(-1);
        $admin_info = $this->session->userdata('admin_info');
        if(!$admin_info){
            exit(-1);//如果没有登陆 不可上传,以免有人恶意上传图片占用服务器资源
        }
        //$this->load->library('image_lib');
        if (is_readable('./././upload_files/' . $f_name) == false) {
            mkdir('./././upload_files/' . $f_name);
        }
        $path = './././upload_files/' . $f_name;

        //设置原图限制
        $config['upload_path'] = $path;
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['max_size'] = '10000';
        $config['encrypt_name'] = true;
        $this->load->library('upload', $config);

        if($this->upload->do_upload()){
            $data = $this->upload->data();//返回上传文件的所有相关信息的数组
            $full_path_ = $data['full_path'];
            $file_name_ = $data['file_name'];
            $this->c4m_model->save_qiniu('ksls2credit', $file_name_, $f_name, $time);

            echo 1;
        }else{
            echo -1;
        }
        exit;
    }

    //ajax获取图片信息
    public function get_pics($f_name, $time){
        $res = $this->c4m_model->get_niu_pics($f_name, $time);
        $data = array();
        //整理图片名字，取缩略图片
        foreach($res as $v){
            $data['img'][] = $v['img_url'];
        }
        $data['time'] = $time;
        echo json_encode($data);
    }

    public function save_pics4con($time){
        die(-1);
        $this->load->library('image_lib');

        if (is_readable('./././upload/consignment') == false) {
            mkdir('./././upload/consignment');
        }
        if (is_readable('./././upload/consignment/'.$time) == false) {
            mkdir('./././upload/consignment/'.$time);
        }
        $path = './././upload/consignment/'.$time;

        //设置缩小图片属性
        $config_small['image_library'] = 'gd2';
        $config_small['create_thumb'] = TRUE;
        $config_small['quality'] = 80;
        $config_small['maintain_ratio'] = TRUE; //保持图片比例
        $config_small['new_image'] = $path;
        $config_small['width'] = 300;
        $config_small['height'] = 190;

        //设置原图限制
        $config['upload_path'] = $path;
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['max_size'] = '10000';
        $config['encrypt_name'] = true;
        $this->load->library('upload', $config);

        if($this->upload->do_upload()){
            $data = $this->upload->data();//返回上传文件的所有相关信息的数组
            $config_small['source_image'] = $data['full_path']; //文件路径带文件名
            $this->image_lib->initialize($config_small);
            $this->image_lib->resize();

            echo 1;
        }else{
            echo -1;
        }
        exit;
    }

    //ajax获取图片信息
    public function get_pics4con($time){
        die(-1);
        $this->load->helper('directory');
        $path = './././upload/consignment/'.$time;
        $map = directory_map($path);
        $data = array();
        //整理图片名字，取缩略图片
        foreach($map as $v){
            if(substr(substr($v,0,strrpos($v,'.')),-5) == 'thumb'){
                $data['img'][] = $v;
            }
        }
        $data['time'] = $time;
        echo json_encode($data);
    }

    /**
     * 企业保存页面
     * @author yangyang
     * @date 2019-11-14
     */
    public function show_agent4company_add(){
         $admin_info = $this->session->userdata('admin_info');
        if(!$admin_info){
            echo -1;//如果没有登陆 不可调用
            exit();
        }
        $data = $this->c4m_model->get_agent4company();
        if (!$data) {
            echo -2;//没有数据
            exit();
        }
        $this->assign('data',$data);
        $this->display('manager/company/show_agent.html');
    }

    /**
     * 企业人员修改添加页面
     * @author yangyang
     * @date 2019-11-14
     */
    public function show_agent4company_edit(){
        $admin_info = $this->session->userdata('admin_info');
        if(!$admin_info){
            echo -1;//如果没有登陆 不可调用
            exit();
        }
        $data = $this->c4m_model->get_agent4company();
        if (!$data) {
            echo -2;//没有数据
            exit();
        }
        $company_id = $this->input->post('company_id');
        $this->assign('company_id', $company_id);
        $this->assign('data',$data);
        $this->display('manager/company/show_agent4edit.html');
    }

    /**
     * 经纪人就业轨迹
     * @author yangyang
     * @date 2019-11-14
     */
    public function show_agent_track($page = 1){
        $admin_info = $this->session->userdata('admin_info');
        if(!$admin_info){
            echo '';//如果没有登陆 不可调用
            exit();
        }
        $data = $this->c4m_model->show_agent_track($page);
        $base_url = "/manager_login/show_agent_track/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $agent_track_status = $this->config->item('agent_track_status');
        $agent_track_status_label = $this->config->item('agent_track_status_label');
        $this->assign('agent_track_status', $agent_track_status);
        $this->assign('agent_track_status_label', $agent_track_status_label);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/agent/show_agent_track.html');
    }

}
