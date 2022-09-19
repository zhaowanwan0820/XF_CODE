<?php
/**
 * SendCloudCallBack 
 * SendCloud回调接口 用于修改发送邮件的送达状态及用户打开状态 
 * 
 * @uses BaseAction
 * @package 
 * @version $id$
 * @copyright 1997-2005 The PHP Group
 * @author zhanglei5 <zhanglei5@group.com> 
 * @license PHP Version 3.0 {@link http://www.php.net/license/3_0.txt}
 */
namespace web\controllers\email;
//error_reporting(E_ALL);ini_set('display_errors', 1);

use web\controllers\BaseAction;
use core\data\EmailData;
use libs\web\Form;
use libs\utils\Logger;

define('SC_KEY','nv8uakz9-d78a-sn68-3tgm-m2i6hecvzq');  //貌似应该放到conf文件中去
class SendCloudCallBack extends BaseAction {

    public function init() {
        $this->form = new Form();
        $this->form->rules = array(
            'event' => array('filter' => 'required','event 必要参数'),
            'token' => array('filter' => 'required','token 必要参数'),
            'signature' => array('filter' => 'required'),
            'timestamp' => array('filter' => 'required'),
            'emailId' => array('filter' => 'required'),
        );
        $this->form->validate();
    }

    public function invoke() {
        $params = $this->form->data; 
        $log_info = implode(' | ', array(__CLASS__, __FUNCTION__, json_encode($params)));
        Logger::info($log_info);

        $event = $params['event'];
        $time = $params['timestamp'];
        $token = $params['token'];
        $signature = $params['signature'];
        $emailId = $params['emailId'];

        $lc_sig = hash_hmac('sha256',$time.$token,SC_KEY);
        //验签通过
        if($lc_sig == $signature) {
            switch($event) {
            case 'deliver': //送达
                $data['receive_time'] = $time;
                $data['is_received'] = 1;
                break;
            case 'open':    //打开
                $data['open_time'] = $time;
                $data['is_opened'] = 1;
                break;
            }
            $rs = $this->rpc->local('DealMsgListService\updateStatusBySCId',array($data,$emailId));
            $log_info = implode(' | ', array(__CLASS__, __FUNCTION__, $rs,json_encode($data)));
            Logger::info($log_info);
        }else {
            $log_info = implode(' | ', array(__CLASS__, __FUNCTION__, '验签没通过'));
            Logger::info($log_info);
            echo '验签没通过';
            return;
        }


    }
}
 
