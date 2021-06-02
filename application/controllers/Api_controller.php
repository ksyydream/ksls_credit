<?php
/**
 * Created by PhpStorm.
 * User: bin.shen
 * Date: 5/31/16
 * Time: 16:23
 */

if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api_controller extends MY_Controller
{
    protected $wxconfig = array();
    public $token = '';
    public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        $this->load->model('sys_model');
        $this->load->helper('url');

    }


    public function set_base_code($token){
        require_once (APPPATH . 'libraries/Base64.php');
        try{
            $token = base64_decode($token);
            $token = base64::decrypt($token, $this->config->item('token_key'));
            $token = explode('_', $token);
            if($token[0]!= 'CX') return -1;
            $t = time() - $token[2];
            if($t >= 60 * 60) return -2;
        }catch(Exception $e){
            return -3;
        }
        return (int)$token[1];
    }

    public function get_header_token(){
        if (function_exists('getallheaders')){
            foreach (getallheaders() as $name => $value) {
                if($name == 'Token'){
                    return $value;
                }
            }
            return -1;
        }else{
            $hears_ = $this->getallheaders4nginx();
            foreach ($hears_ as $name => $value) {
                if($name == 'Token'){
                    return $value;
                }
            }
            return -1;
        }

    }

    public function getallheaders4nginx()
    {
        $headers = array ();
        foreach ($_SERVER as $name => $value)
        {
            if (substr($name, 0, 5) == 'HTTP_')
            {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }

    public function set_token_uid($uid,$role_name){
        require_once(APPPATH ."libraries/Base64.php");
        $uid = $role_name . '_' .$uid.'_'.time();
        $uid = Base64::encrypt($uid, $this->config->item('token_key'));
        return base64_encode($uid);
    }

    public function get_token_uid($token,$role_name){
        $token = base64_decode($token);
        require_once(APPPATH ."libraries/Base64.php");
        $token = Base64::decrypt($token, $this->config->item('token_key'));
        $token = explode('_', $token);
        if($token[0]!= $role_name) return 0;
        return (int)$token[1];
    }

}