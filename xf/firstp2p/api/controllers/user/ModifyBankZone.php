<?php
namespace api\controllers\user;

use libs\web\Form;
use api\controllers\AppBaseAction;
use core\service\BankService;
use core\dao\UserBankcardModel;

/**
 *
 * 修改银行支行名称接口
 *
 * @uses AppBaseAction
 * @package
 */
class ModifyBankZone extends AppBaseAction {
    public function init() {
        parent::init();
        $this->form = new Form();
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'bankzone' => array('filter' => 'string'),
            'region_lv1' => array('filter' => 'int'),
            'region_lv2' => array('filter' => 'int'),
            'region_lv3' => array('filter' => 'int'),
            'region_lv4' => array('filter' => 'int'),
        );
        if (!$this->form->validate()) {
            $this->setErr($this->form->getErrorMsg());
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $loginUser = $this->getUserByToken();
        if (empty($loginUser)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        // 查询用户绑卡记录
        $userBankInfo = UserBankcardModel::instance()->getNewCardByUserId($loginUser['id']);
        // 更新银行支行名称
        if (!empty($data['bankzone']) && !empty($data['region_lv2']) && !empty($data['region_lv3'])) {
            // 没有绑卡记录
            if (empty($userBankInfo)) {
                throw new \Exception('用户尚未绑卡');
            }

            // 默认中国
            $data['region_lv1'] = !empty($data['region_lv1']) ? (int)$data['region_lv1'] : 1;
            // 默认区县
            $data['region_lv4'] = !empty($data['region_lv4']) ? (int)$data['region_lv4'] : 0;
            // 更新银行支行名称
            $bankData = [
                'bankzone'=>addslashes($data['bankzone']), 'region_lv1'=>$data['region_lv1'], 'region_lv2'=>(int)$data['region_lv2'],
                'region_lv3'=>(int)$data['region_lv3'], 'region_lv4'=>$data['region_lv4'],
            ];
            $userBankRet = UserBankcardModel::instance()->updateCard($userBankInfo['id'], $bankData);
            $this->json_data = ['ret'=>$userBankRet];
            return true;
        }

        // 获取银行名称
        $bankService = new BankService();
        $bankInfo = $bankService->getBank($userBankInfo['bank_id']);
        if (empty($bankInfo)) {
            $this->setErr('ERR_MANUAL_REASON', '暂无银行信息');
            return false;
        }

        $result = ['realName'=>nameFormat($loginUser['real_name']), 'idno'=>idnoFormat($loginUser['idno']),
            'bankName'=>$bankInfo['name'], 'bankCard'=>formatBankcard($userBankInfo['bankcard']),
            'bankZone'=>$userBankInfo['bankzone'], 'regionLv1'=>(int)$userBankInfo['region_lv1'],
            'regionLv2'=>(int)$userBankInfo['region_lv2'], 'regionLv3'=>(int)$userBankInfo['region_lv3'],
            'regionLv4'=>(int)$userBankInfo['region_lv4'],
        ];
        $this->json_data = $result;
        return true;
    }
}
