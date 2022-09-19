<?php
/**
 * @author liuzhenpeng
 * @abstract 校验密保问题答案
 * @date 2015-09-08
 */

namespace web\controllers\account;

use libs\web\Form;
use web\controllers\BaseAction;
use core\service\LogRegLoginService;
use core\service\bonus;
use libs\utils\Logger;

class DoCheckProtectionPwd extends BaseAction {

    private $_errorCode = 0;
    private $_errorMsg = null;

    public function init() {
	    $loginResult = array("errorCode" => 0, "errorMsg" => '');
        $this->form = new Form('post');
        $this->form->rules = array(
            'answer1' => array('filter' => 'string'),
            'answer2' => array('filter' => 'string'),
            'answer3' => array('filter' => 'string'),
        );

        if (!$this->form->validate()) {
            $loginResult['errorCode'] = -1;
            $loginResult['errorMsg'] = $this->form->getErrorMsg();
            echo json_encode($loginResult);
            return false;
        }
    }

    public function invoke() {
		$protectionResult = array("errorCode" => 0, "errorMsg" => '');

        $user_id = intval($GLOBALS['user_info']['id']);
        if(!$user_id){
            $protectionResult['errorCode'] = -1;
            $protectionResult['errorMsg']  = "尚未登录";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        $user_res = get_user_security($user_id);
        if(!$user_res){
            $protectionResult['errorCode'] = -2;
            $protectionResult['errorMsg']  = "还没有设置密保";
            setLog($protectionResult);
            die(json_encode($protectionResult));
        }

        $answer[] = $this->form->data['answer1'];
        $answer[] = $this->form->data['answer2'];
        $answer[] = $this->form->data['answer3'];
        foreach($answer as $values){
            if(empty($values)){
                $protectionResult['errorCode'] = -3;
                $protectionResult['errorMsg']  = "请完善密保资料";
                setLog($protectionResult);
                die(json_encode($protectionResult));
            }
        }
        $answer_data = $user_res['data'];
        $i = 0;
        foreach($answer_data as $values){
            $answer_result[] = $values[$i][1];
            $i++;
        }

        $k = 0;
        for($i=0; $i<3; $i++){
            if($answer[$i] != $answer_result[$k]){
                $protectionResult['errorCode'] = -4;
                $protectionResult['errorMsg']  = "答案不正确";
                setLog($protectionResult);
                die(json_encode($protectionResult));
            }
           $k++; 
        }

        unset($_SESSION['user_protect']);
        $_SESSION['user_protion_is_answer'] = 1;

        $protectionResult['errorCode'] = 0;
        $protectionResult['url'] = '/account/ProtectPwd';
        $protectionResult['errorMsg']  = "ok";
        echo json_encode($protectionResult);
    }

} 
