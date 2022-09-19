<?php
require_once APP_ROOT_PATH."system/libs/sms.php";  //引入接口

/**
 * 百分联通短信接口
 */
class CF_sms implements sms
{
    public $user_name = '';
    public $password = '';
    public $server_url = '';

    public $statuses = array(
        "0"  => "没有需要取得的数据",
        "1"  => "发送成功",
        "-1" => "发送失败",
        "-2" => "参数错误",
        "-3" => "序列号密码不正确",
    );

    /**
     * 读取配置信息
     *
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function config()
    {
        $sms_lang = array(
            'ContentType'    => '消息类型',
            'ContentType_15' => '普通短信通道(15)',
            'ContentType_8'  => '长短信通道(8)',
        );
        $config = array(
            'ContentType' => array(
                'INPUT_TYPE' => '1',
                'VALUES'     => array(15,8)
            ),
        );

        $module['class_name'] = 'CF';
        $module['name']       = "百分联通(国内)";
        $module['lang']       = $sms_lang;
        $module['config']     = $config;
        $module['type']       = $GLOBALS['dict']['SMS_CHANNEL_TYPE']['DOMESTIC']; //国内短信
        $module['server_url'] = 'http://cf.lmobile.cn/submitdata/Service.asmx';

        return $module;
    }

    public function __construct($smsInfo)
    {
        if(!empty($smsInfo)) {
            $this->user_name  = $smsInfo['user_name'];
            $this->password   = $smsInfo['password'];
            $this->server_url = $smsInfo['server_url'];
        }
    }

    /**
     * 发送短信
     *
     * @access public
     * @return 
     */
    public function sendSMS($mobile = '',$content = '')
    {
        $result = array(
            'status' => 0,
            'msg'    => ""
        );
        $content = urlencode($content);
        $url = $this->server_url."/g_Submit?sname=".$this->user_name."&spwd=".$this->password."&scorpid=&sprdid=1012818&sdst=".$mobile."&smsg=".$content;
       
        $res = $this->get($url);
        $res = (array)simplexml_load_string($res);
        
        if(intval($res['State']) === 0){
            $result['status'] = 1;
           
        } else {
           $result['msg'] = $this->statuses[$res['State']];
        }

        return $result;
    }

    /**
     * 发起GET请求
     *
     * @param   参数说明
     * @return  void
     * @version 1.0
     * @author  <llx>lixing.li@3g2win.com
     */
    public function get($durl)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $durl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);  
        curl_setopt($ch, CURLOPT_USERAGENT, '');
        curl_setopt($ch, CURLOPT_REFERER,'');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 发起POST请求
     *
     * @return void
     * @author 杨晓恒 <yangxiaoheng@ucfgroup.com>
     **/
    public function post($url, $fields)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $durl);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);  
        curl_setopt($ch, CURLOPT_USERAGENT, '');
        curl_setopt($ch, CURLOPT_REFERER,'');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    public function getSmsInfo()
    {
        $config = $this->config();
        return $config['name'];
    }

    public function check_fee()
    {
        return "无法查询";
    }
}


