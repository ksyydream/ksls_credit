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
    $company_cap_ = $this->session->userdata('company_cap');
    $this->session->unset_userdata('company_cap');
    if (strtolower(trim($this->input->post('userconfirm'))) != strtolower($company_cap_))
            return $this->fun_fail('验证码错误!');
    if(!$business_no = trim($this->input->post('username'))){
        return $this->fun_fail('登录名不能为空!');
    }
    if(!$pwd = trim($this->input->post('userpwd'))){
        return $this->fun_fail('密码不能为空!');
    }

    $this->db->select()->from('company_pending')->where(array(
            'business_no'=>$business_no,
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

   //用于经纪人人事申请时选择的企业列表
   public function get_company4apply($flag){
        $this->db->select('company_name, id, business_no');
        $this->db->from('company_pending');
        if($flag && is_array($flag))
            $this->db->where_in('flag', $flag);
        $data = $this->db->get()->result_array();
	    return $data;
   }

   //企业前台删除经纪人
   public function company_cancel_agent($company_id){
        if(!$agent_id = $this->input->post('a_id'))
            return $this->fun_fail('操作异常');
        $agent_info_ = $this->db->select('id')->from('agent')->where('id', $agent_id)->where('company_id', $company_id)->get()->row_array();
        if (!$agent_info_) 
            return $this->fun_fail('经纪人已不在企业内！不可操作！');
        $this->db->trans_start();//--------开始事务
        $this->db->where('id', $agent_id)->where('company_id', $company_id)->update('agent', array('company_id' => -1, 'wq' => -1));
        $this->save_agent_track4common($agent_id, $company_id, -1, 4);  //加入经纪人轨迹
        $this->agent_apply_all_cancel($agent_id);                       //人事变动，作废经纪人人事申请
        $this->save_company_total_score($company_id);                   //重新计算企业分数和状态
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            return $this->fun_fail('解绑失败!');
        } else {
            return $this->fun_success('解绑成功!');
        }
    }

    public function save_pwd($company_id){
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
        $check_pwd = $this->db->select('id')->from('company_pending')->where(array('id' => $company_id, 'password' => sha1($password)))->get()->row_array();
        if(!$check_pwd)
            return $this->fun_fail('原密码不正确');
        $this->db->where(array('id' => $company_id))->update('company_pending', array('password' => sha1($new_password)));
        return $this->fun_success('修改成功，请重新登录!');
    }

    public function get_cert($ns_id, $company_id){
        $this->db->select('a.*')->from('company_ns_cert a');
        $this->db->join('company_ns_list b','a.ns_id = b.id','inner');
        $this->db->where(array('a.status' => 1, 'a.company_id' => $company_id, 'a.ns_id' => $ns_id, 'b.status' => 2));
        $data = $this->db->get()->row_array();
        return $data;
    }
}