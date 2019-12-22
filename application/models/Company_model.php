<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 16/6/3
 * Time: 下午3:22
 */
class Company_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

   public function login(){
    if (strtolower($this->input->post('userconfirm')) != strtolower($this->session->flashdata('agent_cap')))
            return $this->fun_fail('验证码错误!');
    if(!$job_code = trim($this->input->post('username'))){
        return $this->fun_fail('登录名不能为空!');
    }
    if(!$pwd = trim($this->input->post('userpwd'))){
        return $this->fun_fail('密码不能为空!');
    }

    $this->db->select()->from('company_pending')->where(array(
            'business_no'=>$job_code,
            'password'=>sha1($pwd)
    ));
    $res = $this->db->get()->row_array();
    if(!$res)
       return $this->fun_fail('账号或密码错误!');
    if($res['flag'] !=2)
       return $this->fun_fail('账号状态异常！');
    $data['company_info'] = $res;
    $this->session->unset_userdata('agent_id');
    $this->session->unset_userdata('agent_info');
    $this->session->set_userdata($data);
    $this->session->set_userdata(array('company_id'=>$res['id']));
    return $this->fun_success('登录成功!');
   }

   public function get_company4apply($flag){
        $this->db->select('company_name, id, business_no');
        $this->db->from('company_pending');
        if($flag && is_array($flag))
            $this->db->where_in('flag', $flag);
        $data = $this->db->get()->result_array();
	    return $data;
   }
}