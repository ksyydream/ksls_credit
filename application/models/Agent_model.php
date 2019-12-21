<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 16/6/3
 * Time: 下午3:22
 */
class Agent_model extends MY_Model
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

    $this->db->select()->from('agent')->where(array(
            'job_code'=>$job_code,
            'pwd'=>sha1($pwd)
    ));
    $res = $this->db->get()->row_array();
    if(!$res)
       return $this->fun_fail('账号或密码错误!');
    if($res['flag'] !=2)
       return $this->fun_fail('账号状态异常！');
    $data['agent_info'] = $res;
    $this->session->unset_userdata('company_id');
    $this->session->unset_userdata('company_info');
    $this->session->set_userdata($data);
    $this->session->set_userdata(array('agent_id'=>$res['id']));
    return $this->fun_success('登录成功!');
   }

   public function get_detail($id){
    $this->db->select('a.*, b.company_name');
    $this->db->from('agent a');
	$this->db->join('company_pending b', 'a.company_id = b.id and b.flag = 2', 'left');
	$this->db->where('a.id', $id);
	$data = $this->db->get()->row_array();
	return $data;
   }

   public function save_info($agent_id){
	$phone = trim($this->input->post('phone'));
	if(!check_mobile($phone))
		return $this->fun_fail('手机号码格式不正确！');
	$this->db->where('id', $agent_id)->set('phone', $phone)->update('agent');
	return $this->fun_success('保存成功!');
   }
}