<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Command_sys extends MY_Controller {

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
        $this->load->model('manager_model');
        $this->load->model('common4manager_model', 'c4m_model');
        $this->load->model('command_model');
        if(!is_cli())
        	die('服务暂停...');
    }

    public function index(){
    	die('服务暂停...');
    }

    //处理经纪人每日事务
    public function handle_sys(){
    	$this->command_model->handle_agent_event();
    	$this->command_model->handle_agent_grade();
    	$this->command_model->handle_company_ns();
    }

	//php index.php Command_sys update_agent_job_num
	public function update_agent_job_num(){
		$this->c4m_model->update_agent_job_num();
	}
}
