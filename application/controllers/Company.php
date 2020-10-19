<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Company extends Home_Controller {

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
		if(!$this->company_id){
			home_err_return();
        }
       
    }

    public function company_cancel_agent(){
    	$res = $this->company_model->company_cancel_agent($this->company_id);
    	$this->ajaxReturn($res);
    }

	public function change_pwd(){
		$this->display('homepage/company/change_pwd.html');
	}

	public function save_pwd(){
		$res = $this->company_model->save_pwd($this->company_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/home/logout'));
        }else{
            $this->show_message($res['msg']);
        }
	}

	public function get_cert($ns_id){
		$data = $this->company_model->get_cert($ns_id, $this->company_id);
		if(!$data)
			redirect('/home/index');
		$this->assign('data', $data);
		$this->display('homepage/company/dianzizhengshu.html');
	}

	public function add_agent4companyOnlyEmployess(){
		$res = $this->company_model->add_agent4companyOnlyEmployess($this->company_id);
		$this->ajaxReturn($res);
	}

	public function employees_apply_list($page = 1){
		$data = $this->company_model->employees_apply_list($page, $this->company_id);
		$base_url = "/company/employees_apply_list/";
		$pager = $this->pagination->getPageLink4home($base_url, $data['total_rows'], $data['limit']);
		$this->assign('pager', $pager);
		$this->assign('page', $page);
		$this->assign('data', $data);
		$this->display('homepage/company/employees_apply_list.html');
	}

	public function employees_apply_add(){
		$this->assign('time', date('ymdHis').$this->company_id);
		$this->display('homepage/company/employees_apply_add.html');
	}

	public function employees_apply_save(){
		$res = $this->company_model->employees_apply_save($this->company_id);
		$this->ajaxReturn($res);
	}

	//这里 虽然manager_login存在图片操作,但还是重复写一遍
	public function save_pics($f_name, $time){
		if(!$f_name || strlen($f_name) < 5)
			exit(-1);
		if(strpos($f_name,'.') !== false)
			exit(-1);
		if(strpos($f_name,'php') !== false || strpos($f_name,'js') !== false)
			exit(-1);
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
			$this->load->model('common4manager_model', 'c4m_model');
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
}
