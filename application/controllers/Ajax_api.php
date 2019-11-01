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

}
