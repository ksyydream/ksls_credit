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

    //获取经纪人下拉选项
    //1 经纪人事件新增时使用
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

    //获取企业下拉选项
    //1 企业事件新增时使用
    public function get_company_all($flag = null){
        $this->db->select();
        $this->db->from('company_pending');
        if ($flag) {
            $this->db->where('flag', $flag);
        }
        $res =  $this->db->get()->result_array();
        return $res;
    }

    //企业添加经纪人使用
    public function get_agent4company(){
        if(!$job_code = trim($this->input->post('keyword')))
            return array();
        $this->db->select('a.*, b.company_name');
        $this->db->from('agent a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
        $this->db->where('a.job_code', $job_code);
        $this->db->where('a.flag', 2);
        return $this->db->get()->row_array();
    }

    public function get_niu_pics($f_name, $time){
        $res = $this->db->select()->from('upload_img')->where(array('folder' => $f_name, 'flag_time' => $time))->get()->result_array();
        return $res;
    }

    public function get_company_sys_icon(){
        $this->db->select('*, -1 is_check_',false);
        $this->db->from('sys_score_icon');
        $this->db->where('status', 1);
        $this->db->order_by('icon_class', 'asc');
        $this->db->order_by('icon_no', 'asc');
        return $this->db->get()->result_array();

    }

    /** check fun */

    //检查公司名称是否存在
    public function check_company_name($company_name,$without_id=null){
        /**
         * 更改企业数据结构 修改判断依据
         */
        //$this->db->select()->from('company_pending a')
        //    ->join('company_pass b','a.id = b.company_id','left')
        //    ->group_start()
        //    ->where('a.company_name',trim($company_name))
        //    ->or_where('b.company_name',trim($company_name))->group_end();
        //$this->db->where_in('a.status',array(1,2,3,4));
        //if($without_id)
        //    $this->db->where('a.id <>',$without_id);
        //$data_pending = $this->db->get()->row_array();

        //die($this->db->last_query());
        $this->db->select()->from('company_pending a');
        $this->db->where_in('a.flag',array(1,2));
        $this->db->where('a.company_name',trim($company_name));
        if($without_id)
            $this->db->where('a.id <>',$without_id);
        $data_pending = $this->db->get()->row_array();
        if($data_pending){
           return $this->fun_fail('公司名已被占用!');
        }else{
            return $this->fun_success('可以使用!');
        }
    }

    //检查是否在年审窗口期
    public function check_is_ns_time(){
        $mdate = date('Y-m-d',time());
        $this->db->select();
        $this->db->from('term');
        $this->db->where(array('begin_date <=' => $mdate,'end_date >=' => $mdate));
        $res_check_ = $this->db->get()->row_array();
        if($res_check_){
            return $this->fun_success('在年审中!', $res_check_);
        }else{
            return $this->fun_fail('不在年审中!');
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
        $this->db->where('grade_no >', 1);
        $agent_detail =  $this->db->get()->row_array();
        if(!$agent_detail)
            return $this->fun_fail('经纪人状态异常!');
        if($agent_detail['company_id']==-1 || $agent_detail['company_id'] == $company_id){
            return $this->fun_success('可以使用!', $agent_detail);
        }else{
            return $this->fun_fail('经纪人不可操作!');
        }
    }

    //检查重复和不规范的经纪人，用于企业信息保存和提交
    public function check_repeat_agent($company_id = null, $code_ = array()){
        if ($code_ && is_array($code_)) {
            foreach($code_ as $idx => $card_) {
            $check_card = $this->check_code4get(trim($card_), $company_id);
            if($check_card['status'] != 1){
                 return $this->fun_fail($check_card['msg']);
            }else{
                foreach($code_ as $idx2 => $card_2) {
                    //$card_2 = trim($card_2);
                    if($idx != $idx2 && trim($card_) == trim($card_2)) {
                        return $this->fun_fail('存在重复录入执业经纪人!');
                    }
                }
            }
            }
        }
        return $this->fun_success('可以使用!');
    }

    //检查审核状态是否按照规则执行
    public function check_status_change4company($old_status, $new_status){
        if (!in_array($new_status, array(1, 2, 3, -1)))
            return $this->fun_fail('审核状态不规范!');
        switch ($old_status) {
                 case 1:
                     if ($new_status == 3)
                        return $this->fun_fail('企业审核状态为待初审，不可直接终审成功!');
                     break;
                case 2:
                     if ($new_status == 1)
                        return $this->fun_fail('企业审核状态为待终审，不可直接退回初审!');
                     break;
                case 3:
                     return $this->fun_fail('企业审核状态为终审成功，不可审核!');
                     break;
                case -1:
                     return $this->fun_fail('企业审核状态为审核失败，不可审核!');
                     break;
        }
        return $this->fun_success('可以使用!');
    }

    //检查企业与经纪人等级修改与保存时的合法性
    public function check_grade_lawful($table, $data, $grade_id = null){
        $grade_name = $data['grade_name'];
        $min_score = $data['min_score'];
        $grade_no = $data['grade_no'];
        //第一步 先检查是否有相同名称的等级
        $this->db->select('id')->from($table);
        $this->db->where('grade_name', $grade_name);
        if($grade_id)
            $this->db->where('id <>', $grade_id);
        $res = $this->db->get()->row_array();
        if($res)
            return $this->fun_fail('存在相同等级名称');
        //第二步 先检查是否有相同分数的等级
        $this->db->select('id')->from($table);
        $this->db->where('min_score', $min_score);
        if($grade_id)
            $this->db->where('id <>', $grade_id);
        $res = $this->db->get()->row_array();
        if($res)
            return $this->fun_fail('存在相同分数线');
        //第三步 先检查是否有相同级别的等级
        $this->db->select('id')->from($table);
        $this->db->where('grade_no', $grade_no);
        if($grade_id)
            $this->db->where('id <>', $grade_id);
        $res = $this->db->get()->row_array();
        if($res)
            return $this->fun_fail('存在相同级别');
        //第四步 检查等级与分数是否存在问题
        $res = $this->db->select('id')->from($table)->where('grade_no <', $grade_no)->where('min_score >', $min_score)->get()->row_array();
        if($res)
            return $this->fun_fail('级别与分数设置不规范');
        $res = $this->db->select('id')->from($table)->where('grade_no >', $grade_no)->where('min_score <', $min_score)->get()->row_array();
        if($res)
            return $this->fun_fail('级别与分数设置不规范');
        return $this->fun_success('可以使用!');;
    }

    //经纪人就业轨迹
    public function show_agent_track($page){
        $id = $this->input->post('agent_id') ? $this->input->post('agent_id') : -1;

        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword') ? trim($this->input->get('keyword')):null;

        $this->db->select('count(1) num')->from('agent_track a');
        $this->db->join('company_pass b','a.to_company_id = b.company_id','left');
        $this->db->join('company_pass c','a.from_company_id = c.company_id','left');
        $this->db->join('company_pending d','a.to_company_id = d.id','left');
        $this->db->join('company_pending e','a.from_company_id = e.id','left');
        $this->db->where('a.agent_id',$id);
        if($data['keyword']){
            $this->db->like('a.to_company_name', $data['keyword']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;
        $this->db->select('a.*,b.company_name to_name,c.company_name from_name,
		d.status to_company_status,d.flag to_company_flag,e.status from_company_status,e.flag from_company_flag')->from('agent_track a');
        $this->db->join('company_pass b','a.to_company_id = b.company_id','left');
        $this->db->join('company_pass c','a.from_company_id = c.company_id','left');
        $this->db->join('company_pending d','a.to_company_id = d.id','left');
        $this->db->join('company_pending e','a.from_company_id = e.id','left');
        $this->db->where('a.agent_id',$id);
        if($data['keyword']){
            $this->db->like('a.to_company_name', $data['keyword']);
        }
        $this->db->order_by('a.create_date','desc');
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $data['res_list'] = $this->db->get()->result_array();
        //die(var_dump($this->db->last_query()));
        return $data;
    }

}