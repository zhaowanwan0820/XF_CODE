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

class DoBackInputPwdProtect extends BaseAction {

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

        $answer[] = explode('^', $this->form->data['answer1']);
        $answer[] = explode('^', $this->form->data['answer2']);
        $answer[] = explode('^', $this->form->data['answer3']);
        foreach($answer as $key => $values){
            if(empty($values[0])){
                $protectionResult['errorCode'] = -3;
                $protectionResult['errorData']['ques' . $key+1] = '请完善密保问题';
                $protectionResult['errorMsg']  = "请完善密保资料";
                setLog($protectionResult);
                die(json_encode($protectionResult));
            }

            if(empty($values[1])){
                $protectionResult['errorCode'] = -4;
                $protectionResult['errorData']['answer' . $key+1] = '请完善密答案';
                $protectionResult['errorMsg']  = "请完善密保资料";
                setLog($protectionResult);
                die(json_encode($protectionResult));
            }

            $question[] = $values[0];
            $answers[]   = $values[1];
            /*构建hash表*/
            $answer_key_list[md5($values[0])]++;
        }

        /*检查问题是否有重复*/
        foreach($answer_key_list as $total){
            if($total >1){
                $protectionResult['errorCode'] = -5;
                $protectionResult['errorMsg']  = "密保问题不能有重复";
                setLog($protectionResult);
                die(json_encode($protectionResult));
            }
        }

        /*检查问题和答案是否有重复*/
        foreach($question as $values){
            if(in_array($values, $answers)){
                $protectionResult['errorCode'] = -6;
                $protectionResult['errorMsg']  = "密保问题和答案不能有重复";
                setLog($protectionResult);
                die(json_encode($protectionResult));
            }
        }

        $_SESSION['user_protect'] = serialize($answer);
        $protectionResult['errorCode'] = 0;
        $protectionResult['url']       = '/account/ProtectPwdNext';
        $protectionResult['errorMsg']  = "ok";
        die(json_encode($protectionResult));
    }

} 
