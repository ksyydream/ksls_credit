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
        //获取总记录数
        $this->db->select('count(1) num')->from('agent a');
        //$this->db->join('company_pending b','a.company_id = b.id','left');
        //$this->db->join('company_pass c','b.id = c.company_id','left');
        if($data['keyword']){
            $this->db->group_start();
            $this->db->like('a.name', $data['keyword']);
            $this->db->or_like('a.job_code', $data['keyword']);
            $this->db->group_end();
        }
        if($data['flag']){
            $this->db->where('a.flag', $data['flag']);
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
            $this->db->group_end();
        }
        if($data['flag']){
            $this->db->where('a.flag', $data['flag']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function agent_edit($id){
        $this->db->select('a.*, b.company_name')->from('agent a');
        $this->db->join('company_pending b','a.company_id = b.id','left');
        //$this->db->join('company_pass c','c.company_id = b.id','left');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        if(!$detail)
            return $detail;
        return $detail;
    }

    public function agent_save(){
        $data = array(
            'name'=>trim($this->input->post('name')),
            'phone'=>trim($this->input->post('phone')) ? trim($this->input->post('phone')) : "",
            'job_code'=>trim($this->input->post('job_code')),
            'old_job_code'=>trim($this->input->post('old_job_code')),
            'flag' => $this->input->post('flag'),
            'card'=>trim($this->input->post('card')) ? trim($this->input->post('card')) : "",
            'pwd'=>sha1("666666"),
            'cdate' => date('Y-m-d H:i:s', time()),
        );
        $id = $this->input->post('id');
        if(!$data['name'] || !$data['job_code'] || !$data['flag'] || !$data['card']){
            return $this->fun_fail('缺少必要信息!');
        }
        if($id){
            $chenk_job = $this->db->select()->from('agent')->where('job_code', $data['job_code'])->where('id <>', $id)->get()->row_array();
            $chenk_card = $this->db->select()->from('agent')->where('card', $data['card'])->where('id <>', $id)->get()->row_array();
            if($chenk_job)
                return $this->fun_fail('此职业证号已存在!');
            if($chenk_card)
                return $this->fun_fail('此身份证号已存在!');
            unset($data['pwd']);
            unset($data['cdate']);
            //这里还需要判断 如果是离昆或者无效时 需要解绑公司,解绑公司后可能会让公司状态变更 得分产生变化
            $this->db->where('id', $id)->update('agent', $data);
        }else{
            $chenk_job = $this->db->select()->from('agent')->where('job_code', $data['job_code'])->get()->row_array();
            $chenk_card = $this->db->select()->from('agent')->where('card', $data['card'])->get()->row_array();
            if($chenk_job)
                return $this->fun_fail('此职业证号已存在!');
            if($chenk_card)
                return $this->fun_fail('此身份证号已存在!');
            //增加经纪人初始信用分
            $data['score'] = $this->config->item('agent_score');
            $this->db->insert('agent', $data);
        }
        return $this->fun_success('保存成功!');
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
        $this->db->order_by('a.min_score','desc');
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
        );
        if(!$data['grade_name']){
            return $this->fun_fail('请输入等级名称');
        }
        if(!isset($data['min_score'])){
            return $this->fun_fail('分数线设置异常');
        }
        if((int)$data['min_score'] < 0){
            return $this->fun_fail('分数线设置异常');
        }
        $grade_id = $this->input->post('grade_id');
        if($grade_id){
            $info_ = $this->readByID($table_, 'id', $grade_id);
            if(!$info_)
                return $this->fun_fail('等级不存在');
            if($info_['flag'] == -1)
                $data['min_score'] = $info_['min_score'];
            $res = $this->db->select('')->from($table_)->where(array('grade_name'=>$data['grade_name'],'id <>' => $grade_id))->get()->row_array();
            if($res)
                return $this->fun_fail('存在相同等级名称');
            $res1 = $this->db->select('')->from($table_)->where(array('min_score'=>$data['min_score'],'id <>' => $grade_id))->get()->row_array();
            if($res1)
                return $this->fun_fail('存在相同分数线');
            $res2 = $this->db->where('id',$this->input->post('grade_id'))->update($table_,$data);
        }else{
            $res = $this->db->select('')->from($table_)->where('grade_name',$data['grade_name'])->get()->row_array();
            if($res)
                return $this->fun_fail('存在相同等级名称');
            $res1 = $this->db->select('')->from($table_)->where('min_score',$data['min_score'])->get()->row_array();
            if($res1)
                return $this->fun_fail('存在相同分数线');
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
            'remark' => trim($this->input->post('remark')),
            'create_uid' => $admin_id,
            'status' => 1,
            'create_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['agent_id'] || !$data['event_type_id'] || !$data['event_id'] || !$data['record_fact'] || !$data['remark'] || !$data['event_date']){
            return $this->fun_fail('缺少必要信息!');
        }
        $agent_info_ = $this->readByID('agent', 'id', $data['agent_id']);
        if(!$agent_info_)
            return $this->fun_fail('所选经纪人异常!');
        if($agent_info_['score'] < 0)
            return $this->fun_fail('所选经纪人分数异常!');
        $event_info_ = $this->readByID('event4agent_detail', 'id', $data['event_id']);
        if(!$event_info_ || $event_info_['status'] != 1)
            return $this->fun_fail('所选事件状态异常!');
        if( $event_info_['type_id'] != $data['event_type_id'])
            return $this->fun_fail('所选事件类别与事件不符!');
        //检查事件是否存在 次数限制，并查看是否可以新建
        if ($event_info_['allow_times'] > 0) {
            $check_times_ = $this->db->select('count(1) num')->from('event4agent_record')->where(array('agent_id' => $data['agent_id'], 'event_id' => $data['event_id'], 'status' => 1))->get()->row_array();
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
        $data['score'] =  ($event4agent_type_index * $event_info_['score']);
        $data['event_type_type'] = $type_info_['type'];
        $new_score_ = $agent_info_['score'] + $data['score'];
       

        $res = $this->db->insert('event4agent_record', $data);
        if ($res) {
            $this->db->where(array('id' => $data['agent_id'], 'score' => $agent_info_['score']));
            $res_agent_ = $this->db->set('score', 'score + ' . $data['score'], FALSE)->update('agent');
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
        $agent_info_ = $this->readByID('agent', 'id', $record_info_['agent_id']);
        if(!$agent_info_)
            return $this->fun_fail('所选经纪人异常!');
        if($agent_info_['score'] < 0)
            return $this->fun_fail('所选经纪人分数异常!');
        $new_score_ = $agent_info_['score'] - $record_info_['score'];
        if($new_score_ < 0)
            return $this->fun_fail('所选经纪人分数不足!');
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
        $this->db->order_by('a.min_score','desc');
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
        );
        if(!$data['grade_name']){
            return $this->fun_fail('请输入等级名称');
        }
        if(!isset($data['min_score'])){
            return $this->fun_fail('分数线设置异常');
        }
        if((int)$data['min_score'] < 0){
            return $this->fun_fail('分数线设置异常');
        }
        $grade_id = $this->input->post('grade_id');
        if($grade_id){
            $info_ = $this->readByID($table_, 'id', $grade_id);
            if(!$info_)
                return $this->fun_fail('等级不存在');
            if($info_['flag'] == -1)
                $data['min_score'] = $info_['min_score'];
            $res = $this->db->select('')->from($table_)->where(array('grade_name'=>$data['grade_name'],'id <>' => $grade_id))->get()->row_array();
            if($res)
                return $this->fun_fail('存在相同等级名称');
            $res1 = $this->db->select('')->from($table_)->where(array('min_score'=>$data['min_score'],'id <>' => $grade_id))->get()->row_array();
            if($res1)
                return $this->fun_fail('存在相同分数线');
            $res2 = $this->db->where('id',$this->input->post('grade_id'))->update($table_,$data);
        }else{
            $res = $this->db->select('')->from($table_)->where('grade_name',$data['grade_name'])->get()->row_array();
            if($res)
                return $this->fun_fail('存在相同等级名称');
            $res1 = $this->db->select('')->from($table_)->where('min_score',$data['min_score'])->get()->row_array();
            if($res1)
                return $this->fun_fail('存在相同分数线');
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
        $data['record_num'] = $this->input->get('record_num')?trim($this->input->get('record_num')):null;
        $data['company_keyword'] = $this->input->get('company_keyword')?trim($this->input->get('company_keyword')):null;
        $data['event_keyword'] = $this->input->get('event_keyword')?trim($this->input->get('event_keyword')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4company_record a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
        if ($data['record_num']) {
            $this->db->where('b.record_num', $data['record_num']);
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
        $this->db->select('a.*, b.company_name new_company_name_, b.record_num record_num_')->from('event4company_record a');
        $this->db->join('company_pending b', 'a.company_id = b.id', 'left');
        if ($data['record_num']) {
            $this->db->where('b.record_num', $data['record_num']);
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
        $data['old_company_name'] = $company_info_['company_name'];
        $event_info_ = $this->readByID('event4company_detail', 'id', $data['event_id']);
        if(!$event_info_ || $event_info_['status'] != 1)
            return $this->fun_fail('所选事件状态异常!');
        if( $event_info_['type_id'] != $data['event_type_id'])
            return $this->fun_fail('所选事件类别与事件不符!');
        //检查事件是否存在 次数限制，并查看是否可以新建
        if ($event_info_['allow_times'] > 0) {
            $check_times_ = $this->db->select('count(1) num')->from('event4company_record')->where(array('company_id' => $data['company_id'], 'event_id' => $data['event_id'], 'status' => 1))->get()->row_array();
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
        $data['event_name'] = $event_info_['event_name'];
        $data['event_type_name'] = $type_info_['event_type_name'];
        $data['score'] =  ($event4company_type_index * $event_info_['score']);
        $data['event_type_type'] = $type_info_['type'];
        $new_score_ = $company_info_['score'] + $data['score'];
       

        $res = $this->db->insert('event4company_record', $data);
        if ($res) {
            $this->db->where(array('id' => $data['company_id'], 'score' => $company_info_['score']));
            $res_company_ = $this->db->set('score', 'score + ' . $data['score'], FALSE)->update('company_pending');
            //DBY重要
            //这里需要加入 企业分数变更时的处理
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
        $this->db->select('a.*, b.company_name new_company_name_, b.record_num record_num_')->from('event4company_record a');
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
        $new_score_ = $company_info_['score'] - $record_info_['score'];

        $data = array(
            'del_remark' => trim($this->input->post('del_remark')),
            'del_uid' => $admin_id,
            'status' => -1,
            'del_time' => date('Y-m-d H:i:s', time()),
        );
        if(!$data['del_remark']){
            return $this->fun_fail('缺少必要信息!');
        }
        $res = $this->db->where('record_id', $record_id)->update('event4company_record', $data);
         if ($res) {
            $this->db->where(array('id' => $record_info_['company_id'], 'score' => $company_info_['score']));
            $res_company_ = $this->db->set('score', 'score - ' . $record_info_['score'], FALSE)->update('company_pending');
            //DBY重要
            //这里需要加入 经纪人状态变更，企业分数更新和状态检查 可能还需要做相应的记录
            return $this->fun_success('作废成功!');
        }
        return $this->fun_fail('作废失败!');
    }

    /**
     *********************************************************************************************
     * 以下代码为企业管理
     *********************************************************************************************
     */

     /**
     * 历史原因 status 1代表等待初审，2代表审核通过，3代表审核失败，4代表等待终审
     * flag  1代表报备，2代表报备成功，-1代表作废
     * 企业列表 设计为通用
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_common_list($page = 1, $flag, $status = array()){
        $data['limit'] = $this->limit;
        //搜索条件
        $data['keyword'] = $this->input->get('keyword')?trim($this->input->get('keyword')):null;
       
        $this->db->select('count(1) num')->from('company_pending a');
        if($data['keyword']){
            $this->db->like('a.company_name', $data['keyword']);
        }
        if($flag){
            $this->db->where('a.flag',$flag);
        }
        if($status){
            $this->db->where_in('a.status',$status);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*')->from('company_pending a');
        if($data['keyword']){
            $this->db->like('a.company_name', $data['keyword']);
        }
        if($flag){
            $this->db->where('a.flag',$flag);
        }
        if($status){
            $this->db->where_in('a.status',$status);
        }
        $this->db->order_by('a.mdate','asc');
      
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function company_apply_edit($id){
        $this->db->select('a.*')->from('company_pending a');
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
        return $detail;
    }

    public function company_apply_save(){
        $data = array(
            'company_name'=>trim($this->input->post('company_name')),
            'register_path'=>trim($this->input->post('register_path')),
            'business_path'=>trim($this->input->post('business_path')),
            'issuing_date'=>trim($this->input->post('issuing_date')),
            'company_phone'=>trim($this->input->post('company_phone')),
            'director_name'=>trim($this->input->post('director_name')),
            'director_phone'=>trim($this->input->post('director_phone')),
            'legal_name'=>trim($this->input->post('legal_name')),
            'legal_phone'=>trim($this->input->post('legal_phone')),
            'cdate'=>date('Y-m-d H:i:s',time()),
            'mdate'=>date('Y-m-d H:i:s',time()),
            'score' => $this->config->item('company_score'),
            'username'=>$this->get_username(),
            'password'=>sha1('123456'),
            'status' => 1,
        );
        $company_id = $this->input->post('company_id');
        if(!$data['company_name'] || !$data['register_path'] || !$data['business_path'] || 
            !$data['issuing_date'] || !$data['company_phone'] || !$data['director_name'] || 
            !$data['director_phone'] || !$data['legal_name'] || !$data['legal_phone']){
            return $this->fun_fail('缺少必要信息!');
        }
        $this->load->model('common4manager_model', 'c4m_model');
        $check_company_name_ = $this->c4m_model->check_company_name($data['company_name'], $company_id);
        if($check_company_name_['status'] != 1)
            return $this->fun_fail($check_company_name_['msg']);
        $code_ = $this->input->post('agent_job_code');
        if ($code_ && is_array($code_)) {
            foreach($code_ as $idx => $card_) {
            $check_card = $this->c4m_model->check_code4get(trim($card_), $company_id);
            if($check_card['status'] != 1){
                 return $this->fun_fail($check_card['msg']);;exit();
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
        
        $save4track_old = array();
        $where_arr_ = array('id' => $company_id, 'status' => 1, 'flag' => 1);
        if($company_id){
             $company_info_ = $this->db->where($where_arr_)->from('company_pending')->get()->row_array();
             if (!$company_info_) {
                 return $this->fun_fail('企业状态变更不可修改!');
             }
        }
       
        $this->db->trans_start();//--------开始事务
        if($company_id){
            unset($data['cdate']);
            unset($data['username']);
            unset($data['password']);
            unset($data['status']);
            unset($data['flag']);
            $this->db->where('id', $company_id)->update('company_pending', $data);
            $this->db->select('a.*')->from('agent a');
            $this->db->where('a.company_id',$company_id);
            $save4track_old = $this->db->get()->result_array();
            $this->db->where($where_arr_)->update('company_pending', $data);
        }else{
            $this->db->insert('company_pending', $data);
            $company_id = $this->db->insert_id();
        }
        //处理经纪人
        $this->db->where('company_id',$company_id)->update('agent',array('company_id'=>-1));
        $arr_agent_job_code = $this->input->post('agent_job_code');
        $arr_agent_wq = $this->input->post('setwq');
        $arr_agent_company_id = array(
            'company_id' => $company_id,
        );
        if ($arr_agent_job_code && is_array($arr_agent_job_code)) {
             foreach($arr_agent_job_code as $idx => $pic) {
            $update_data4agent_ = $arr_agent_company_id;
            $update_data4agent_['wq'] = $arr_agent_wq[$idx];
            $this->db->where('job_code',$pic)->where('flag',2)->update('agent', $update_data4agent_);
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
        $this->db->trans_complete();//------结束事务
        if ($this->db->trans_status() === FALSE) {
           return $this->fun_fail('保存失败!');
        } else {
            $this->db->select('a.*')->from('agent a');
            $this->db->where('a.company_id',$company_id);
            $save4track_new = $this->db->get()->result_array();
            $this->save_agent_track($company_id, $data, $save4track_old, $save4track_new);
            return $this->fun_success('保存成功!');
        }
        
    }

    //报备通过
    public function company_apply_pass(){
        $company_id = $this->input->post('company_id');
        $record_num = trim($this->input->post('record_num'));
        if(!$company_id)
            return $this->fun_fail('信息缺失!');
        $company_data = $this->db->select('*')->from('company_pending')->where('id',$company_id)->get()->row_array();
        if(!$company_data)
            return $this->fun_fail('企业信息丢失!');
        if($company_data['flag'] != 1)
            return $this->fun_fail('企业状态变更不可通过!');
        $this->load->model('common4manager_model', 'c4m_model');
        $check_num_ = $this->c4m_model->check_record_num($record_num, $company_id);
        if($check_num_['status'] != 1)
            return $this->fun_fail('备案号已占用!');
        $check_name = $this->c4m_model->check_company_name($company_data['company_name'], $company_id);
        if($check_name['status'] != 1)
           return $this->fun_fail('企业名称已被申请!');
        //暂时不做判断
        $agents = $this->db->select()->from('agent')->where('flag',2)->where('company_id',$company_id)->get()->result_array();
        $data = array('zz_status'=>1,'record_num'=>$record_num,'flag'=>2,'status'=>2,'sdate'=>date('Y-m-d H:i:s',time()));
        if(count($agents) < 3)
            $data['zz_status'] = -1;
        //$this->log_company($company_id);

        //$data['username'] = $this->get_username($company_data['id']);
        $data['password'] = sha1('123456');
        $res = $this->db->where('id',$company_id)->update('company_pending',$data);
        //$this->company_ns_save($company_id,$data['status']);
        if($res){
            return $this->fun_success('通过成功!');
        }else{
            return $this->fun_fail('审核失败!');
        }
    }
}
