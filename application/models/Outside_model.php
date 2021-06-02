<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 16/6/3
 * Time: 下午3:22
 */
class Outside_model extends MY_Model
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
        $data = array(
            'appId' => trim($this->input->post('appId')),
            'appKey' => trim($this->input->post('appKey')),
        );
        if(!$data['appId']){
            return $this->fun_fail('appId不能为空!');
        }
        if(!$data['appKey']){
            return $this->fun_fail('appKey不能为空!');
        }

        $row = $this->db->select()->from('api_token')->where(array(
            'appId' => $data['appId'],
            'appKey' => $data['appKey']
        ))->get()->row_array();
        if ($row) {
            if($row['status'] != 1)
                return $this->fun_fail('appId账号禁用');
            return $this->fun_success('操作成功',$row);
        } else {
            return $this->fun_fail('appId或appKey错误');
        }
    }

    public function update_api_tt($api_id,$token = ''){
        $update_data = array('last_time' => time());
        if($token){
            $update_data['token'] = $token;
        }
        $this->db->where('id', $api_id)->update('api_token', $update_data);
    }

    public function check_token($token, $api_id = 0){
        $api_info_ = $this->db->select()->from('api_token')->where(array('token' => $token))->get()->row_array();
        if(!$api_info_){
            return $this->fun_fail('token失效!');
        }
        if(time() - $api_info_['last_time'] > 60 * 60 * 2){
            return $this->fun_fail('token过期!');
        }
        if($api_id != $api_info_['id']){
            return $this->fun_fail('账号异常!!');
        }
        if($api_info_['status'] != 1){
            return $this->fun_fail('账号异常!');
        }

        return $this->fun_success('登录成功',$api_info_);
    }

    public function get_c(){
        $business_no = trim($this->input->post('c_no'));
        if(!$business_no)
            return $this->fun_fail("缺少必要参数");
        $this->db->select('a.id,a.company_name,a.business_path,a.record_num,b.grade_name b_grade_name_')->from('company_pending a');
        $this->db->join('company_grade b','a.grade_no = b.grade_no','left');
        $this->db->where('a.business_no', $business_no);
        $this->db->where('a.flag', 2);
        $detail =  $this->db->get()->row_array();
        if(!$detail)
            return $this->fun_fail("未找到信息");
        //获取有效期
        $company_ns_cert = $this->db->select('max(end_date) end_date')->from('company_ns_cert')->where(array('company_id' => $detail['id'], 'status' => 1))->get()->row_array();
        $detail['end_date'] = $company_ns_cert ? $company_ns_cert['end_date'] : null;
        unset($detail['id']);
        return $this->fun_success("操作成功", $detail);
    }

    public function get_a(){
        $card = trim($this->input->post('a_no'));
        if(!$card)
            return $this->fun_fail("缺少必要参数");
        $this->db->select('a.name,a.job_num,a.job_code,a.work_type, b.company_name');
        $this->db->from('agent a');
        $this->db->join('company_pending b', 'a.company_id = b.id and b.flag = 2', 'left');
        $this->db->where('a.card', $card);
        $this->db->where('a.flag', 2);
        $data = $this->db->get()->row_array();
        if(!$data)
            return $this->fun_fail("未找到信息");
        return $this->fun_success("操作成功", $data);
    }


}