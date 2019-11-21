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

    //检查公司名称是否存在
    public function check_company_name($company_name,$without_id=null){
        $this->db->select()->from('company_pending a')
            ->join('company_pass b','a.id = b.company_id','left')
            ->group_start()
            ->where('a.company_name',trim($company_name))
            ->or_where('b.company_name',trim($company_name))->group_end();
        $this->db->where_in('a.status',array(1,2,3,4));
        if($without_id)
            $this->db->where('a.id <>',$without_id);
        $data_pending = $this->db->get()->row_array();
        //die($this->db->last_query());
        if($data_pending){
           return $this->fun_fail('公司名已被占用!');
        }else{
            return $this->fun_success('可以使用!');
        }
    }

    //检查备案号是否存在
    public function check_record_num($record_num,$without_id=null){
        $this->db->select()->from('company_pending')->where('record_num',trim($record_num));
        $this->db->where_in('status',array(1,2,3,4));
        if($without_id)
            $this->db->where('id <>',$without_id);
        $data_pending = $this->db->get()->row_array();
        if($data_pending){
             return $this->fun_fail('备案号存在!');
        }else{
            return $this->fun_success('可以使用!');
        }
    }

    //检查持证经纪人
    //暂时未使用，需要考虑是否直接判断company_id来回去经纪人是否就职
    public function check_agent($job_code,$without_id=null){
        $this->db->select('b.*')->from('company_pending a');
        $this->db->join('agent b','a.id = b.company_id','left');
        $this->db->where_in('a.status',array(1,2,3));
        $this->db->where('b.job_code',$job_code);
        $this->db->where('b.flag',2);
        if($without_id)
            $this->db->where('company_id <>',$without_id);
        $res = $this->db->get()->row_array();
        if($res){
            return -1;
        }else{
            return 1;
        }
    }

    public function check_code4get($job_code, $company_id = -1){
        if(!$job_code)
             return $this->fun_fail('执业证号异常!');
        $this->db->select('*')->from('agent');
        $this->db->where('job_code',$job_code);
        $this->db->where('flag',2);
        $agent_detail =  $this->db->get()->row_array();
        if(!$agent_detail)
            return $this->fun_fail('经纪人状态异常!');
        if($agent_detail['company_id']==-1 || $agent_detail['company_id'] == $company_id){
            return $this->fun_success('可以使用!', $agent_detail);
        }else{
            return $this->fun_fail('经纪人不可操作!');
        }
    }

}