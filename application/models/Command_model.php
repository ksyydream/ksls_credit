<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 16/6/3
 * Time: 下午3:22
 */
class Command_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    /**
     * 处理经纪人重置事件
     *
     * @return boolean
     */
    public function handle_agent_event(){
        //判断所有未失信的经纪人 是否存在超出重置期 但未重置的事件，如果存在就重置并退还分数
        $event_num = 0;
        $agent_cz_event_ = $this->config->item('agent_cz_event');
        $cz_event_time= date("Y-m-d H:i:s", strtotime("-{$agent_cz_event_} day"));  //事件重置时间
        $where_ = array(
            'a.grade_no <>' => 1,
            'b.is_cz' => -1,
            'b.status' => 1,
            'b.create_time <' => $cz_event_time
        );
        $this->db->trans_start();//--------开始事务
        $this->db->select('a.id,ifnull(sum(b.score),0) total_score_, group_concat(b.record_id) record_ids_, count(b.record_id) event_num_')->from('agent a');
        $this->db->join('event4agent_record b','a.id = b.agent_id', 'inner');
        $this->db->where($where_);
        $this->db->group_by('a.id');
        $record_list_ = $this->db->get()->result_array();
        if($record_list_){
            foreach ($record_list_ as $k_ => $v_) {
                $event_num += $v_['event_num_'];
                $agent_id = $v_['id'];
                $this->db->where(array('status' => 1, 'is_cz' => -1, 'create_time <' => $cz_event_time))
                ->where('agent_id', $agent_id)
                ->update('event4agent_record', array('is_cz' => 1, 'cz_type' => 1, 'cz_date' => date("Y-m-d H:i:s")));
                $this->db->where(array('id' => $agent_id));
                $this->db->set('score', 'score - ' . $v_['total_score_'], FALSE)->update('agent');
                $this->handle_agent_score($agent_id);
            }
        }
        
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            $insert_arr = array(
                    'type' => 2,
                    'add_time' => time(),
                    'err_msg' => "handle_agent_event运行异常"
                );
                $this->db->insert('log', $insert_arr);
            return false;
        } else {
            $this->db->insert('log_sys_auto', array('type' => 1, 'create_time' => date("Y-m-d H:i:s"), 'event_num' => $event_num));
            return true;
        }
    }

    /**
     * 处理经纪人重置禁业
     *
     * @return boolean
     */
    public function handle_agent_grade(){
        $agent_num = 0;
        //判断所有失信的经纪人 禁业开始时间 是否已经超过禁业时限，如果超过了，就把之前所有的事件均重置，经纪人恢复初始分数
        $agent_cz_grade_ = $this->config->item('agent_cz_grade');
     
        $cz_grade_time= date("Y-m-d H:i:s", strtotime("-{$agent_cz_grade_} day"));  //禁业重置时间

        $where_ = array(
            'a.grade_no' => 1,
            'a.forbid_time <' => $cz_grade_time
        );
        $this->db->trans_start();//--------开始事务
        $agent_list = $this->db->select('a.id')->from('agent a')->where($where_)->get()->result_array();
        foreach ($agent_list as $k_ => $v_) {
            $agent_num += 1;
            $agent_id = $v_['id'];
            $this->db->where('id', $agent_id)->update('agent', array('grade_no' => 2, 'score' => $this->config->item('agent_score')));
            $this->db->where(array('status' => 1, 'is_cz' => -1))
            ->where('agent_id', $agent_id)
            ->update('event4agent_record', array('is_cz' => 1, 'cz_type' => 2, 'cz_date' => date("Y-m-d H:i:s")));
        }
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            $insert_arr = array(
                    'type' => 2,
                    'add_time' => time(),
                    'err_msg' => "handle_agent_grade运行异常"
                );
                $this->db->insert('log', $insert_arr);
            return false;
        } else {
            $this->db->insert('log_sys_auto', array('type' => 2, 'create_time' => date("Y-m-d H:i:s"), 'agent_num' => $agent_num));
            return true;
        }
    }

     /**
     * 处理企业年审缺席
     *
     * @return boolean
     */
    public function handle_company_ns(){
        //先判断是否在年审窗口期内，如果在窗口期内，不做处理
        //如果不在窗口期内，找到最近一次过期的年审，判断是否有企业company_pass没有该年的年审申请，如果没有直接给与缺席
        $company_num = 0;
        $mdate = date('Y-m-d',time());
        $this->db->select();
        $this->db->from('term');
        $this->db->where(array('begin_date <=' => $mdate,'end_date >=' => $mdate));
        $res_check_ = $this->db->get()->row_array();
        if($res_check_){
            $this->db->insert('log_sys_auto', array('type' => 3, 'create_time' => date("Y-m-d H:i:s")));
            return true;
        }
        $qx_ = $this->db->select()->from('term')->where(array('end_date <=' => $mdate))->order_by('end_date','desc')->get()->row_array();
        if(!$qx_){
            $this->db->insert('log_sys_auto', array('type' => 3, 'create_time' => date("Y-m-d H:i:s")));
            return true;
        }

        $qx_annual_year_ = $qx_['annual_year'];
        $this->db->trans_start();//--------开始事务
        $company_list = $this->db->select('a.id')->from('company_pending a')->join('company_ns_list b',"a.id = b.company_id and b.annual_year ='{$qx_annual_year_}'",'left')->where_in('a.flag', array(1,2))->where('b.id is null')->get()->result_array();
        if($company_list){
            foreach ($company_list as $k_ => $v_) {
                $company_id = $v_['id'];
                $check_pass_ = $this->db->from('company_pass')->where(array('company_id' => $company_id, 'annual_date' => $qx_annual_year_))->get()->row_array();
                if($check_pass_){
                    continue;
                }
                //加入缺席年数记录
                $insert_annual_year_ = array(
                    'annual_year' => $qx_annual_year_,
                    'company_id'  => $company_id,
                    'status'      => -1,
                    'create_date' => date('Y-m-d H:i:s',time()),
                    'create_user' => -1
                );
                $grade_no_ = $this->db->select()->from('company_grade')->where('grade_no', 2)->order_by('grade_no','desc')->get()->row_array();
                if($grade_no_){
                    $insert_annual_year_['grade_no'] = $grade_no_['grade_no'];
                    $insert_annual_year_['grade_name'] = $grade_no_['grade_name'];
                }else{
                    $insert_annual_year_['grade_no'] = 2;
                    $insert_annual_year_['grade_name'] = '失信警告';
                }
                $this->db->insert('company_ns_list', $insert_annual_year_);
                //重置分数
                $this->db->where('id', $company_id)->set('event_score', 0)->set('qx_num', 'qx_num + 1', false)->update('company_pending');
                $this->db->where(array('status' => 1, 'is_nscz' => -1, 'company_id' => $company_id))->update('event4company_record', array('is_nscz' => 1, 'annual_year' => $qx_annual_year_));
                $company_num += 1;
                //计算下分数，主要是为了 计算进去缺席分数和是否连续两年缺席加上异常标签
                $this->save_company_total_score($company_id);
            }
        }
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            $insert_arr = array(
                    'type' => 2,
                    'add_time' => time(),
                    'err_msg' => "handle_company_ns运行异常"
                );
                $this->db->insert('log', $insert_arr);
            return false;
        } else {
            $this->db->insert('log_sys_auto', array('type' => 3, 'create_time' => date("Y-m-d H:i:s"), 'company_num' => $company_num));
            return true;
        }
    }

}