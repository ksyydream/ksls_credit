<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager extends MY_Controller {
    /**
     * 管理员操作控制器
     * @version 2.0
     * @author yangyang 
     * @date 2018-03-30
     * @Copyright (C) 2018, Tianhuan Co., Ltd.
    */
    private $admin_id = 0;

	public function __construct()
    {
        parent::__construct();
        $this->load->model('manager_model');
        $this->load->model('common4manager_model', 'c4m_model');
        $admin_info = $this->session->userdata('admin_info');
        $admin = $this->manager_model->get_admin($admin_info['admin_id']);
        if(!$admin){
           $this->logout();
        }
        $this->manager_model->save_admin_log($admin_info['admin_id']);
        $this->admin_id = $admin_info['admin_id'];
        if ($admin['group_id'] != 1 && !$this->manager_model->check($this->uri->segment(1) . '/' . $this->uri->segment(2), $admin_info['admin_id'])){
            if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest')
            {
              //echo -99;exit();
                $err_ = $this->manager_model->fun_fail('你没有操作权限');
                $this->ajaxReturn($err_);
            }
            else {
                $this->show_message('没有权限访问本页面!');
            }
        }
        $this->assign('admin', $admin);
        $current = $this->manager_model->get_action_menu($this->uri->segment(1),$this->uri->segment(2));
        $this->assign('current', $current);
        $menu = $this->manager_model->get_menu4admin($admin_info['admin_id']);
        $menu = $this->getMenu($menu);
        $this->assign('menu', $menu);
        $this->assign('self_url',$_SERVER['PHP_SELF']);
        $this->assign('group_id', $admin['group_id']);
        $allow_t = $this->manager_model->get_admin_t_list($this->admin_id);
        $this->assign('allow_t', $allow_t);
    }

    protected function getMenu($items, $id = 'id', $pid = 'pid', $son = 'children')
    {
        $tree = array();
        $tmpMap = array();
        //修复父类设置islink=0，但是子类仍然显示的bug @感谢linshaoneng提供代码
        foreach( $items as $item ){
            if( $item['pid']==0 ){
                $father_ids[] = $item['id'];
            }
        }
        //----
        foreach ($items as $item) {
            $tmpMap[$item[$id]] = $item;
        }

        foreach ($items as $item) {
            //修复父类设置islink=0，但是子类仍然显示的bug by shaoneng @感谢linshaoneng提供代码
            if( $item['pid']<>0 && !in_array( $item['pid'], $father_ids )){
                continue;
            }
            //----
            if (isset($tmpMap[$item[$pid]])) {
                $tmpMap[$item[$pid]][$son][] = &$tmpMap[$item[$id]];
            } else {
                $tree[] = &$tmpMap[$item[$id]];
            }
        }
        return $tree;
    }

    /**
     *********************************************************************************************
     * 以下代码为看板模块
     *********************************************************************************************
     */

    /**
     * 看板
     * @author yangyang 
     * @date 2018-03-30
     */
    public function index()
	{
        $this->display('manager/index/index.html');
	}


    /**
     *********************************************************************************************
     * 以下代码为系统设置模块
     *********************************************************************************************
     */

    /**
     * 后台菜单列表
     * @author yangyang 
     * @date 2018-04-01
     */
    public function menu_list(){
        $menu_all = $this->manager_model->get_menu_all();
        $data['res_list'] = $this->getMenu($menu_all);
        $this->assign('data', $data);
        $this->display('manager/menu/index.html');
    }

    /**
     * 新增后台菜单页面
     * @author yangyang 
     * @date 2018-04-01
     */
    public function menu_add(){
        $menu_all = $this->manager_model->get_menu_all();
        $data['res_list'] = $this->getMenu($menu_all);
        $this->assign('data', $data);
        $this->display('manager/menu/form.html');
    }

    /**
     * 编辑后台菜单
     * @author yangyang 
     * @date 2018-04-01
     */
    public function menu_edit($id){
        $data = $this->manager_model->menu_info($id);
        if(!$data){
            $this->show_message('未找到菜单信息!');
        }
        $menu_all = $this->manager_model->get_menu_all();
        $data['res_list'] = $this->getMenu($menu_all);
        $this->assign('data', $data);
        $this->display('manager/menu/form.html');
    }

    /**
     * 保存后台菜单
     * @author yangyang 
     * @date 2018-04-01
     */
    public function menu_save(){
        $res = $this->manager_model->menu_save();
        if($res == 1){
            $this->show_message('保存成功!', site_url('/manager/menu_list'));
        }elseif($res == -2){
            $this->show_message('信息不全,保存失败!');
        }else{
            $this->show_message('保存失败!');
        }
    }

    /**
     * 删除后台菜单
     * @author yangyang 
     * @date 2018-04-01
     */
    public function menu_del($id){
        $res = $this->manager_model->menu_del($id);
        $this->ajaxReturn($res);
    }

    /**
     * 编辑 人事申请提交提示
     * @author yangyang
     * @date 2018-04-01
     */
    public function apply_msg(){
        $data = $this->manager_model->config_detail('apply_msg');
        $this->assign('data', $data);
        $this->display('manager/config/apply_msg.html');
    }

    //保存 人事申请提醒
    public function apply_msg_save(){
        $res = $this->manager_model->config_save('apply_msg');
        $this->ajaxReturn($res);
    }

    /**
     *********************************************************************************************
     * 以下代码为个人中心模块
     *********************************************************************************************
     */

    /**
     * 管理员管理
     * @author yangyang 
     * @date 2018-03-31
     */
    public function admin_list($page=1){
        $data = $this->manager_model->admin_list($page);
        $base_url = "/manager/admin_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/admin/index.html');
    }

    /**
     * 新增管理员管理
     * @author yangyang 
     * @date 2018-03-31
     */
    public function admin_add(){
        $groups = $this->manager_model->get_group_all();
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('data', array());
        $this->assign('town_list', $town_list);
        $this->assign('t_list', array());
        $this->assign('groups', $groups);
        $this->display('manager/admin/form.html');
    }

    /**
     * 编辑管理员管理
     * @author yangyang 
     * @date 2018-03-31
     */
    public function admin_edit($id){
        $data = $this->manager_model->get_admin($id);
        if(!$data){
            $this->show_message('未找到管理员信息!');
        }
        $groups = $this->manager_model->get_group_all();
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $t_list = $this->manager_model->get_admin_t_list($data['admin_id']);
        $this->assign('t_list', $t_list);
        $this->assign('data', $data);
        $this->assign('groups', $groups);
        $this->display('manager/admin/form.html');
    }

    /**
     * 保存管理员管理
     * @author yangyang 
     * @date 2018-03-31
     */
    public function admin_save(){
        $res = $this->manager_model->admin_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/admin_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 删除管理员
     * @author yangyang 
     * @date 2018-04-01
     */
    public function admin_del($id){
        $res =  $this->manager_model->admin_del($id);
        $this->ajaxReturn($res);
    }

    /**
     * 新增用户组
     * @author yangyang 
     * @date 2018-03-31
     */
    public function group_add(){
        $group = array();
        $group['rules']=array(1,48,49,50,55);//默认选择 5个菜单
        $menu_all = $this->manager_model->get_menu_all();
        $menu_all = $this->getMenu($menu_all);
        $this->assign('rule', $menu_all);
        $this->assign('group', $group);
        $this->display('manager/group/form.html');
    }

    /**
     * 编辑用户组
     * @author yangyang 
     * @date 2018-03-31
     */
    public function group_edit($id){
        $group =  $this->manager_model->get_group_detail($id);
        if($group == -1){
            $this->show_message('未找到用户组信息!', site_url('/manager/group_list'));
        }
        $menu_all = $this->manager_model->get_menu_all();
        $menu_all = $this->getMenu($menu_all);
        $this->assign('rule', $menu_all);
        $this->assign('group', $group);
        $this->display('manager/group/form.html');
    }

    /**
     * 保存用户组
     * @author yangyang 
     * @date 2018-03-31
     */
    public function group_save(){
        $res = $this->manager_model->group_save();
        if($res == 1){
            $this->show_message('保存成功!',site_url('/manager/group_list'));
        }else{
            $this->show_message('保存失败!');
        }
    }

    /**
     * 用户组列表
     * @author yangyang 
     * @date 2018-03-31
     */
    public function group_list($page=1){
        $data = $this->manager_model->group_list($page);
        $base_url = "/manager/group_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/group/index.html');
    }

    /**
     * 删除用户组
     * @author yangyang 
     * @date 2018-03-31
     */
    public function group_del($id){
        $res =  $this->manager_model->group_del($id);
        $this->ajaxReturn($res);
    }

    /**
     * 个人资料页面
     * @author yangyang 
     * @date 2018-04-01
     */
    public function personal_info(){
        $data = $this->manager_model->get_admin($this->admin_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/personal/profile.html');
    }

    /**
     * 保存管理员管理
     * @author yangyang 
     * @date 2018-04-01
     */
    public function personal_save(){
        $res = $this->manager_model->personal_save($this->admin_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/personal_info'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 退出
     * @author yangyang 
     * @date 2018-03-30
     */
    public function logout(){
        $this->session->sess_destroy();
        redirect(base_url('/manager_login/index'));
    }

    /**
     *********************************************************************************************
     * 以下代码为经纪人管理
     *********************************************************************************************
     */

    //查看经纪人证书
    public function get_cert4agent($agent_id){
        $this->load->model('agent_model');
        $data = $this->agent_model->get_detail4self($agent_id);
        if(!$data)
            redirect('/home/index');
        //判断是否可以生成证书
        if($data && strlen($data['job_code']) == 6 && in_array(substr($data['job_code'],0,2), array('20','19'))) {

        }else{
            redirect('/home/index');
        }
        $this->assign('data', $data);
        $show_person_ = '';
        if($data['person_img_list'])
            $show_person_ = $data['person_img_list'][0]['img'];
        $this->assign('show_person_', $show_person_);
        $this->display('homepage/agent/dianzizhengshu.html');
    }


    /**
     * 执业经纪人列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function agent_list($page = 1){
        $data = $this->manager_model->agent_list($page);
        $base_url = "/manager/agent_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/agent/agent_list.html');
    }

    /**
     * 执业经纪人新增页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function agent_add(){
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
        $this->display('manager/agent/agent_detail.html');
    }

    public function agent_edit($m_id){
        $data = $this->manager_model->agent_edit($m_id);
        if(!$data){
            $this->show_message('未找到执业经纪人信息!');
        }
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
        $this->assign('data', $data);
        $this->display('manager/agent/agent_detail.html');
    }

    /**
     * 执业经纪人保存页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function agent_save(){
        $res = $this->manager_model->agent_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/agent_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function agent_view($m_id){
        $data = $this->manager_model->agent_edit($m_id);
        if(!$data){
            $this->show_message('未找到执业经纪人信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/agent/agent_view.html');
    }

    //重置经纪人密码
    public function refresh_agent_password(){
        $res = $this->manager_model->refresh_agent_password();
        $this->ajaxReturn($res);
    }

    /**
     * 执业经纪人人事申请列表
     * @author yangyang
     * @date 2019-12-20
     */
    public function agent_apply_list($page = 1){
        $data = $this->manager_model->agent_apply_list($page);
        $base_url = "/manager/agent_apply_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/agent/agent_apply_list.html');
    }

    //人事申请详情
    public function agent_apply_view($m_id){
        $data = $this->manager_model->agent_apply_view($m_id);
        if(!$data){
            $this->show_message('未找到人事信息信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/agent/agent_apply_detail.html');
    }

    //人事申请作废
    public function agent_apply_cancel(){
        $res = $this->manager_model->agent_apply_handle(-1);
        $this->ajaxReturn($res);
    }

    //人事申请通过
    public function agent_apply_submit(){
        $res = $this->manager_model->agent_apply_handle(2);
        $this->ajaxReturn($res);
    }

    //20201009 从业人员申请
    public function employees_list($page = 1){
        $data = $this->manager_model->employees_list($page);
        $base_url = "/manager/employees_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->display('manager/agent/employees_list.html');
    }

    //20201009 从业人员申请详情
    public function employees_audit($id){
        $data = $this->manager_model->employees_audit($id);
        if(!$data){
            $this->show_message('未找到从业申请信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/agent/employees_view.html');
    }

    //20201009 从业人员申请通过
    public function employees_pass(){
        $res = $this->manager_model->employees_apply_handle($this->admin_id, 2);
        $this->ajaxReturn($res);
    }

    //20201009 从业人员申请拒绝
    public function employees_cancel(){
        $res = $this->manager_model->employees_apply_handle($this->admin_id, -1);
        $this->ajaxReturn($res);
    }

    /**
     * 从业黑名单列表
     * @author yangyang
     * @date 2020-12-12
     */
    public function agent_blacklist_list($page = 1){
        $data = $this->manager_model->agent_blacklist_list($page);
        $base_url = "/manager/agent_blacklist_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/agent/agent_blacklist_list.html');
    }

    /**
     * 从业黑名单新增页面
     * @author yangyang
     * @date 2020-12-12
     */
    public function agent_blacklist_add(){
        $this->display('manager/agent/agent_blacklist_detail.html');
    }

    public function agent_blacklist_edit($m_id){
        $data = $this->manager_model->agent_blacklist_edit($m_id);
        if(!$data){
            $this->show_message('未找到从业黑名单信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/agent/agent_blacklist_view.html');
    }

    /**
     * 从业黑名单保存页面
     * @author yangyang
     * @date 2020-12-12
     */
    public function agent_blacklist_save(){
        $res = $this->manager_model->agent_blacklist_save($this->admin_id);
        $this->ajaxReturn($res);
    }

    public function agent_blacklist_cancel(){
        $res = $this->manager_model->agent_blacklist_cancel($this->admin_id);
        $this->ajaxReturn($res);
    }
    /**
     *********************************************************************************************
     * 经纪人事件
     *********************************************************************************************
     */

    /**
     * 经纪人信用等级
     * @author yangyang
     * @date 2019-11-09
     */
    public function agent_grade_list($page = 1){
        $data = $this->manager_model->agent_grade_list($page);
        $base_url = "/manager/agent_grade_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/event_agent/agent_grade_list.html');
    }

    public function agent_grade_add(){
        $this->assign('s_no', array(1,2));
        $this->display('manager/event_agent/agent_grade_detail.html');
    }

    public function agent_grade_edit($id){
        $data = $this->manager_model->agent_grade_edit($id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('s_no', array(1,2));
        $this->assign('data', $data);
        $this->display('manager/event_agent/agent_grade_detail.html');
    }

    public function agent_grade_save(){
        $res = $this->manager_model->agent_grade_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/agent_grade_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function agent_grade_delete($id){
        $res = $this->manager_model->agent_grade_delete($id);
        $this->ajaxReturn($res);
    }


    /**
     * 经纪人事件一级列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4agent_type_list($page = 1){
        $data = $this->manager_model->event4agent_type_list($page);
        $event4agent_type = $this->config->item('event4agent_type');
        $event4agent_type4label = $this->config->item('event4agent_type4label');
        $base_url = "/manager/event4agent_type_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->assign('event4agent_type', $event4agent_type);
        $this->assign('event4agent_type4label', $event4agent_type4label);
        $this->display('manager/event_agent/event4agent_type_list.html');
    }

    /**
     * 经纪人事件一级新增页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4agent_type_add(){
        $event4agent_type = $this->config->item('event4agent_type');
        $event4agent_type4label = $this->config->item('event4agent_type4label');
        $this->assign('event4agent_type4label', $event4agent_type4label);
        $this->assign('event4agent_type', $event4agent_type);
        $this->display('manager/event_agent/event4agent_type_detail.html');
    }

    /**
     * 经纪人事件一级保存页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4agent_type_save(){
        $res = $this->manager_model->event4agent_type_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4agent_type_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function event4agent_type_edit($m_id){
        $data = $this->manager_model->event4agent_type_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $event4agent_type = $this->config->item('event4agent_type');
        $this->assign('event4agent_type', $event4agent_type);
        $event4agent_type4label = $this->config->item('event4agent_type4label');
        $this->assign('event4agent_type4label', $event4agent_type4label);
        $this->assign('data', $data);
        $this->display('manager/event_agent/event4agent_type_detail.html');
    }

    /**
     * 经纪人事件二级列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function event4agent_detail_list($page = 1){
        $data = $this->manager_model->event4agent_detail_list($page);
        $event_type_all = $this->c4m_model->get_event4agent_type_all();
        $event4agent_type = $this->config->item('event4agent_type');
        $base_url = "/manager/event4agent_detail_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->assign('event4agent_type', $event4agent_type);
        $event4agent_type4label = $this->config->item('event4agent_type4label');
        $this->assign('event4agent_type4label', $event4agent_type4label);
        $this->assign('event_type_all', $event_type_all);
        $this->display('manager/event_agent/event4agent_detail_list.html');
    }

    /**
     * 经纪人事件二级新增页面
     * @author yangyang
     * @date 2019-11-14
     */
    public function event4agent_detail_add(){
        $event_type_all = $this->c4m_model->get_event4agent_type_all(1);
        $this->assign('event_type_all', $event_type_all);
        $this->display('manager/event_agent/event4agent_detail_detail.html');
    }

     /**
     * 经纪人事件二级保存页面
     * @author yangyang
     * @date 2019-11-14
     */
    public function event4agent_detail_save(){
        $res = $this->manager_model->event4agent_detail_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4agent_detail_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

     public function event4agent_detail_edit($m_id){
        $data = $this->manager_model->event4agent_detail_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
         $event_type_all = $this->c4m_model->get_event4agent_type_all(1);
        $this->assign('event_type_all', $event_type_all);
        $this->assign('data', $data);
        $this->display('manager/event_agent/event4agent_detail_detail.html');
    }

    /**
     * 经纪人事件(良好信用)列表
     * @author yangyang
     * @date 2019-11-14
     */

    public function event4agent_GRecord_list($page = 1){
        $data = $this->manager_model->event4agent_record_list($page, 1);
        $base_url = "/manager/event4agent_GRecord_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $event_type_all = $this->c4m_model->get_event4agent_type_all(null, 1);
        $this->assign('event_type_all', $event_type_all);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/event_agent/event4agent_GRecord_list.html');
    }

    //新增
    public function event4agent_GRecord_add(){
        $event_type_all = $this->c4m_model->get_event4agent_type_all(1,1);
        $this->assign('event_type_all', $event_type_all);
        $get_agent_all = $this->c4m_model->get_agent_all(2);
        $this->assign('agent_all', $get_agent_all);
        $this->assign('return_url', '/manager/event4agent_GRecord_list');
        $this->assign('save_url', '/manager/event4agent_GRecord_save');
        $this->display('manager/event_agent/event4agent_Record_add.html');
    }

    //保存
    public function event4agent_GRecord_save(){
         $res = $this->manager_model->event4agent_Record_save($this->admin_id, 1);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4agent_GRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //修改
    public function event4agent_GRecord_update(){
         $res = $this->manager_model->event4agent_Record_update($this->admin_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4agent_GRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //编辑
    public function event4agent_GRecord_edit($m_id){
        $data = $this->manager_model->event4agent_Record_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('data', $data);
        $this->assign('return_url', '/manager/event4agent_GRecord_list');
        $this->assign('update_url', '/manager/event4agent_GRecord_update');
        $this->assign('cancel_url', '/manager/event4agent_GRecord_cancel');
        $this->display('manager/event_agent/event4agent_Record_edit.html');
    }

    //作废
    public function event4agent_GRecord_cancel(){
         $res = $this->manager_model->event4agent_Record_cancel($this->admin_id);
        $this->ajaxReturn($res);
    }

     /**
     * 经纪人事件(不良信用)列表
     * @author yangyang
     * @date 2019-11-14
     */

    public function event4agent_BRecord_list($page = 1){
        $data = $this->manager_model->event4agent_record_list($page, -1);
        $base_url = "/manager/event4agent_BRecord_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $event_type_all = $this->c4m_model->get_event4agent_type_all(null, -1);
        $this->assign('event_type_all', $event_type_all);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/event_agent/event4agent_BRecord_list.html');
    }

    //新增
    public function event4agent_BRecord_add(){
        $event_type_all = $this->c4m_model->get_event4agent_type_all(1, -1);
        $this->assign('event_type_all', $event_type_all);
        $get_agent_all = $this->c4m_model->get_agent_all(2);
        $this->assign('agent_all', $get_agent_all);
        $this->assign('return_url', '/manager/event4agent_BRecord_list');
        $this->assign('save_url', '/manager/event4agent_BRecord_save');
        $this->display('manager/event_agent/event4agent_Record_add.html');
    }

    //保存
    public function event4agent_BRecord_save(){
         $res = $this->manager_model->event4agent_Record_save($this->admin_id, -1);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4agent_BRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //修改
    public function event4agent_BRecord_update(){
         $res = $this->manager_model->event4agent_Record_update($this->admin_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4agent_BRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //编辑
    public function event4agent_BRecord_edit($m_id){
        $data = $this->manager_model->event4agent_Record_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('data', $data);
        $this->assign('return_url', '/manager/event4agent_BRecord_list');
        $this->assign('update_url', '/manager/event4agent_BRecord_update');
        $this->assign('cancel_url', '/manager/event4agent_BRecord_cancel');
        $this->display('manager/event_agent/event4agent_Record_edit.html');
    }

    //作废
    public function event4agent_BRecord_cancel(){
         $res = $this->manager_model->event4agent_Record_cancel($this->admin_id);
        $this->ajaxReturn($res);
    }

    /**
     *********************************************************************************************
     * 企业事件
     *********************************************************************************************
     */

    /**
     * 企业信用等级
     * @author yangyang
     * @date 2019-11-09
     */
    public function company_grade_list($page = 1){
        $data = $this->manager_model->company_grade_list($page);
        $base_url = "/manager/company_grade_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/event_company/company_grade_list.html');
    }

    public function company_grade_add(){
        $this->assign('s_no', array(1,2));
        $this->display('manager/event_company/company_grade_detail.html');
    }

    public function company_grade_edit($id){
        $data = $this->manager_model->company_grade_edit($id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('s_no', array(1,2));
        $this->assign('data', $data);
        $this->display('manager/event_company/company_grade_detail.html');
    }

    public function company_grade_save(){
        $res = $this->manager_model->company_grade_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/company_grade_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function company_grade_delete($id){
        $res = $this->manager_model->company_grade_delete($id);
        $this->ajaxReturn($res);
    }


    /**
     * 企业事件一级列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4company_type_list($page = 1){
        $data = $this->manager_model->event4company_type_list($page);
        $event4company_type = $this->config->item('event4company_type');
        $base_url = "/manager/event4company_type_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->assign('event4company_type', $event4company_type);
        $event4company_type4label = $this->config->item('event4company_type4label');
        $this->assign('event4company_type4label', $event4company_type4label);
        $this->display('manager/event_company/event4company_type_list.html');
    }

    /**
     * 企业事件一级新增页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4company_type_add(){
        $event4company_type = $this->config->item('event4company_type');
        $this->assign('event4company_type', $event4company_type);
        $event4company_type4label = $this->config->item('event4company_type4label');
        $this->assign('event4company_type4label', $event4company_type4label);
        $this->display('manager/event_company/event4company_type_detail.html');
    }

    /**
     * 企业事件一级保存页面
     * @author yangyang
     * @date 2019-11-09
     */
    public function event4company_type_save(){
        $res = $this->manager_model->event4company_type_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4company_type_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function event4company_type_edit($m_id){
        $data = $this->manager_model->event4company_type_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $event4company_type = $this->config->item('event4company_type');
        $this->assign('event4company_type', $event4company_type);
        $event4company_type4label = $this->config->item('event4company_type4label');
        $this->assign('event4company_type4label', $event4company_type4label);
        $this->assign('data', $data);
        $this->display('manager/event_company/event4company_type_detail.html');
    }

    /**
     * 企业事件二级列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function event4company_detail_list($page = 1){
        $data = $this->manager_model->event4company_detail_list($page);
        $event_type_all = $this->c4m_model->get_event4company_type_all();
        $event4company_type = $this->config->item('event4company_type');
        $base_url = "/manager/event4company_detail_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->assign('event4company_type', $event4company_type);
        $event4company_type4label = $this->config->item('event4company_type4label');
        $this->assign('event4company_type4label', $event4company_type4label);
        $this->assign('event_type_all', $event_type_all);
        $this->display('manager/event_company/event4company_detail_list.html');
    }

    /**
     * 企业事件二级新增页面
     * @author yangyang
     * @date 2019-11-14
     */
    public function event4company_detail_add(){
        $event_type_all = $this->c4m_model->get_event4company_type_all(1);
        $this->assign('event_type_all', $event_type_all);
        $this->display('manager/event_company/event4company_detail_detail.html');
    }

    /**
     * 企业事件二级保存页面
     * @author yangyang
     * @date 2019-11-14
     */
    public function event4company_detail_save(){
        $res = $this->manager_model->event4company_detail_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4company_detail_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function event4company_detail_edit($m_id){
        $data = $this->manager_model->event4company_detail_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $event_type_all = $this->c4m_model->get_event4company_type_all(1);
        $this->assign('event_type_all', $event_type_all);
        $this->assign('data', $data);
        $this->display('manager/event_company/event4company_detail_detail.html');
    }


    /**
     * 企业事件(良好信用)列表
     * @author yangyang
     * @date 2019-11-26
     */

    public function event4company_GRecord_list($page = 1){
        $data = $this->manager_model->event4company_record_list($page, 1);
        $base_url = "/manager/event4company_GRecord_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $event_type_all = $this->c4m_model->get_event4company_type_all(null, 1);
        $this->assign('event_type_all', $event_type_all);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/event_company/event4company_GRecord_list.html');
    }

    //新增
    public function event4company_GRecord_add(){
        $event_type_all = $this->c4m_model->get_event4company_type_all(1,1);
        $this->assign('event_type_all', $event_type_all);
        $get_company_all = $this->c4m_model->get_company_all(2);
        $this->assign('company_all', $get_company_all);
        $grade_list = $this->c4m_model->get_company_grade_all();
        $this->assign('grade_list', $grade_list);
        $this->assign('return_url', '/manager/event4company_GRecord_list');
        $this->assign('save_url', '/manager/event4company_GRecord_save');
        $this->display('manager/event_company/event4company_Record_add.html');
    }

    //保存
    public function event4company_GRecord_save(){
         $res = $this->manager_model->event4company_Record_save($this->admin_id, 1);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4company_GRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //修改
    public function event4company_GRecord_update(){
         $res = $this->manager_model->event4company_Record_update($this->admin_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4company_GRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //编辑
    public function event4company_GRecord_edit($m_id){
        $data = $this->manager_model->event4company_Record_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('data', $data);
        $grade_list = $this->c4m_model->get_company_grade_all();
        $this->assign('grade_list', $grade_list);
        $this->assign('return_url', '/manager/event4company_GRecord_list');
        $this->assign('update_url', '/manager/event4company_GRecord_update');
        $this->assign('cancel_url', '/manager/event4company_GRecord_cancel');
        $this->display('manager/event_company/event4company_Record_edit.html');
    }

    //作废
    public function event4company_GRecord_cancel(){
         $res = $this->manager_model->event4company_Record_cancel($this->admin_id);
        $this->ajaxReturn($res);
    }

     /**
     * 企业事件(不良信用)列表
     * @author yangyang
     * @date 2019-11-26
     */

    public function event4company_BRecord_list($page = 1){
        $data = $this->manager_model->event4company_record_list($page, -1);
        $base_url = "/manager/event4company_BRecord_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $event_type_all = $this->c4m_model->get_event4company_type_all(null, -1);
        $this->assign('event_type_all', $event_type_all);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/event_company/event4company_BRecord_list.html');
    }

    //新增
    public function event4company_BRecord_add(){
        $event_type_all = $this->c4m_model->get_event4company_type_all(1, -1);
        $this->assign('event_type_all', $event_type_all);
        $get_company_all = $this->c4m_model->get_company_all(2);
        $this->assign('company_all', $get_company_all);
        $grade_list = $this->c4m_model->get_company_grade_all();
        $this->assign('grade_list', $grade_list);
        $this->assign('return_url', '/manager/event4company_BRecord_list');
        $this->assign('save_url', '/manager/event4company_BRecord_save');
        $this->display('manager/event_company/event4company_Record_add.html');
    }

    //保存
    public function event4company_BRecord_save(){
         $res = $this->manager_model->event4company_Record_save($this->admin_id, -1);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4company_BRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //修改
    public function event4company_BRecord_update(){
         $res = $this->manager_model->event4company_Record_update($this->admin_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/event4company_BRecord_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    //编辑
    public function event4company_BRecord_edit($m_id){
        $data = $this->manager_model->event4company_Record_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $grade_list = $this->c4m_model->get_company_grade_all();
        $this->assign('grade_list', $grade_list);
        $this->assign('data', $data);
        $this->assign('return_url', '/manager/event4company_BRecord_list');
        $this->assign('update_url', '/manager/event4company_BRecord_update');
        $this->assign('cancel_url', '/manager/event4company_BRecord_cancel');
        $this->display('manager/event_company/event4company_Record_edit.html');
    }

    //作废
    public function event4company_BRecord_cancel(){
         $res = $this->manager_model->event4company_Record_cancel($this->admin_id);
        $this->ajaxReturn($res);
    }

     /**
     *********************************************************************************************
     * 以下代码为年审设置
     *********************************************************************************************
     */

      /**
     * 年审时间列表
     * @author yangyang
     * @date 2019-11-09
     */
    public function term_list($page = 1){
        $data = $this->manager_model->term_list($page);
        $base_url = "/manager/term_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/term/term_list.html');
    }

    public function term_add(){
        $this->display('manager/term/term_detail.html');
    }

    public function term_edit($id){
        $data = $this->manager_model->term_edit($id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        $this->assign('data', $data);
        $this->display('manager/term/term_detail.html');
    }

    public function term_save(){
        $res = $this->manager_model->term_save($this->admin_id);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/term_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function term_delete($id){
        $res = $this->manager_model->term_delete($id);
        $this->ajaxReturn($res);
    }

    /**
     *********************************************************************************************
     * 以下代码为企业管理
     *********************************************************************************************
     */

    /**
     * 企业报备列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pending_list($page = 1){
        $data = $this->manager_model->company_pending_list($page, array(1,2));
        $base_url = "/manager/company_pending_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $is_ns_ = $this->c4m_model->check_is_ns_time();
        $this->assign('is_ns', -1);
        if($is_ns_['status'] == 1)
            $this->assign('is_ns', 1);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->display('manager/company/company_apply_list.html');
    }


    /**
     * 企业报备保存页面 用于测试
     * @author yangyang
     * @date 2019-11-18
     */
    public function company_pending_add(){
        $icon_list = $this->c4m_model->get_company_sys_icon();
        $this->assign('icon_list', $icon_list);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
        $this->assign('m_id', -1);
        $this->display('manager/company/company_apply_add.html');
    }

    public function company_pending_edit($m_id){
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
         $data = $this->manager_model->company_pending_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if (!in_array($data['flag'], array(1,2)))
            $this->show_message('企业信息已不在报备列表!');
        $icon_list = $this->c4m_model->get_company_sys_icon();
        foreach ($icon_list as $k1 => $v1) {
            foreach ($data['icon'] as $k2 => $v2) {
               if ($v1['icon_no'] == $v2['icon_no']) {
                   $icon_list[$k1]['is_check_'] = 1;
               }
            }
        }
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->assign('icon_list', $icon_list);
        $this->assign('data', $data);
        $this->assign('m_id', $m_id);
        $this->assign('reload_url', '/manager/company_apply_list');
        $this->display('manager/company/company_apply_add.html');
    }

    //查看企业证书
    public function get_cert($ns_id, $company_id){
        $this->load->model('company_model');
        $data = $this->company_model->get_cert($ns_id, $company_id);
        if(!$data)
            redirect('/home/index');
        $this->assign('data', $data);
        $this->display('homepage/company/dianzizhengshu.html');
    }


    //年审提报 编辑页面
    public function company_pending_audit($m_id){
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
         $data = $this->manager_model->company_pending_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if (!in_array($data['flag'], array(1,2)))
            $this->show_message('企业信息已不在报备列表!');
        $is_ns_ = $this->c4m_model->check_is_ns_time();
        if($is_ns_['status'] != 1)
            $this->show_message('不在年审窗口期,不可提交年审!');
        $this->assign('data', $data);
        $this->assign('reload_url', '/manager/company_apply_list');
        $this->display('manager/company/company_apply_audit.html');
    }

    //年审 提交页面
    public function company_pending_pass(){
        $res = $this->manager_model->company_pending_pass($this->admin_id);
        $this->ajaxReturn($res);
    }

    //企业信息保存
    public function company_pending_save(){
        $res = $this->manager_model->company_pending_save($this->admin_id);
        $this->ajaxReturn($res);
    }

    public function company_pending_cancel(){
        $res = $this->manager_model->company_pending_cancel($this->admin_id);
        $this->ajaxReturn($res);
    }

    //20201009 企业人员列表
    public function company_pending_temp($page = 1){
        //先检查 company_id 是否合法
        if(!$company_id = $this->input->get('company_id'))
            $this->show_message('信息异常');
        $company_info = $this->manager_model->company_pending_edit($company_id);
        if(!$company_info || $company_info['flag'] == -1)
            $this->show_message('企业信息异常');
        $res_check_town_ = $this->manager_model->check_admin_townByTown_id($this->admin_id,$company_info['town_id']);
        if(!$res_check_town_)
            $this->show_message('不可操作此区镇企业');
        $data = $this->manager_model->company_pending_temp($company_id,$page);
        $base_url = "/manager/company_pending_temp/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);

        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        unset($company_info['agent']);
        $this->assign('company_info', $company_info);
        $this->display('manager/company/company_pending_temp.html');
    }
    //20201009 企业人员添加
    public function company_pending_add_agent(){
        $res = $this->manager_model->company_pending_add_agent($this->admin_id);
        $this->ajaxReturn($res);
    }
    //20201009 企业人员删除
    public function company_pending_delete_agent(){
        $res = $this->manager_model->company_pending_delete_agent($this->admin_id);
        $this->ajaxReturn($res);
    }
    //20201009 企业人员设置网签
    public function company_pending_wq_agent(){
        $res = $this->manager_model->company_pending_wq_agent($this->admin_id);
        $this->ajaxReturn($res);
    }

    public function company_pending_down_excel(){
        ini_set('memory_limit', '64M');
        $data_res = $this->manager_model->company_pending_list_all(array(1,2));
        if($data_res['total_rows'] == 0){
            $this->show_message('没有信息可以导出');
        }
        require_once (APPPATH . 'libraries/PHPExcel/PHPExcel.php');
        $excel  = new \PHPExcel ();

        $excel->getActiveSheet()->setCellValue("A1","诚信系统企业信息导出");
        $excel->getActiveSheet()->mergeCells('A1:N2');
        $excel->getActiveSheet()->getStyle('A1')->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $excel->getActiveSheet()->getStyle('A1')->getFont()->setSize(26);
        $letter = array('A','B','C','D','E','F','G','H','I','J','K','L','M');
        $tableheader = array('企业名称','统一社会信用代码','注册地址','经营地址','企业电话','负责人姓名','负责人电话', '法人姓名','法人电话','分支机构数量','当前信用分','信用等级','所属区镇');
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]3","$tableheader[$i]");
            $excel->getActiveSheet()->getStyle("$letter[$i]3")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        $excel->getActiveSheet()->getColumnDimension("A")->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension("B")->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension("C")->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension("D")->setWidth(30);
        $excel->getActiveSheet()->getColumnDimension("E")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("F")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("G")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("H")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("I")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("J")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("K")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("L")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("M")->setWidth(18);
        $data = array();

        foreach ($data_res['res_list'] as $k=>$v){
            $data[] = array($v['company_name'],$v['business_no'],$v['register_path'],$v['business_path'],$v['company_phone']
            ,$v['director_name'],$v['director_phone'],$v['legal_name'],$v['legal_phone'],$v['fz_num'],$v['total_score'],$v['grade_name'],$v['town_name_']);
        }

        for ($i = 4;$i <= count($data) + 3;$i++) {
            $j = 0;
            foreach ($data[$i - 4] as $key=>$value) {
                if($key==1){
                    $excel->getActiveSheet()->setCellValue("$letter[$j]"."$i"," $value");
                    $excel->getActiveSheet()->getStyle("$letter[$j]"."$i")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
                }else{
                    $excel->getActiveSheet()->setCellValue("$letter[$j]"."$i","$value",PHPExcel_Cell_DataType::TYPE_STRING);
                }
                $j++;
            }
        }

        $excel->getActiveSheet()->setTitle('企业信息列表');

        $excel->createSheet();
        $excel->setActiveSheetIndex(1);
        $excel->getActiveSheet()->setTitle('经纪人信息');

        $letter = array('A','B','C','D','E','F','G','H','I');
        $tableheader = array('从业机构','经纪人姓名','信息卡号','执业证号','身份证号','联系电话','是否网签', '人员类型','所属区镇');
        for($i = 0;$i < count($tableheader);$i++) {
            $excel->getActiveSheet()->setCellValue("$letter[$i]1","$tableheader[$i]");
            $excel->getActiveSheet()->getStyle("$letter[$i]1")->getAlignment()->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        }
        $excel->getActiveSheet()->getColumnDimension("A")->setWidth(40);
        $excel->getActiveSheet()->getColumnDimension("B")->setWidth(25);
        $excel->getActiveSheet()->getColumnDimension("C")->setWidth(25);
        $excel->getActiveSheet()->getColumnDimension("D")->setWidth(25);
        $excel->getActiveSheet()->getColumnDimension("E")->setWidth(25);
        $excel->getActiveSheet()->getColumnDimension("F")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("G")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("H")->setWidth(18);
        $excel->getActiveSheet()->getColumnDimension("I")->setWidth(18);
        $data = array();
        foreach ($data_res['agent_list'] as $k=>$v){
            $wq_name_ = '否';
            if($v['wq'] == 2)
                $wq_name_ = '是';
            $work_type_name_ = '持证经纪人';
            if($v['work_type'] == 2)
                $work_type_name_ = '从业人员';
            $data[] = array($v['company_name'],$v['name'],$v['job_num'],$v['job_code'],$v['card']
            ,$v['phone'],$wq_name_,$work_type_name_,$v['town_name_']);
        }

        for ($i = 2;$i <= count($data) + 1;$i++) {
            $j = 0;
            foreach ($data[$i - 2] as $key=>$value) {
                if($key==4){
                    $excel->getActiveSheet()->setCellValue("$letter[$j]"."$i"," $value");
                    $excel->getActiveSheet()->getStyle("$letter[$j]"."$i")->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_TEXT);
                }else{
                    $excel->getActiveSheet()->setCellValue("$letter[$j]"."$i","$value",PHPExcel_Cell_DataType::TYPE_STRING);
                }
                $j++;
            }
        }
        $excel->setactivesheetindex(0);
        $write = new \PHPExcel_Writer_Excel5 ($excel);
        header("Pragma: public");
        header("Expires: 0");
        header("Cache-Control:must-revalidate, post-check=0, pre-check=0");
        header("Content-Type:application/force-download");
        header("Content-Type:application/vnd.ms-execl");
        header("Content-Type:application/octet-stream");
        header("Content-Type:application/download");;
        header('Content-Disposition:attachment;filename="'.'企业信息列表'.date('Y-m-d H:i:s',time()).'.xls"');
        header("Content-Transfer-Encoding:binary");
        $write->save('php://output');
    }

    /**
     * 注销企业列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_cancel_list($page = 1){
        $data = $this->manager_model->company_pending_list($page, array(-1));
        $base_url = "/manager/company_cancel_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->display('manager/company/company_cancel_list.html');
    }

    public function company_cancel_edit($m_id){

        $data = $this->manager_model->company_pending_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if (!in_array($data['flag'], array(-1)))
            $this->show_message('企业信息已不在注销企业列表!');
        $icon_list = $this->c4m_model->get_company_sys_icon();
        foreach ($icon_list as $k1 => $v1) {
            foreach ($data['icon'] as $k2 => $v2) {
                if ($v1['icon_no'] == $v2['icon_no']) {
                    $icon_list[$k1]['is_check_'] = 1;
                }
            }
        }
        $this->assign('icon_list', $icon_list);
        $this->assign('data', $data);
        $this->assign('m_id', $m_id);
        $this->display('manager/company/company_cancel_result.html');
    }

    /**
     * 企业初审列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pass_1_list($page = 1){
        $data = $this->manager_model->company_pass_list($page, array(1));
        $base_url = "/manager/company_pass_1_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->display('manager/company/company_pass_1_list.html');
    }

    /**
     * 初审审核页面
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pass_1_audit($m_id){
        $data = $this->manager_model->company_pass_data($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['status'] != 1)
            $this->show_message('年审信息已不在初审列表!');
        $pending_data = $this->manager_model->company_pending_edit($data['company_id']);
        $this->assign('data', $data);
        $this->assign('pending_data', $pending_data);
        $icon_list = $this->c4m_model->get_company_sys_icon();
        foreach ($icon_list as $k1 => $v1) {
            foreach ($pending_data['icon'] as $k2 => $v2) {
               if ($v1['icon_no'] == $v2['icon_no']) {
                   $icon_list[$k1]['is_check_'] = 1;
               }
            }
        }
        $this->assign('icon_list', $icon_list);
        $this->assign('pass_url', "/manager/company_pass_1_submit");
        $this->assign('cancel_url', "/manager/company_pass_1_cancel");
        $this->assign('return_url_', "/manager/company_pass_1_list");
        $this->assign('pass_btn_value', "初审通过");
        $this->display('manager/company/company_pass_audit.html');
    }

     /**
     * 初审审核通过
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pass_1_submit(){
        $res = $this->manager_model->company_pass_submit(2, $this->admin_id);
        $this->ajaxReturn($res);
    }

    /**
     * 初审审核失败
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pass_1_cancel(){
        $res = $this->manager_model->company_pass_submit(-1, $this->admin_id);
        $this->ajaxReturn($res);
    }  

    /**
     * 初审审核编辑页面
     * @author yangyang
     * @date 2019-12-12
     */
    public function company_pass_1_edit($pass_id){
        $data = $this->manager_model->company_pass_data($pass_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['status'] != 1)
            $this->show_message('年审信息不在初审审核列表!');
        $pending_data = $this->manager_model->company_pending_edit($data['company_id']);
        $this->assign('data', $data);
        $this->assign('pending_data', $pending_data);
        $this->assign('save_url', "/manager/company_pass_1_save");
        $this->assign('return_url', "/manager/company_pass_1_list");
        $this->display('manager/company/company_pass_edit.html');
    }

    public function company_pass_1_save(){
        $res = $this->manager_model->company_pass_save(1, $this->admin_id);
        $this->ajaxReturn($res);
    }
    
    /**
     * 待终审列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pass_2_list($page = 1){
        $data = $this->manager_model->company_pass_list($page, array(2));
        $base_url = "/manager/company_pass_2_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->display('manager/company/company_pass_2_list.html');
    }

     /**
     * 终审审核通过
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pass_2_submit(){
        $res = $this->manager_model->company_pass_submit(3, $this->admin_id);
        $this->ajaxReturn($res);
    }

     /**
     * 终审审核页面
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pass_2_audit($m_id){
        $data = $this->manager_model->company_pass_data($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['status'] != 2)
            $this->show_message('年审信息已不在终审列表!');
        $pending_data = $this->manager_model->company_pending_edit($data['company_id']);
        $this->assign('data', $data);
        $this->assign('pending_data', $pending_data);
        $icon_list = $this->c4m_model->get_company_sys_icon();
        foreach ($icon_list as $k1 => $v1) {
            foreach ($pending_data['icon'] as $k2 => $v2) {
               if ($v1['icon_no'] == $v2['icon_no']) {
                   $icon_list[$k1]['is_check_'] = 1;
               }
            }
        }
        $this->assign('icon_list', $icon_list);
        $this->assign('pass_url', "/manager/company_pass_2_submit");
        $this->assign('cancel_url', "/manager/company_pass_2_cancel");
        $this->assign('return_url_', "/manager/company_pass_2_list");
        $this->assign('pass_btn_value', "终审通过");
        $this->display('manager/company/company_pass_audit.html');
    }

    /**
     * 终审审核失败
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pass_2_cancel(){
        $res = $this->manager_model->company_pass_submit(-1, $this->admin_id);
        $this->ajaxReturn($res);
    }

    /**
     * 终审审核编辑页面
     * @author yangyang
     * @date 2019-12-12
     */
    public function company_pass_2_edit($pass_id){
        $data = $this->manager_model->company_pass_data($pass_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['status'] != 2)
            $this->show_message('年审信息不在终审审核列表!');
        $pending_data = $this->manager_model->company_pending_edit($data['company_id']);
        $this->assign('data', $data);
        $this->assign('pending_data', $pending_data);
        $this->assign('save_url', "/manager/company_pass_2_save");
        $this->assign('return_url', "/manager/company_pass_2_list");
        $this->display('manager/company/company_pass_edit.html');
    }

    public function company_pass_2_save(){
        $res = $this->manager_model->company_pass_save(2, $this->admin_id);
        $this->ajaxReturn($res);
    }

    /**
     * 企业审核成功列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pass_3_list($page = 1){
        $data = $this->manager_model->company_pass_list($page, array(3));
        $base_url = "/manager/company_pass_3_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->display('manager/company/company_pass_3_list.html');
    }

    //审核成功的详情
    public function company_pass_3_edit($pass_id){
        $data = $this->manager_model->company_pass_data($pass_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['status'] != 3)
            $this->show_message('年审信息不在终审成功列表!');
        $pending_data = $this->manager_model->company_pending_edit($data['company_id']);
        $this->assign('data', $data);
        $this->assign('pending_data', $pending_data);
        $this->display('manager/company/company_pass_result.html');
    }

    

    /**
     * 审核失败列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pass_f1_list($page = 1){
       $data = $this->manager_model->company_pass_list($page, array(-1));
        $base_url = "/manager/company_pass_f1_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $town_list = $this->c4m_model->get_town(1);
        $this->assign('town_list', $town_list);
        $this->display('manager/company/company_pass_f1_list.html');
    }

        //审核失败的详情
    public function company_pass_f1_edit($pass_id){
        $data = $this->manager_model->company_pass_data($pass_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['status'] != -1)
            $this->show_message('年审信息不在终审失败列表!');
        $pending_data = $this->manager_model->company_pending_edit($data['company_id']);
        $this->assign('data', $data);
        $this->assign('pending_data', $pending_data);
        $this->display('manager/company/company_pass_result.html');
    }

    //重置企业密码
    public function refresh_company_password(){
        $res = $this->manager_model->refresh_company_password($this->admin_id);
        $this->ajaxReturn($res);
    }

    //锁定企业资质状态
    public function locking_company_zz(){
        $res = $this->manager_model->locking_company_zz($this->admin_id);
        $this->ajaxReturn($res);
    }
}
