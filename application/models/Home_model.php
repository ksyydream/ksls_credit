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

}