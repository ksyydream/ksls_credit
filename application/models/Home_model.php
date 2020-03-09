<?php
/**
 * Created by PhpStorm.
 * User: yangyang
 * Date: 16/6/3
 * Time: 下午3:22
 */
class Home_model extends MY_Model
{
    public function __construct()
    {
        parent::__construct();
    }

    public function __destruct()
    {
        parent::__destruct();
    }

    public function get_company_flag($company_id){
        return $this->db->select('flag,company_name')->where('id', $company_id)->from('company_pending')->get()->row_array();
    }

    public function get_agent_flag($agent_id){
        return $this->db->select('flag,name')->where('id', $agent_id)->from('agent')->get()->row_array();
    }

    public function company_list($page = 1) {
        $data['limit'] = $this->home_limit;//每页显示多少调数据
        $data['c_k'] = $this->input->get('c_k')?trim($this->input->get('c_k')):null;
        $this->db->select('count(1) num');
        $this->db->from('company_pending a');
        $this->db->join('company_grade b','a.grade_no = b.grade_no','left');
        if ($data['c_k']) {
            $this->db->group_start();
            $this->db->like('a.company_name', $data['c_k']);
            $this->db->or_like('a.record_num', $data['c_k']);
            $this->db->or_like('a.business_no', $data['c_k']);
            $this->db->group_end();
        }
        $this->db->where('a.flag', 2);
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;

        //list
        $this->db->select('a.company_name,a.id,a.business_no,a.record_num,a.legal_name,b.grade_name b_grade_name_');
        $this->db->from("company_pending a");
        $this->db->join('company_grade b','a.grade_no = b.grade_no','left');
        if($data['c_k']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['c_k']);
            $this->db->or_like('a.record_num', $data['c_k']);
            $this->db->or_like('a.business_no', $data['c_k']);
            $this->db->group_end();
        }
        $this->db->where('a.flag', 2);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('a.grade_no', 'desc');
        $this->db->order_by('a.id', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function get_company_detail($id){
        $this->db->select('a.*,b.grade_name b_grade_name_')->from('company_pending a');
        $this->db->join('company_grade b','a.grade_no = b.grade_no','left');
        $this->db->where('a.id', $id);
        $detail =  $this->db->get()->row_array();
        if (!$detail) {
            return array();
        }
        $this->db->select()->from('company_pending_img');
        $this->db->where('company_id', $id);
        $detail['img'] = $this->db->get()->result_array();
        $this->db->select()->from('agent');
        $this->db->where('company_id', $id);
        $detail['agent'] = $this->db->get()->result_array();
        $this->db->select()->from('company_pending_icon');
        $this->db->where('company_id', $id);
        $detail['icon'] = $this->db->get()->result_array();
        $this->db->select()->from('company_ns_list');
        $this->db->where('company_id', $id);
        $detail['ns_list'] = $this->db->get()->result_array();
        return $detail;
    }

    public function show_agent($page = 1) {
        $data['limit'] = 6;//每页显示多少调数据
        $data['c_id'] = $this->input->post('c_id') ? trim($this->input->post('c_id')) : null;
        $this->db->select('count(1) num');
        $this->db->from('agent a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        if ($data['c_id']) {
            $this->db->where('a.company_id', $data['c_id']);
        }else{
            //如果没有企业ID，就故意不显示数据
            $this->db->where('a.id <', 0);
        }
        $this->db->where('a.flag', 2);
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //这里处理如何是在删除情况下 最后一页数据不现实的情况
        $page = get_right_page($page, $data['total_rows'], $data['limit']);
        //list
        $this->db->select('a.*,b.company_name');
       $this->db->from('agent a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        if ($data['c_id']) {
            $this->db->where('a.company_id', $data['c_id']);
        }else{
            $this->db->where('a.id <', 0);
        }
        $this->db->where('a.flag', 2);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('a.id', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //企业详情中的 企业事件列表
    public function show_company_record($page = 1){
        $data['limit'] = 6;//每页显示多少调数据
        $data['c_id'] = $this->input->post('c_id') ? trim($this->input->post('c_id')) : null;
        $data['year'] = $this->input->post('year') ? trim($this->input->post('year')) : null;
        $this->db->select('count(1) num');
        $this->db->from('event4company_record a');
		$this->db->join('company_pending b','a.company_id = b.id', 'left');
        if ($data['c_id']) {
            $this->db->where('a.company_id', $data['c_id']);
        }else{
            //如果没有企业ID，就故意不显示数据
            $this->db->where('a.record_id <', 0);
        }
        if($data['year'] && is_numeric($data['year'])){
            $this->db->where('a.event_date <=', $data['year'] . '-12-31');
            $this->db->where('a.event_date >=', $data['year'] . '-01-01');
        }
        $this->db->where('a.status', 1);
		$this->db->where('b.flag', 2);
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //这里处理如何是在删除情况下 最后一页数据不现实的情况
        $page = get_right_page($page, $data['total_rows'], $data['limit']);
        //list
        $this->db->select('a.*');
        $this->db->from('event4company_record a');
		$this->db->join('company_pending b','a.company_id = b.id', 'left');
        if ($data['c_id']) {
            $this->db->where('a.company_id', $data['c_id']);
        }else{
            //如果没有企业ID，就故意不显示数据
            $this->db->where('a.record_id <', 0);
        }
        if($data['year'] && is_numeric($data['year'])){
            $this->db->where('a.event_date <=', $data['year'] . '-12-31');
            $this->db->where('a.event_date >=', $data['year'] . '-01-01');
        }
        $this->db->where('a.status', 1);
		$this->db->where('b.flag', 2);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('a.event_date', 'desc');
        $this->db->order_by('a.record_id', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function agent_list($page = 1) {
        $data['limit'] = $this->home_limit;//每页显示多少调数据
        $data['a_k'] = $this->input->get('a_k')?trim($this->input->get('a_k')):null;
        $this->db->select('count(1) num');
        $this->db->from('agent a');
        $this->db->join('agent_grade b','a.grade_no = b.grade_no','left');
        $this->db->join('company_pending c','a.company_id = c.id and c.flag = 2','left');
        if ($data['a_k']) {
            $this->db->group_start();
            $this->db->like('a.name', $data['a_k']);
            $this->db->or_like('a.job_code', $data['a_k']);
            $this->db->group_end();
        }
        $this->db->where('a.flag', 2);
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;

        //list
        $this->db->select('a.*,c.company_name company_name_,b.grade_name b_grade_name_');
        $this->db->from('agent a');
        $this->db->join('agent_grade b','a.grade_no = b.grade_no','left');
        $this->db->join('company_pending c','a.company_id = c.id and c.flag = 2','left');
        if ($data['a_k']) {
            $this->db->group_start();
            $this->db->like('a.name', $data['a_k']);
            $this->db->or_like('a.job_code', $data['a_k']);
            $this->db->group_end();
        }
        $this->db->where('a.flag', 2);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('a.grade_no', 'desc');
        $this->db->order_by('a.id', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //给游客展示的经纪人信息
   public function get_agent_detail($id){
    $this->db->select('a.*, b.company_name');
    $this->db->from('agent a');
    $this->db->join('company_pending b', 'a.company_id = b.id and b.flag = 2', 'left');
    $this->db->where('a.id', $id);
    $data = $this->db->get()->row_array();
    return $data;
   }

    //通过职业证号获取经纪人信息
    public function mobile_get_agent_detail(){
        $job_code = trim($this->input->get('job_code'));
        if(!$job_code)
            return null;
        $this->db->select('a.*, b.company_name');
        $this->db->from('agent a');
        $this->db->join('company_pending b', 'a.company_id = b.id and b.flag = 2', 'left');
        $this->db->where('a.job_code', $job_code);
        $data = $this->db->get()->row_array();
        return $data;
    }

   //企业详情中的 企业事件列表
    public function show_agent_record($page = 1){
        $data['limit'] = 6;//每页显示多少调数据
        $data['a_id'] = $this->input->post('a_id') ? trim($this->input->post('a_id')) : null;
        $data['year'] = $this->input->post('year') ? trim($this->input->post('year')) : null;
        $this->db->select('count(1) num');
        $this->db->from('event4agent_record a');
        $this->db->join('agent b','a.agent_id = b.id', 'left');
        if ($data['a_id']) {
            $this->db->where('a.agent_id', $data['a_id']);
        }else{
            //如果没有企业ID，就故意不显示数据
            $this->db->where('a.record_id <', 0);
        }
        if($data['year'] && is_numeric($data['year'])){
            $this->db->where('a.event_date <=', $data['year'] . '-12-31');
            $this->db->where('a.event_date >=', $data['year'] . '-01-01');
        }
        $this->db->where('a.status', 1);
        $this->db->where('b.flag', 2);
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //这里处理如何是在删除情况下 最后一页数据不现实的情况
        $page = get_right_page($page, $data['total_rows'], $data['limit']);
        //list
        $this->db->select('a.*');
       $this->db->from('event4agent_record a');
        $this->db->join('agent b','a.agent_id = b.id', 'left');
        if ($data['a_id']) {
            $this->db->where('a.agent_id', $data['a_id']);
        }else{
            //如果没有企业ID，就故意不显示数据
            $this->db->where('a.record_id <', 0);
        }
        if($data['year'] && is_numeric($data['year'])){
            $this->db->where('a.event_date <=', $data['year'] . '-12-31');
            $this->db->where('a.event_date >=', $data['year'] . '-01-01');
        }
        $this->db->where('a.status', 1);
        $this->db->where('b.flag', 2);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('a.event_date', 'desc');
        $this->db->order_by('a.record_id', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

}