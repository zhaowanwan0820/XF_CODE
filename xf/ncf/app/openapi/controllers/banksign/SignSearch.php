<?php
namespace openapi\controllers\banksign;


use libs\web\Form;
use libs\utils\Logger;
use core\enum\SupervisionEnum;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Library\Idworker;
use core\service\supervision\SupervisionFinanceService;

/**
 * 协议支付-签约查询接口
 * http://jira.corp.ncfgroup.com/browse/WXPH-202
 * @author jinhaidong
 * @package openapi\controllers\deal
 */
class SignSearch extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'user_name' => ['filter' => 'required', "message" => "user_name is required"], //用户姓名
            'id_no' => ['filter' => 'required', "message" => "id_no is required"],
            'id_type' => ['filter' => 'string', 'option' => array('optional' => true)],
            'bank_no' => ['filter' => 'required', "message" => "bank_no is required"],
            'mobile' => ['filter' => 'required', "message" => "mobile is required"],
        );

        $this->form->rules = array_merge($this->sys_param_rules, $this->form->rules);
        if (!$this->form->validate()) {
            $this->setErr("ERR_PARAMS_ERROR", $this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke()
    {
        $data = $this->form->data;
        $user_name = trim($data['user_name']);
        $id_no = trim($data['id_no']);
        $id_type = empty($data['id_type']) ? 'IDC' : $data['id_type'];
        $bank_no = trim($data['bank_no']);
        $mobile = preg_match("/^1[3456789]\d{9}$/", trim($data['mobile'])) ? trim($data['mobile']) : '';

        if(empty($id_type)){
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，id_type 参数错误');
            return false;
        }
        if (empty($mobile)) {
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，mobile 参数错误');
            return false;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "协议支付-签约查询接口", json_encode($data))));


        $return = array('status' => 0 , 'err_msg' => '');
        $s = new SupervisionFinanceService();
        $params = array(
            'bankCardNo' => $bank_no,
            'realName' => $user_name,
            'certNo' => $id_no,
            'mobileNo' => $mobile,
            'certType' => $id_type,
            'contractChannelId' => 'R_UCF_PAY',
        );
        $res =  $s->notBindSignQuery($params);
        if($res['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
            $return['status'] = $res['respCode'];
            $return['err_msg'] = $res['respMsg'];
        }else{
            $return['status'] = ($res['data']['contractResult'] == 'Y') ? 1 : 0;
            $return['err_msg'] = ($res['data']['contractResult'] == 'Y') ? '已签约' : '未签约';
        }
        $this->json_data = $return;
        return true;
    }
}
