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
       $agent_cap_ = $this->session->userdata('agent_cap');
        $this->session->unset_userdata('agent_cap');
       
    if (strtolower($this->input->post('userconfirm')) != strtolower($agent_cap_)){
        return $this->fun_fail('验证码错误!');
    }
    if(!$job_code = trim($this->input->post('username'))){
        return $this->fun_fail('登录名不能为空!');
    }
    if(!$pwd = trim($this->input->post('userpwd'))){
        return $this->fun_fail('密码不能为空!');
    }
    //20200914 登录改为 身份证登录
    $this->db->select()->from('agent')->where(array(
            'card'=>$job_code,
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

    //给自己展示,因为存在 还没有完成一次年审的企业,前台不给与显示.但自己是需要看到的
    public function get_detail4self($id){
        $this->db->select('a.*, b.company_name');
        $this->db->from('agent a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
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

    public function save_apply($agent_id){
        //再检查一遍经纪人状态
        $agent_info_ = $this->db->select('a.*,b.company_name')->from('agent a')->join('company_pending b', 'a.company_id = b.id', 'left')->where('a.id', $agent_id)->get()->row_array();
        if(!$agent_info_)
            return $this->fun_fail('经纪人信息丢失！');
        if($agent_info_['flag'] != 2)
            return $this->fun_fail('经纪人账号无效！');
        if($agent_info_['grade_no'] == 1)
            return $this->fun_fail('经纪人失信,不可申请！');
        $check_apply_ = $this->db->select('id')->from('agent_apply')->where('agent_id', $agent_id)->where('status', 1)->get()->row_array();
        if($check_apply_)
            return $this->fun_fail('存在未处理的人事申请,不可再申请！');
        $new_company_id = $this->input->post('new_company');
        if(!$new_company_id)
            return $this->fun_fail('新公司选择异常！');
        if($agent_info_['company_id'] == $new_company_id)
            return $this->fun_fail('新公司与旧公司不可相同！');
        $new_company_info = array('id' => -1, 'company_name' => '');
        if($new_company_id != -1){
            $new_company_info = $this->db->select('company_name,flag,id')->from('company_pending')->where('id', $new_company_id)->get()->row_array();
            if(!$new_company_info)
                return $this->fun_fail('新公司不存在！');
            if($new_company_info['flag'] != 2)
                return $this->fun_fail('新公司不可选择！');
        }

        $data = array(
            'agent_id' => $agent_id,
            'old_company_id'=> $agent_info_['company_id'],
            'old_company_name' => $agent_info_['company_id'] != -1 ? $agent_info_['company_name'] : '--非执业--',
            'new_company_id' => $new_company_info['id'],
            'new_company_name' => $new_company_info['id'] != -1 ? $new_company_info['company_name'] : '--非执业--',
            'remark'=>$this->input->post('remark'),
            'cdate'=>date('Y-m-d H:i:s',time()),
            'status'=>1,
        );
        $this->db->insert('agent_apply',$data);
        $sys_config_ =  $this->readByID('sys_config', 'type_name', 'apply_msg');
        if($sys_config_){
            return $this->fun_success($sys_config_['remark']);
        }
        return $this->fun_success('申请成功!');
    }

    public function apply_list($page = 1, $agent_id) {
        $data['limit'] = $this->home_limit;//每页显示多少调数据

        $this->db->select('count(1) num');
        $this->db->from('agent_apply b');
        $this->db->join('agent a','a.id = b.agent_id','left');
        $this->db->where('a.flag', 2);
        $this->db->where('a.id', $agent_id);
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //这里处理如何是在删除情况下 最后一页数据不现实的情况
        $page = get_right_page($page, $data['total_rows'], $data['limit']);
        //list
        $this->db->select('b.*, c1.company_name c1_name_, c2.company_name c2_name_');
        $this->db->from('agent_apply b');
        $this->db->join('agent a','a.id = b.agent_id','left');
        $this->db->join('company_pending c1', 'b.old_company_id = c1.id', 'left');
        $this->db->join('company_pending c2', 'b.new_company_id = c2.id', 'left');
        $this->db->where('a.flag', 2);
        $this->db->where('a.id', $agent_id);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('b.id', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function get_apply_info($apply_id, $agent_id){
        $this->db->select('b.*, c1.company_name c1_name_, c2.company_name c2_name_');
        $this->db->from('agent_apply b');
        $this->db->join('agent a','a.id = b.agent_id','left');
        $this->db->join('company_pending c1', 'b.old_company_id = c1.id', 'left');
        $this->db->join('company_pending c2', 'b.new_company_id = c2.id', 'left');
        $this->db->where('a.flag', 2);
        $this->db->where('a.id', $agent_id);
        $this->db->where('b.id', $apply_id);
        return $this->db->get()->row_array();
    }

    public function save_pwd($agent_id){
        $password = $this->input->post('password');
        $new_password = $this->input->post('new_password');
        $new_password2 = $this->input->post('new_password2');
        if(!$password)
            return $this->fun_fail('请输入原密码');
        if(!$new_password)
            return $this->fun_fail('请输入新密码');
        if($new_password == $password)
            return $this->fun_fail('所设置的新密码不可与原密码一致');
        if($new_password != $new_password2)
            return $this->fun_fail('确认密码与新密码不一致');
        if (strlen($new_password) < 6) {
                return $this->fun_fail('密码长度不可小于6位!');
        }
        $check_pwd = $this->db->select('id')->from('agent')->where(array('id' => $agent_id, 'pwd' => sha1($password)))->get()->row_array();
        if(!$check_pwd)
            return $this->fun_fail('原密码不正确');
        $this->db->where(array('id' => $agent_id))->update('agent', array('pwd' => sha1($new_password)));
        return $this->fun_success('修改成功，请重新登录!');
    }
}