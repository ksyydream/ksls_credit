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
        $this->db->where('id', $agent_id)->where('company_id', $company_id)->update('agent', array('company_id' => -1, 'wq' => 1));
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
        $this->db->select('a.*,c.record_num')->from('company_ns_cert a');
        $this->db->join('company_ns_list b','a.ns_id = b.id','inner');
        $this->db->join('company_pending c','a.company_id = c.id','left');
        $this->db->where(array('a.status' => 1, 'a.company_id' => $company_id, 'a.ns_id' => $ns_id, 'b.status' => 2));
        $data = $this->db->get()->row_array();
        return $data;
    }

    public function add_agent4companyOnlyEmployess($company_id){
        $agent_id = $this->input->post('agent_id');
        if(!$agent_id)
            return $this->fun_fail('信息异常!');
        //检查从业人员是否可以加入
        $this->db->select('a.*, b.company_name, c.grade_name');
        $this->db->from('agent a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
        $this->db->join('agent_grade c', 'a.grade_no = c.grade_no', 'left');
        $this->db->where('a.id', $agent_id);
        $this->db->where('a.flag', 2);
        $agent_info_ =  $this->db->get()->row_array();
        if(!$agent_info_)
            return $this->fun_fail('经纪人信息异常');
        if($agent_info_['work_type'] != 2)
            return $this->fun_fail('不是从业经纪人,不可添加');
        if($agent_info_['grade_no'] == 1)
            return $this->fun_fail('信息异常,不可添加');
        if($agent_info_['company_id'] != -1)
            return $this->fun_fail('经纪人已就业,不可添加');
        $update_rows_ = $this->db->where(array('id' => $agent_id, 'flag' => 2, 'company_id' => -1))->update('agent',array('company_id' => $company_id, 'wq' => 1, 'last_work_time' => time()));
        //人员加入企业后需要做两个操作
        //1.更新企业信用分数
        $this->save_company_total_score($company_id);
        //2.添加人员轨迹
        if($update_rows_){
            $this->save_agent_track4common($agent_id, -1, $company_id, 10);
        }
        return $this->fun_success('添加成功');
    }

    public function employees_apply_list($page = 1, $company_id) {
        $data['limit'] = 8;//每页显示多少调数据

        $this->db->select('count(1) num');
        $this->db->from('employees a');
        $this->db->join('company_pending c','a.company_id = c.id','left');

        $this->db->where('c.id', $company_id);
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;

        //list
        $this->db->select('a.*');
        $this->db->from('employees a');
        $this->db->join('company_pending c','a.company_id = c.id','left');
        $this->db->where('c.id', $company_id);
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('a.id', 'desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function employees_apply_save($company_id){
        $employees_data_ = array(
            'name' => trim($this->input->post('name')),
            'phone' => trim($this->input->post('phone')),
            'card' => strtoupper(trim($this->input->post('card'))),
            'company_id' => $company_id,
            'cdate'=>date('Y-m-d H:i:s',time()),
            'flag' => 1
        );
        if($employees_data_['name'] == "" || $employees_data_['name'] == "" || $employees_data_['name'] == ""){
            return $this->fun_fail("缺少基础信息!");
        }
        //检查身份证是否已经是经纪人
        $check_agent_ = $this->db->select()->from('agent')->where('card', $employees_data_['card'])->get()->row_array();
        if($check_agent_){
            return $this->fun_fail("所申请的身份证号已是经纪人!");
        }

        //检查一下是否有重复提交 身份证申请
        $check_employees_ = $this->db->select()->from('employees')->where(array(
            'card' => $employees_data_['card'],
            'flag' => 1,
            'company_id' => $company_id
        ))->get()->row_array();
        if($check_employees_){
            return $this->fun_fail("此身份证号人员申请重复提交!");
        }

        //检查照片数量
        $pic_short1 = $this->input->post('pic_short1');
        $pic_short3 = $this->input->post('pic_short3');
        if(!$pic_short1 || !is_array($pic_short1)){
            return $this->fun_fail("身份证照片必须上传!");
        }
        if(!$pic_short3 || !is_array($pic_short3)){
            return $this->fun_fail("个人证件照片必须上传!");
        }
        if(count($pic_short3) != 1){
            return $this->fun_fail("个人证件照片只需上传一张!");
        }
        $this->db->insert('employees', $employees_data_);
        $id = $this->db->insert_id();

        $this->db->delete('employees_code_img', array('employees_id' => $id));

        if($pic_short1){
            foreach($pic_short1 as $idx => $pic) {
                $code_pic = array(
                    'employees_id' => $id,
                    'img' => $pic,
                    'm_img' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
                );
                $this->db->insert('employees_code_img', $code_pic);
            }
        }

        $this->db->delete('employees_person_img', array('employees_id' => $id));

        if($pic_short3){
            foreach($pic_short3 as $idx => $pic) {
                $job_pic = array(
                    'employees_id' => $id,
                    'img' => $pic,
                    'm_img' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
                );
                $this->db->insert('employees_person_img', $job_pic);
            }
        }
        return $this->fun_success('申请成功!');
    }

    public function employees_apply_cancel($company_id){
        if(!$employees_id = $this->input->post('employees_id'))
            return $this->fun_fail('操作异常');
        $data = $this->db->select()->from('employees')->where('id', $employees_id)->where('company_id', $company_id)->get()->row_array();
        if(!$data){
            return $this->fun_fail('申请不存在');
        }
        if($data['flag'] != 1)
            return $this->fun_fail('申请已被操作,不可作废');
        $this->db->where(array('id' => $employees_id, 'company_id' => $company_id, 'flag' => 1))->update('employees', array('flag' => -2, 'audit_time' => date('Y-m-d H:i:s',time())));
        return $this->fun_success('操作成功!');
    }

    public function employees_apply_detail($id, $company_id){
        $data = $this->db->select()->from('employees')->where('id', $id)->where('company_id', $company_id)->get()->row_array();
        if(!$data){
            return $data;
        }
        $data['code_img_list'] = $this->db->select()->from('employees_code_img')->where('employees_id', $id)->get()->result_array();
        $data['person_img_list'] = $this->db->select()->from('employees_person_img')->where('employees_id', $id)->get()->result_array();
        return $data;
    }
}