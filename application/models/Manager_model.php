<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager_model extends MY_Model
{

    /**
     * 管理员操作Model
     * @version 1.0
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-29
     * @Copyright (C) 2017, Tianhuan Co., Ltd.
     */

    public function __construct() {
        parent::__construct();
    }

    public function check_login() {
        if (strtolower($this->input->post('verify')) != strtolower($this->session->flashdata('cap')))
            return -1;
        $data = array(
            'user' => trim($this->input->post('user')),
            'password' => password(trim($this->input->post('password'))),
        );
        $row = $this->db->select()->from('admin')->where($data)->get()->row_array();
        if ($row) {
            $data['admin_info'] = $row;
            $this->session->set_userdata($data);
            return 1;
        } else {
            return -2;
        }
    }

    /**
     * 获取用户所能显示的菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-30
     */
    public function get_menu4admin($admin_id = 0) {
        $admin_info = $this->db->select()->from('auth_group g')
            ->join('auth_group_access a', 'g.id=a.group_id', 'left')
            ->where('a.admin_id', $admin_id)->get()->row_array();
        if (!$admin_info) {
            return array();
        }
        $menu_access_arr = explode(",", $admin_info['rules']);
        $this->db->select('id,title,pid,name,icon');
        $this->db->from('auth_rule');
        $this->db->where('islink', 1);
        $this->db->where('status', 1);
        if ($admin_info['group_id'] != 1) {
            $this->db->where_in('id', $menu_access_arr);
        }
        $menu = $this->db->order_by('o asc')->get()->result_array();
        return $menu;
    }

    public function get_action_menu($controller = null, $action = null) {
        $action_new = str_replace('_edit', '_list', $action);
        $action_new = str_replace('_add', '_list', $action_new);
        $action_new = str_replace('_view', '_list', $action_new);
        $action_new = str_replace('_audit', '_list', $action_new);
        $action_new = str_replace('_temp', '_list', $action_new);
        $this->db->select('s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle');
        $this->db->from('auth_rule s');
        $this->db->join('auth_rule p', 'p.id = s.pid', 'left');
        $this->db->where('s.name', $controller . '/' . $action_new);
        $row = $this->db->get()->row_array();
        if (!$row) {
            $this->db->select('s.id,s.title,s.name,s.tips,s.pid,p.pid as ppid,p.title as ptitle');
            $this->db->from('auth_rule s');
            $this->db->join('auth_rule p', 'p.id = s.pid', 'left');
            $this->db->where('s.name', $controller . '/' . $action);
            $row = $this->db->get()->row_array();
        }
        return $row;
    }

    public function get_admin($admin_id) {
        $admin_info = $this->db->select('a.*,b.group_id,c.title')->from('admin a')
            ->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left')
            ->join('auth_group c', 'c.id = b.group_id', 'left')
            ->where('a.admin_id', $admin_id)->get()->row_array();
        return $admin_info;
    }

    /**
     *********************************************************************************************
     * 以下代码为系统设置模块
     *********************************************************************************************
     */

    /**
     * 查找所有可添加的菜单
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_menu_all() {
        $this->db->select('id,title,pid,name,icon,islink,o');
        $this->db->from('auth_rule');
        $this->db->where('status', 1);
        $menu = $this->db->order_by('o asc')->get()->result_array();
        return $menu;
    }

    /**
     * 获取后台菜单详情
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_info($id) {
        $menu_info = $this->db->select()->from('auth_rule')->where('id', $id)->get()->row_array();
        return $menu_info;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_save() {
        $data = array(
            'pid' => trim($this->input->post('pid')) ? trim($this->input->post('pid')) : 0,
            'title' => trim($this->input->post('title')) ? trim($this->input->post('title')) : null,
            'name' => trim($this->input->post('name')) ? trim($this->input->post('name')) : '',
            'icon' => trim($this->input->post('icon')) ? trim($this->input->post('icon')) : '',
            'islink' => trim($this->input->post('islink')) ? trim($this->input->post('islink')) : 0,
            'o' => trim($this->input->post('o')) ? trim($this->input->post('o')) : 0,
            'tips' => trim($this->input->post('tips')) ? trim($this->input->post('tips')) : '',
            'cdate' => date('Y-m-d H:i:s', time()),
            'mdate' => date('Y-m-d H:i:s', time())
        );
        if (!$data['title'])
            return -2;//信息不全
        if ($id = $this->input->post('id')) {
            unset($data['cdate']);
            $this->db->where('id', $id)->update('auth_rule', $data);
        } else {
            $this->db->insert('auth_rule', $data);
        }
        return 1;
    }

    /**
     * 删除管理员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_del($id) {
        if (!$id)
            return $this->fun_fail('删除失败');
        $rs = $this->db->where('id', $id)->delete('auth_rule');
        if ($rs)
            return $this->fun_success('删除成功');
        return $this->fun_fail('删除失败');
    }

    /**
     * 编辑 人事申请提交提示
     * @author yangyang
     * @date 2018-04-01
     */
    public function config_detail($type_name){
        return $this->readByID('sys_config', 'type_name', $type_name);
    }

    //保存 人事申请提醒
    public function config_save($type_name){
        $remark = $this->input->post('remark');
        if(!$remark)
            $this->fun_fail('请输入内容');
        if(!$type_name)
            $this->fun_fail('请求异常');
        $this->db->where('type_name', $type_name)->delete('sys_config');
        $this->db->insert('sys_config', array('type_name' => $type_name, 'cdate' => date('Y-m-d H:i:s', time()), 'remark' => $remark));
        return $this->fun_success('操作成功');
    }

    /**
     *********************************************************************************************
     * 以下代码为个人中心模块
     *********************************************************************************************
     */

    /**
     * 管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */

    public function admin_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $data['keyword'] = trim($this->input->get('keyword')) ? trim($this->input->get('keyword')) : null;
        $data['field'] = trim($this->input->get('field')) ? trim($this->input->get('field')) : 1;// 1是用户名,2是电话,3是QQ,4是邮箱
        $data['order'] = trim($this->input->get('order')) ? trim($this->input->get('order')) : 1;// 1是desc,2是asc
        $this->db->select('count(1) num');
        $this->db->from('admin a');
        $this->db->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left');
        $this->db->join('auth_group c', 'c.id = b.group_id', 'left');
        if ($data['keyword']) {
            switch ($data['field']) {
                case '1':
                    $this->db->like('a.user', $data['keyword']);
                    break;
                case '2':
                    $this->db->like('a.phone', $data['keyword']);
                    break;
                case '3':
                    $this->db->like('a.qq', $data['keyword']);
                    break;
                case '4':
                    $this->db->like('a.email', $data['keyword']);
                    break;
                default:
                    $this->db->like('a.user', $data['keyword']);
                    break;
            }
        }
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;
        //list
        $this->db->select('a.*,b.group_id,c.title');
        $this->db->from('admin a');
        $this->db->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left');
        $this->db->join('auth_group c', 'c.id = b.group_id', 'left');
        if ($data['keyword']) {
            switch ($data['field']) {
                case '1':
                    $this->db->like('a.user', $data['keyword']);
                    break;
                case '2':
                    $this->db->like('a.phone', $data['keyword']);
                    break;
                case '3':
                    $this->db->like('a.qq', $data['keyword']);
                    break;
                case '4':
                    $this->db->like('a.email', $data['keyword']);
                    break;
                default:
                    $this->db->like('a.user', $data['keyword']);
                    break;
            }
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        if ($data['order'] == 1) {
            $this->db->order_by('a.t', 'desc');
        } else {
            $this->db->order_by('a.t', 'asc');
        }
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 查找所有可添加的用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_group_all() {
        $this->db->select('id,title');
        $this->db->from('auth_group');
        $this->db->where('status', 1);
        $menu = $this->db->order_by('id asc')->get()->result_array();
        return $menu;
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_save() {
        $data = array(
            'user' => trim($this->input->post('user')) ? trim($this->input->post('user')) : null,
            'sex' => $this->input->post('sex') ? $this->input->post('sex') : 0,
            'head' => $this->input->post('head') ? $this->input->post('head') : null,
            'phone' => trim($this->input->post('phone')) ? trim($this->input->post('phone')) : null,
            'qq' => trim($this->input->post('qq')) ? trim($this->input->post('qq')) : null,
            'email' => trim($this->input->post('email')) ? trim($this->input->post('email')) : null,
            'birthday' => trim($this->input->post('birthday')) ? trim($this->input->post('birthday')) : null,
            't' => time()
        );
        if (!$data['user'] || !$data['head'] || !$data['phone'] || !$data['qq'] || !$data['email'] || !$data['birthday'])
            return $this->fun_fail('信息不全!');
        if (!file_exists(dirname(SELF) . '/upload_files/head/' . $data['head'])) {
            return $this->fun_fail('信息不全,头像异常!');
        }
        if (!$group_id = $this->input->post('group_id')) {
            return $this->fun_fail('需要选择用户组!');
        }
        if (trim($this->input->post('password'))) {
            if (strlen(trim($this->input->post('password'))) < 6) {
                return $this->fun_fail('密码长度不可小于6位!');
            }
            if (is_numeric(trim($this->input->post('password')))) {
                return $this->fun_fail('密码不可是纯数字!');
            }
            $data['password'] = password(trim($this->input->post('password')));
        }
        if ($admin_id = $this->input->post('admin_id')) {
            unset($data['t']);
            $check_ = $this->db->select()->from('admin')
                ->where('user', $data['user'])
                ->where('admin_id <>', $admin_id)
                ->get()->row_array();
            if ($check_) {
                return $this->fun_fail('新建或修改的用户名已存在!');
            }
            $this->db->where('admin_id', $admin_id)->update('admin', $data);
        } else {
            if (!trim($this->input->post('password'))) {
                return $this->fun_fail('新建用户需要设置密码!');
            }
            $check_ = $this->db->select()->from('admin')->where('user', $data['user'])->get()->row_array();
            if ($check_) {
                return $this->fun_fail('新建或修改的用户名已存在!');
            }
            $this->db->insert('admin', $data);
            $admin_id = $this->db->insert_id();
        }
        $this->db->where('admin_id', $admin_id)->delete('auth_group_access');
        $this->db->insert('auth_group_access', array('admin_id' => $admin_id, 'group_id' => $group_id));

        $town_ids = $this->input->post('town_ids');
        $this->db->where('admin_id', $admin_id)->delete('admin_town');
        if ($town_ids) {
            if (is_array($town_ids)) {
                foreach ($town_ids as $item) {
                    $this->db->insert('admin_town', array('admin_id' => $admin_id, 't_id' => $item));
                }
            }
        }

        return $this->fun_success('保存成功');
    }

    /**
     * 删除管理员
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function admin_del($id) {
        if (!$id)
            return $this->fun_fail('删除失败');
        $admin_info = $this->get_admin($id);
        if (!$admin_info)
            return $this->fun_fail('删除失败');
        if ($admin_info['group_id'] == 1)
            return $this->fun_fail('该管理员为超级管理员权限不可直接删除');
        $rs = $this->db->where('admin_id', $id)->delete('admin');
        if ($rs)
            return $this->fun_success('删除成功');
        return $this->fun_fail('删除失败');
    }

    /**
     * 获取用户组信息
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function get_group_detail($id = 0) {
        $group_detail = $this->db->select()->from('auth_group')->where('id', $id)->get()->row_array();
        if (!$group_detail) {
            return -1;
        }
        $group_detail['rules'] = explode(',', $group_detail['rules']);
        return $group_detail;
    }

    /**
     * 保存用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_save() {
        $data = array(
            'title' => trim($this->input->post('title')) ? trim($this->input->post('title')) : null,
            'status' => $this->input->post('status') ? $this->input->post('status') : -1,
        );
        if ($data['title'] == "") {
            return -1;
        }
        $rules = $this->input->post('rules') ? $this->input->post('rules') : 0;
        if (is_array($rules)) {
            foreach ($rules as $k => $v) {
                $rules[$k] = intval($v);
            }
            $rules = implode(',', $rules);
        }
        $data['rules'] = $rules;
        if ($group_id = $this->input->post('id')) {
            $this->db->where('id', $group_id)->update('auth_group', $data);
        } else {
            $this->db->insert('auth_group', $data);
        }
        return 1;
    }

    /**
     * 用户组列表
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_list($page = 1) {
        $data['limit'] = $this->limit;//每页显示多少调数据
        $this->db->select('count(1) num');
        $this->db->from('auth_group a');
        $rs_total = $this->db->get()->row();
        //总记录数
        $total_rows = $rs_total->num;
        $data['total_rows'] = $total_rows;

        //list
        $this->db->select('a.*');
        $this->db->from("auth_group a");
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('id', 'asc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 删除用户组
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_del($id) {
        if (!$id)
            return $this->fun_fail('删除失败');
        if ($id == 1)
            return $this->fun_fail('超级管理员不可删除');
        $rs = $this->db->where('id', $id)->delete('auth_group');
        if ($rs)
            return $this->fun_success('删除成功');
        return $this->fun_fail('删除失败');
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function personal_save($admin_id) {
        $data = array(
            'user' => trim($this->input->post('user')) ? trim($this->input->post('user')) : null,
            'sex' => $this->input->post('sex') ? $this->input->post('sex') : 0,
            'head' => $this->input->post('head') ? $this->input->post('head') : null,
            'phone' => trim($this->input->post('phone')) ? trim($this->input->post('phone')) : null,
            'qq' => trim($this->input->post('qq')) ? trim($this->input->post('qq')) : null,
            'email' => trim($this->input->post('email')) ? trim($this->input->post('email')) : null,
            'birthday' => trim($this->input->post('birthday')) ? trim($this->input->post('birthday')) : null,
        );
        if (!$data['user'] || !$data['head'] || !$data['phone'] || !$data['qq'] || !$data['email'] || !$data['birthday'])
            return $this->fun_fail('信息不全!');
        if (!file_exists(dirname(SELF) . '/upload_files/head/' . $data['head'])) {
            return $this->fun_fail('信息不全!');
        }
        if (trim($this->input->post('password'))) {
            if (strlen(trim($this->input->post('password'))) < 6) {
                return $this->fun_fail('密码长度不可小于6位!');
            }
            if (is_numeric(trim($this->input->post('password')))) {
                return $this->fun_fail('密码不可是纯数字!');
            }
            $data['password'] = password(trim($this->input->post('password')));
        }
        $this->db->where('admin_id', $admin_id)->update('admin', $data);
        return $this->fun_success('保存成功!');
    }

    /**
     *********************************************************************************************
     * 以下代码为经纪人管理
     *********************************************************************************************
     */

    public function agent_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['flag'] = $this->input->get('flag')?trim($this->input->get('flag')):null;
        $data['work_status_'] = $this->input->get('work_status_')?trim($this->input->get('work_status_')):null;
        $data['work_type'] = $this->input->get('work_type')?trim($this->input->get('work_type')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('agent a');
        //$this->db->join('company_pending b','a.company_id = b.id','left');
        //$this->db->join('company_pass c','b.id = c.company_id','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.job_code', $data['keyword']);
            $this->db->or_like('a.job_num', $data['keyword']);
            $this->db->or_like('a.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['flag']){
            $this->db->where('a.flag', $data['flag']);
        }
        if($data['work_type']){
            $this->db->where('a.work_type', $data['work_type']);
        }
        if($data['work_status_']){
            if($data['work_status_'] == -1)
                $this->db->where('a.company_id', -1);
            if($data['work_status_'] == 1)
                $this->db->where('a.company_id >', -1);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.company_name')->from('agent a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        //$this->db->join('company_pass c','b.id = c.company_id','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.job_code', $data['keyword']);
            $this->db->or_like('a.job_num', $data['keyword']);
            $this->db->or_like('a.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['flag']){
            $this->db->where('a.flag', $data['flag']);
        }
        if($data['work_type']){
            $this->db->where('a.work_type', $data['work_type']);
        }
        if($data['work_status_']){
            if($data['work_status_'] == -1)
                $this->db->where('a.company_id', -1);
            if($data['work_status_'] == 1)
                $this->db->where('a.company_id >', -1);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function agent_edit($id){
        $this->db->select('a.*, b.company_name, c.grade_name')->from('agent a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        $this->db->join('agent_grade c','a.grade_no = c.grade_no','left');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        if(!$detail)
            return $detail;
        $detail['code_img_list'] = $this->db->select()->from('agent_code_img')->where('agent_id', $id)->get()->result_array();
        $detail['job_img_list'] = $this->db->select()->from('agent_job_img')->where('agent_id', $id)->get()->result_array();
        $detail['person_img_list'] = $this->db->select()->from('agent_person_img')->where('agent_id', $id)->get()->result_array();
        return $detail;
    }

    public function agent_save(){
        $data = array(
            'name'=>trim($this->input->post('name')),
            'phone'=>trim($this->input->post('phone')) ? trim($this->input->post('phone')) : "",
            'job_code'=>trim($this->input->post('job_code')),
            'old_job_code'=>trim($this->input->post('old_job_code')),
            'flag' => $this->input->post('flag'),
            'work_type' => $this->input->post('work_type'),
            'card'=>trim($this->input->post('card')) ? trim($this->input->post('card')) : "",
            'pwd'=>sha1("666666"),
            'cdate' => date('Y-m-d H:i:s', time()),
        );
        $id = $this->input->post('id');
        if(!$data['name'] || !$data['flag'] || !$data['card'] || !$data['work_type']){
            return $this->fun_fail('缺少必要信息!');
        }

        if($data['work_type'] != 1){
            //如果不是执业经纪人 就去掉执业证号传入
            $data['job_code'] = '';
        }else{
            if(!$data['job_code']){
                return $this->fun_fail('缺少必要信息!');
            }
        }

        if($id){
            $chenk_job = $this->db->select()->from('agent')->where('job_code', $data['job_code'])->where('id <>', $id)->get()->row_array();
            $chenk_card = $this->db->select()->from('agent')->where('card', $data['card'])->where('id <>', $id)->get()->row_array();
            //只有当是 执业经纪人时才做执业证号唯一判断
            if($chenk_job && $data['work_type'] == 1)
                return $this->fun_fail('此职业证号已存在!');
            if($chenk_card)
                return $this->fun_fail('此身份证号已存在!');
            unset($data['pwd']);
            unset($data['cdate']);
            //这里还需要判断 如果是离昆或者无效时 需要解绑公司,解绑公司后可能会让公司状态变更 得分产生变化
            $this->db->where('id', $id)->update('agent', $data);
        }else{
            //新增前加入验证是否是从业黑名单
            $this->load->model('common4manager_model', 'c4m_model_temp');
            $check_is_blace_ = $this->c4m_model_temp->check_is_black4agent($data['card']);
            if($check_is_blace_)
                return $this->fun_fail('此身份证号在从业黑名单中,不可新增!');
            $chenk_job = $this->db->select()->from('agent')->where('job_code', $data['job_code'])->get()->row_array();
            $chenk_card = $this->db->select()->from('agent')->where('card', $data['card'])->get()->row_array();
            //只有当是 执业经纪人时才做执业证号唯一判断
            if($chenk_job && $data['work_type'] == 1)
                return $this->fun_fail('此职业证号已存在!');
            if($chenk_card)
                return $this->fun_fail('此身份证号已存在!');
            //增加经纪人初始信用分
            $data['score'] = $this->config->item('agent_score');
            $data['job_num'] = $this->get_job_num();
            $this->db->insert('agent', $data);
            $id = $this->db->insert_id();
        }
        //保存 身份证照片和执业证照片

        $this->db->delete('agent_code_img', array('agent_id' => $id));
        $pic_short_code = $this->input->post('pic_short1');
        if($pic_short_code){
            foreach($pic_short_code as $idx => $pic) {
                $code_pic = array(
                    'agent_id' => $id,
                    'img' => $pic,
                    'm_img' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
                );
                $this->db->insert('agent_code_img', $code_pic);
            }
        }


        //$this->db->delete('agent_job_img', array('agent_id' => $id));
        //$pic_short_job = $this->input->post('pic_short2');
        //if($pic_short_job){
        //    foreach($pic_short_job as $idx => $pic) {
        //        $job_pic = array(
        //            'agent_id' => $id,
        //            'img' => $pic,
        //            'm_img' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
        //        );
        //        $this->db->insert('agent_job_img', $job_pic);
        //    }
        //}

        $this->db->delete('agent_person_img', array('agent_id' => $id));
        $pic_short_job = $this->input->post('pic_short3');
        if($pic_short_job){
            foreach($pic_short_job as $idx => $pic) {
                $job_pic = array(
                    'agent_id' => $id,
                    'img' => $pic,
                    'm_img' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
                );
                $this->db->insert('agent_person_img', $job_pic);
            }
        }

        $this->handle_agent_flag($id);
        return $this->fun_success('保存成功!');
    }

     //重置经纪人密码
    public function refresh_agent_password(){
        $agent_id = $this->input->post('id');
        $job_num = $this->input->post('job_num');
        if(!$agent_id || !$job_num)
            return $this->fun_fail('信息缺失!');
        $this->db->where(array('id' => $agent_id, 'job_num' => $job_num))->update('agent', array('pwd' => sha1('666666')));
        return $this->fun_success('重置成功!');
    }

    /**
     * 执业经纪人人事申请列表
     * @author yangyang
     * @date 2019-12-20
     */
    public function agent_apply_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['agent_job_code'] = $this->input->get('agent_job_code')?trim($this->input->get('agent_job_code')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('agent_apply a');
        $this->db->join('agent b', 'a.agent_id = b.id', 'inner');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('b.name', $data['keyword']);
            $this->db->or_like('b.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        if($data['agent_job_code']){
            $this->db->where('b.job_code', $data['agent_job_code']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.name agent_name_, b.job_code agent_job_code_, c1.company_name c1_name_, c2.company_name c2_name_')->from('agent_apply a');
        $this->db->join('agent b', 'a.agent_id = b.id', 'inner');
        $this->db->join('company_pending c1', 'c1.id = a.old_company_id', 'left');
        $this->db->join('company_pending c2', 'c2.id = a.new_company_id', 'left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('b.name', $data['keyword']);
            $this->db->or_like('b.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        if($data['agent_job_code']){
            $this->db->where('b.job_code', $data['agent_job_code']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.cdate','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function agent_apply_view($id){
        $this->db->select('a.*, b.name agent_name_, b.job_code agent_job_code_, c1.company_name c1_name_, c2.company_name c2_name_')->from('agent_apply a');
        $this->db->join('agent b', 'a.agent_id = b.id', 'inner');
        $this->db->join('company_pending c1', 'c1.id = a.old_company_id', 'left');
        $this->db->join('company_pending c2', 'c2.id = a.new_company_id', 'left');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        return $detail;
    }

    public function agent_apply_handle($status){
        if(!$id = $this->input->post('id'))
            return $this->fun_fail('信息缺失!');
        $apply_info = $this->db->select()->from('agent_apply')->where('id', $id)->get()->row_array();
        if(!$apply_info)
            return $this->fun_fail('申请不存在!');
        if ($apply_info['status'] != 1) 
            return $this->fun_fail('申请状态变更，不可操作!');
        if(!in_array($status, array(-1,2)))
            return $this->fun_fail('操作异常!');
        switch ($status) {
            case '2':
                $agent_info_ = $this->db->select('a.*,b.company_name')->from('agent a')->join('company_pending b', 'a.company_id = b.id', 'left')->where('a.id', $apply_info['agent_id'])->get()->row_array();
                if(!$agent_info_ || $agent_info_['flag'] != 2 || $agent_info_['grade_no'] == 1)
                    return $this->fun_fail('经纪人异常，不可操作!');
                if($agent_info_['company_id'] != $apply_info['old_company_id'])
                    return $this->fun_fail('经纪人存在人事变动，不可操作!');
                if($apply_info['new_company_id'] != -1){
                    $new_company_info_ = $this->readByID('company_pending', 'id', $apply_info['new_company_id']);
                    if(!$new_company_info_ || $new_company_info_['flag'] != 2)
                        return $this->fun_fail('新企业状态异常，不可操作!');
                }else{
                    $new_company_info_ = array('id' => -1, 'company_name' => null);
                }
                $this->db->where('id', $apply_info['agent_id'])->update('agent', array('company_id' => $apply_info['new_company_id'], 'wq' => 1,'last_work_time' => time()));
                $this->db->where('id', $id)->update('agent_apply', array(
                    'status' => 2,
                    'sdate' => date('Y-m-d H:i:s', time())
                ));
                $this->save_company_total_score($apply_info['old_company_id']);
                $this->save_company_total_score($apply_info['new_company_id']);
                //增加轨迹
                $data_insert = array(
                    'to_company_id'         =>      $new_company_info_['id'],
                    'to_company_name'       =>      $new_company_info_['company_name'],
                    'from_company_id'       =>      $agent_info_['company_id'],
                    'from_company_name'     =>      $agent_info_['company_name'],
                    'agent_id'              =>      $agent_info_['id'],
                    'create_date'           =>      date('Y-m-d H:i:s',time()),
                    'status'                =>      5
                );
                $this->db->insert('agent_track',$data_insert);
                $this->agent_apply_all_cancel($apply_info['agent_id']);
                break;
            case '-1':
                if(!$err_remark = $this->input->post('err_remark'))
                    return $this->fun_fail('请填写作废备注!');
                $this->db->where('id', $id)->update('agent_apply', array(
                    'status' => -1,
                    'err_remark' => $err_remark,
                    'sdate' => date('Y-m-d H:i:s', time())
                ));
                break;
            default:
                return $this->fun_fail('操作异常!');
                break;
        }

        return $this->fun_success('操作成功!');
    }


    public function employees_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['flag'] = $this->input->get('flag')?trim($this->input->get('flag')):null;
        $data['town_id'] = $this->input->get('town_id')?trim($this->input->get('town_id')):null;  //保留单个区镇 虽然实际是不使用
        $data['town_ids'] = $this->input->get('town_ids');
        //当区镇什么也没有选时就自动取默认
        if(!$data['town_ids']){
            $admin_info = $this->session->userdata('admin_info');
            $data['town_ids'] = $this->get_admin_t_list($admin_info['admin_id']);
        }
        $data['town_ids'] = $data['town_ids'] ? $data['town_ids'] : array('');

        //获取总记录数
        $this->db->select('count(1) num')->from('employees a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        $this->db->join('town t', 'b.town_id = t.id', 'left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['flag']){
            $this->db->where('a.flag', $data['flag']);
        }
        if(is_array($data['town_ids']))
            $this->db->where_in('b.town_id', $data['town_ids']);
        $this->db->where('a.flag <>', -2);
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*,b.company_name,t.s_name')->from('employees a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        $this->db->join('town t', 'b.town_id = t.id', 'left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['flag']){
            $this->db->where('a.flag', $data['flag']);
        }
        if(is_array($data['town_ids']))
            $this->db->where_in('b.town_id', $data['town_ids']);
        $this->db->where('a.flag <>', -2);
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function employees_audit($id){
        $this->db->select('a.*, b.company_name, t.name t_name')->from('employees a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        $this->db->join('town t','b.town_id = t.id','left');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        if(!$detail)
            return $detail;
        $detail['code_img_list'] = $this->db->select()->from('employees_code_img')->where('employees_id', $id)->get()->result_array();
        $detail['person_img_list'] = $this->db->select()->from('employees_person_img')->where('employees_id', $id)->get()->result_array();
        return $detail;
    }

    public function employees_apply_handle($admin_id, $flag){
        $employees_id = $this->input->post('employees_id');
        if(!$employees_id)
            return $this->fun_fail('信息丢失!');
        $employees_info_ = $this->db->select('*')->from('employees')->where('id',$employees_id)->get()->row_array();
        if(!$employees_info_)
            return $this->fun_fail('信息异常!');
        if($employees_info_['flag'] != 1)
            return $this->fun_fail('信息已被处理,不可重复操作!');
        $audit_remark_ = trim($this->input->post('audit_remark'));
        $company_info_ = $this->db->select("*")->from("company_pending")->where('id', $employees_info_['company_id'])->get()->row_array();
        if(!$company_info_)
            return $this->fun_fail('企业信息丢失!');
        $check_town_ = $this->check_admin_townByTown_id($admin_id, $company_info_['town_id']);
        if(!$check_town_)
            return $this->fun_fail('不可操作此区镇下企业!');
        switch($flag){
            case 2:
                //加入验证此人员是否是从业黑名单
                $this->load->model('common4manager_model', 'c4m_model_temp');
                $check_is_blace_ = $this->c4m_model_temp->check_is_black4agent($employees_info_['card']);
                if($check_is_blace_)
                    return $this->fun_fail('此身份证号在从业黑名单中,不可通过!');
                //1.需要先验证申请企业是否可用
                if($company_info_['flag'] == -1)
                    return $this->fun_fail('企业不可使用!');
                //2.验证人员是否可用加入,判断身份证号是否存在
                if(!$employees_info_['card'] || !trim($employees_info_['card']))
                    return $this->fun_fail('申请信息不完整!');
                $check_agent_ = $this->db->select('*')->from('agent')->where('card', trim($employees_info_['card']))->get()->row_array();
                if($check_agent_)
                    return $this->fun_fail('已存在相同身份证号的人员!');

                //3.完成验证后开始生成人员信息,并生成轨迹 ,从新计算企业信用分数
                $this->db->trans_start();//--------开始事务
                $agent_data_ = array(
                    'name'          =>      trim($employees_info_['name']),
                    'phone'         =>      trim($employees_info_['phone']) ? trim($employees_info_['phone']) : "",
                    'job_code'      =>      '',
                    'old_job_code'  =>      '',
                    'flag'          =>      2,
                    'work_type'     =>      2,
                    'card'          =>      trim($employees_info_['card']),
                    'pwd'           =>      sha1("666666"),
                    'cdate'         =>      date('Y-m-d H:i:s', time()),
                    'company_id'    =>      $employees_info_['company_id'],
                    'last_work_time'=>      time(),

                );
                $agent_data_['score'] = $this->config->item('agent_score');
                $agent_data_['job_num'] = $this->get_job_num();
                $this->db->insert('agent', $agent_data_);
                $agent_id = $this->db->insert_id();
                //回写agent_id
                $this->db->where(array('id' => $employees_id))->update('employees',array(
                    'flag' => 2,
                    'audit_remark' => $audit_remark_,
                    'audit_time' => date('Y-m-d H:i:s', time()),
                    'agent_id' => $agent_id
                ));

                $code_img_list_ = $this->db->select("{$agent_id} agent_id,img,m_img")->from('employees_code_img')->where('employees_id', $employees_id)->get()->result_array();
                if($code_img_list_)
                    $this->db->insert_batch('agent_code_img', $code_img_list_);
                $person_img_list_ = $this->db->select("{$agent_id} agent_id,img,m_img")->from('employees_person_img')->where('employees_id', $employees_id)->get()->result_array();
                if($person_img_list_)
                    $this->db->insert_batch('agent_person_img', $person_img_list_);

                //人员加入企业后需要做两个操作
                //1.更新企业信用分数
                $this->save_company_total_score($employees_info_['company_id']);
                //2.添加人员轨迹
                $this->save_agent_track4common($agent_id, -1, $employees_info_['company_id'], 9);

                $this->db->trans_complete();//------结束事务
                if ($this->db->trans_status() === FALSE) {
                    return $this->fun_fail('保存失败!');
                }
                break;
            case -1:
                if(!$audit_remark_)
                    return $this->fun_fail('拒绝时,必须填写审核备注!');
                $this->db->where(array('id' => $employees_id, 'flag' => 1))->update('employees',array('flag' => -1, 'audit_remark' => $audit_remark_, 'audit_time' => date('Y-m-d H:i:s', time()),));
                break;
            default:
                return $this->fun_fail('请求异常!');
        }
        return $this->fun_success('操作成功');

    }

    /**
     * 从业黑名单
     * @author yangyang
     * @date 2020-12-12
     */
    public function agent_blacklist_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('agent_blacklist a');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*')->from('agent_blacklist a');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.card', $data['keyword']);
            $this->db->group_end();
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function agent_blacklist_edit($id){
        $this->db->select('a.*')->from('agent_blacklist a');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        if(!$detail)
            return $detail;
        return $detail;
    }

    public function agent_blacklist_save($admin_id){
        $data = array(
            'name'=>trim($this->input->post('name')),
            'remark'=>$this->input->post('remark'),
            'status' => 1,
            'card'=>trim($this->input->post('card')) ? trim($this->input->post('card')) : "",
            'cdate' => date('Y-m-d H:i:s', time()),
            'c_uid' => $admin_id
        );
        //$id = $this->input->post('id');
        if(!$data['name'] || !$data['card']){
            return $this->fun_fail('缺少必要信息!');
        }
        //检查是否已经从业
        $chenk_card = $this->db->select()->from('agent')->where('card', $data['card'])->get()->row_array();
        if($chenk_card)
            return $this->fun_fail('此身份证号已从业!');
        $check_blacklist = $this->db->select()->from('agent_blacklist')->where(array('card'=>$data['card'], 'status' => 1))->get()->row_array();
        if($check_blacklist)
            return $this->fun_fail('此身份证号已经在黑名单中!');
        $this->db->insert('agent_blacklist', $data);
        //保存 身份证照片和执业证照片
        return $this->fun_success('操作成功!');
    }

    public function agent_blacklist_cancel($admin_id){
        $id = $this->input->post('black_id');
        $update_data_ = array(
            'mdate' => date('Y-m-d H:i:s', time()),
            'm_uid' => $admin_id,
            'status' => -1
        );
        $res = $this->db->where(array('id' => $id, 'status' => 1))->update('agent_blacklist', $update_data_);
        return $this->fun_success('操作成功!');
    }

    /**
     *********************************************************************************************
     * 经纪人事件
     *********************************************************************************************
     */

    /**
     * 经纪人事件一级列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function agent_grade_list($page = 1){
        $data['limit'] = $this->limit;

        //获取总记录数
        $this->db->select('count(1) num')->from('agent_grade a');

        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*')->from('agent_grade a');

        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.grade_no','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function agent_grade_edit($id){
        $detail =  $this->readByID('agent_grade', 'id', $id);
        return $detail;
    }

    public function agent_grade_save(){
        $table_ = 'agent_grade';
        $data =array(
            'grade_name' => trim($this->input->post('grade_name')),
            'min_score' => trim($this->input->post('min_score')) ? trim($this->input->post('min_score')) : 0,
            'grade_no' => trim($this->input->post('grade_no')),
        );
        if(!$data['grade_name']){
            return $this->fun_fail('请输入等级名称');
        }
        if(!isset($data['min_score'])){
            return $this->fun_fail('分数线设置异常');
        }
        if(!isset($data['grade_no'])){
            return $this->fun_fail('等级设置异常');
        }
        $this->load->model('common4manager_model', 'c4m_model');
        $grade_id = $this->input->post('grade_id');
        if($grade_id){
            $info_ = $this->readByID($table_, 'id', $grade_id);
            if(!$info_)
                return $this->fun_fail('等级不存在');
            if($info_['flag'] == -1){
                $data['min_score'] = $info_['min_score'];
            }else{
                if((int)$data['min_score'] < 0)
                    return $this->fun_fail('分数线设置异常');
            }
            if(in_array($info_['grade_no'],array(1,2)))
                $data['grade_no'] = $info_['grade_no'];
            $check_ = $this->c4m_model->check_grade_lawful($table_, $data, $grade_id);
            if($check_['status'] != 1)
                return $this->fun_fail($check_['msg']);
            $res2 = $this->db->where('id',$this->input->post('grade_id'))->update($table_,$data);
        }else{
            if((int)$data['min_score'] < 0)
                return $this->fun_fail('分数线设置异常');
            $check_ = $this->c4m_model->check_grade_lawful($table_, $data);
            if($check_['status'] != 1)
                return $this->fun_fail($check_['msg']);
            $res2 = $this->db->insert($table_,$data);
        }

        if($res2){
            return $this->fun_success('保存成功');
        }else{
            return $this->fun_fail('保存失败');
        }
    }

    public function agent_grade_delete($id){
        if(!$id)
            return $this->fun_fail('删除失败');
        $info_= $this->db->from('agent_grade')->where('id', $id)->get()->row_array();
        if(!$info_)
            return $this->fun_fail('分数线不存在');
        if($info_['flag'] == -1)
            return $this->fun_fail('失信分数线不可删除');
        if(in_array($info_['grade_no'], array(1,2)))
            return $this->fun_fail('特殊分数线不可删除');
        $res = $this->db->where('id', $id)->delete('agent_grade');
        if($res)
            return $this->fun_success('删除成功');
        return $this->fun_fail('删除失败');
    }

    /**
     * 经纪人事件一级列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4agent_type_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4agent_type a');
        if($data['keyword']){
            $this->db->like('a.event_type_name', $data['keyword']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*')->from('event4agent_type a');
        if($data['keyword']){
            $this->db->like('a.event_type_name', $data['keyword']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 经纪人事件一级保存页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4agent_type_save(){
        $data = array(
            'event_type_name'=>trim($this->input->post('event_type_name')),
            'type'=>trim($this->input->post('type')),
            'status' => trim($this->input->post('status')) ? trim($this->input->post('status')) : -1,
            'cdate' => date('Y-m-d H:i:s', time()),
        );
        $id = $this->input->post('id');
        if(!$data['event_type_name'] || !$data['type']){
            return $this->fun_fail('缺少必要信息!');
        }
        if($id){
            unset($data['cdate']);
            $this->db->where('id', $id)->update('event4agent_type', $data);
        }else{
            $this->db->insert('event4agent_type', $data);
        }
        return $this->fun_success('保存成功!');
    }

    public function event4agent_type_edit($id){
        $this->db->select('a.*')->from('event4agent_type a');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        return $detail;
    }

    /**
     * 经纪人事件二级列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function event4agent_detail_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['type_id'] = $this->input->get('type_id')?trim($this->input->get('type_id')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4agent_detail a');
        $this->db->join('event4agent_type b', 'a.type_id = b.id', 'left');
        if($data['keyword']){
            $this->db->like('a.event_name', $data['keyword']);
        }
        if($data['type_id']){
            $this->db->where('a.type_id', $data['type_id']);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.event_type_name, b.type b_type_')->from('event4agent_detail a');
        $this->db->join('event4agent_type b', 'a.type_id = b.id', 'left');
        if($data['keyword']){
            $this->db->like('a.event_name', $data['keyword']);
        }
        if($data['type_id']){
            $this->db->where('a.type_id', $data['type_id']);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 经纪人事件二级保存页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4agent_detail_save(){
        $data = array(
            'event_name'=> trim($this->input->post('event_name')),
            'type_id'=> trim($this->input->post('type_id')),
            'score' => trim($this->input->post('score')),
            'allow_times' => trim($this->input->post('allow_times')) ? trim($this->input->post('allow_times')) : 0,
            'status' => trim($this->input->post('status')) ? trim($this->input->post('status')) : -1,
            'cdate' => date('Y-m-d H:i:s', time()),
        );
        $id = $this->input->post('id');
        if(!$data['event_name'] || !$data['type_id'] || !$data['score']){
            return $this->fun_fail('缺少必要信息!');
        }
        if($data['allow_times'] < 0){
            return $this->fun_fail('缺少必要信息!');
        }
        if($id){
            unset($data['cdate']);
            $this->db->where('id', $id)->update('event4agent_detail', $data);
        }else{
            $this->db->insert('event4agent_detail', $data);
        }
        return $this->fun_success('保存成功!');
    }

    public function event4agent_detail_edit($id){
        $this->db->select('a.*')->from('event4agent_detail a');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        return $detail;
    }

    /**
     * 经纪人事件列表
     * @author yangyang
     * @date 2019-11-12
     */

    public function event4agent_record_list($page = 1, $type_type = null){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['agent_job_code'] = $this->input->get('agent_job_code')?trim($this->input->get('agent_job_code')):null;
        $data['agent_keyword'] = $this->input->get('agent_keyword')?trim($this->input->get('agent_keyword')):null;
        $data['event_keyword'] = $this->input->get('event_keyword')?trim($this->input->get('event_keyword')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4agent_record a');
        $this->db->join('agent b', 'a.agent_id = b.id', 'left');
        if ($data['agent_job_code']) {
            $this->db->where('b.job_code', $data['agent_job_code']);
        }
        if($data['agent_keyword']){
            $this->db->group_start();
            $this->db->like('b.name', $data['agent_keyword']);
            $this->db->or_like('b.job_code', $data['agent_keyword']);
            $this->db->or_like('b.card', $data['agent_keyword']);
            $this->db->group_end();
        }
        if($data['event_keyword']){
            $this->db->group_start();
            $this->db->like('a.event_name', $data['event_keyword']);
            $this->db->or_like('a.event_type_name', $data['event_keyword']);
            $this->db->group_end();
        }
        if($type_type){
            $this->db->where('a.event_type_type', $type_type);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.name agent_name_, b.job_code agent_job_code_')->from('event4agent_record a');
        $this->db->join('agent b', 'a.agent_id = b.id', 'left');
        if ($data['agent_job_code']) {
            $this->db->where('b.job_code', $data['agent_job_code']);
        }
        if($data['agent_keyword']){
            $this->db->group_start();
            $this->db->like('b.name', $data['agent_keyword']);
            $this->db->or_like('b.job_code', $data['agent_keyword']);
            $this->db->or_like('b.card', $data['agent_keyword']);
            $this->db->group_end();
        }
        if($data['event_keyword']){
            $this->db->group_start();
            $this->db->like('a.event_name', $data['event_keyword']);
            $this->db->or_like('a.event_type_name', $data['event_keyword']);
            $this->db->group_end();
        }
        if($type_type){
            $this->db->where('a.event_type_type', $type_type);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.record_id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //通过$event4agent_type判断是 良好信用操作 还是失信信用操作，1代表良好信用，-1代表失信信用
    public function event4agent_Record_save($admin_id, $event4agent_type_index){
        $event4agent_type = $this->config->item('event4agent_type');
        $data = array(
            'agent_id'=> trim($this->input->post('agent_id')),
            'event_type_id'=> trim($this->input->post('type_id')),
            'event_id' => trim($this->input->post('event_id')),
            'record_fact' => trim($this->input->post('record_fact')),
            'event_date' => trim($this->input->post('event_date')),
            'score' => trim($this->input->post('score')),
            'remark' => trim($this->input->post('remark')),
            'create_uid' => $admin_id,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['score'] || !$data['agent_id'] || !$data['event_type_id'] || !$data['event_id'] || !$data['record_fact'] || !$data['remark'] || !$data['event_date']){
            return $this->fun_fail('缺少必要信息!');
        }
        if($data['score'] < 0 || $data['score'] >= 100)
            return $this->fun_fail('分数设置异常!');
        $agent_info_ = $this->readByID('agent', 'id', $data['agent_id']);
        if(!$agent_info_)
            return $this->fun_fail('所选经纪人异常!');
        $event_info_ = $this->readByID('event4agent_detail', 'id', $data['event_id']);
        if(!$event_info_ || $event_info_['status'] != 1)
            return $this->fun_fail('所选事件状态异常!');
        if( $event_info_['type_id'] != $data['event_type_id'])
            return $this->fun_fail('所选事件类别与事件不符!');
        //检查事件是否存在 次数限制，并查看是否可以新建
        if ($event_info_['allow_times'] > 0) {
            $check_times_ = $this->db->select('count(1) num')->from('event4agent_record')->where(array('agent_id' => $data['agent_id'], 'event_id' => $data['event_id'], 'status' => 1, 'is_cz' => -1))->get()->row_array();
            if ($check_times_['num'] >= $event_info_['allow_times']) {
                return $this->fun_fail('事件已设置到次数上限!');
            }

        }

        $type_info_ = $this->readByID('event4agent_type', 'id', $data['event_type_id']);
        if(!$type_info_ || $type_info_['status'] != 1)
            return $this->fun_fail('所选事件类别状态异常!');
        if ($type_info_['type'] != $event4agent_type_index) {
           return $this->fun_fail("所选事件不属于 " . $event4agent_type[$event4agent_type_index] . " 事件!");
        }
        $data['event_name'] = $event_info_['event_name'];
        $data['event_type_name'] = $type_info_['event_type_name'];
        $data['score'] =  ($event4agent_type_index * $data['score']);
        $data['event_type_type'] = $type_info_['type'];
        $new_score_ = $agent_info_['score'] + $data['score'];
       

        $res = $this->db->insert('event4agent_record', $data);
        if ($res) {
            $this->db->where(array('id' => $data['agent_id'], 'score' => $agent_info_['score']));
            $res_agent_ = $this->db->set('score', 'score + ' . $data['score'], FALSE)->update('agent');
            $this->handle_agent_score($data['agent_id']);
            //DBY重要
            //这里需要加入 经纪人状态变更，企业分数更新和状态检查 可能还需要做相应的记录
            return $this->fun_success('保存成功!');
        }
        return $this->fun_fail('保存失败!');
    }

     public function event4agent_Record_update($admin_id){
        if(!$record_id = $this->input->post('record_id'))
            return $this->fun_fail('事件状态异常');
        $record_info_ = $this->readByID('event4agent_record', 'record_id', $record_id);
        if(!$record_info_ || $record_info_['status'] != 1)
            return $this->fun_fail('事件状态异常!');
        $data = array(
            'record_fact' => trim($this->input->post('record_fact')),
            'event_date' => trim($this->input->post('event_date')),
            'remark' => trim($this->input->post('remark')),
            'modify_uid' => $admin_id,
            'modify_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['record_fact'] || !$data['remark'] || !$data['event_date']){
            return $this->fun_fail('缺少必要信息!');
        }
        $res = $this->db->where('record_id', $record_id)->update('event4agent_record', $data);
        return $this->fun_success('保存成功!');
    }

     public function event4agent_Record_edit($id){
        $this->db->select('a.*, b.name agent_name_ ,b.job_code agent_job_code_')->from('event4agent_record a');
        $this->db->join('agent b', 'b.id = a.agent_id', 'left');
        $this->db->where('a.record_id',$id);
        $detail =  $this->db->get()->row_array();
        return $detail;
    }

    public function event4agent_Record_cancel($admin_id){
        if(!$record_id = $this->input->post('record_id'))
            return $this->fun_fail('事件状态异常');
        $record_info_ = $this->readByID('event4agent_record', 'record_id', $record_id);
        if(!$record_info_ || $record_info_['status'] != 1)
            return $this->fun_fail('事件状态异常!');
        //重置后的事件 不退分
        if($record_info_['is_cz'] == 1)
            $record_info_['score'] = 0;
        $agent_info_ = $this->readByID('agent', 'id', $record_info_['agent_id']);
        if(!$agent_info_)
            return $this->fun_fail('所选经纪人异常!');
        $data = array(
            'del_remark' => trim($this->input->post('del_remark')),
            'del_uid' => $admin_id,
            'status' => -1,
            'del_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['del_remark']){
            return $this->fun_fail('缺少必要信息!');
        }
        $res = $this->db->where('record_id', $record_id)->update('event4agent_record', $data);
         if ($res) {
            $this->db->where(array('id' => $record_info_['agent_id'], 'score' => $agent_info_['score']));
            $res_agent_ = $this->db->set('score', 'score - ' . $record_info_['score'], FALSE)->update('agent');
            $this->handle_agent_score($record_info_['agent_id']);
            //DBY重要
            //这里需要加入 经纪人状态变更，企业分数更新和状态检查 可能还需要做相应的记录
            return $this->fun_success('作废成功!');
        }
        return $this->fun_fail('作废失败!');
    }

    /**
     *********************************************************************************************
     * 企业事件
     *********************************************************************************************
     */

    /**
     * 企业事件一级列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function company_grade_list($page = 1){
        $data['limit'] = $this->limit;

        //获取总记录数
        $this->db->select('count(1) num')->from('company_grade a');

        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*')->from('company_grade a');

        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.grade_no','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function company_grade_edit($id){
        $detail =  $this->readByID('company_grade', 'id', $id);
        return $detail;
    }

    public function company_grade_save(){
        $table_ = 'company_grade';
        $data =array(
            'grade_name' => trim($this->input->post('grade_name')),
            'min_score' => trim($this->input->post('min_score')) ? trim($this->input->post('min_score')) : 0,
            'grade_no' => trim($this->input->post('grade_no')),
        );
        if(!$data['grade_name']){
            return $this->fun_fail('请输入等级名称');
        }
        if(!isset($data['min_score'])){
            return $this->fun_fail('分数线设置异常');
        }
        if(!isset($data['grade_no'])){
            return $this->fun_fail('等级设置异常');
        }
        $this->load->model('common4manager_model', 'c4m_model');
        $grade_id = $this->input->post('grade_id');
        if($grade_id){
            $info_ = $this->readByID($table_, 'id', $grade_id);
            if(!$info_)
                return $this->fun_fail('等级不存在');
            if($info_['flag'] == -1){
                $data['min_score'] = $info_['min_score'];
            }else{
                if((int)$data['min_score'] < 0)
                    return $this->fun_fail('分数线设置异常');
            }
            if(in_array($info_['grade_no'],array(1,2)))
                $data['grade_no'] = $info_['grade_no'];
            $check_ = $this->c4m_model->check_grade_lawful($table_, $data, $grade_id);
            if($check_['status'] != 1)
                return $this->fun_fail($check_['msg']);
            $res2 = $this->db->where('id',$this->input->post('grade_id'))->update($table_,$data);
        }else{
            if((int)$data['min_score'] < 0)
                    return $this->fun_fail('分数线设置异常');
            $check_ = $this->c4m_model->check_grade_lawful($table_, $data);
            if($check_['status'] != 1)
                return $this->fun_fail($check_['msg']);
            $res2 = $this->db->insert($table_,$data);
        }

        if($res2){
            return $this->fun_success('保存成功');
        }else{
            return $this->fun_fail('保存失败');
        }
    }

    public function company_grade_delete($id){
        if(!$id)
            return $this->fun_fail('删除失败');
        $info_= $this->db->from('company_grade')->where('id', $id)->get()->row_array();
        if(!$info_)
            return $this->fun_fail('分数线不存在');
        if($info_['flag'] == -1)
            return $this->fun_fail('失信分数线不可删除');
        if(in_array($info_['grade_no'],array(1,2)))
            return $this->fun_fail('特殊分数线不可删除');
        $res = $this->db->where('id', $id)->delete('company_grade');
        if($res)
            return $this->fun_success('删除成功');
        return $this->fun_fail('删除失败');
    }

    /**
     * 企业事件一级列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4company_type_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4company_type a');
        if($data['keyword']){
            $this->db->like('a.event_type_name', $data['keyword']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*')->from('event4company_type a');
        if($data['keyword']){
            $this->db->like('a.event_type_name', $data['keyword']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 企业事件保存页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4company_type_save(){
        $data = array(
            'event_type_name'=>trim($this->input->post('event_type_name')),
            'type'=>trim($this->input->post('type')),
            'status' => trim($this->input->post('status')) ? trim($this->input->post('status')) : -1,
            'cdate' => date('Y-m-d H:i:s', time()),
        );
        $id = $this->input->post('id');
        if(!$data['event_type_name'] || !$data['type']){
            return $this->fun_fail('缺少必要信息!');
        }
        if($id){
            unset($data['cdate']);
            $this->db->where('id', $id)->update('event4company_type', $data);
        }else{
            $this->db->insert('event4company_type', $data);
        }
        return $this->fun_success('保存成功!');
    }

    public function event4company_type_edit($id){
        $this->db->select('a.*')->from('event4company_type a');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        return $detail;
    }

    /**
     * 企业事件二级列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function event4company_detail_list($page = 1){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['type_id'] = $this->input->get('type_id')?trim($this->input->get('type_id')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4company_detail a');
        $this->db->join('event4company_type b', 'a.type_id = b.id', 'left');
        if($data['keyword']){
            $this->db->like('a.event_name', $data['keyword']);
        }
        if($data['type_id']){
            $this->db->where('a.type_id', $data['type_id']);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.event_type_name, b.type b_type_')->from('event4company_detail a');
        $this->db->join('event4company_type b', 'a.type_id = b.id', 'left');
        if($data['keyword']){
            $this->db->like('a.event_name', $data['keyword']);
        }
        if($data['type_id']){
            $this->db->where('a.type_id', $data['type_id']);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    /**
     * 企业事件二级保存页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4company_detail_save(){
        $data = array(
            'event_name'=> trim($this->input->post('event_name')),
            'type_id'=> trim($this->input->post('type_id')),
            'score' => trim($this->input->post('score')),
            'allow_times' => trim($this->input->post('allow_times')) ? trim($this->input->post('allow_times')) : 0,
            'status' => trim($this->input->post('status')) ? trim($this->input->post('status')) : -1,
            'cdate' => date('Y-m-d H:i:s', time()),
        );
        $id = $this->input->post('id');
        if(!$data['event_name'] || !$data['type_id'] || !$data['score']){
            return $this->fun_fail('缺少必要信息!');
        }
        if($data['allow_times'] < 0){
            return $this->fun_fail('缺少必要信息!');
        }
        if($id){
            unset($data['cdate']);
            $this->db->where('id', $id)->update('event4company_detail', $data);
        }else{
            $this->db->insert('event4company_detail', $data);
        }
        return $this->fun_success('保存成功!');
    }

    public function event4company_detail_edit($id){
        $this->db->select('a.*')->from('event4company_detail a');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        return $detail;
    }

    /**
     * 企业事件列表
     * @author yangyang
     * @date 2019-11-12
     */

    public function event4company_record_list($page = 1, $type_type = null){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['business_no'] = $this->input->get('business_no')?trim($this->input->get('business_no')):null;
        $data['company_keyword'] = $this->input->get('company_keyword')?trim($this->input->get('company_keyword')):null;
        $data['event_keyword'] = $this->input->get('event_keyword')?trim($this->input->get('event_keyword')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4company_record a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
        if ($data['business_no']) {
            $this->db->where('b.business_no', $data['business_no']);
        }
        if($data['company_keyword']){
            $this->db->group_start();
            $this->db->like('b.company_name', $data['company_keyword']);
            $this->db->or_like('b.record_num', $data['company_keyword']);
            $this->db->group_end();
        }
        if($data['event_keyword']){
            $this->db->group_start();
            $this->db->like('a.event_name', $data['event_keyword']);
            $this->db->or_like('a.event_type_name', $data['event_keyword']);
            $this->db->group_end();
        }
        if($type_type){
            $this->db->where('a.event_type_type', $type_type);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.company_name new_company_name_, b.business_no business_no_')->from('event4company_record a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
        if ($data['business_no']) {
            $this->db->where('b.business_no', $data['business_no']);
        }
        if($data['company_keyword']){
            $this->db->group_start();
            $this->db->like('b.company_name', $data['company_keyword']);
            $this->db->or_like('b.record_num', $data['company_keyword']);
            $this->db->group_end();
        }
        if($data['event_keyword']){
            $this->db->group_start();
            $this->db->like('a.event_name', $data['event_keyword']);
            $this->db->or_like('a.event_type_name', $data['event_keyword']);
            $this->db->group_end();
        }
        if($type_type){
            $this->db->where('a.event_type_type', $type_type);
        }
        if($data['status']){
            $this->db->where('a.status', $data['status']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.record_id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //通过$event4company_type判断是 良好信用操作 还是失信信用操作，1代表良好信用，-1代表失信信用
    public function event4company_Record_save($admin_id, $event4company_type_index){
        $event4company_type = $this->config->item('event4company_type');
        $data = array(
            'company_id'=> trim($this->input->post('company_id')),
            'event_type_id'=> trim($this->input->post('type_id')),
            'event_id' => trim($this->input->post('event_id')),
            'record_fact' => trim($this->input->post('record_fact')),
            'event_date' => trim($this->input->post('event_date')),
            'remark' => trim($this->input->post('remark')),
            'create_uid' => $admin_id,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['company_id'] || !$data['event_type_id'] || !$data['event_id'] || !$data['record_fact'] || !$data['remark'] || !$data['event_date']){
            return $this->fun_fail('缺少必要信息!');
        }
        $company_info_ = $this->readByID('company_pending', 'id', $data['company_id']);
        if(!$company_info_)
            return $this->fun_fail('所选企业异常!');
        if($company_info_['flag'] != 2)
            return $this->fun_fail('所选企业状态异常!');
        $check_pass_ = $this->db->select()->from('company_pass')->where('company_id', $data['company_id'])->where_in('status', array(1,2))->get()->row_array();
        if ($check_pass_) 
            return $this->fun_fail('企业正在年审审核中，不可操作事件!');
        $data['old_company_name'] = $company_info_['company_name'];
        $event_info_ = $this->readByID('event4company_detail', 'id', $data['event_id']);
        if(!$event_info_ || $event_info_['status'] != 1)
            return $this->fun_fail('所选事件状态异常!');
        if( $event_info_['type_id'] != $data['event_type_id'])
            return $this->fun_fail('所选事件类别与事件不符!');
        //检查事件是否存在 次数限制，并查看是否可以新建
        if ($event_info_['allow_times'] > 0) {
            $check_times_ = $this->db->select('count(1) num')->from('event4company_record')->where(array('company_id' => $data['company_id'], 'event_id' => $data['event_id'], 'status' => 1, 'is_nscz' => -1))->get()->row_array();
            if ($check_times_['num'] >= $event_info_['allow_times']) {
                return $this->fun_fail('事件已设置到次数上限!');
            }

        }
        

        $type_info_ = $this->readByID('event4company_type', 'id', $data['event_type_id']);
        if(!$type_info_ || $type_info_['status'] != 1)
            return $this->fun_fail('所选事件类别状态异常!');
        if ($type_info_['type'] != $event4company_type_index) {
           return $this->fun_fail("所选事件不属于 " . $event4company_type[$event4company_type_index] . " 事件!");
        }
        if($type_info_['is_change_grade'] == 1){
            $data['grade_no'] = trim($this->input->post('grade_no'));
            if(!$data['grade_no'])
                return $this->fun_fail('请选择需要调整的信用等级!');
            $grade_info = $this->db->select()->from('company_grade')->where('grade_no', $data['grade_no'])->get()->row_array();
            if(!$grade_info)
                return $this->fun_fail('请选择存在的信用等级!');
            $data['grade_name'] = $grade_info['grade_name'];
        }
        $data['event_name'] = $event_info_['event_name'];
        $data['event_type_name'] = $type_info_['event_type_name'];
        $data['score'] =  ($event4company_type_index * $event_info_['score']);
        $data['event_type_type'] = $type_info_['type'];

        $res = $this->db->insert('event4company_record', $data);
        if ($res) {
            $this->db->where(array('id' => $data['company_id'], 'event_score' => $company_info_['event_score']));
            if($type_info_['is_change_grade'] == 1 && isset($data['grade_no']))
                $this->db->set('grade_no', $data['grade_no']);
            $res_company_ = $this->db->set('event_score', 'event_score + ' . $data['score'], FALSE)->update('company_pending');
            $this->save_company_total_score($data['company_id']);
            return $this->fun_success('保存成功!');
        }
        return $this->fun_fail('保存失败!');
    }

     public function event4company_Record_update($admin_id){
        if(!$record_id = $this->input->post('record_id'))
            return $this->fun_fail('事件状态异常');
        $record_info_ = $this->readByID('event4company_record', 'record_id', $record_id);
        if(!$record_info_ || $record_info_['status'] != 1)
            return $this->fun_fail('事件状态异常!');
        $data = array(
            'record_fact' => trim($this->input->post('record_fact')),
            'event_date' => trim($this->input->post('event_date')),
            'remark' => trim($this->input->post('remark')),
            'modify_uid' => $admin_id,
            'modify_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['record_fact'] || !$data['remark'] || !$data['event_date']){
            return $this->fun_fail('缺少必要信息!');
        }
        $res = $this->db->where('record_id', $record_id)->update('event4company_record', $data);
        return $this->fun_success('保存成功!');
    }

     public function event4company_Record_edit($id){
        $this->db->select('a.*, b.company_name new_company_name_, b.business_no business_no_')->from('event4company_record a');
        $this->db->join('company_pending b', 'b.id = a.company_id', 'left');
        $this->db->where('a.record_id',$id);
        $detail =  $this->db->get()->row_array();
        return $detail;
    }

    public function event4company_Record_cancel($admin_id){
        if(!$record_id = $this->input->post('record_id'))
            return $this->fun_fail('事件状态异常');
        $record_info_ = $this->readByID('event4company_record', 'record_id', $record_id);
        if(!$record_info_ || $record_info_['status'] != 1)
            return $this->fun_fail('事件状态异常!');
        $company_info_ = $this->readByID('company_pending', 'id', $record_info_['company_id']);
        if(!$company_info_)
            return $this->fun_fail('所选企业异常!');
        if($company_info_['flag'] != 2)
            return $this->fun_fail('所选经企业状态异常!');
        $check_pass_ = $this->db->select()->from('company_pass')->where('company_id', $record_info_['company_id'])->where_in('status', array(1,2))->get()->row_array();
        if ($check_pass_) 
            return $this->fun_fail('企业正在年审审核中，不可操作事件!');
        $new_score_ = $company_info_['event_score'] - $record_info_['score'];
        if($record_info_['is_nscz'] == 1)
            $record_info_['score'] = 0;
        $data = array(
            'del_remark' => trim($this->input->post('del_remark')),
            'del_uid' => $admin_id,
            'status' => -1,
            'del_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['del_remark']){
            return $this->fun_fail('缺少必要信息!');
        }
        if($record_info_['grade_no'] > 0){
            $data['del_grade_no'] =  trim($this->input->post('del_grade_no'));
            if(!$data['del_grade_no'])
                return $this->fun_fail('请选择需要调整的信用等级!');
            $grade_info = $this->db->select()->from('company_grade')->where('grade_no', $data['del_grade_no'])->get()->row_array();
            if(!$grade_info)
                return $this->fun_fail('请选择存在的信用等级!');
            $data['del_grade_name'] = $grade_info['grade_name'];
        }
        $res = $this->db->where('record_id', $record_id)->update('event4company_record', $data);
         if ($res) {
            $this->db->where(array('id' => $record_info_['company_id'], 'event_score' => $company_info_['event_score']));
            if($record_info_['grade_no'] > 0)
                $this->db->set('grade_no', $data['del_grade_no']);
            $res_company_ = $this->db->set('event_score', 'event_score - ' . $record_info_['score'], FALSE)->update('company_pending');
            $this->save_company_total_score($record_info_['company_id']);
            return $this->fun_success('作废成功!');
        }
        return $this->fun_fail('作废失败!');
    }

     /**
     *********************************************************************************************
     * 以下代码为年审设置
     *********************************************************************************************
     */

     /**
     * 企业事件一级列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function term_list($page = 1){
        $data['limit'] = $this->limit;

        //获取总记录数
        $this->db->select('count(1) num')->from('term a');

        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*')->from('term a');

        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.annual_year','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function term_edit($id){
        $detail =  $this->readByID('term', 'id', $id);
        return $detail;
    }

    public function term_save($admin_id = -1){
        $data =array(
            'annual_year'=>trim($this->input->post('annual_year')),
            'begin_date'=>trim($this->input->post('begin_date')),
            'end_date'=>trim($this->input->post('end_date')),
            'create_user' => $admin_id,
            'create_date'=>date('Y-m-d H:i:s',time()),
            'modify_user' => $admin_id,
            'modify_date'=>date('Y-m-d H:i:s',time())
        );
        if(!$data['annual_year'] || !$data['begin_date'] || !$data['end_date'])
            return $this->fun_fail('缺失信息！');
        try {
            $end_year_ =  date('Y', strtotime($data['end_date']));
            $begin_year_ =  date('Y', strtotime($data['begin_date']));
            if($data['annual_year'] != $end_year_ || $data['annual_year'] != $begin_year_)
                return $this->fun_fail('年审窗口期只可在年审年份内！');
        } catch (Exception $e) {
            return $this->fun_fail('信息异常！');
        }
        if($term_id = $this->input->post('id')){
            $detail =  $this->readByID('term', 'id', $term_id);
            if (!$detail)
                return $this->fun_fail('年审时间 已不存在');
            if(strtotime($detail['end_date']) < strtotime (date("y-m-d h:i:s")))
                return $this->fun_fail('窗口期已过期，不可修改！');
            $check_pass_ = $this->db->select('id')->from('company_pass')->where('annual_date', $detail['annual_year'])->get()->row_array();
            if ($check_pass_ && $data['annual_year'] != $detail['annual_year']){
                return $this->fun_fail('已有年审提交，不可改年份');
            }
            $res = $this->db->select('')->from('term')->where(array('annual_year'=>$data['annual_year'],'id <>'=>$term_id))->get()->row_array();
            if($res)
                return $this->fun_fail('此审核年份已经设置,不可保存');
            $this->db->select();
            $this->db->from('term');
            $this->db->group_start();
                $this->db->group_start();
                $this->db->where(array('begin_date <='=>$data['begin_date'],'end_date >='=>$data['begin_date']));
                $this->db->group_end();
                $this->db->or_group_start();
                $this->db->where(array('begin_date <='=>$data['end_date'],'end_date >='=>$data['end_date']));
                $this->db->group_end();
                $this->db->or_group_start();
                $this->db->where(array('begin_date >='=>$data['begin_date'],'end_date <='=>$data['end_date']));
                $this->db->group_end();
            $this->db->group_end();
            $this->db->where('id <>', $term_id);
            $res_check_ = $this->db->get()->row_array();
            if($res_check_)
                return $this->fun_fail('期限范围与其他期限有重叠!');
            unset($data['create_user']);
            unset($data['create_date']);
            $res2 = $this->db->where('id', $term_id)->update('term',$data);
        }else{
            $res = $this->db->select('')->from('term')->where('annual_year',$data['annual_year'])->get()->row_array();
            if($res)
                return $this->fun_fail('此审核年份已经设置,不可新增');
            $this->db->select();
            $this->db->from('term');
            $this->db->group_start();
            $this->db->where(array('begin_date <='=>$data['begin_date'],'end_date >='=>$data['begin_date']));
            $this->db->group_end();
            $this->db->or_group_start();
            $this->db->where(array('begin_date <='=>$data['end_date'],'end_date >='=>$data['end_date']));
            $this->db->group_end();
            $this->db->or_group_start();
            $this->db->where(array('begin_date >='=>$data['begin_date'],'end_date <='=>$data['end_date']));
            $this->db->group_end();
            $res_check_ = $this->db->get()->row_array();
            if($res_check_)
                return $this->fun_fail('期限范围与其他期限有重叠!');
            $res2 = $this->db->insert('term',$data);
        }

         if($res2)
            return $this->fun_success('保存成功');
        return $this->fun_fail('保存失败');
    }

    public function term_delete($id){
        if(!$id)
            return $this->fun_fail('删除失败');
        $detail =  $this->readByID('term', 'id', $id);
        if (!$detail) {
            return $this->fun_fail('年审时间 已不存在');
        }
        $check_pass_ = $this->db->select('id')->from('company_pass')->where('annual_date', $detail['annual_year'])->get()->row_array();
        if ($check_pass_) {
           return $this->fun_fail('已有此年审的年审申请产生，不可删除');
        }
        $res = $this->db->where('id', $id)->delete('term');
        if($res)
            return $this->fun_success('删除成功');
        return $this->fun_fail('删除失败');
    }

    /**
     *********************************************************************************************
     * 以下代码为企业管理
     *********************************************************************************************
     */


     /**
     * [作废]历史原因 status 1代表等待初审，2代表审核通过，3代表审核失败，4代表等待终审
      * 以免给自己挖坑,把status 修改为 1代表等待初审，2代表等待终审，3代表审核通过，-1代表审核失败
     * flag  1代表报备，2代表报备成功，-1代表作废
     * 企业列表 设计为通用
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pending_list($page = 1, $flag = array(), $status = array()){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['zz_status'] = $this->input->get('zz_status')?trim($this->input->get('zz_status')):null;
        $data['flag'] = $this->input->get('flag')?trim($this->input->get('flag')):null;
        $data['town_id'] = $this->input->get('town_id')?trim($this->input->get('town_id')):null;  //保留单个区镇 虽然实际是不使用
        $data['town_ids'] = $this->input->get('town_ids');
        //当区镇什么也没有选时就自动取默认
        if(!$data['town_ids']){
            $admin_info = $this->session->userdata('admin_info');
            //$admin = $this->get_admin($admin_info['admin_id']);
            //if($admin['group_id'] != 1)
                $data['town_ids'] = $this->get_admin_t_list($admin_info['admin_id']);
        }
        $data['town_ids'] = $data['town_ids'] ? $data['town_ids'] : array('');
        $this->db->select('count(1) num')->from('company_pending a');
        $this->db->join('town t', 'a.town_id = t.id', 'left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['keyword']);
            $this->db->or_like('a.business_no', $data['keyword']);
            $this->db->group_end();
        }
        if($data['zz_status'])
            $this->db->where('a.zz_status', $data['zz_status']);
        if($data['town_id'])
            $this->db->where('a.town_id', $data['town_id']);
        if(is_array($data['town_ids']))
            $this->db->where_in('a.town_id', $data['town_ids']);
        if($flag)
            $this->db->where_in('a.flag',$flag);
        if($data['flag'])
            $this->db->where('a.flag', $data['flag']);
        if($status){
            $this->db->where_in('a.status',$status);
        }

        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.grade_name,t.name town_name_,t.s_name s_town_name_,max(start_date) start_date, max(end_date) end_date')->from('company_pending a');
        $this->db->join('company_grade b', 'a.grade_no = b.grade_no', 'left');
        $this->db->join('town t', 'a.town_id = t.id', 'left');
        $this->db->join('company_ns_cert c','a.id = c.company_id and c.status = 1','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['keyword']);
            $this->db->or_like('a.business_no', $data['keyword']);
            $this->db->group_end();
        }
        if($data['town_id'])
            $this->db->where('a.town_id', $data['town_id']);
        if($data['zz_status'])
            $this->db->where('a.zz_status', $data['zz_status']);
        if(is_array($data['town_ids']))
            $this->db->where_in('a.town_id', $data['town_ids']);
        if($flag)
            $this->db->where_in('a.flag',$flag);
        if($data['flag'])
            $this->db->where('a.flag', $data['flag']);
        if($status){
            $this->db->where_in('a.status',$status);
        }
        if(in_array(-1, $flag))
            $this->db->order_by('a.cancel_date','desc');
        $this->db->order_by('a.cdate','desc');
        $this->db->group_by('a.id');
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //打印所使用
    public function company_pending_list_all($flag = array(), $status = array()){
        //获取详细列
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
        $data['zz_status'] = $this->input->get('zz_status')?trim($this->input->get('zz_status')):null;
        $data['flag'] = $this->input->get('flag')?trim($this->input->get('flag')):null;
        $data['town_id'] = $this->input->get('town_id')?trim($this->input->get('town_id')):null;
        $data['town_ids'] = $this->input->get('town_ids');
        //当区镇什么也没有选时就自动取默认
        if(!$data['town_ids']){
            $admin_info = $this->session->userdata('admin_info');
            //$admin = $this->get_admin($admin_info['admin_id']);
            //if($admin['group_id'] != 1)
                $data['town_ids'] = $this->get_admin_t_list($admin_info['admin_id']);
        }
        $data['town_ids'] = $data['town_ids'] ? $data['town_ids'] : array('');
        $this->db->select('count(1) num')->from('company_pending a');
        $this->db->join('town t', 'a.town_id = t.id', 'left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['keyword']);
            $this->db->or_like('a.business_no', $data['keyword']);
            $this->db->group_end();
        }
        if($data['town_id'])
            $this->db->where('a.town_id', $data['town_id']);
        if($data['zz_status'])
            $this->db->where('a.zz_status', $data['zz_status']);
        if(is_array($data['town_ids']))
            $this->db->where_in('a.town_id', $data['town_ids']);
        if($flag)
            $this->db->where_in('a.flag',$flag);
        if($data['flag'])
            $this->db->where('a.flag', $data['flag']);
        if($status){
            $this->db->where_in('a.status',$status);
        }

        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        $this->db->select('a.*, b.grade_name,t.name town_name_')->from('company_pending a');
        $this->db->join('company_grade b', 'a.grade_no = b.grade_no', 'left');
        $this->db->join('town t', 'a.town_id = t.id', 'left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['keyword']);
            $this->db->or_like('a.business_no', $data['keyword']);
            $this->db->group_end();
        }
        if($data['town_id'])
            $this->db->where('a.town_id', $data['town_id']);
        if($data['zz_status'])
            $this->db->where('a.zz_status', $data['zz_status']);
        if(is_array($data['town_ids']))
            $this->db->where_in('a.town_id', $data['town_ids']);
        if($flag)
            $this->db->where_in('a.flag',$flag);
        if($data['flag'])
            $this->db->where('a.flag', $data['flag']);
        if($status){
            $this->db->where_in('a.status',$status);
        }
        if(in_array(-1, $flag))
            $this->db->order_by('a.cancel_date','desc');
        $this->db->order_by('a.cdate','desc');
        $data['res_list'] = $this->db->get()->result_array();
        //增加导出经纪人
        $this->db->select('a1.*,a.company_name,t.name town_name_')->from('company_pending a');
        $this->db->join('company_grade b', 'a.grade_no = b.grade_no', 'left');
        $this->db->join('town t', 'a.town_id = t.id', 'left');
        $this->db->join('agent a1', 'a1.company_id = a.id','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['keyword']);
            $this->db->or_like('a.business_no', $data['keyword']);
            $this->db->group_end();
        }
        if($data['town_id'])
            $this->db->where('a.town_id', $data['town_id']);
        if($data['zz_status'])
            $this->db->where('a.zz_status', $data['zz_status']);
        if(is_array($data['town_ids']))
            $this->db->where_in('a.town_id', $data['town_ids']);
        if($flag)
            $this->db->where_in('a.flag',$flag);
        if($data['flag'])
            $this->db->where('a.flag', $data['flag']);
        if($status){
            $this->db->where_in('a.status',$status);
        }
        $this->db->where('a1.flag', 2);
        if(in_array(-1, $flag))
            $this->db->order_by('a.cancel_date','desc');
        $this->db->order_by('a.cdate','desc');
        $this->db->order_by('a1.work_type','asc');
        $this->db->order_by('a1.last_work_time','desc');
        $data['agent_list'] = $this->db->get()->result_array();

        return $data;
    }

    //企业人员列表
    public function company_pending_temp($company_id, $page = 1){
        $data['company_id'] = $company_id;
        $data['limit'] = $this->limit;
        //$data['limit'] = 5;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;

        $data['work_type'] = $this->input->get('work_type')?trim($this->input->get('work_type')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('agent a');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.job_code', $data['keyword']);
            $this->db->or_like('a.job_num', $data['keyword']);
            $this->db->group_end();
        }
        $this->db->where('a.company_id', $company_id);
        if($data['work_type']){
            $this->db->where('a.work_type', $data['work_type']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.company_name')->from('agent a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        //$this->db->join('company_pass c','b.id = c.company_id','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.job_code', $data['keyword']);
            $this->db->or_like('a.job_num', $data['keyword']);
            $this->db->group_end();
        }
        $this->db->where('a.company_id', $company_id);
        if($data['work_type']){
            $this->db->where('a.work_type', $data['work_type']);
        }
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $this->db->order_by('a.last_work_time,a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //企业人员添加
    public function company_pending_add_agent($admin_id){
        if(!$company_id = $this->input->post('company_id'))
            return $this->fun_fail('企业信息丢失!');
        $company_info = $this->company_pending_edit($company_id);
        if(!$company_info || $company_info['flag'] == -1)
            return $this->fun_fail('企业信息异常!');
        if(!$agent_id = $this->input->post('agent_id'))
            return $this->fun_fail('人员信息丢失!');
        $this->load->model('common4manager_model', 'c4m_model');
        $check_agent_ = $this->c4m_model->check_agent_id4get($agent_id, $company_id);
        if($check_agent_['status'] != 1)
            return $this->fun_fail($check_agent_['msg']);
        $res_check_town_ = $this->check_admin_townByCompany_id($admin_id, $company_id);
        if(!$res_check_town_)
            return $this->fun_fail('不可操作此区镇下的企业!');

        //将人员加入企业
        $this->db->trans_start();//--------开始事务
        $update_rows_ = $this->db->where(array('id' => $agent_id, 'flag' => 2, 'company_id' => -1))->update('agent',array('company_id' => $company_id, 'wq' => 1, 'last_work_time' => time()));
        //人员加入企业后需要做两个操作
        //1.更新企业信用分数
        $this->save_company_total_score($company_id);
        //2.添加人员轨迹
        if($update_rows_){
            $this->save_agent_track4common($agent_id, -1, $company_id, 1);
        }
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            return $this->fun_fail('保存失败!');
        } else {
            return $this->fun_success('保存成功!');
        }
    }

    //企业人员删除
    public function company_pending_delete_agent($admin_id){
        if(!$company_id = $this->input->post('company_id'))
            return $this->fun_fail('企业信息丢失!');
        $company_info = $this->company_pending_edit($company_id);
        if(!$company_info || $company_info['flag'] == -1)
            return $this->fun_fail('企业信息异常!');
        if(!$agent_id = $this->input->post('agent_id'))
            return $this->fun_fail('人员信息丢失!');
        $res_check_town_ = $this->check_admin_townByCompany_id($admin_id, $company_id);
        if(!$res_check_town_)
            return $this->fun_fail('不可操作此区镇下的企业!');
        //将人员从企业中删除
        $this->db->trans_start();//--------开始事务
        $update_rows_ = $this->db->where(array('id' => $agent_id, 'company_id' => $company_id))->update('agent',array('company_id' => -1, 'wq' => 1, 'last_work_time' => time()));
        //人员加入企业后需要做两个操作
        //1.更新企业信用分数
        $this->save_company_total_score($company_id);
        //2.添加人员轨迹
        if($update_rows_){
            $this->save_agent_track4common($agent_id, $company_id, -1, 2);
        }
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            return $this->fun_fail('保存失败!');
        } else {
            return $this->fun_success('保存成功!');
        }
    }

    public function company_pending_wq_agent($admin_id){
        if(!$company_id = $this->input->post('company_id'))
            return $this->fun_fail('企业信息丢失!');
        $company_info = $this->company_pending_edit($company_id);
        if(!$company_info || $company_info['flag'] == -1)
            return $this->fun_fail('企业信息异常!');
        if(!$agent_id = $this->input->post('agent_id'))
            return $this->fun_fail('人员信息丢失!');
        $res_check_town_ = $this->check_admin_townByCompany_id($admin_id, $company_id);
        if(!$res_check_town_)
            return $this->fun_fail('不可操作此区镇下的企业!');
        $agent_info_ = $this->db->from('agent')->where('id', $agent_id)->get()->row_array();
        if(!$agent_info_ || $agent_info_['flag'] != 2)
            return $this->fun_fail('人员信息异常!');
        if($agent_info_['work_type'] != 1)
            return $this->fun_fail('非持证经纪人不可设置网签!');
        $update_rows_ = $this->db->where(array('id' => $agent_id, 'company_id' => $company_id))->update('agent',array('wq' => 2));
        if($update_rows_){
            return $this->fun_success('操作成功!');
        }else{
            return $this->fun_fail('操作失败!');
        }
    }

    public function company_pending_edit($id){
        $this->db->select('a.*, t.name t_name_')->from('company_pending a');
        $this->db->join('town t','t.id = a.town_id', 'left');
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

    //企业备案信息保存【重要】对company_pending做处理,理论上只修改信息,不会影响 备案/审核/信用等级 等状态.
    public function company_pending_save($admin_id){
        $data = array(
            'company_name'=>trim($this->input->post('company_name')),
            'business_no'=>strtoupper(trim($this->input->post('business_no'))),
            'register_path'=>trim($this->input->post('register_path')),
            'business_path'=>trim($this->input->post('business_path')),
            'town_id'=>trim($this->input->post('town_id')),
            'issuing_date'=>trim($this->input->post('issuing_date')),
            'company_phone'=>trim($this->input->post('company_phone')),
            'director_name'=>trim($this->input->post('director_name')),
            'director_phone'=>trim($this->input->post('director_phone')),
            //'record_num' => trim($this->input->post('record_num')),
            'fz_num' => trim($this->input->post('fz_num')),
            'legal_name'=>trim($this->input->post('legal_name')),
            'legal_phone'=>trim($this->input->post('legal_phone')),
            'cdate'=>date('Y-m-d H:i:s',time()),
            'mdate'=>date('Y-m-d H:i:s',time()),
            'base_score' => $this->config->item('company_score'),
            'password'=>sha1('123456'),
            'status' => 1,
            'flag' => 1,
            'tj_date' => date('Y-m-d H:i:s',time()),
        );
        $res_check_town_ = $this->check_admin_townByTown_id($admin_id,$data['town_id']);
        if(!$res_check_town_)
            return $this->fun_fail('不可设置此区镇!');
        $company_id = $this->input->post('company_id');
        if($company_id){
            $res_check_town_ = $this->check_admin_townByCompany_id($admin_id, $company_id);
            if(!$res_check_town_)
                return $this->fun_fail('不可操作此区镇下的企业!');
        }
        if(!$data['company_name'] || !$data['register_path'] || !$data['business_path'] ||  !$data['business_no']
            //|| !$data['issuing_date']  || !$data['record_num']
            || !$data['company_phone'] || !$data['director_name'] || !$data['town_id'] ||
            !$data['director_phone'] || !$data['legal_name'] || !$data['legal_phone']){
            return $this->fun_fail('缺少必要信息!');
        }
        $this->load->model('common4manager_model', 'c4m_model');
        //检查企业名称是否唯一
        $check_company_name_ = $this->c4m_model->check_company_name($data['company_name'], $company_id);
        if($check_company_name_['status'] != 1)
            return $this->fun_fail($check_company_name_['msg']);
        //检查 统一社会信用代码 是否唯一
        $check_business_no_ = $this->c4m_model->check_business_no($data['business_no'], $company_id);
        if($check_business_no_['status'] != 1)
            return $this->fun_fail($check_business_no_['msg']);
        //20200324不再判断备案号
        //$check_num_ = $this->c4m_model->check_record_num($data['record_num'], $company_id);
        //if($check_num_['status'] != 1)
            //return $this->fun_fail('备案号已占用!');
        //检查执业证号是否可用或者重复
        $code_ = $this->input->post('new_agent_id');
        $check_repeat_agent_ = $this->c4m_model->check_repeat_agent($company_id, $code_);
        if($check_repeat_agent_['status'] != 1)
            return $this->fun_fail($check_repeat_agent_['msg']);
        
        $save4track_old = array();
        //判断如果是新增的 先判断下经纪人数量
        if(!$this->input->post('company_id')){
            if($check_repeat_agent_['result']['job_code_count'] < 3)
                return $this->fun_fail('新增报备时,持证经纪人不能小于三人!');
        }else{
            $check_company_flag_ = $this->db->select('flag')->from('company_pending')->where('id',$company_id)->order_by('id','desc')->get()->row_array();
            if(!$check_company_flag_)
                return $this->fun_fail('企业信息丢失!');
            if(!in_array($check_company_flag_['flag'], array(1, 2)))
                return $this->fun_fail('企业状态变更,不可编辑!');
        }
        //事务开始前 判断标签
        $post_icon_list = $this->input->post('icon_list');

        $this->db->select('count(id)')->from('sys_score_icon');
        $this->db->where('status', 1)->where_in('icon_no', $post_icon_list);
        $this->db->group_by('icon_class');
        $this->db->having('count(id) > 1');
        $check_icon_ = $this->db->get()->result_array();
        if($check_icon_ && $post_icon_list)
            return $this->fun_fail('标签选择异常!');
        $icon_list = array();

        $this->db->trans_start();//--------开始事务
        if($company_id){
            unset($data['cdate']);
            unset($data['status']);
            unset($data['flag']);
            unset($data['tj_date']);
            unset($data['username']);
            unset($data['password']);
            $this->db->where('id', $company_id)->update('company_pending', $data);
            $this->db->select('a.*')->from('agent a');
            $this->db->where('a.company_id',$company_id);
            $save4track_old = $this->db->get()->result_array();
            $this->db->where(array('id' => $company_id))->update('company_pending', $data);
        }else{
            $data['username'] = $this->get_username();
            $data['record_num'] = $this->get_record_num();
            $this->db->insert('company_pending', $data);
            $company_id = $this->db->insert_id();
        }
        //处理经纪人 20201004经纪人批量操作只在 新增时有效
        if(!$this->input->post('company_id')){
            $this->db->where('company_id',$company_id)->update('agent',array('company_id' => -1, 'wq' => 1, 'last_work_time' => time()));
            $arr_new_agent_id = $this->input->post('new_agent_id');
            $arr_agent_wq = $this->input->post('setwq');
            $arr_agent_company_id = array(
                'company_id' => $company_id,
            );
            if ($arr_new_agent_id && is_array($arr_new_agent_id)) {
                foreach($arr_new_agent_id as $idx => $pic) {
                    $update_data4agent_ = $arr_agent_company_id;
                    $update_data4agent_['wq'] = $arr_agent_wq[$idx];
                    $update_data4agent_['last_work_time'] = time();
                    $this->db->where('id',$pic)->where('flag',2)->update('agent', $update_data4agent_);
                }
            }
        }

       
        //处理图片
        $this->db->delete('company_pending_img', array('company_id' => $company_id));
        $pic_short = $this->input->post('pic_short');
        if($pic_short){
            foreach($pic_short as $idx => $pic) {
                $company_pic = array(
                    'company_id' => $company_id,
                    'img_path' => $pic,
                    'm_img_path' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
                );
                $this->db->insert('company_pending_img', $company_pic);
            }
        }
        //处理基本信用标签
        $this->db->delete('company_pending_icon', array('company_id' => $company_id));
        if($post_icon_list){
            $icon_insert_data = $this->db->select("{$company_id} company_id, icon_no")->from('sys_score_icon')->where('status', 1)->where_in('icon_no', $post_icon_list)->get()->result_array();
            if ($icon_insert_data)
                $this->db->insert_batch('company_pending_icon', $icon_insert_data);
        }


        //判断如果是新增的 就自动提报年审
        if(!$this->input->post('company_id'))
            $this->save_pass_company($company_id);

        $this->save_company_total_score($company_id);
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
           return $this->fun_fail('保存失败!');
        } else {

            $this->db->select('a.*')->from('agent a');
            $this->db->where('a.company_id',$company_id);
            $save4track_new = $this->db->get()->result_array();
            $this->save_agent_track($company_id, $data, $save4track_old, $save4track_new);
            $this->save_log_company($company_id);
            return $this->fun_success('保存成功!');
        }
        
    }

    //年审提交
    public function company_pending_pass($admin_id){
        $this->load->model('common4manager_model', 'c4m_model');
        $res_check_ = $this->c4m_model->check_is_ns_time();
        if($res_check_['status'] != 1)
            return $this->fun_fail('不在年审窗口期,不可年审!');
        $company_id = $this->input->post('company_id');
        $check_ns_ = $this->db->select()->from('company_pass')->where('company_id',$company_id)->where_not_in('status', array(-1,3))->order_by('id','desc')->get()->row_array();
        if($check_ns_)
            return $this->fun_fail('存在未处理的年审,不可年审!');
        $company_data = $this->db->select()->from('company_pending')->where('id',$company_id)->order_by('id','desc')->get()->row_array();
        if(!$company_data)
            return $this->fun_fail('企业信息丢失!');
        $res_check_town_ = $this->check_admin_townByTown_id($admin_id, $company_data['town_id']);
        if(!$res_check_town_)
            return $this->fun_fail('不可操作此区镇下企业!');
        if(!in_array($company_data['flag'], array(1, 2)))
            return $this->fun_fail('企业状态变更,不可年审!');
        $company_data['annual_date'] = $res_check_['result']['annual_year'];
        $company_data['status'] = 1;
        $company_data['tj_user'] = $admin_id;
        $company_data['tj_date'] = date('Y-m-d H:i:s',time());
        //覆盖信息
        $company_data['company_name'] = trim($this->input->post('company_name'));
        $company_data['business_no'] =  strtoupper(trim($this->input->post('business_no')));
        $company_data['register_path'] = trim($this->input->post('register_path'));
        $company_data['business_path'] = trim($this->input->post('business_path'));
        $company_data['issuing_date'] = trim($this->input->post('issuing_date'));
        $company_data['company_phone'] = trim($this->input->post('company_phone'));
        $company_data['director_name'] = trim($this->input->post('director_name'));
        $company_data['director_phone'] = trim($this->input->post('director_phone'));
        $company_data['legal_name'] = trim($this->input->post('legal_name'));
        $company_data['legal_phone'] = trim($this->input->post('legal_phone'));
        $company_data['company_id'] = $company_data['id'];
        unset($company_data['id']);
        unset($company_data['username']);
        unset($company_data['password']);
        unset($company_data['cancel_date']);
        unset($company_data['cancel_user']);
        unset($company_data['cancel_remark']);
        if(!$company_data['company_name'] || !$company_data['register_path'] || !$company_data['business_path'] || !$company_data['business_no']
            //|| !$company_data['issuing_date']
            || !$company_data['company_phone'] || !$company_data['director_name'] ||
            !$company_data['director_phone'] || !$company_data['legal_name'] || !$company_data['legal_phone']){
            return $this->fun_fail('缺少必要信息!');
        }
        //检查企业名称是否唯一
        $check_company_name_ = $this->c4m_model->check_company_name($company_data['company_name'], $company_id);
        if($check_company_name_['status'] != 1)
            return $this->fun_fail($check_company_name_['msg']);
         //检查工商注册号是否唯一
        $check_business_no_ = $this->c4m_model->check_business_no($company_data['business_no'], $company_id);
        if($check_business_no_['status'] != 1)
            return $this->fun_fail($check_business_no_['msg']);
        $agent_num = $this->get_agent_num4company($company_id);
        if($agent_num < 3)
            return $this->fun_fail('年审提交,持证经纪人不能小于三个!');
        $this->db->trans_start();//--------开始事务

        $this->db->insert('company_pass',$company_data);
        $pass_id = $this->db->insert_id();
        $pic_short = $this->input->post('pic_short');
        if($pic_short){
            foreach($pic_short as $idx => $pic) {
                $company_pic = array(
                    'company_id' => $company_id,
                    'pass_id' => $pass_id,
                    'img_path' => $pic,
                    'm_img_path' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
                );
                $this->db->insert('company_pass_img', $company_pic);
            }
        }
        //经纪人信息 只做暂存,实际 只有在审核结束后有效
        $this->db->select("id agent_id,name,phone,job_code,card,company_id,wq,old_job_code,{$pass_id} pass_id,job_num,work_type,last_work_time")->from('agent');
        $this->db->where('flag',2); //如果是离昆的就不要进行保存 //有什么信息就保存什么信息，真正是否显示，还是要看实际的状态
        $this->db->where('company_id', $company_id);
        $pass_agent = $this->db->get()->result_array();
        if($pass_agent)
            $this->db->insert_batch('company_pass_agent',$pass_agent);
        //结束前也更新下 company_pending的状态
        $this->db->where('id', $company_id)->update('company_pending', array('annual_date' => $company_data['annual_date'],'tj_date' => $company_data['tj_date'], 'status' => 1));
        $this->save_company_total_score($company_id, $company_data['annual_date']);
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            return $this->fun_fail('保存失败!');
        } else {
            return $this->fun_success('保存成功!');
        }
    }

    //获取最近一次终审通过的信息
    public function company_pass_data($pass_id){
        $this->db->select('a.*, t.name t_name_')->from('company_pass a');
        $this->db->join('town t', 't.id = a.town_id', 'left');
        $this->db->where('a.id', $pass_id);
        $detail =  $this->db->get()->row_array();
        $this->db->select()->from('company_pass_img');
        $this->db->where('pass_id', $pass_id);
        $detail['img'] = $this->db->get()->result_array();
        $this->db->select('a.*')->from('company_pass_agent a');
        $this->db->where('a.pass_id', $pass_id);
        $detail['agent'] = $this->db->get()->result_array();
        $this->db->select('a.*')->from('company_pass_icon a');
        $this->db->where('a.pass_id', $pass_id);
        $detail['icon'] = $this->db->get()->result_array();
        $this->db->select('a.*')->from('company_pass_score_log a');
        $this->db->where('a.pass_id', $pass_id);
        $detail['score_log'] = $this->db->get()->result_array();
        return $detail;
    }

    //审核操作
    public function company_pass_submit($status, $admin_id){
        $pass_id = $this->input->post('pass_id');
        if (!$pass_id) {
            return $this->fun_fail('请求异常!');
        }
        $pass_info_ = $this->db->where('id', $pass_id)->from('company_pass')->get()->row_array();
        if (!$pass_info_) {
            return $this->fun_fail('企业年审信息丢失!');
        }
        $company_info_ = $this->db->where('id', $pass_info_['company_id'])->from('company_pending')->get()->row_array();
        if(!$company_info_)
            return $this->fun_fail('企业信息丢失!');
        $check_town_ = $this->check_admin_townByTown_id($admin_id, $company_info_['town_id']);
        if(!$check_town_)
            return $this->fun_fail('不可操作此区镇下企业!');
        $company_id = $pass_info_['company_id'];
        $check_company_name_ = $this->c4m_model->check_company_name($pass_info_['company_name'], $company_id);
        if($check_company_name_['status'] != 1)
            return $this->fun_fail($check_company_name_['msg']);  
         //检查工商注册号是否唯一
        $check_business_no_ = $this->c4m_model->check_business_no($pass_info_['business_no'], $company_id);
        if($check_business_no_['status'] != 1)
            return $this->fun_fail($check_business_no_['msg']); 
        //如果审核状态相同，就只是编辑
        if ($status != $pass_info_['status']) {
            $check_status_change4company_ = $this->c4m_model->check_status_change4company($pass_info_['status'], $status);
            if ($check_status_change4company_['status'] != 1) {
                return $this->fun_fail($check_status_change4company_['msg']);
            }
        }else{
            return $this->fun_fail('请求异常!审核状态不变');
        }

        $update_data = array(
            'status' => $status,
            'town_id' => $company_info_['town_id']
        );
        if($status != -1){
            $agent_num = $this->get_agent_num4company($company_id);
            if($agent_num < 3)
                return $this->fun_fail('年审通过,持证经纪人不能小于三个!');
            if ($status == 2){
                $update_data['cs_date'] = date('Y-m-d H:i:s',time());
                $update_data['cs_user'] = $admin_id;
            }
            if ($status == 3){
                $update_data['s_date'] = date('Y-m-d H:i:s',time());
                $update_data['s_user'] = $admin_id;
            }
        }else{
            if($pass_info_['status'] == 1){
                $update_data['cs_date'] = date('Y-m-d H:i:s',time());
                $update_data['cs_user'] = $admin_id;
            }
            if($pass_info_['status'] == 2){
                $update_data['s_date'] = date('Y-m-d H:i:s',time());
                $update_data['s_user'] = $admin_id;
            }
        }
        
        $this->db->trans_start();//--------开始事务
        $this->db->where('id', $pass_id)->update('company_pass', $update_data);
        //审核均重新计算下分数
        $is_ns_ = null;
        if($status == 3)
            $is_ns_ = 2;
        if($status == -1)
            $is_ns_ = 1;
        $this->save_company_total_score($company_id, $pass_info_['annual_date'], $is_ns_, $pass_id);
        if ($is_ns_) {
            //如果是年审结束时，需要再次计算下分数和异常状态
            $this->save_company_total_score($company_id, $pass_info_['annual_date']);
        }
        if ($status == 3) {
            //pass信息覆盖 pending
            $company_data['company_name']       =   $pass_info_['company_name'];
            $company_data['business_no']        =   $pass_info_['business_no'];
            $company_data['register_path']      =   $pass_info_['register_path'];
            $company_data['business_path']      =   $pass_info_['business_path'];
            $company_data['issuing_date']       =   $pass_info_['issuing_date'];
            $company_data['company_phone']      =   $pass_info_['company_phone'];
            $company_data['director_name']      =   $pass_info_['director_name'];
            $company_data['director_phone']     =   $pass_info_['director_phone'];
            $company_data['legal_name']         =   $pass_info_['legal_name'];
            $company_data['legal_phone']        =   $pass_info_['legal_phone'];
            $this->db->where('id', $company_id)->update('company_pending', $company_data);

            $this->db->where('company_id', $company_id)->delete('company_pending_img');
            $this->db->select("img_path,m_img_path,folder,company_id")->from('company_pass_img');
            $this->db->where('pass_id', $pass_id);
            $pending_img = $this->db->get()->result_array();
            if($pending_img)
                $this->db->insert_batch('company_pending_img',$pending_img);

            //pass存入 审核结束时的数据
            $this->db->where('pass_id', $pass_id)->delete('company_pass_agent');
            $this->db->select("id agent_id,name,phone,job_code,card,company_id,wq,old_job_code,{$pass_id} pass_id,flag,learn_time,grade_no,score,work_type,job_num")->from('agent');
            $this->db->where('company_id',$company_id);
            $pass_agent = $this->db->get()->result_array();
            if($pass_agent)
                $this->db->insert_batch('company_pass_agent',$pass_agent);

            $this->db->where('pass_id', $pass_id)->delete('company_pass_icon');
            $this->db->select("a.icon_no,a.icon_class,a.`name`,a.short_name,b.company_id,a.type,(a.score * a.type) score,a.status,{$pass_id} pass_id")->from('fm_sys_score_icon a');
            $this->db->join('company_pending_icon b','a.icon_no = b.icon_no ','left');
            $this->db->where('company_id',$company_id);
            $pass_icon = $this->db->get()->result_array();
            if($pass_icon)
                $this->db->insert_batch('company_pass_icon',$pass_icon);
        }
        $temp_update_data = array('status'=>$status);
        if($status == 3 || $status == -1){
            $temp_update_data['qx_num'] = 0;
            $this->db->where('id', $company_id)->where('flag', 1)->update('company_pending', array('flag' => 2));
            //在审核失败 或者终审成功时 处理证书
            $this->create_cert($pass_id);
        }
        //每次审核后都同步一下company_pending 的status 栏位，没有实际意义，只做暂存，
        $this->db->where('id', $company_id)->update('company_pending', $temp_update_data);
        //每次审核后都同步一下 agent 和 icon
        $this->db->select("id agent_id,name,phone,job_code,card,company_id,wq,old_job_code,{$pass_id} pass_id,flag,learn_time,grade_no,score,work_type,job_num")->from('agent');
        $this->db->where('flag',2); //如果是离昆的就不要进行保存 //有什么信息就保存什么信息，真正是否显示，还是要看实际的状态
        $this->db->where('company_id', $company_id);
        $pass_agent = $this->db->get()->result_array();
        $this->db->where('pass_id', $pass_id)->delete('company_pass_agent');
        if($pass_agent)
            $this->db->insert_batch('company_pass_agent',$pass_agent);

        $this->db->where('pass_id', $pass_id)->delete('company_pass_icon');
        $this->db->select("a.icon_no,a.icon_class,a.`name`,a.short_name,b.company_id,a.type,(a.score * a.type) score,a.status,{$pass_id} pass_id")->from('fm_sys_score_icon a');
        $this->db->join('company_pending_icon b','a.icon_no = b.icon_no ','left');
        $this->db->where('company_id',$company_id);
        $this->db->where('a.status', 1);
        $log_icon = $this->db->get()->result_array();
        if($log_icon)
            $this->db->insert_batch('company_pass_icon',$log_icon);


        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            return $this->fun_fail('操作失败!');
        } else {
            return $this->fun_success('操作成功!');
        }
    }

    //企业审核列表
    public function company_pass_list($page=1, $status = array()){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword') ? trim($this->input->get('keyword')) : null;
        $data['business_no'] = $this->input->get('business_no') ? trim($this->input->get('business_no')) : null;
        $data['town_id'] = $this->input->get('town_id') ? trim($this->input->get('town_id')) : null; //保留单个区镇 虽然实际是不使用
        $data['town_ids'] = $this->input->get('town_ids');
        //当区镇什么也没有选时就自动取默认
        if(!$data['town_ids']){
            $admin_info = $this->session->userdata('admin_info');
            //$admin = $this->get_admin($admin_info['admin_id']);
            //if($admin['group_id'] != 1)
                $data['town_ids'] = $this->get_admin_t_list($admin_info['admin_id']);
        }
        $data['town_ids'] = $data['town_ids'] ? $data['town_ids'] : array('');
        $this->db->select('count(1) num')->from('company_pass a');
        $this->db->join('company_pending b','b.id = a.company_id','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['keyword']);
            $this->db->group_end();
        }
        if($data['business_no'])
            $this->db->where('b.business_no', $data['business_no']);
        if($status)
            $this->db->where_in('a.status',$status);
        if($data['town_id']){
            $this->db->where('b.town_id', $data['town_id']);
        }
        if(is_array($data['town_ids']))
            $this->db->where_in('b.town_id', $data['town_ids']);
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.id,a.annual_date,a.company_name,a.legal_name,a.tj_date,a.director_name,b.business_no,cs_date,s_date,t.name t_name_')->from('company_pass a');
        $this->db->join('company_pending b','b.id = a.company_id','left');
        $this->db->join('town t','t.id = b.town_id','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.company_name', $data['keyword']);
            $this->db->group_end();
        }
        if($data['business_no'])
            $this->db->where('b.business_no', $data['business_no']);
        if($data['town_id']){
            $this->db->where('b.town_id', $data['town_id']);
        }
        if(is_array($data['town_ids']))
            $this->db->where_in('b.town_id', $data['town_ids']);

        if($status){
            $this->db->where_in('a.status',$status);
            if (in_array(3, $status))
                $this->db->order_by('a.s_date','desc');
            if (in_array(-1, $status))
                $this->db->order_by('a.s_date','desc');
            if (in_array(2, $status))
                $this->db->order_by('a.cs_date','desc');
            if (in_array(1, $status))
                $this->db->order_by('a.tj_date','desc');
        }else{
            $this->db->order_by('a.tj_date','desc');
        }
        
    
        $this->db->limit($data['limit'], $offset = ($page - 1) * $data['limit']);
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    //企业审核信息修改保存
    //$status 是用来标记 是哪个控制器发来的，同时也用来判断可以编辑哪种状态的 年审记录
    public function company_pass_save($status, $admin_id){
        if(!in_array($status, array(1,2)))
            return $this->fun_fail('请求异常!');
        $pass_id = $this->input->post('pass_id');
        if (!$pass_id) {
            return $this->fun_fail('请求异常!');
        }
        $pass_info_ = $this->db->where('id', $pass_id)->from('company_pass')->get()->row_array();
        if (!$pass_info_) {
            return $this->fun_fail('企业年审信息丢失!');
        }
        $check_town_ = $this->check_admin_townByCompany_id($admin_id, $pass_info_['company_id']);
        if(!$check_town_)
            return $this->fun_fail('不可操作此区镇下企业!');
        if($pass_info_['status'] != $status)
            return $this->fun_fail('企业年审信息状态已变更!');
        $company_id = $pass_info_['company_id'];
        $company_data = array(
            'company_name'=>trim($this->input->post('company_name')),
            'business_no'=>strtoupper(trim($this->input->post('business_no'))),
            'register_path'=>trim($this->input->post('register_path')),
            'business_path'=>trim($this->input->post('business_path')),
            'issuing_date'=>trim($this->input->post('issuing_date')),
            'company_phone'=>trim($this->input->post('company_phone')),
            'director_name'=>trim($this->input->post('director_name')),
            'director_phone'=>trim($this->input->post('director_phone')),
            'legal_name'=>trim($this->input->post('legal_name')),
            'legal_phone'=>trim($this->input->post('legal_phone')),
            'mdate'=>date('Y-m-d H:i:s',time()),
        );
        if(!$company_data['company_name'] || !$company_data['register_path'] || !$company_data['business_path'] || !$company_data['business_no']
            //|| !$company_data['issuing_date']
            || !$company_data['company_phone'] || !$company_data['director_name'] ||
            !$company_data['director_phone'] || !$company_data['legal_name'] || !$company_data['legal_phone']){
            return $this->fun_fail('缺少必要信息!');
        }
        //检查企业名称是否唯一
        $check_company_name_ = $this->c4m_model->check_company_name($company_data['company_name'], $company_id);
        if($check_company_name_['status'] != 1)
            return $this->fun_fail($check_company_name_['msg']);
         //检查工商注册号是否唯一
        $check_business_no_ = $this->c4m_model->check_business_no($company_data['business_no'], $company_id);
        if($check_business_no_['status'] != 1)
            return $this->fun_fail($check_business_no_['msg']);
        $this->db->trans_start();//--------开始事务
        $this->db->where('id', $pass_id)->update('company_pass', $company_data);
        $this->db->where('pass_id', $pass_id)->delete('company_pass_img');
        $pic_short = $this->input->post('pic_short');
        if($pic_short){
            foreach($pic_short as $idx => $pic) {
                $company_pic = array(
                    'company_id' => $company_id,
                    'pass_id' => $pass_id,
                    'img_path' => $pic,
                    'm_img_path' => $pic . '?imageView2/0/w/200/h/200/q/75|imageslim'
                );
                $this->db->insert('company_pass_img', $company_pic);
            }
        }
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            return $this->fun_fail('保存失败!');
        } else {
            return $this->fun_success('保存成功!');
        }

    }

    //重置企业密码
    public function refresh_company_password($admin_id){
        $company_id = $this->input->post('id');
        $business_no = $this->input->post('username');
        if(!$company_id || !$business_no)
            return $this->fun_fail('信息缺失!');
        $check_town_ = $this->check_admin_townByCompany_id($admin_id, $company_id);
        if(!$check_town_)
            return $this->fun_fail('不可操作此区镇下企业!');
        $this->db->where(array('id' => $company_id, 'business_no' => $business_no))->update('company_pending', array('password' => sha1('123456')));
        return $this->fun_success('重置成功!');
    }

    //重置企业密码
    public function locking_company_zz($admin_id){
        $company_id = $this->input->post('id');
        $business_no = $this->input->post('data_name');
        $locking_zz = $this->input->post('locking_zz');
        if(!$company_id || !$business_no)
            return $this->fun_fail('信息缺失!');
        $check_town_ = $this->check_admin_townByCompany_id($admin_id, $company_id);
        if(!$check_town_)
            return $this->fun_fail('不可操作此区镇下企业!');
        switch($locking_zz){
            case 1:
                //如果是锁定 则在加入锁定状态时 还需要强制加入异常状态
                $this->db->where(array('id' => $company_id, 'business_no' => $business_no))->update('company_pending', array('locking_zz' => 1));
                //这里分开做动作是为了以后 强制锁定正常资质做准备
                $this->db->where(array('id' => $company_id, 'business_no' => $business_no))->update('company_pending', array('zz_status' => -1));
                break;
            case 0:
                //如果是解除锁定则在解除时重新评估企业资质状态
                $this->db->where(array('id' => $company_id, 'business_no' => $business_no))->update('company_pending', array('locking_zz' => 0));
                $this->save_company_total_score($company_id);
                break;
            default:
                return $this->fun_fail('信息缺失!');
        }
        return $this->fun_success('操作成功!');
    }

    //企业注销
    public function company_pending_cancel($admin_id){
        $company_id = $this->input->post('id');
        $cancel_remark = trim($this->input->post('cancel_remark'));
        if(!$company_id || !$cancel_remark)
            return $this->fun_fail('信息丢失!');
        $company_info = $this->db->select()->from('company_pending')->where('id', $company_id)->get()->row_array();
        if(!$company_info)
            return $this->fun_fail('未找到企业信息!');
        if(!in_array($company_info['flag'], array(1, 2)))
            return $this->fun_fail('企业状态已变更!');
        $check_town_ = $this->check_admin_townByCompany_id($admin_id, $company_id);
        if(!$check_town_)
            return $this->fun_fail('不可操作此区镇下的企业!');
        $this->db->trans_start();//--------开始事务
        $this->db->where(array('id' => $company_id))->where_in('flag', array(1,2))
            ->update('company_pending', array(
                'flag' => -1,
                'cancel_remark' => $cancel_remark,
                'cancel_date' => date('Y-m-d H:i:s',time()),
                'cancel_user' => $admin_id
            ));
        $agent_ids_ = $this->db->select()->from('agent')->where('company_id', $company_id)->get()->result_array();
        if($agent_ids_){
            $data_insert = array();
            foreach($agent_ids_ as $k_ => $v_){
                //[企业注销] 发生人事变动 ，经纪人所申请的人事申请自动作废
                $this->agent_apply_all_cancel($v_['id']);
                $data_insert[] = array(
                    'to_company_id'         =>      -1,
                    'to_company_name'       =>      null,
                    'from_company_id'       =>      $company_id,
                    'from_company_name'     =>      $company_info['company_name'],
                    'agent_id'              =>      $v_['id'],
                    'status'                =>      8,
                    'create_date'           =>      date('Y-m-d H:i:s',time()),
                );
            }
            $this->db->insert_batch('agent_track',$data_insert);
        }
        $this->db->where(array('company_id' => $company_id))->update('agent', array('company_id' => -1, 'wq' => 1, 'last_work_time' => time()));

        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
            return $this->fun_fail('注销失败!');
        } else {
            return $this->fun_success('注销成功!');
        }
    }
}
