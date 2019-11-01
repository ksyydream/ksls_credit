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

}
