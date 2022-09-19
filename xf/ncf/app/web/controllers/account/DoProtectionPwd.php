<?php
/**
 * @author liuzhenpeng
 * @abstract 保存密保
 * @date 2015-09-08
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\LogRegLoginService;
use core\service\bonus;
use libs\utils\Logger;

class DoProtectionPwd extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
	    $protectionResult = array("errorCode" => 0, "errorMsg" => '');
        $this->form = new Form('post');
        $this->form->rules = array(
            'answer1' => array('filter' => 'string'),
            'answer2' => array('filter' => 'string'),
            'answer3' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $protectionResult['errorCode'] = -1;
            $protectionResult['errorMsg'] = $this->form->getErrorMsg();
            echo json_encode($protectionResult);
            return false;
        }
    }

    public function invoke() {
        $user_id = intval($GLOBALS['user_info']['id']);
        if(!$user_id){
            $protectionResult['errorCode'] = -2;
            $protectionResult['errorMsg']  = "尚未登录";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        if($_SESSION['user_protion_is_mobile'] !=1 && $_SESSION['user_protion_is_answer'] != 1){
            $protectionResult['errorCode'] = -7;
            $protectionResult['errorMsg']  = "无法访问";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        /*从session取出数据校验*/ 
        $user_protect = isset($_SESSION['user_protect']) ? unserialize($_SESSION['user_protect']) : '';
        if(!is_array($user_protect) || !count($user_protect)){
            $protectionResult['errorCode'] = -3;
            $protectionResult['errorMsg']  = '数据发生异常';
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        /*校验密保完整性*/
        $answer[] = $this->form->data['answer1'];
        $answer[] = $this->form->data['answer2'];
        $answer[] = $this->form->data['answer3'];
        foreach($answer as $values){
            if(empty($values)){
                $protectionResult['errorCode'] = -4;
                $protectionResult['errorMsg']  = "请填写答案";
                setLog($protectionResult);
                die(json_encode($protectionResult));
            }
        }

        $protectio_answer = array();
        foreach($user_protect as $vals){
            $protectio_answer[] = $vals[1];
        }

        $j = 1;
        $is_output = false;
        for($i=0; $i<3; $i++){
            if($protectio_answer[$i] != $answer[$i]){
                $is_output= true;
                $protectionResult['errorCode'] = -5;
                $protectionResult['errorData']['answer'.$j] = '与上一步设置的答案不符';
            }
            $j++;
        }

        if($is_output == true){
            die(json_encode($protectionResult));
        }

        if($res = set_user_security($user_id, $user_protect) == false){
            $protectionResult['errorCode'] = -6;
            $protectionResult['errorMsg']  = "发生系统错误";
            die(json_encode($protectionResult));
        }

        $_SESSION['user_protion_is_mobile'] = 0; 
        $_SESSION['user_protion_is_answer'] = 0;

        $protectionResult['errorCode'] = 0;
        $protectionResult['url'] = "/account/ProtectPwdSuccess";
        $protectionResult['errorMsg']  = "ok";
        echo json_encode($protectionResult);
    }

} 
