<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Manager extends MY_Controller {
    /**
     * 管理员操作控制器
     * @version 2.0
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function menu_del($id){
        $res = $this->manager_model->menu_del($id);
        $this->ajaxReturn($res);
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
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_add(){
        $groups = $this->manager_model->get_group_all();
        $this->assign('data', array());
        $this->assign('groups', $groups);
        $this->display('manager/admin/form.html');
    }

    /**
     * 编辑管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function admin_edit($id){
        $data = $this->manager_model->get_admin($id);
        if(!$data){
            $this->show_message('未找到管理员信息!');
        }
        $groups = $this->manager_model->get_group_all();
        $this->assign('data', $data);
        $this->assign('groups', $groups);
        $this->display('manager/admin/form.html');
    }

    /**
     * 保存管理员管理
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-04-01
     */
    public function admin_del($id){
        $res =  $this->manager_model->admin_del($id);
        $this->ajaxReturn($res);
    }

    /**
     * 新增用户组
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2018-03-31
     */
    public function group_del($id){
        $res =  $this->manager_model->group_del($id);
        $this->ajaxReturn($res);
    }

    /**
     * 个人资料页面
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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
     * @author yangyang <yang.yang@thmarket.cn>
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

        $this->display('manager/agent/agent_detail.html');
    }

    public function agent_edit($m_id){
        $data = $this->manager_model->agent_edit($m_id);
        if(!$data){
            $this->show_message('未找到执业经纪人信息!');
        }
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
        $this->display('manager/event_agent/agent_grade_detail.html');
    }

    public function agent_grade_edit($id){
        $data = $this->manager_model->agent_grade_edit($id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
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
        $this->display('manager/event_agent/event4agent_GRecord_add.html');
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
        $this->display('manager/event_agent/event4agent_GRecord_edit.html');
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
        $this->display('manager/event_agent/event4agent_BRecord_add.html');
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
        $this->display('manager/event_agent/event4agent_BRecord_edit.html');
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
        $this->display('manager/event_company/company_grade_detail.html');
    }

    public function company_grade_edit($id){
        $data = $this->manager_model->company_grade_edit($id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
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
        $this->display('manager/event_company/event4company_GRecord_add.html');
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
        $this->display('manager/event_company/event4company_GRecord_edit.html');
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
        $this->display('manager/event_company/event4company_BRecord_add.html');
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
        $this->assign('data', $data);
        $this->display('manager/event_company/event4company_BRecord_edit.html');
    }

    //作废
    public function event4company_BRecord_cancel(){
         $res = $this->manager_model->event4company_Record_cancel($this->admin_id);
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
    public function company_apply_list($page = 1){
        $data = $this->manager_model->company_common_list($page, 1, array(1));
        $base_url = "/manager/company_apply_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/company/company_apply_list.html');
    }


    /**
     * 企业报备保存页面 用于测试
     * @author yangyang
     * @date 2019-11-18
     */
    public function company_apply_add(){
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
        $this->display('manager/company/company_apply_add.html');
    }

    public function company_apply_edit($m_id){
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
         $data = $this->manager_model->company_apply_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['flag'] != 1) 
            $this->show_message('企业信息已不在报备列表!');
        $this->assign('data', $data);
        $this->display('manager/company/company_apply_add.html');
    }


    //报备审核页面
    public function company_apply_audit($m_id){
        $this->assign('f_user_id', $this->admin_id);
        $this->assign('time', time());
         $data = $this->manager_model->company_apply_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['flag'] != 1) 
            $this->show_message('企业信息已不在报备列表!');
        $this->assign('data', $data);
        $this->display('manager/company/company_apply_audit.html');
    }

    //报备通过
    public function company_apply_pass(){
        $res = $this->manager_model->company_apply_pass();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/company_apply_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    public function company_apply_save(){
         $res = $this->manager_model->company_apply_save();
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/company_apply_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 企业初审列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pending_1_list($page = 1){
        $data = $this->manager_model->company_common_list($page, 2, array(1));
        $base_url = "/manager/company_pending_1_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/company/company_pending_1_list.html');
    }

    /**
     * 初审审核页面
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pending_1_audit($m_id){
        $data = $this->manager_model->company_apply_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['flag'] != 2 || $data['status'] != 1) 
            $this->show_message('企业信息已不在初审列表!');
        $pass_data = $this->manager_model->company_pass_data($m_id);
        $this->assign('data', $data);
        $this->assign('pass_data', $pass_data);
        $this->display('manager/company/company_pending_audit.html');
    }

     /**
     * 初审审核提交
     * @author yangyang
     * @date 2019-11-27
     */
    public function company_pending_1_submit(){
        $res = $this->manager_model->company_pending_submit(2);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/company_pending_1_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 待终审列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pending_2_list($page = 1){
        $data = $this->manager_model->company_common_list($page, 2, array(2));
        $base_url = "/manager/company_pending_2_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/company/company_pending_2_list.html');
    }

    /**
     * 企业审核成功列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pending_3_list($page = 1){
        $data = $this->manager_model->company_common_list($page, 2, array(3));
        $base_url = "/manager/company_pending_3_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/company/company_pending_3_list.html');
    }

    /**
     * 编辑终审审核
     * @author yangyang
     * @date 2019-11-12
     */
     public function company_pending_3_edit($m_id){
         $data = $this->manager_model->company_apply_edit($m_id);
        if(!$data){
            $this->show_message('未找到信息!');
        }
        if ($data['flag'] != 2 || !in_array($data['status'], array(3)))
            $this->show_message('企业信息已不在终审列表!');
        $this->assign('data', $data);
        $this->display('manager/company/company_pending_3_edit.html');
    }

    /**
     * 提交审核信息
     * @author yangyang
     * @date 2019-11-12
     */
     public function company_pending_3_save(){
          $res = $this->manager_model->company_audit_save(3);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/company_pending_3_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }

    /**
     * 提交审核信息
     * @author yangyang
     * @date 2019-11-12
     */
     public function company_pending_3_submit(){
          $res = $this->manager_model->company_audit_save(1);
        if($res['status'] == 1){
            $this->show_message($res['msg'], site_url('/manager/company_pending_3_list'));
        }else{
            $this->show_message($res['msg']);
        }
    }



    /**
     * 审核失败列表
     * @author yangyang
     * @date 2019-11-12
     */
    public function company_pending_f1_list($page = 1){
        $data = $this->manager_model->company_common_list($page, 2, array(-1));
        $base_url = "/manager/company_pending_f1_list/";
        $pager = $this->pagination->getPageLink4manager($base_url, $data['total_rows'], $data['limit']);
        $this->assign('pager', $pager);
        $this->assign('page', $page);
        $this->assign('data', $data);
        $this->display('manager/company/company_pending_f1_list.html');
    }




}
