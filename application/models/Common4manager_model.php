<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 16/6/3
 * Time: 下午3:22
 */
class Common4manager_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

   //获取所有 经纪人事件一级分类
    public function get_event4agent_type_all($status = null, $type = null){
        $this->db->select();
        $this->db->from('event4agent_type');
        if($status)
            $this->db->where('status', $status);
        if($type)
            $this->db->where('type', $type);
        $res =  $this->db->get()->result_array();
        return $res;
    }

    public function get_agent_all($flag = null){
        $this->db->select();
        $this->db->from('agent');
        if ($flag) {
            $this->db->where('flag', $flag);
        }
        $res =  $this->db->get()->result_array();
        return $res;
    }

    public function get_eventByType4agent($type_id = null){
        $this->db->select();
        $this->db->from('event4agent_detail');
        $this->db->where('status', 1);
        if($type_id)
            $this->db->where('type_id', $type_id);
        $res =  $this->db->get()->result_array();
        return $res;
    }

    //获取所有 经纪人事件一级分类
    public function get_event4company_type_all($status = null, $type = null){
        $this->db->select();
        $this->db->from('event4company_type');
        if($status)
            $this->db->where('status', $status);
        if($type)
            $this->db->where('type', $type);
        $res =  $this->db->get()->result_array();
        return $res;
    }

    public function get_eventByType4company($type_id = null){
        $this->db->select();
        $this->db->from('event4company_detail');
        $this->db->where('status', 1);
        if($type_id)
            $this->db->where('type_id', $type_id);
        $res =  $this->db->get()->result_array();
        return $res;
    }

    public function get_agent4company(){
        if(!$job_code = trim($this->input->post('keyword')))
            return array();
        $this->db->select('a.*, b.company_name');
        $this->db->from('agent a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
        $this->db->where('a.job_code', $job_code);
        //这里暂时不排除其他无效状态的人员
        //DBY重要
        return $this->db->get()->row_array();
    }

    public function get_niu_pics($f_name, $time){
        $res = $this->db->select()->from('upload_img')->where(array('folder' => $f_name, 'flag_time' => $time))->get()->result_array();
        return $res;
    }

}