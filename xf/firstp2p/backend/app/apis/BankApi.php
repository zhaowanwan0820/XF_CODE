<?php

namespace NCFGroup\Ptp\Apis;

use NCFGroup\Common\Library\ApiBackend;
use libs\utils\PaymentApi;
use core\dao\BankModel;
use core\dao\UserBankcardModel;
use core\dao\BanklistModel;
use core\service\BankService;
use core\service\BanklistService;
use core\service\AccountService;
use core\service\UserBankcardService;
use core\service\PaymentService;

/**
 * 银行卡相关接口
 */
class BankApi extends ApiBackend {
    /**
     * 根据银行卡联行号查询银行基本信息
     * @param $bankId int id
     * @return array
     */
    public function getBankInfoByBankId() {
        $bankId = $this->getParam('bankId');
        if (empty($bankId)) {
            return $this->formatResult(array());
        }

        $fields = $this->getParam('fields', '*');
        $res = BankModel::instance()->find($bankId, $fields, true);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 获取根据银行名字银行卡信息
     * @param $name string 银行名称
     * @return array
     */
    public function getBankInfoByName() {
        $name = $this->getParam('name');
        if (empty($name)) {
            return $this->formatResult(array());
        }

        $res = BankModel::instance()->getBankByName($name);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 通过code获取银行卡信息
     * @param $code string 简称
     * @return array
     */
    public function getBankInfoByCode() {
        $code = $this->getParam('code');
        if (empty($code)) {
            return $this->formatResult(array());
        }

        $res = BankModel::instance()->getBankByCode($code);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 根据支行名称查询联行号
     * @param $bankzone string 支行名称
     * @return array
     */
    public function getBankIssueByName() {
        $bankzone = $this->getParam('bankzone');
        if (empty($bankzone)) {
            return $this->formatResult('');
        }

        $banklistService = new BanklistService();
        $res = $banklistService->getBankIssueByName($bankzone);
        return $this->formatResult($res);
    }

    /**
     * 获取用户的所有绑卡信息
     */
    public function getCardByUserId() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }

        $fields = $this->getParam('fields', '*');
        $res = UserBankcardModel::instance()->getCardByUser($userId, $fields);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 获取用户最新的绑卡信息
     */
    public function getNewCardByUserId() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }

        $fields = $this->getParam('fields', '*');
        $res = UserBankcardModel::instance()->getNewCardByUserId($userId, $fields);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 获取用户其中一张银行卡
     */
    public function getOneCardByUser() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }

        $res = UserBankcardModel::instance()->getOneCardByUser($userId);
        $res = !empty($res) ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 根据银行卡联行号查询银行支行信息
     * @param $branchNo sting 银行支行联行号
     * @return array
     */
    public function getBranchInfoByBranchNo() {
        $branchNo = $this->getParam('branchNo');
        if (empty($branchNo)) {
            return $this->formatResult(array());
        }

        $fields = $this->getParam('fields', '*');
        $res = BanklistModel::instance()->findBy('bank_id = \':bank_id\'', $fields, array(':bank_id' => $branchNo), true);
        $res = $res ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 根据状态获取银行列表，排序顺序为推荐度、排序位置和ID
     */
    public function getAllByStatusOrderByRecSortId() {
        $status = $this->getParam('status', 0);
        $res = BankModel::instance()->getAllByStatusOrderByRecSortId($status, true);
        return $this->formatResult($res);
    }

    /**
     * 批量获取用户的基本信息
     * @param $userIds array 用户id列表，也可是逗号分割的字符串
     * @return array
     */
    public function getBankListByUserIds() {
        $userIds = $this->getParam('userIds');
        if (empty($userIds)) {
            return $this->formatResult(array());
        }

        if (!is_array($userIds)) {
            $userIds = explode(',', $userIds);
        }

        $obj = new UserBankcardService();
        $res = $obj->getBankListByUserIds($userIds);
        return $this->formatResult($res);
    }

    /**
     * 获取用户银行卡数据
     * @param $userId
     * @return array
     */
    public function getUserBankInfo() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }
        $accountService = new AccountService();
        $res = $accountService->getUserBankInfo($userId);
        $res = !empty($res) ? $res : array();
        return $this->formatResult($res);
    }

    /**
     * 获取用户银行名称/logo等信息
     * @param $userId
     * @return array
     */
    public function getUserBankLogoInfo() {
        $userId = $this->getParam('userId');
        if (empty($userId)) {
            return $this->formatResult(array());
        }
        $obj = new UserBankcardService();
        $res = $obj->getUserBankInfo($userId);
        $res = !empty($res) ? $res->getRow() : array();
        return $this->formatResult($res);
    }

    /**
     * 获取推荐的银行列表
     * @return array
     */
    public function getBankUserByPaymentMethod() {
        $bankService = new BankService();
        $res = $bankService->getBankUserByPaymentMethod();
        $res = !empty($res) ? $res : array();
        return $this->formatResult($res);
    }

    /**
     * 判断指定银行卡可否被该uid绑定，是否没被其他用户占用
     */
    public function canBankcardBind() {
        $cardNo = $this->getParam('cardNo');
        $userId = $this->getParam('userId');
        if (empty($userId) || empty($cardNo)) {
            return $this->formatResult(array());
        }
        $bankService = new BankService();
        $res = $bankService->canBankcardBind($cardNo, $userId);
        return $this->formatResult($res);
    }

    /**
     * 添加用户绑卡记录
     */
    public function insertUserBankCard() {
        $data = $this->getParam('data');
        $res = UserBankcardModel::instance()->insertCard($data);

        //先锋支付绑卡
        $ucfpayData = $this->getParam('ucfpayData');
        if (!empty($ucfpayData)) {
            $reqResult = PaymentApi::instance()->request('bindbankcard', $ucfpayData);
            if (!isset($reqResult['status']) || $reqResult['status'] != '00') {
                $res = false;
            }
        }
        return $this->formatResult($res);
    }

    /**
     * 根据银行卡号，获取卡信息
     * @param int $cardNo
     */
    public function searchCardBin() {
        $cardNo = $this->getParam('cardNo');
        if (empty($cardNo)) {
            return $this->formatResult(array());
        }

        $obj = new PaymentService();
        $res = $obj->getCardBinInfoByCardNo($cardNo);
        return $this->formatResult($res);
    }

}
