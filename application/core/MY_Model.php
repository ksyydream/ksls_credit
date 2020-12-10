<?php
/**
 * 扩展模型
 *
 * 提供了大部分读写数据库的功能，继承后可以直接使用，降低模型的代码量
 * @package		app
 * @subpackage	Libraries
 * @category	model
 * @author		yaobin<645894453@qq.com>
 *
 */
class MY_Model extends CI_Model{
    public $model_success = array('status' => 1, 'msg' => '', 'result' => array());
    public $model_fail = array('status' => -1, 'msg' => '操作失败!', 'result' => array());
    public $limit = 15;
    public $home_limit = 12;
    protected $db_error = "数据操作发生错误，请稍后再试-_-!";
    /**
     * 构造函数
     *
     * 加载数据库和日志类
     */
    public function __construct()
    {
        parent::__construct();
        $this->load->database();
    }

    /**
     * 把数据写入表
     * @param string $table 表名
     * @param array $arr 待写入数据
     */
    protected function create($table,$arr)
    {
        return $this->db->insert($table, $arr);
    }

    /**
     * 根据id读取一条记录
     * @param string $table 读取的表
     * @param int $id 主键id
     * @return array 一条记录信息数组
     */
    protected function read($table,$id)
    {
        return $this->db->get_where($table, array('id' => $id))->row_array();
    }

    /**
     * 根据id读取一条记录
     * @param string $table 读取的表
     * @param int $id_name 主键id的名称
     * @param int $id_value 主键id的值
     * @return array 一条记录信息数组
     */
    protected function readByID($table, $id_name, $id_value)
    {
        return $this->db->get_where($table, array($id_name => $id_value))->row_array();
    }

    /**
     * 按id返回指定列，id可以是批量
     * @param string $select 指定字段，例:'title, content, date'
     * @param string $table 查询的目标表
     * @param int,array $id 主键id，或id数组
     * @return array 返回对象数组
     */
    protected function select_where($select,$table,$id)
    {
        $this->db->select($select);
        $this->db->from($table);
        if(is_array($id)){
            $this->db->where_in('id',$id);
            return $this->db->get()->result();
        }else{
            $this->db->where('id',$id);
            return $this->db->get()->result();
        }
    }

    /**
     * 根据id更新数据
     * @param string $table 查询的目标表
     * @param int $id 主键id
     * @param array $arr 新的数据
     */
    protected function update($table,$id,$arr)
    {
        $this->db->where('id',$id);
        return $this->db->update($table,$arr);
    }

    /**
     * 删除数据
     * id可以是单个，也可以是个数组
     * @param string $table 查询的目标表
     * @param int|array $id 主键id，或id数组
     */
    protected function delete($table,$id)
    {
        if(is_array($id)){
            $this->db->where_in('id',$id);
            return $this->db->delete($table);
        }
        return $this->db->delete($table, array('id' => $id));
    }
    
    /**
     * 检测某字段是否已经存在某值
     * 
     * 存在返回该记录的信息数组，否则返回false
     * @param string $table 查询的目标表
     * @param string $field 条件字段
     * @param string $value 条件值
     * @return false,array 返回false或存在的记录信息数组
     */
    protected function is_exists($table,$field,$value)
    {
        $query = $this->db->get_where($table, array($field => $value));
        if($query->num_rows() > 0)
            return $query->row_array();
        return false;
    }

    /**
     * 分页列出数据
     * @param string $table 表名
     * @param int $limit 记录数
     * @param int $offset 偏移量
     * @param string $sort_by 排序字段 默认id
     * @param string $sort 排序 默认倒序desc,asc,random
     * @param string,null where条件，默认为空
     * @return object 返回记录对象数组
     */
    protected function list_data($table,$limit,$offset,$sort_by='id',$sort='desc',$where=null)
    {
        if(! is_null($where)) {
            $this->db->where($where);
        }
        $this->db->order_by($sort_by,$sort);
        return $this->db->get($table,$limit,$offset)->result();
    }

    /**
     * 总记录数
     * @param string $table 表名
     */
    protected function count($table)
    {
        return $this->db->count_all($table);
    }

    /**
     * 按条件统计记录
     * @param string $table 表名
     * @param string $where 条件
     */
    protected function count_where($table,$where)
    {
        $this->db->from($table);
        $this->db->where($where);
        $result = $this->db->get();
        return $result->num_rows();
    }

    /**
     * 列出全部
     * @param string $table 表名
     */
    protected function list_all($table)
    {
        return $this->db->get($table)->result();
    }

    /**
     * 列出全部根据条件
     * @param string $table 表名
     * @param string $where where条件字段
     * @param string $value where的值
     * @param string $sort_by 排序字段
     * @param string $sort 排序方式
     */
    protected function list_all_where($table,$where,$value,$sort_by='id',$sort='desc')
    {
        $this->db->from($table);
        if($where!='' and $value!=''){
            $this->db->where($where,$value);
        }
        $this->db->order_by($sort_by,$sort);
        return $this->db->get()->result();
    }

    /**
     * 列出数据（两个表关联查询）
     * @param string $select 查询字段
     * @param string $table1 表名1
     * @param string $table2 表名2
     * @param string $on 联合条件
     * @param int $limit 读取记录数
     * @param int $offset 偏移量
     * @param string $sort_by 排序字段，默认id
     * @param string $sort 排序方式，默认降序
     * @param string $where 过滤条件
     * @param string $join_type 链接方式，默认left
     */
    protected function list_data_join($select,$table1,$table2,$on,$limit,$offset,$sort_by="id",$sort='DESC',$where=null,$join_type='left')
    {
        $this->db->select($select);
        $this->db->from($table1);

        if(! is_null($where)) {
            $this->db->where($where);
        }

        $this->db->join($table2,$on,$join_type);
        $this->db->limit($limit,$offset);
        $this->db->order_by($sort_by,$sort);
        return $this->db->get()->result();
    }

    /**
     * 设置状态
     * 状态字段必须是status
     * @param string $table 表名
     * @param int $id 主键id的值
     * @param int $status 状态值
     */
    protected function set_status($table,$id,$status)
    {
        $this->db->where('id',$id);
        $this->db->set('status',$status);
        return $this->db->update($table);
    }

    /**
     * 析构函数
     *
     * 关闭数据库连接
     */
    public function __destruct()
    {
        $this->db->close();
    }
    
    ///////////////////////////////////////////////////////////////////////////////
    // WeiXin API related
    ///////////////////////////////////////////////////////////////////////////////
    public function post($url, $post_data, $timeout = 300){
    	$options = array(
    			'http' => array(
    					'method' => 'POST',
    					'header' => 'Content-type:application/json;encoding=utf-8',
    					'content' => urldecode(json_encode($post_data)),
    					'timeout' => $timeout
    			)
    	);
    	$context = stream_context_create($options);
    	return file_get_contents($url, false, $context);
    }

    public function wxpost($template_id,$post_data,$user_id,$url_www='www.funmall.com.cn'){
        $openid = $this->get_openidByUserid($user_id);
        if($openid == -1 || empty($openid)){
            return false;
        }
        $this->load->library('wxjssdk_th',array('appid' => $this->config->item('appid'), 'appsecret' => $this->config->item('appsecret')));
        $access_token = $this->wxjssdk_th->wxgetAccessToken();
        $url = "https://api.weixin.qq.com/cgi-bin/message/template/send?access_token=".$access_token;//access_token改成你的有效值

        /*$data = array(
            'first' => array(
                'value' => '数据提交成功！',
                'color' => '#FF0000'
            ),
            'keyword1' => array(
                'value' => '休假单',
                'color' => '#FF0000'
            ),
            'keyword2' => array(
                'value' => date("Y-m-d H:i:s"),
                'color' => '#FF0000'
            ),
            'remark' => array(
                'value' => '请审核！',
                'color' => '#FF0000'
            )
        );*/
        $template = array(
            'touser' => $openid,
            'template_id' => $template_id,
            'url' => $url_www,
            'topcolor' => '#7B68EE',
            'data' => $post_data
        );
        $json_template = json_encode($template);
        $dataRes = $this->request_post($url, urldecode($json_template)); //这里执行post请求,并获取返回数据
        $res_ = json_decode($dataRes);
        if ($res_->errcode == 0) {
            return true;
        } else {
            return false;
        }

    }

    private function wxhttpGet($url) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    public function get_openidByUserid($user_id){
        $row = $this->db->select()->from('users')->where('user_id',$user_id)->get()->row_array();
        if ($row){
            return $row['openid'];
        }else{
            return -1;
        }
    }

    function request_post($url = '', $param = '')
    {
        if (empty($url) || empty($param)) {
            return false;
        }
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init(); //初始化curl
        curl_setopt($ch, CURLOPT_URL, $postUrl); //抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0); //设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); //要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1); //post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch); //运行curl
        curl_close($ch);
        return $data;
    }

    //返回失败的信息
    public function fun_fail($msg, $result = array()){
        $this->model_fail['msg'] = $msg;
        $this->model_fail['result'] = $result;
        return $this->model_fail;
    }

    //返回成功的信息
    public function fun_success($msg = '操作成功', $result = array()){
        $this->model_success['msg'] = $msg;
        $this->model_success['result'] = $result;
        return $this->model_success;
    }

    //验证短信
    public function check_sms($mobile, $code){
        $sms_time_out = $this->config->item('sms_time_out');
        $sms_time_out = $sms_time_out ? $sms_time_out : 120;
        $sms_log = $this->db->from('sms_log')->where(array('mobile' => $mobile, 'status' => 1))->order_by('add_time', 'desc')->limit(1)->get()->row_array();
        if(!$sms_log){
            return $this->fun_fail('请先获取验证码');
        }
        if($sms_log['code'] == $code){
            $timeOut = $sms_log['add_time'] + $sms_time_out;
            if($timeOut < time()){
                return $this->fun_fail('验证码已超时失效');
            }
        }else{
            return $this->fun_fail('验证失败,验证码有误');
        }
        return $this->fun_success('验证成功');
    }

    //将openid其他的登录状态清楚
    public function delOpenidById($id, $openid, $type){
        if($type == 'users'){
            //意味着是user登录
            $this->db->where(array('user_id <>' => $id, 'openid' => $openid))->update('users', array('openid' => ''));
            $this->db->where(array('openid' => $openid))->update('members', array('openid' => ''));
        }
        if($type == 'members'){
            //意味着是memeber登录
            $this->db->where(array('openid' => $openid))->update('users', array('openid' => ''));
            $this->db->where(array('m_id <>' => $id, 'openid' => $openid))->update('members', array('openid' => ''));
        }
    }

    //存入user的session
    public function set_user_session_wx($id){
        $this->db->from('users');
        $this->db->where('user_id', $id);
        $rs = $this->db->get();
        if ($rs->num_rows() > 0) {
            $res = $rs->row();
            $token = uniqid();
            $user_info['wx_token'] = $token;
            $user_info['wx_user_id'] = $res->user_id;
            $user_info['wx_rel_name'] = $res->rel_name;
            $user_info['wx_user_pic'] = $res->pic;
            $user_info['wx_class'] = 'users';
            $this->session->set_userdata($user_info);
            return 1;
        }
        return -1;
    }

    //存入member的session
    public function set_member_session_wx($id){
        $this->db->from('members');
        $this->db->where('m_id', $id);
        $rs = $this->db->get();
        if ($rs->num_rows() > 0) {
            $res = $rs->row();
            $token = uniqid();
            $member_info['wx_token'] = $token;
            $member_info['wx_m_id'] = $res->m_id;
            $member_info['wx_rel_name'] = $res->rel_name;
            $member_info['wx_user_pic'] = $res->pic;
            $member_info['wx_class'] = 'members';
            $this->session->set_userdata($member_info);
            return 1;
        }
        return -1;
    }

    //通过invite_code查询管理员
    public function getMemberByInvite($invite_code){
        $res = $this->db->select()->from('members')->where(array('status' => 1, 'invite_code' => $invite_code))->get()->row_array();
        return $res;
    }


    /**
     * 自动增加单号
     * @author yangyang <yang.yang@thmarket.cn>
     * @date 2019-07-10
     */
    public function get_sys_num_auto($title){
        $check_ = $this->db->select()->from('sys_num')->where('title',$title)->get()->row_array();
        if($check_){
            $this->db->where('title',$title)->set('num','num + 1',false)->update('sys_num');
            return $check_['num'];
        }else{
            $insert_data = array(
                'title' => $title,
                'num' => 2
            );
            $this->db->insert('sys_num', $insert_data);
            return 1;
        }
    }

    //微信图片上传
    public function getmedia($media_id, $finance_num, $file){
        $app = $this->config->item('appid');
        $appsecret = $this->config->item('appsecret');
        $this->load->library('wxjssdk_th',array('appid' => $app, 'appsecret' => $appsecret));
        $accessToken = $this->wxjssdk_th->wxgetAccessToken();
        $url = "http://file.api.weixin.qq.com/cgi-bin/media/get?access_token=".$accessToken."&media_id=".$media_id;

        if (is_readable('./upload_files/' . $file) == false) {
            mkdir('./upload_files/' . $file, 0777, true);
        }
        if (is_readable('./upload_files/' . $file . '/'.$finance_num) == false) {
            mkdir('./upload_files/' . $file . '/'.$finance_num, 0777, true);
        }
        $file_name = date('YmdHis').rand(1000,9999).'.jpg';
        $targetName = './upload_files/'.$file.'/'.$finance_num.'/'.$file_name;
        //file_put_contents('/var/yy.txt', $url);

        $ch = curl_init($url); // 初始化
        $fp = fopen($targetName, 'wb'); // 打开写入
        curl_setopt($ch, CURLOPT_FILE, $fp); // 设置输出文件的位置，值是一个资源类型
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);

        return $file_name;
    }

    /**
     *********************************************************************************************
     * 以下代码后台公共调用
     *********************************************************************************************
     */

    public function save_admin_log($admin_id){
        $data = array(
            'admin_id' => $admin_id,
            'action_url' => $_SERVER['REQUEST_URI'],
            'post_json' => json_encode($this->input->post()),
            'get_json' => json_encode($this->input->get()),
            'cdate' => date('Y-m-d H:i:s',time())
        );
        if(!$data['action_url']){
            //Nginx 下 可能获取不到值
            $data['action_url'] = @explode('?',$_SERVER['REQUEST_URI'])[0];

        }
        $this->db->insert('admin_log',$data);

    }

    /**
     * 检查权限
     * @param name string|array  需要验证的规则列表,支持逗号分隔的权限规则或索引数组
     * @param admin_id  int           认证用户的id
     * @param string mode        执行check的模式
     * @param relation string    如果为 'or' 表示满足任一条规则即通过验证;如果为 'and'则表示需满足所有规则才能通过验证
     * @return boolean           通过验证返回true;失败返回false
     */
    public function check($name, $admin_id, $type=1, $mode='url', $relation='or') {

        $authList = $this->getAuthList($admin_id,$type); //获取用户需要验证的所有有效规则列表

        if (is_string($name)) {

            $name = strtolower($name);

            if (strpos($name, ',') !== false) {

                $name = explode(',', $name);

            } else {

                $name = array($name);

            }

        }

        $list = array(); //保存验证通过的规则名

        if ($mode=='url') {

            $REQUEST = unserialize( strtolower(serialize($_REQUEST)) );

        }

        foreach ( $authList as $auth ) {

            $query = preg_replace('/^.+\?/U','',$auth);

            if ($mode=='url' && $query!=$auth ) {

                parse_str($query,$param); //解析规则中的param

                $intersect = array_intersect_assoc($REQUEST,$param);

                $auth = preg_replace('/\?.*$/U','',$auth);

                if ( in_array($auth,$name) && $intersect==$param ) {  //如果节点相符且url参数满足

                    $list[] = $auth ;

                }

            }else if (in_array($auth , $name)){

                $list[] = $auth ;

            }

        }

        if ($relation == 'or' and !empty($list)) {

            return true;

        }

        $diff = array_diff($name, $list);

        if ($relation == 'and' and empty($diff)) {

            return true;

        }

        return false;

    }

    /**
     * 获得权限列表
     * @param integer $admin_id  用户id
     * @param integer $type
     */

    protected function getAuthList($admin_id,$type) {

        static $_authList = array(); //保存用户验证通过的权限列表

        $t = implode(',',(array)$type);

        if (isset($_authList[$admin_id.$t])) {

            return $_authList[$admin_id.$t];

        }

        //读取用户所属用户组

        $groups = $this->getGroups($admin_id);

        $ids = array();//保存用户所属用户组设置的所有权限规则id

        foreach ($groups as $g) {

            $ids = array_merge($ids, explode(',', trim($g['rules'], ',')));

        }

        $ids = array_unique($ids);

        if (empty($ids)) {

            $_authList[$admin_id.$t] = array();

            return array();

        }

        $map=array(

            'type'=>$type,

            'status'=>1,

        );

        //读取用户组所有权限规则

        $rules = $this->db->select('condition,name')->from('auth_rule')->where($map)->where_in('id',$ids)->get()->result_array();

        //循环规则，判断结果。

        $authList = array();   //

        foreach ($rules as $rule) {

            //只要存在就记录

            $authList[] = strtolower($rule['name']);

        }

        $_authList[$admin_id.$t] = $authList;

        return array_unique($authList);

    }

    /**
     * 根据用户id获取用户组,返回值为数组
     * @param  admin_id int     用户id
     * @return array       用户所属的用户组 array(
     *     array('admin_id'=>'用户id','group_id'=>'用户组id','title'=>'用户组名称','rules'=>'用户组拥有的规则id,多个,号隔开'),
     *     ...)
     */

    public function getGroups($admin_id) {
        $this->db->select('a.admin_id, a.group_id, b.title, b.rules');

        $this->db->from('auth_group_access a');

        $this->db->join('auth_group b','a.group_id = b.id','left');

        $groups = $this->db->where('a.admin_id',$admin_id)->get()->result_array();

        return $groups;
    }

    //从服务器端上传图片
    function save_qiniu($bucket, $file_name , $file, $time){
        $this->load->library('Qiniu');
        $accessKey = $this->config->item('qiniu_AccessKey');
        $secretKey = $this->config->item('qiniu_SecretKey');
        $auth = new Qiniu\Auth($accessKey, $secretKey);
        $ossSupportPath = array('ksls2credit');
        if(!in_array($bucket, $ossSupportPath)){
            return '';
        }
        $token = $auth->uploadToken($bucket);
        $bucketMgr = new Qiniu\Storage\UploadManager();
        $object = 'public/upload/' .md5(time().mt_rand(100000,999999)).'.'.pathinfo($file_name, PATHINFO_EXTENSION);
        $filePath = './upload_files/'.$file.'/'.$file_name;
        if(file_exists($filePath)){
            list($ret, $err) = $bucketMgr->putFile($token, $object, $filePath);
            if ($err !== null) {
                $insert_arr = array(
                    'type' => 1,
                    'add_time' => time(),
                    'err_msg' => "oss上传文件失败，".$err
                );
                $this->db->insert('log', $insert_arr);
                return '';
            } else {
                @unlink($filePath);
                $url = "http://"  . $this->config->item('qiniu_url') . '/' . $ret['key'];
                $insert_arr = array(
                    'img_url' => $url,
                    'folder' => $file,
                    'flag_time' => $time,
                    'add_time' => time()
                );
                $this->db->insert('upload_img', $insert_arr);
                return $url;
            }
        }else{
            return '';
        }

    }

    //获取随机登陆账号
    public function get_username(){
        $title_ = 'KS' . date('Ymd', time());
        $username = $title_ . sprintf('%03s', $this->get_sys_num_auto($title_));
        $check = $this->db->select('id')->from('company_pending')->where('username',$username)->order_by('id','desc')->get()->row_array();
        if($check)
            $username = $this->get_username();
        return $username;
    }

    //自动生成 从业编号
    public function get_job_num(){
        $title_ = 'SZ';
        $num_ = $title_ . '_9' . sprintf('%07s', $this->get_sys_num_auto($title_));
        $check = $this->db->select('id')->from('agent')->where('job_num', $num_)->order_by('id','desc')->get()->row_array();
        if($check)
            $num_ = $this->get_job_num();
        return $num_;
    }

    //获取备案号,20200524 备案号用于在生成的证书上显示,因新需求是 证书编号不变
    public function get_record_num(){
        $title_ = 'KS';
        $record_num = $title_ . sprintf('%04s', $this->get_sys_num_auto($title_));
        $check = $this->db->select('id')->from('company_pending')->where('record_num',$record_num)->order_by('id','desc')->get()->row_array();
        if($check)
            $record_num = $this->get_record_num();
        return $record_num;
    }

    //获取企业当前的经纪人人数，因为使用的地方比较多，写成公共方法
    public function get_agent_num4company($company_id){
         $this->db->select('count(1) num')->from('agent a');
        $this->db->where('flag', 2);
        //20200928 只计算持证经纪人人数, 也就是不包含 从业人员
        $this->db->where('work_type', 1);
        $this->db->where('grade_no >', 1);
        $this->db->where('company_id', $company_id);
        $num = $this->db->get()->row();
        return $num->num;
    }

    //按备案存续有效年份
    public function get_ns_num4company($company_id, $annual_year = null){
        $year_num_ = 0;
        $this->db->select('a.annual_year, a.status')->from('company_ns_list a');
        $this->db->where('a.company_id ', $company_id);
        $this->db->order_by('annual_year', 'desc');
        $annual_year_list_ = $this->db->get()->result_array();
        foreach ($annual_year_list_ as $k_ => $v_) {
            if ($v_['status'] != 2)
                break;
            $year_num_ += 1;
        }
        return $year_num_;

        /**$year_num_ = 0;
        $this->db->select('a.annual_year, b.id')->from('term a');
        $this->db->join('company_ns_list b', 'a.annual_year = b.annual_year and  b.company_id = ' . $company_id . ' and b.status in (2)', 'left');
        if ($annual_year) 
            $this->db->where('a.annual_year <', $annual_year);
        $this->db->order_by('annual_year', 'desc');
        $annual_year_list_ = $this->db->get()->result_array();
        foreach ($annual_year_list_ as $k_ => $v_) {
            //如果没有输入审核年份，可能是在非年审窗口期内 计算，这个时候如果第一个年审期没有年审记录就略过，因为可能还没有开始
            if (!$annual_year && $k_ == 0 && !$v_['id'])
                continue;
            if (!$v_['id'])
                break;
            $year_num_ += 1;
        }
        return $year_num_;
        */
    }

    //获取管理员所管辖的 区镇数组,因为可能经常会被判断使用 放在主模块中
    public function get_admin_t_list($id = -1) {
        $t_list = $this->db->select('group_concat(distinct t_id ORDER BY t_id) t_list')->from('admin_town')->where(array('admin_id' => $id))->get()->row_array();
        return explode(",", $t_list['t_list']);
    }

    //通过town_id 判断管理员是否有操作权限 因为可能经常使用 放在主模块中
    public function check_admin_townByTown_id($admin_id, $town_id){
        $admin_t_list_ = $this->get_admin_t_list($admin_id);
        $admin_info = $this->db->select('a.*,b.group_id,c.title')->from('admin a')
            ->join('auth_group_access b', 'a.admin_id = b.admin_id', 'left')
            ->join('auth_group c', 'c.id = b.group_id', 'left')
            ->where('a.admin_id', $admin_id)->get()->row_array();
        //如果用户组是 超级管理员 就直接通过
        if($admin_info['group_id'] == 1)
            return true;
        if(!$admin_t_list_ || !is_array($admin_t_list_)){
            return false;
        }
        if(in_array($town_id, $admin_t_list_)){
            return true;
        }
        return false;
    }

    //通过town_id 判断管理员是否有操作权限 因为可能经常使用 放在主模块中
    public function check_admin_townByCompany_id($admin_id, $company_id){
        $company_info = $this->db->select('town_id')->from('company_pending')->where('id', $company_id)->get()->row_array();
        if(!$company_info)
            return false;
        $res = $this->check_admin_townByTown_id($admin_id, $company_info['town_id']);
        if($res)
            return true;
        return false;
    }
    //
    /**
     *********************************************************************************************
     * 通用逻辑类函数
     *********************************************************************************************
     */

    /**
    *重新计算企业总分/企业异常标记
     * @param $company_id       企业ID
     * @param $annual_year      年审年份，在年审审核计算分数时带入
     * @param $is_ns_           年审标记位，当为 2时表示年审成功，1时表示年审失败，并将执行相关代码
     * @param $pass_id          company_pass的主键ID，用于回写一些结果信息
     * @return bool
     * 因为企业存在多种分值，在计算结束后，无论结果如何都要相加计算，以方便之后排名和查看分数线
     */
    public function save_company_total_score($company_id, $annual_year = null, $is_ns_ = null, $pass_id = null){
        $company_pending_info_ = $this->db->select('fz_num, qx_num,total_score, event_score, sys_score, base_score')->from('company_pending')->where('id', $company_id)->get()->row_array();
        if (!$company_pending_info_) {
            return false;
        }
        $pass_score_log = array();

        $zz_status_ = 1;        //异常状态
        $sys_score = 0;         //系统分 (经纪人分数 + 标签分)
        //第一步基础分，无需操作
        $pass_score_log[] = array(
            'company_id' => $company_id,
            'pass_id' => $pass_id,
            'type' => 1, 
            'msg' => '初始分：' . $company_pending_info_['base_score'] . '分'
        );
        //第二步事件分，无需操作
        $pass_score_log[] = array(
            'company_id' => $company_id,
            'pass_id' => $pass_id,
            'type' => 2, 'msg' => '事件分总分：' . $company_pending_info_['event_score'] . '分');
        //第三步系统分，主要由实际的经纪人数量和状态决定
       
        // 1 先加入人员分数
        $agent_num = $this->get_agent_num4company($company_id);
        $company_agent_score_ = $this->config->item('company_agent_score') ? $this->config->item('company_agent_score') : 0;
        $company_agent_score_max_ = $this->config->item('company_agent_score_max') ? $this->config->item('company_agent_score_max') : 0;
        if($agent_num > 3){
            $agent_score_total = ($agent_num - 3) * $company_agent_score_;
            if($agent_score_total > $company_agent_score_max_){
                $pass_score_log[] = array(
                     'company_id' => $company_id,
                    'pass_id' => $pass_id,
                    'type' => 3,
                     'msg' => '额外注册持证经纪人' . ($agent_num - 3) . '个，每个' . $company_agent_score_ . '分，因不可超过' . $company_agent_score_max_ . '分，最终计分' . $company_agent_score_max_ . '分'
                 );
                $sys_score += $company_agent_score_max_;
            }
            else{
                $pass_score_log[] = array(
                     'company_id' => $company_id,
                    'pass_id' => $pass_id,
                    'type' => 3,
                     'msg' => '额外注册持证经纪人' . ($agent_num - 3) . '个，每个' . $company_agent_score_ . '分，最终计分' . $agent_score_total . '分'
                 );
                $sys_score += $agent_score_total;
            }
        }
        
        // 2 计算有效年份
        $ns_num = $this->get_ns_num4company($company_id, $annual_year);
        $company_ns_score_ = $this->config->item('company_ns_score') ? $this->config->item('company_ns_score') : 0;
        $company_ns_score_max_ = $this->config->item('company_ns_score_max') ? $this->config->item('company_ns_score_max') : 0;
        $ns_score_total = $ns_num * $company_ns_score_;
        if($ns_score_total > $company_ns_score_max_){
            $pass_score_log[] = array(
                'company_id' => $company_id,
                'pass_id' => $pass_id,
                'type' => 3, 
                'msg' => '备案存续有效年份累计' . ($ns_num) . '年，每年' . $company_ns_score_ . '分，因不可超过' . $company_ns_score_max_ . '分，最终计分' . $company_ns_score_max_ . '分'
            );
            $sys_score += $company_ns_score_max_;
        }
        else{
            $pass_score_log[] = array(
                'company_id' => $company_id,
                'pass_id' => $pass_id,
                'type' => 3, 
                'msg' => '备案存续有效年份累计' . ($ns_num) . '年，每年' . $company_ns_score_ . '分，最终计分' . $ns_score_total . '分'
            );
            $sys_score += $ns_score_total;
        }
        // 3 计算分支机构
        $company_fz_score_ = $this->config->item('company_fz_score') ? $this->config->item('company_fz_score') : 0;
        $company_fz_score_max_ = $this->config->item('company_fz_score_max') ? $this->config->item('company_fz_score_max') : 0;
        $fz_num = $company_pending_info_['fz_num'] ? $company_pending_info_['fz_num'] : 0;
        if ($fz_num > 0) {
            $fz_score_total = $fz_num * $company_fz_score_;
            if($fz_score_total > $company_fz_score_max_){
                $pass_score_log[] = array(
                    'company_id' => $company_id,
                    'pass_id' => $pass_id,
                    'type' => 3, 'msg' => '开设分支机构并备案' . ($fz_num) . '个，每个' . $company_fz_score_ . '分，因不可超过' . $company_fz_score_max_ . '分，最终计分' . $company_fz_score_max_ . '分');
                $sys_score += $company_fz_score_max_;
            }
            else{
                $pass_score_log[] = array(
                    'company_id' => $company_id,
                    'pass_id' => $pass_id,
                    'type' => 3, 'msg' => '开设分支机构并备案' . ($fz_num) . '个，每个' . $company_fz_score_ . '分，最终计分' . $fz_score_total . '分');
                $sys_score += $fz_score_total;
            }
        }
        // 4 计算缺席分数，直接通过年审记录判断，所见即所得，这样用户也可以直接看到原因
        $company_qx_score_ = $this->config->item('company_qx_score') ? $this->config->item('company_qx_score') : 0;
        $annual_list_ = $this->db->select('*')->from('company_ns_list')->where(array('company_id' => $company_id))->order_by('annual_year', 'desc')->get()->result_array();
        $is_qx_1_ = false;  //上年缺席
        $is_qx_2_ = false;  //连续两年缺席
        if($annual_list_){
            if($annual_list_[0]['status'] == -1)
                $is_qx_1_ = true;
            if(isset($annual_list_[1]) && $annual_list_[1]['status'] == -1)
                $is_qx_2_ = true;
        }
        if($is_qx_1_){
            $pass_score_log[] = array('company_id' => $company_id,'pass_id' => $pass_id,'type' => 3, 
                'msg' => '存在缺席行为，计分' . $company_qx_score_ . '分'
            );
            $sys_score += $company_qx_score_;
        }
        // 5 再计算标签分数 计划所有标签如果存在修改,均需要在此通用方法前进行
        $icon_ = $this->db->select("ifnull(sum(a.score),0) sum_score_")->from('sys_score_icon a')
                ->join('company_pending_icon b','a.icon_no = b.icon_no', 'left')->where('a.status', 1)->where('b.company_id', $company_id)->get()->row_array();
        if($icon_){
            $pass_score_log[] = array('company_id' => $company_id,'pass_id' => $pass_id,'type' => 3, 
                'msg' => '信用指标，总计分' . $icon_['sum_score_'] . '分'
            );
            $sys_score += $icon_['sum_score_'];
        }

        $this->db->where('id', $company_id)->set('sys_score', $sys_score)->update('company_pending');

        //最后一步 把各个分值相加
        $this->db->where('id', $company_id)->set('total_score', 'event_score + sys_score + base_score', FALSE)->update('company_pending');
        if($agent_num < 3 || $is_qx_2_)
            $zz_status_ = -1;
        //只对资质状态不锁定的企业进行修改
        $this->db->where('id', $company_id)->where('locking_zz', 0)->set('zz_status', $zz_status_)->update('company_pending');

        if ($annual_year && $is_ns_ && in_array($is_ns_, array(1, 2))) {
            $annual_info_ = $this->db->select('*')->from('company_ns_list')->where(array('annual_year' => $annual_year, 'company_id' => $company_id))->get()->row_array();
            $admin_info = $this->session->userdata('admin_info');
            $admin_id = -1;
            if($admin_info)
                $admin_id = $admin_info['admin_id'];
            if ($annual_info_) {
                //如果存在 当前年审的年审结果，就不再重置和结算信用登记，只更改审核结果
                $this->db->where(array('annual_year' => $annual_year, 'company_id' => $company_id));
                $this->db->update('company_ns_list', array('status' => $is_ns_, 'modify_date' => date('Y-m-d H:i:s',time()), 'modify_user' => $admin_id));
            }else{
                //如果不存在，当前年审的年审结果，就除了要增加年审结果记录，还需要计算信用登记 和 重置分数
                $insert_annual_year_ = array(
                    'annual_year' => $annual_year,
                    'company_id'  => $company_id,
                    'status'      => $is_ns_,
                    'create_date' => date('Y-m-d H:i:s',time()),
                    'create_user' => $admin_id
                );
                //获取信用等级
                $company_pending_ns_ =  $this->db->select('total_score, event_score, sys_score, base_score')->from('company_pending')->where('id', $company_id)->get()->row_array();
                $total_score = $company_pending_ns_['total_score'];
                $grade_no_ = $this->db->select()->from('company_grade')->where('min_score <=', $total_score)->order_by('grade_no','desc')->get()->row_array();
                if($grade_no_){
                    $insert_annual_year_['grade_no'] = $grade_no_['grade_no'];
                    $insert_annual_year_['grade_name'] = $grade_no_['grade_name'];
                }else{
                    $insert_annual_year_['grade_no'] = 1;
                    $insert_annual_year_['grade_name'] = 'D级';
                }
                $this->db->insert('company_ns_list', $insert_annual_year_);

                //更新 company_pass 的信息
                if($pass_id){
                    $update_pass_data = array(
                        'total_score' => $company_pending_ns_['total_score'],
                        'event_score' => $company_pending_ns_['event_score'],
                        'sys_score' => $company_pending_ns_['sys_score'],
                        'base_score' => $company_pending_ns_['base_score'],
                        'grade_no' => $insert_annual_year_['grade_no'] ? $insert_annual_year_['grade_no'] : 1,
                        'grade_name' => $insert_annual_year_['grade_name'] ? $insert_annual_year_['grade_name'] : '失信'
                    );
                    $this->db->where('id', $pass_id)->update('company_pass', $update_pass_data);
                }
                //重置分数
                $this->db->where('id', $company_id)->set('event_score', 0)->update('company_pending');
                $this->db->where(array('status' => 1, 'is_nscz' => -1, 'company_id' => $company_id))->update('event4company_record', array('is_nscz' => 1, 'annual_year' => $annual_year));
                $this->db->where('id', $company_id)->set('total_score', 'event_score + sys_score + base_score', FALSE)->update('company_pending');
                //更新company_pending的最新年审时间和信用等级
                $this->db->where('id', $company_id)->where('annual_date <=', $annual_year)->update('company_pending', array('grade_no' => $insert_annual_year_['grade_no'], 'annual_date' => $annual_year, 'qx_num' => 0));
                $this->db->where('pass_id', $pass_id)->delete('company_pass_score_log');
                $this->db->insert_batch('company_pass_score_log', $pass_score_log);
            }
        }
        return true;
    }

    /**
     * 生成证书
     * @param $pass_id      年审申请ID,通过年审申请来生成证书,因为证书包含企业名称,所以只能等年审结束,信息覆盖后生成
     */
    public function create_cert($pass_id){
        $cert_insert_ = array('status' => 1);
        //先找到年审审核信息,主要查找 年审年份,企业ID
        //DBY 20200325 以后需要根据社区生成证件编号,这里需要在年审信息中查看
        $pass_info = $this->db->select('company_id, status, annual_date,tj_date')->from('company_pass')->where(array('id' => $pass_id))->get()->row_array();
        if(!$pass_info)
            return false;
        $title_ = 'KFQ';
        //通过企业ID查找到当前的名称,企业pass内也有,但还是直接获取company_pending 内的
        $pending_info = $this->db->select('a.company_name, a.legal_name,b.code')->from('company_pending a')
            ->join('town b','a.town_id = b.id','left')
            ->where(array('a.id' => $pass_info['company_id']))->get()->row_array();
        if(!$pending_info)
            return false;
        $cert_insert_['company_name']   = $pending_info['company_name'];
        $cert_insert_['legal_name']     = $pending_info['legal_name'];
        $cert_insert_['company_id']     = $pass_info['company_id'];
        $title_ = $pending_info['code'] ? $pending_info['code'] : 'KFQ';
        //通过年审时间 获取年审记录信息,如果年审记录是失败 只删除可能存在的证书,如果年审记录是成功,就再继续操作
        $annual_info_ = $this->db->select('*')->from('company_ns_list')->where(array('annual_year' => $pass_info['annual_date'], 'company_id' => $pass_info['company_id']))->get()->row_array();
        if(!$annual_info_)
            return false;
        $cert_insert_['ns_id'] = $annual_info_['id'];
        $this->db->where(array('company_id' => $pass_info['company_id'], 'ns_id' => $annual_info_['id']))->update('company_ns_cert', array('status' => -1));
        if($annual_info_['status'] == 2){
            $cert_insert_['company_name'] = $pending_info['company_name'];
            $cert_insert_['num'] = $this->get_cert_num($title_);
            //获取年审窗口期, 生成证件的 授权起止日期
            $term_info_ = $this->db->select('*')->from('term')->where(array('annual_year' => $pass_info['annual_date'], 'flag' => 1))->get()->row_array();
            if(!$term_info_)
                return false;
            $cert_insert_['start_date'] =   date("Y-m-d",strtotime($pass_info['tj_date']));
            $cert_insert_['end_date']   =   date("Y-m-d",strtotime('+1year', strtotime($pass_info['tj_date'])));
            $this->db->insert('company_ns_cert', $cert_insert_);
        }

        return true;
    }

    //获取证书编号
    public function get_cert_num($title){
        $title_ = $title;
        $num = $title_ . sprintf('%04s', $this->get_sys_num_auto($title_));
        $check = $this->db->select('id')->from('company_ns_cert')->where('num',$num)->order_by('id','desc')->get()->row_array();
        if($check)
            $num = $this->get_username();
        return $num;
    }


    public function save_company_ns_info($status, $company_id, $pass_id = null){

    }

    public function save_agent_track($company_id,$company_data,$agent_old,$agent_new){
        $data_insert= array();
        $agent_arr_ = array();
        if($agent_old && is_array($agent_old)){
            foreach($agent_old as $item){
                $agent_arr_[$item['id']] = 2;
            }
        }
        if($agent_new && is_array($agent_new)){
            foreach($agent_new as $item){
                if(!isset($agent_arr_[$item['id']])){
                    $agent_arr_[$item['id']] = 1;
                }else{
                    $agent_arr_[$item['id']] = 3;
                }
            }
        }
        foreach($agent_arr_ as $k=>$v){

            if($v==1){
                //[后台添加]发生人事变动 ，经纪人所申请的人事申请自动作废
                $this->agent_apply_all_cancel($k);
                $data_insert[] = array(
                    'to_company_id'     =>      $company_id,
                    'to_company_name'   =>      $company_data['company_name'],
                    'from_company_id'   =>      -1,
                    'from_company_name' =>      null,
                    'agent_id'=>$k,
                    'status'=>1,
                    'create_date'=>date('Y-m-d H:i:s',time()),
                );
            }
            if($v==2){
                //[后台删除]发生人事变动 ，经纪人所申请的人事申请自动作废
                $this->agent_apply_all_cancel($k);
                $data_insert[] = array(
                    'to_company_id'     =>      -1,
                    'to_company_name'   =>      null,
                    'from_company_id'   =>      $company_id,
                    'from_company_name' =>      $company_data['company_name'],
                    'agent_id'=>$k,
                    'status'=>2,
                    'create_date'=>date('Y-m-d H:i:s',time()),
                );
            }

        }

        if($data_insert){
            $this->db->insert_batch('agent_track',$data_insert);
        }

    }

    /**
    *经纪人就业轨迹 公共存储方法
     * @param $agent_id             经纪人ID
     * @param $old_company_id       原公司
     * @param $new_company_id       新公司
     * @param $status               轨迹种类
     * @return bool
     * 因为企业存在多种分值，在计算结束后，无论结果如何都要相加计算，以方便之后排名和查看分数线
     */
    public function save_agent_track4common($agent_id, $old_company_id, $new_company_id, $status){
        $old_company = $this->db->select('company_name')->from('company_pending')->where('id',$old_company_id)->get()->row_array();
        $old_company_name = null;
        if ($old_company) 
            $old_company_name = $old_company['company_name'];

        $new_company = $this->db->select('company_name')->from('company_pending')->where('id',$new_company_id)->get()->row_array();
        $new_company_name = null;
        if ($new_company) 
            $new_company_name = $new_company['company_name'];

        $data_insert = array(
                    'to_company_id'     =>      $new_company_id,
                    'to_company_name'   =>      $new_company_name,
                    'from_company_id'   =>      $old_company_id,
                    'from_company_name' =>      $old_company_name,
                    'agent_id'  =>  $agent_id,
                    'status'    =>  $status,
                    'create_date'=>date('Y-m-d H:i:s',time()),
                );
        $this->db->insert('agent_track',$data_insert);
        return false;
    }

    //保存企业修改记录
    //第一 指记录保存成功的数据
    //第二 尽可能的保存多的数据，比如分数，备案号，审核状态，报备状态等信息
    public function save_log_company($company_id){
        $company_data = $this->db->select()->from('company_pending')->where('id',$company_id)->order_by('id','desc')->get()->row_array();
        $company_data['company_id']=$company_data['id'];
        unset($company_data['id']);
        unset($company_data['username']);
        unset($company_data['password']);
        unset($company_data['qx_num']);
        unset($company_data['cancel_date']);
        unset($company_data['cancel_user']);
        unset($company_data['cancel_remark']);
        $admin_info = $this->session->userdata('admin_info');
        $company_data['handle_user'] = $admin_info ? $admin_info['admin_id'] : -1;
        $company_data['handle_date'] = date('Y-m-d H:i:s',time());
        $this->db->insert('company_log',$company_data);
        $log_id = $this->db->insert_id();
        $this->db->select("img_path,m_img_path,folder,company_id,{$log_id} log_id")->from('company_pending_img');
        $this->db->where('company_id',$company_id);
        $data['img'] = $this->db->get()->result_array();
        if($data['img'])
            $this->db->insert_batch('company_log_img',$data['img']);
        $this->db->select("id agent_id,name,phone,job_code,card,company_id,{$log_id} log_id,wq,old_job_code,work_type,job_num")->from('agent');
        $this->db->where('company_id',$company_id);
        $data['agent'] = $this->db->get()->result_array();
        if($data['agent'])
            $this->db->insert_batch('company_log_agent',$data['agent']);
        $this->db->where('log_id', $log_id)->delete('company_log_icon');
        $this->db->select("a.icon_no,a.icon_class,a.`name`,a.short_name,b.company_id,a.type,(a.score * a.type) score,a.status,{$log_id} log_id")->from('fm_sys_score_icon a');
        $this->db->join('company_pending_icon b','a.icon_no = b.icon_no ','left');
        $this->db->where('company_id',$company_id);
        $log_icon = $this->db->get()->result_array();
        if($log_icon)
            $this->db->insert_batch('company_log_icon',$log_icon);
    }

    //第一 新增报备时 自动保存的 年审信息
    //第二 尽可能的保存多的数据，比如分数，备案号，审核状态，报备状态等信息
    public function save_pass_company($company_id){
        //先抓取年审年份 这里抓取符合当前时间的,最近一次年审,如果没有年审,就不进行下去
        //注意,如果真的出现,用户忘记先设置年审时间,就开始新增企业,使其没有出现年审申请,那就需要手动先设置年审时间在当天,然后手动提审
        $mdate = date('Y-m-d',time());
        $res_check_ = $this->db->select()->from('term')->where(array('begin_date <=' => $mdate))->order_by('begin_date','desc')->get()->row_array();
        if(!$res_check_)
            return false;
        //以防万一,还是判断下是否存在正在处理的 年审
        $check_ns_ = $this->db->select()->from('company_pass')->where('company_id',$company_id)->where_not_in('status', array(-1,3))->order_by('id','desc')->get()->row_array();
        if($check_ns_)
            return false;
        $company_data = $this->db->select()->from('company_pending')->where('id',$company_id)->order_by('id','desc')->get()->row_array();
        $company_data['company_id']=$company_data['id'];
        unset($company_data['id']);
        unset($company_data['annual_date']);
        unset($company_data['username']);
        unset($company_data['password']);
        unset($company_data['cancel_date']);
        unset($company_data['cancel_user']);
        unset($company_data['cancel_remark']);
        $company_data['annual_date'] = $res_check_['annual_year'];
        $company_data['status'] = 1;
        $admin_info = $this->session->userdata('admin_info');
        $company_data['tj_user'] = $admin_info ? $admin_info['admin_id'] : -1;
        $company_data['tj_date'] = date('Y-m-d H:i:s',time());
        $this->db->insert('company_pass',$company_data);
        $pass_id = $this->db->insert_id();


        $this->db->select("img_path,m_img_path,folder,company_id,{$pass_id} pass_id")->from('company_pending_img');
        $this->db->where('company_id',$company_id);
        $pass_img = $this->db->get()->result_array();
        if($pass_img)
            $this->db->insert_batch('company_pass_img',$pass_img);

        //经纪人信息 只做暂存,实际 只有在审核结束后有效
        $this->db->select("id agent_id,name,phone,job_code,card,company_id,wq,old_job_code,{$pass_id} pass_id")->from('agent');
        //$this->db->where('flag',2); //如果是离昆的就不要进行保存 //有什么信息就保存什么信息，真正是否显示，还是要看实际的状态
        $this->db->where('company_id',$company_id);
        $pass_agent = $this->db->get()->result_array();
        if($pass_agent)
            $this->db->insert_batch('company_pass_agent',$pass_agent);
        //结束前也更新下 company_pending的状态
        $this->db->where('id', $company_id)->update('company_pending', array('annual_date' => $company_data['annual_date'],'tj_date' => $company_data['tj_date'], 'status' => 1));
        return true;
    }

    //判断经纪人分数，并设置等级，和处理失信时的一些特殊处理
    public function handle_agent_score($agent_id){
        $agent_info = $this->db->select()->from('agent')->where('id', $agent_id)->get()->row_array();
        if(!$agent_info)
            return false;
        $grade_no_ = $this->db->select()->from('agent_grade')->where('min_score <=', $agent_info['score'])->order_by('grade_no','desc')->get()->row_array();
        if(!$grade_no_){
            $grade_no_['grade_no'] = 1;
        }
        if ($grade_no_['grade_no'] == 1) {
            $update_agent_ = array(
                'grade_no' => $grade_no_['grade_no'], 
                'company_id' => -1,
                'wq' => 1
            );
            if($agent_info['grade_no'] != 1)
                $update_agent_['forbid_time'] =  date('Y-m-d H:i:s',time());
            $this->db->where('id', $agent_id)->update('agent', $update_agent_);
            $company_info_ = $this->db->select('company_name')->from('company_pending')->where('id', $agent_info['company_id'])->get()->row_array();
            if ($company_info_) {
               $this->save_company_total_score($agent_info['company_id']);//重新计算企业信用分和异常状态
               $data_insert = array(
                    'to_company_id'         =>      -1,
                    'to_company_name'       =>      null,
                    'from_company_id'       =>      $agent_info['company_id'],
                    'from_company_name'     =>      $company_info_['company_name'],
                    'agent_id'              =>      $agent_id,
                    'status'                =>      3,
                    'create_date'           =>      date('Y-m-d H:i:s',time()),
                );
               $this->db->insert('agent_track',$data_insert);
            }
            //[经纪人失信]发生人事变动,不管是否真有人事变动 ，经纪人所申请的人事申请自动作废
            $this->agent_apply_all_cancel($agent_id);
            
        }else{
            $this->db->where('id', $agent_id)->update('agent', array('grade_no' => $grade_no_['grade_no']));
        }
        return true;
    }

    //判断经纪人状态，是否需要解绑操作
    public function handle_agent_flag($agent_id){
        $agent_info = $this->db->select()->from('agent')->where('id', $agent_id)->get()->row_array();
        if(!$agent_info)
            return false;
        $company_info_ = $this->db->select('company_name')->from('company_pending')->where('id', $agent_info['company_id'])->get()->row_array();
        if($agent_info['flag'] != 2 && $company_info_){
            $data_insert = array(
                'to_company_id'         =>      -1,
                'to_company_name'       =>      null,
                'from_company_id'       =>      $agent_info['company_id'],
                'from_company_name'     =>      $company_info_['company_name'],
                'agent_id'              =>      $agent_id,
                'create_date'           =>      date('Y-m-d H:i:s',time()),
            );
            switch($agent_info['flag']){
                case 1:
                    //[经纪人离昆]发生人事变动 ，经纪人所申请的人事申请自动作废
                    $this->agent_apply_all_cancel($agent_id);
                    $data_insert['status'] = 7;
                    break;
                case -1:
                    //[经纪人无效]发生人事变动 ，经纪人所申请的人事申请自动作废
                    $this->agent_apply_all_cancel($agent_id);
                    $data_insert['status'] = 6;
                    break;
                default:
                    return false;
            }
            $this->db->where('id', $agent_id)->update('agent', array('company_id' => -1, 'wq' => 1));
            $this->save_company_total_score($agent_info['company_id']);//重新计算企业信用分和异常状态
            $this->db->insert('agent_track',$data_insert);
        }
        return true;
    }

    //批量系统作废 人事申请
    public function agent_apply_all_cancel($agent_id, $err_remark = ''){
        $this->db->where('agent_id', $agent_id)->where('status', 1)->update('agent_apply',array(
                    'status' => -2,
                    'err_remark' => $err_remark,
                    'sdate' => date('Y-m-d H:i:s', time())
                ));
    }
}

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */