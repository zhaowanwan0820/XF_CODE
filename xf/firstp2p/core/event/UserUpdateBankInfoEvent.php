<?php
/**
 * 同步修改用户绑卡信息，超级账户、存管账户三端同步
 */
namespace core\event;

use NCFGroup\Common\Library\GTM\GlobalTransactionManager;
use libs\utils\PaymentApi;
use core\event\BaseEvent;
use core\service\PaymentService;
use core\service\SupervisionService;
use core\service\SupervisionAccountService;
use core\service\UserService;
use core\dao\BanklistModel;
use core\dao\UserModel;
use core\tmevent\supervision\WxUpdateUserBankCardEvent;
use core\tmevent\supervision\WxUpdateBankListEvent;
use core\tmevent\supervision\UcfpayUpdateUserBankCardEvent;
use core\tmevent\supervision\SupervisionUpdateUserBankCardEvent;
use core\tmevent\supervision\UcfpayEnterpriseUpdateBankEvent;

class UserUpdateBankInfoEvent extends BaseEvent {
    private $banklistId;
    private $bankId;
    private $branchNo;
    private $branchName;
    private $userId;
    private $userBankData;
    private $userBaseInfo = array();
    private $isUcfpayUser = false;
    private $supervisionAccountObj = null;
    private $svService = null;

    public function __construct($banklistId, $branchNo, $branchName, $userBankData) {
        $this->banklistId = (int)$banklistId;
        $this->branchNo = addslashes(trim($branchNo));
        $this->branchName = addslashes(trim($branchName));
        $this->userId = $GLOBALS['user_info']['id'] = !empty($userBankData['user_id']) ? (int)$userBankData['user_id'] : 0;
        $this->userBankData = $userBankData;
        $this->supervisionAccountObj = new SupervisionAccountService();
        $this->svService = new SupervisionService();
    }

    public function execute() {
        if (empty($this->banklistId) || empty($this->userId)
            || empty($this->branchName) || empty($this->userBankData)) {
            return true;
        }

        // 获取用户基本信息
        $this->userBaseInfo = UserModel::instance()->find($this->userId, 'id,user_name,user_type,payment_user_id,supervision_user_id', true);
        if (empty($this->userBaseInfo) || $this->userBaseInfo['id'] <= 0) {
            return true;
        }

        // 检查是否企业用户
        $userObj = new UserService($this->userId);
        $isEnterprise = $userObj->isEnterpriseUser();
        if ($isEnterprise === true) {
            return $this->_updateUserBankCardForEnterprise();
        }
        return $this->_updateUserBankCardForPerson();
    }

    /**
     * 更新绑卡信息-普通用户
     */
    private function _updateUserBankCardForPerson() {
        try{
            $gtm = new GlobalTransactionManager();
            $gtm->setName('AdminBankBranchForPerson');

            // 超级账户-普通用户银行卡修改
            $this->userBankData['bankzone'] = $this->branchName;
            $paymentService = new PaymentService();
            $bankcardInfo = $paymentService->getBankcardInfo($this->userBankData, false, 0, $this->userId);
            // 新更新的银行支行名称
            $bankcardInfo['branchBankId'] = $this->branchNo;
            // 是否开通超级账户
            $isUcfpayUser = $this->supervisionAccountObj->isUcfpayUser($this->userId);
            if ($isUcfpayUser) {
                // 不管是新添加银行卡还是修改旧银行卡，都发送银行卡绑定信息， 如果绑定失败，则不进行修改
                $gtm->addEvent(new UcfpayUpdateUserBankCardEvent(['user_id'=>$this->userId], $bankcardInfo));
            }

            // 用户已在存管账户开户或者是存管预开户用户
            if ($this->supervisionAccountObj->isSupervisionUser($this->userId) || $this->svService->isUpgradeAccount($this->userId)) {
                $gtm->addEvent(new SupervisionUpdateUserBankCardEvent($this->userId));
            }

            // 更新用户绑卡的支行名称
            $userBankcardInfo = array(
                'bank_card_name' => addslashes($this->userBankData['card_name']), //开户姓名
                'c_region_lv1' => (int)$this->userBankData['region_lv1'],
                'c_region_lv2' => (int)$this->userBankData['region_lv2'],
                'c_region_lv3' => (int)$this->userBankData['region_lv3'],
                'c_region_lv4' => (int)$this->userBankData['region_lv4'],
                'bank_bankzone' => $this->userBankData['bankzone'],
                'bank_bankcard' => addslashes($this->userBankData['bankcard']),
                'bank_id' => (int)$this->userBankData['bank_id'],
                'short_name' => $bankcardInfo['bankCode'],
                'bank_name' => $bankcardInfo['bankName'],
                'id' => $this->userId,
                'bankcard_id' => (int)$this->userBankData['id'],
                'card_type' => (int)$this->userBankData['card_type'],
            );
            $gtm->addEvent(new WxUpdateUserBankCardEvent($userBankcardInfo));

            $gtmRet = $gtm->execute();
            if (true !== $gtmRet) {
                throw new \Exception($gtm->getError());
            }
            PaymentApi::log(implode('|', array(__CLASS__, __FUNCTION__, APP, $this->userId, sprintf('AdminEditBankBranchNameForPerson_Success,userBankData:%s', json_encode($this->userBankData)))));
            return true;
        } catch (\Exception $e) {
            PaymentApi::log(implode('|', array(__CLASS__, __FUNCTION__, APP, $this->userId, sprintf('AdminEditBankBranchNameForPerson_Failed,ExceptionCode:%s,ExceptionMsg:%s', $e->getCode(), $e->getMessage()))));
            return false;
        }
    }

    /**
     * 更新绑卡信息-企业用户
     */
    private function _updateUserBankCardForEnterprise() {
        try{
            if (!empty($this->userBankData['bankzone']) && $this->userBankData['bankzone'] === $this->branchName) {
                return true;
            }

            $gtm = new GlobalTransactionManager();
            $gtm->setName('AdminBankBranchForEnterprise');

            // 超级账户-企业用户银行卡修改
            // 旧的绑卡数据
            $paymentService = new PaymentService();
            $bankcardInfoOld = $paymentService->getBankcardInfo($this->userBankData, false, 0, $this->userId);
            $bankcardInfoOld['branchBankId'] = $this->branchNo;

            // 新的绑卡数据
            $this->userBankData['bankzone'] = $this->branchName;
            $paymentService = new PaymentService();
            $bankcardInfo = $paymentService->getBankcardInfo($this->userBankData, false, 0, $this->userId);
            // 新更新的银行支行名称
            $bankcardInfo['branchBankId'] = $this->branchNo;

            // 是否开通超级账户
            $isUcfpayUser = $this->supervisionAccountObj->isUcfpayUser($this->userId);
            if ($isUcfpayUser) {
                $gtm->addEvent(new UcfpayEnterpriseUpdateBankEvent($this->userId, $bankcardInfo, $this->userBaseInfo, $bankcardInfoOld));
            }

            // 用户已在存管账户开户或者是预开户用户
            if ($this->supervisionAccountObj->isSupervisionUser($this->userId) || $this->svService->isUpgradeAccount($this->userId)) {
                $gtm->addEvent(new SupervisionUpdateUserBankCardEvent($this->userId));
            }

            // 更新用户绑卡的支行名称
            $userBankcardInfo = array(
                'bank_card_name' => addslashes($this->userBankData['card_name']), //开户姓名
                'c_region_lv1' => (int)$this->userBankData['region_lv1'],
                'c_region_lv2' => (int)$this->userBankData['region_lv2'],
                'c_region_lv3' => (int)$this->userBankData['region_lv3'],
                'c_region_lv4' => (int)$this->userBankData['region_lv4'],
                'bank_bankzone' => $this->userBankData['bankzone'],
                'bank_bankcard' => addslashes($this->userBankData['bankcard']),
                'bank_id' => (int)$this->userBankData['bank_id'],
                'short_name' => $bankcardInfo['bankCode'],
                'bank_name' => $bankcardInfo['bankName'],
                'id' => $this->userId,
                'bankcard_id' => (int)$this->userBankData['id'],
                'card_type' => (int)$this->userBankData['card_type'],
            );
            $gtm->addEvent(new WxUpdateUserBankCardEvent($userBankcardInfo));

            $gtmRet = $gtm->execute();
            if (true !== $gtmRet) {
                throw new \Exception($gtm->getError());
            }
            PaymentApi::log(implode('|', array(__CLASS__, __FUNCTION__, APP, $this->userId, sprintf('AdminEditBankBranchNameForEnterprise_Success,userBankData:%s', json_encode($this->userBankData)))));
            return true;
        } catch (\Exception $e) {
            PaymentApi::log(implode('|', array(__CLASS__, __FUNCTION__, APP, $this->userId, sprintf('AdminEditBankBranchNameForEnterprise_Failed,ExceptionCode:%s,ExceptionMsg:%s', $e->getCode(), $e->getMessage()))));
            return false;
        }
    }

    public function alertMails() {
        return array('wangqunqiang@ucfgroup.com', 'guofeng3@ucfgroup.com');
    }
}