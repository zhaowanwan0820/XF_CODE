<?php
require_once APP_ROOT_PATH."system/sms/MD_sms.php";  //引入接口

/**
 * 漫道短信接口
 */
class MDI_sms extends MD_sms
{
    public $statuses = array(
        "-1"  => "重复注册",
        "-2"  => "帐号/密码不正确",
        "-4"  => "余额不足以支持本次发送",
        "-5"  => "数据格式错误",
        "-6"  => "参数有误",
        "-9"  => "扩展码权限错误",
        "-10" => "内容长度长",
        "-12" => "序列号状态错误",
        "-14" => "服务器写文件失败",
        "-18" => "上次提交没有等待返回不能继续提交",
        "-19" => "禁止同时使用多个接口地址",
        "-20" => "相同手机号，相同内容重复提交",
        "-22" => "Ip鉴权失败",
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
            'ContentType' => '消息类型',
            'ContentType_15' => '普通短信通道(15)',
            'ContentType_8'  => '长短信通道(8)',
        );
        $config = array(
            'ContentType' => array(
                'INPUT_TYPE' => '1',
                'VALUES'     => array(15,8)
            ),
        );

        $module['class_name'] = 'MDI';
        $module['name']       = "漫道短信(国际)";
        $module['lang']       = $sms_lang;
        $module['config']     = $config;
        $module['type']       = $GLOBALS['dict']['SMS_CHANNEL_TYPE']['INTERNATIONAL'];
        $module['server_url'] = 'http://sdk2.entinfo.cn:8060';

        return $module;
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
        //$content = iconv('UTF-8','GB2312//IGNORE',$content);
        //$content = urlencode($content);
        $url = $this->server_url."/gjWebService.asmx/mdSmsSend_g?sn=".$this->user_name."&pwd=".$this->password."&mobile=".$mobile."&content=".$content."&ext=&stime=&rrid=";
        $res = $this->get($url);
        $res = $this->parseResult($res);
        if(intval($res) > 0 ){
            $result['status'] = 1;
        } else {
            $result['msg'] = $this->statuses[$res];
        }

        return $result;
    }

    private function parseResult($res){
        if(preg_match_all('|<string xmlns="http://tempuri.org/">(.*)</string>|U', $res, $matches)){
                return $matches[1][0];
        }
    }
}
