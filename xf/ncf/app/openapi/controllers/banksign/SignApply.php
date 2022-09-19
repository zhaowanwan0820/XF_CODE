<?php
namespace openapi\controllers\banksign;

use libs\encrypt\DES;
use libs\utils\DBDes;
use libs\web\Form;
use libs\utils\Logger;
use openapi\lib\Tools;
use core\enum\SupervisionEnum;
use core\enum\ThirdpartyDkEnum;
use core\service\user\UserService;
use openapi\controllers\BaseAction;
use NCFGroup\Common\Library\Idworker;
use core\dao\thirdparty\ThirdpartyDkModel;
use core\service\banksign\BankSignService;
use core\service\thirdparty\ThirdpartyDkService;
use core\service\supervision\SupervisionFinanceService;

/**
 * 协议支付-签约申请
 * http://jira.corp.ncfgroup.com/browse/WXPH-202
 * @author jinhaidong
 * @package openapi\controllers\deal
 */
class SignApply extends BaseAction
{

    public function init()
    {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'wx_open_id'   => ['filter' => 'required', 'message' => "wx_open_id is error"],
            'out_order_id' => ['filter' => 'required', "message" => "out_order_id is error"],
            'user_name' => ['filter' => 'required', "message" => "user_name is required"], //用户姓名
            'id_no' => ['filter' => 'required', "message" => "id_no is required"],
            'id_type' => ['filter' => 'string', 'option' => array('optional' => true)],
            'bank_no' => ['filter' => 'required', "message" => "bank_no is required"],
            'mobile' => ['filter' => 'required', "message" => "mobile is required"],
            'notify_url' => ['filter' => 'string', 'option' => array('optional' => true)],
            'return_url' => ['filter' => 'string', 'option' => array('optional' => true)],//签约成功后的资产端跳转地址
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
        $wxOpenId     = trim($data['wx_open_id']);
        $outerOrderId = trim($data['out_order_id']);
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
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，mobile参数错误');
            return false;
        }

        $userId = Tools::getUserIdByOpenID($wxOpenId);

        if(!$userId){
            $this->setErr("ERR_PARAMS_ERROR", '请求错误，用户信息不存在');
            return false;
        }

        Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "协议支付-签约申请接口", json_encode($data))));

        $return = array('status' => 0,'err_msg' => '');

        // 资产端需要每次更换单号进行申请
        $outOrderInfo = ThirdpartyDkService::getThirdPartyByOutOrderId($outerOrderId, $data['client_id']);
        if(!empty($outOrderInfo)){
            $return['status'] = 2;
            $return['err_msg'] = '订单已存在:'.$outerOrderId;
            $this->json_data = $return;
            return true;
        }


        // 请求支付申请签约
        $sfs = new SupervisionFinanceService();
        $orderId = Idworker::instance()->getId();
        $params = array(
            'userId' => $userId,
            'orderId' => $orderId,
            'remark' => '',
            'realName' => $data['user_name'],
            'certNo' => $data['id_no'],
            'certType' => $data['id_type'],
            'bankCardNo' => $data['bank_no'],
            'mobile' => $data['mobile'],
        );


        try{
            $res = $sfs->noBindCardSign($params);
            if($res['status'] != SupervisionEnum::RESPONSE_SUCCESS) {
                throw new \Exception('协议支付-签约申请失败:'.$res['respMsg']);
            }

            $return['sign_url'] = $this->getSignUrl($orderId);
            $thirdpartyParams = array(
                'user_name' => $data['user_name'],
                'id_no' => DBDes::encryptOneValue($data['id_no']),
                'id_type' => $data['id_type'],
                'bank_no' => DBDes::encryptOneValue($data['bank_no']),
                'mobile' => DBDes::encryptOneValue($data['mobile']),
                'return_url' => addslashes($data['return_url']),
                'notify_url' => addslashes($data['notify_url']),
            );
            $thirdpartyDkModel = new ThirdpartyDkModel();
            $thirdpartyDkModel->outer_order_id = $outerOrderId;
            $thirdpartyDkModel->order_id       = $orderId;
            $thirdpartyDkModel->client_id      = $data['client_id'];
            $thirdpartyDkModel->status         = ThirdpartyDkEnum::REQUEST_STATUS_SUCCESS;
            $thirdpartyDkModel->notify_url     = $data['notify_url'];
            $thirdpartyDkModel->create_time    = time();
            $thirdpartyDkModel->update_time    = time();
            $thirdpartyDkModel->type           = ThirdpartyDkEnum::SERVICE_TYPE_BANKSIGN;
            $thirdpartyDkModel->params         = json_encode($thirdpartyParams);

            if ($thirdpartyDkModel->insert() === false) {
                throw new \Exception(sprintf('insert fail: %s', 'ThirdpartyDk'));
            }
        }catch (\Exception $ex){
            $return['status']  = '-1';
            $return['err_msg'] = $ex->getMessage();
            $this->json_data = $return;
            Logger::info(implode(" | ", array(__CLASS__, __FUNCTION__, __LINE__, "协议支付-签约申请失败", $ex->getMessage())));
            return true;
        }
        $this->json_data = $return;
        return true;
    }

    private function getSignUrl($token){
        $http = app_conf('ENV_FLAG') == 'online' ? 'https://' : 'http://';
        $token = BankSignService::encToken($token);
        $req = array('token' => $token);
        return $http . app_conf("FIRSTP2P_CN_DOMAIN") . "/banksign?" . http_build_query($req);
    }
}
