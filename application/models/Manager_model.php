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
        $action_new = str_replace('edit', 'list', $action);
        $action_new = str_replace('add', 'list', $action_new);
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
        $this->db->select('a.*')->from('agent a');
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
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function agent_edit($id){
        $this->db->select('a.*')->from('agent a');
        //$this->db->join('company_pending b','a.company_id = b.id','left');
        //$this->db->join('company_pass c','c.company_id = b.id','left');
        $this->db->where('a.id',$id);
        $detail =  $this->db->get()->row_array();
        if(!$detail)
            return $detail;
        //$this->db->select('a.*')->from('agent_ns_list a');
        //$this->db->where('a.agent_id',$id);
        //$this->db->order_by('a.year','desc');
        //$detail['ns_list'] = $this->db->get()->result_array();
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
            if($info_['min_score'] == 0)
                $data['min_score'] = 0;
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
        if($info_['min_score'] == 0)
            return $this->fun_fail('0分分数线不可删除');
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
            $this->db->like('a.type_id', $data['type_id']);
        }
        if($data['status']){
            $this->db->like('a.status', $data['status']);
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
            $this->db->like('a.type_id', $data['type_id']);
        }
        if($data['status']){
            $this->db->like('a.status', $data['status']);
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
            'status' => trim($this->input->post('status')) ? trim($this->input->post('status')) : -1,
            'cdate' => date('Y-m-d H:i:s', time()),
        );
        $id = $this->input->post('id');
        if(!$data['event_name'] || !$data['type_id'] || !$data['score']){
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
        $data['agent_keyword'] = $this->input->get('agent_keyword')?trim($this->input->get('agent_keyword')):null;
        $data['event_keyword'] = $this->input->get('event_keyword')?trim($this->input->get('event_keyword')):null;
        $data['status'] = $this->input->get('status')?trim($this->input->get('status')):null;
        //获取总记录数
        $this->db->select('count(1) num')->from('event4agent_record a');
        $this->db->join('agent b', 'a.agent_id = b.id', 'left');
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
            $this->db->like('a.event_type_type', $type_type);
        }
        if($data['status']){
            $this->db->like('a.status', $data['status']);
        }
        $num = $this->db->get()->row();
        $data['total_rows'] = $num->num;

        //获取详细列
        $this->db->select('a.*, b.name agent_name_, b.job_code agent_job_code_')->from('event4agent_record a');
        $this->db->join('agent b', 'a.agent_id = b.id', 'left');
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
            $this->db->like('a.event_type_type', $type_type);
        }
        if($data['status']){
            $this->db->like('a.status', $data['status']);
        }
        $this->db->limit($this->limit, $offset = ($page - 1) * $this->limit);
        $this->db->order_by('a.record_id','desc');
        $data['res_list'] = $this->db->get()->result_array();
        return $data;
    }

    public function event4agent_GRecord_save($admin_id){
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
        $event_info_ = $this->readByID('event4agent_detail', 'id', $data['agent_id']);
        if(!$event_info_ || $event_info_['status'] != 1)
            return $this->fun_fail('所选事件状态异常!');
        if( $event_info_['type_id'] != $data['event_type_id'])
            return $this->fun_fail('所选事件类别与事件不符!');
        $type_info_ = $this->readByID('event4agent_type', 'id', $data['event_type_id']);
        if(!$type_info_ || $type_info_['status'] != 1)
            return $this->fun_fail('所选事件类别状态异常!');
        if ($type_info_['type'] != 1) {
           return $this->fun_fail("所选事件不属于 " . $event4agent_type[1] . " 事件!");
        }
        $data['event_name'] = $event_info_['event_name'];
        $data['event_type_name'] = $type_info_['event_type_name'];
        $data['score'] = $event_info_['score'];
        $data['event_type_type'] = $type_info_['type'];
        $res = $this->db->insert('event4agent_record', $data);
        if ($res) {
            return $this->fun_success('保存成功!');
        }
        return $this->fun_fail('保存失败!');
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
            if($info_['min_score'] == 0)
                $data['min_score'] = 0;
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
        if($info_['min_score'] == 0)
            return $this->fun_fail('0分分数线不可删除');
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
}
