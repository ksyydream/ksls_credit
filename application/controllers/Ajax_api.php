<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Ajax_api extends CI_Controller {

    /**
     * Ajax控制器
     * @version 1.0
     * @author yangyang
     * @date 2019-11-01
     */
	public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        $this->load->library('image_lib');
        $this->load->helper('directory');
        $this->load->model('manager_model');
        $this->load->model('common4manager_model', 'c4m_model');
    }

    /**
     * 上传头像
     * @author yangyang
     * @date 2018-10-31
     */
    public function upload_head(){
        $admin_info = $this->session->userdata('admin_info');
        if(!$admin_info){
            echo -1;//如果没有登陆 不可上传,以免有人恶意上传图片占用服务器资源
        }
        $dir = FCPATH . '/upload_files/head';
        if(!is_dir($dir)){
            mkdir($dir,0777,true);
        }
        $config['upload_path'] = './upload_files/head/';
        $config['allowed_types'] = 'gif|jpg|png|jpeg';
        $config['encrypt_name'] = true;
        $config['max_size'] = '3200';
        $this->load->library('upload', $config);

        if ( ! $this->upload->do_upload('userfile')){
            echo  1;
        }else{
            $pic_arr = $this->upload->data();
            echo $pic_arr['file_name'];
        }
    }

    //暂时只设计给后台 经纪人事件新增选择事件时使用，所以在没有type_id的时候不返回信息
    public function get_eventByType4agent(){
        if($type_id = $this->input->post('type_id'))
            $region = $this->c4m_model->get_eventByType4agent($type_id);
        else
            $region = array();
        echo json_encode($region);
        exit();
    }

    //暂时只设计给后台 企业事件新增选择事件时使用，所以在没有type_id的时候不返回信息
    public function get_eventByType4company(){
        if($type_id = $this->input->post('type_id'))
            $region = $this->c4m_model->get_eventByType4company($type_id);
        else
            $region = array();
        echo json_encode($region);
        exit();
    }

    //判断企业是否存在本年度年审通过的记录
    public function check_company_ns_pass(){
        if(!$company_id = $this->input->post('company_id')){
            echo 1;
            exit();
        }
        $res = $this->c4m_model->check_company_ns_pass($company_id);
        if($res){
            echo 1;
        }else{
            echo -1;
        }
        exit();
    }

    public function update_company_num(){
        die();

    }

}
