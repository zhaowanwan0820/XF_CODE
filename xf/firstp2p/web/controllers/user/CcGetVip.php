<?php
/**
 * call center使用 获取vip信息
 * @author <zhaoxiaoan@ucfgroup.com>
 * 
 */

namespace web\controllers\user;

use web\controllers\BaseAction;
use libs\web\Form;
use core\service\vip\VipService;

\FP::import("libs.utils.logger");

class CcGetVip extends BaseAction {

    private $log_m = '';
    private $signToken = '0e4f021a3b233a3d59db44314bb48b72';

    public function init() {
        $this->log_m = __CLASS__.' ';

        $ip = get_client_ip();
        $white_list = array('10.12.160.97', '10.12.160.102', '10.12.160.103', '223.203.210.18');
        if (!in_array($ip,$white_list)){
            \logger::info($this->log_m.'ip forbidden '.$ip);
           // $this->errorXml('param error');
        }

        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required','message' => '参数错误'),
            'mobile' => array('filter' => 'required','message' => '参数错误'),
        );

       if (!$this->form->validate()){
            \logger::info($this->log_m.'param error');
            $this->errorXml('param error');
        }
	}
    
	public function invoke(){

        $data = $this->form->data;
        
        $user_vip_service = new VipService();
        if (empty($data['token']) || $data['token'] != $this->signToken){
            \logger::info($this->log_m.'param error');
            $this->errorXml('param error');
        }

        $user_vip_info = $user_vip_service->getVipInfoByMobile($data['mobile']);
        if (empty($user_vip_info)){
            // 用户不存在
            $this->errorXml('user not existed ');
        }
        \logger::info($this->log_m.' mobile|'.$data['mobile'].'|level|'.$user_vip_info['service_grade']);
        $this->SuccessXml('', $user_vip_info['service_grade']);
    }

    /**
     * 给cc返回vip xml数据
     * @param $type
     * @param $level
     * @return string(xml)
     */
    private function SuccessXml($type,$level){
        echo  '<?xml version="1.0" encoding="UTF-8"?><vxml xmlns="http://www.w3.org/2001/vxml" xsi:schemaLocation="http://www.w3.org/2001/vxml http://www.w3.org/TR/voicexml20/vxml.xsd" version="2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"><form><var expr="'.$type.'" name="type" /><var expr="'.$level.'" name="level" /><block><return namelist="type level"/></block></form></vxml>';
        exit;
    }

    /**
     * 给cc返回错误消息
     * @param $errorMsg
     * @return string
     */
    private function errorXml($errorMsg){
        echo '<?xml version="1.0" encoding="UTF-8"?>
<vxml xmlns="http://www.w3.org/2001/vxml" xsi:schemaLocation="http://www.w3.org/2001/vxml http://www.w3.org/TR/voicexml20/vxml.xsd" version="2.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"> <form>
                <var expr="0" name="type"/>
                <var expr="0" name="level"/>
                <block>
                    <return namelist="type level"/>
                </block>

        </form></vxml>';
        exit;
    }
}
