<?php
/**
 * 黄金项目-充值申请相关service
 */
namespace core\service;

use libs\utils\PaymentApi;
use NCFGroup\Protos\Gold\RequestCommon;
use core\service\GoldService;
use core\service\UserService;
use core\service\PaymentService;
use core\dao\PaymentNoticeModel;
use core\dao\DealModel;

class GoldChargeService extends GoldService
{
    /**
     * 获取黄金-申请充值列表
     */
    public function getChargeList($params)
    {
        $request = new RequestCommon();
        $request->setVars($params);
        $res = $this->requestGold('NCFGroup\Gold\Services\GoldCharge', 'getChargeList', $request);
        $data = array();
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return $data;
        }
        $data = $res['data'];
        if(!empty($data['data'])) {
            $userService = new UserService();
            foreach ($data['data'] as $key => $value) {
                // 获取用户信息
                $userInfo = $userService->getUserByUserId($value['userId']);
                $data['data'][$key]['id'] = sprintf('%s_%s', $value['orderId'], $value['userId']);
                $data['data'][$key]['gold'] .= 'g';
                $data['data'][$key]['userName'] = !empty($userInfo['user_name']) ? $userInfo['user_name'] : '';
                $data['data'][$key]['realName'] = !empty($userInfo['real_name']) ? $userInfo['real_name'] : '';
                $data['data'][$key]['mobile'] = !empty($userInfo['mobile']) ? $userInfo['mobile'] : '暂无';
            }
        }
        return $data;
    }

    /**
     * 获取申请充值列表
     * @param array $params
     * @return boolean
     */
    public function getGoldChargeByOrderId($orderId, $userId) {
        $request = new RequestCommon();
        $request->setVars(['orderId'=>$orderId, 'userId'=>$userId]);
        $res = $this->requestGold('NCFGroup\Gold\Services\GoldCharge', 'getGoldChargeByOrderId', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return [];
        }
        return (array)$res['data'];
    }

    /**
     * 编辑申请充值列表
     * @param array $params
     * @return boolean
     */
    public function updateGoldChargeByOrderId($params) {
        if (empty($params['userId']) || empty($params['gold'])) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars($params);
        $res = $this->requestGold('NCFGroup\Gold\Services\GoldCharge', 'updateGoldChargeByOrderId', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return false;
        }
        return $res['data'];
    }

    /**
     * 更新/冻结黄金余额、资金记录
     * @param array $params
     * @return boolean
     */
    public function checkUserAndChangeMoney($params) {
        if (empty($params['userId']) || empty($params['gold']) || empty($params['message']) || empty($params['note'])) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars($params);
        $res = $this->requestGold('NCFGroup\Gold\Services\GoldCharge', 'checkUserAndChangeMoney', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return false;
        }
        return $res['data'];
    }

    /**
     * 批量审批申请充值记录
     * @param array $params
     * @return boolean
     */
    public function chargeAudit($params) {
        if (empty($params['ids']) || !isset($params['auditStatus'])) {
            return false;
        }
        $request = new RequestCommon();
        $request->setVars($params);
        $res = $this->requestGold('NCFGroup\Gold\Services\GoldCharge', 'chargeAudit', $request);
        if ($res['errCode'] != 0 || empty($res['data'])) {
            return ['ret'=>false, 'errMsg'=>$res['errMsg']];
        }
        return ['ret'=>true, 'data'=>$res['data']];
    }

    /**
     * 黄金运营方商户，自动提现
     */
    public function goldAutoPickup() {
        try{
            // 获取黄金运营方用户ID
            $goldService = new GoldService();
            $dealRes = $goldService->getDealCurrentInfo();
            if (empty($dealRes) || empty($dealRes['userId'])) {
                throw new \Exception('获取运营方用户ID失败');
            }

            // 运营方用户ID
            $goldOperateUid = (int)$dealRes['userId'];
            // 获取用户信息
            $userService = new UserService();
            $userInfo = $userService->getUserByUserId($goldOperateUid);
            if (empty($userInfo)) {
                throw new \Exception('运营方用户信息不存在,userId:' . $goldOperateUid);
            }
            if (bccomp($userInfo['money'], '0.00', 2) <= 0) {
                throw new \Exception(sprintf('%s, userId:%d, 当前余额:%s元', $GLOBALS['lang']['CARRY_MONEY_NOT_ENOUGHT'], $goldOperateUid, $userInfo['money']));
            }

            // 发起提现申请，指定黄金业务的交易类型
            $paymentService = new PaymentService();
            $carryRet = $paymentService->cashOut($goldOperateUid, $userInfo['money'], PaymentNoticeModel::PLATFORM_ADMIN, DealModel::DEAL_TYPE_GOLD);
            if (false === $carryRet) {
                throw new \Exception(sprintf('发起提现申请失败, userId:%d, 提现金额:%s元', $goldOperateUid, $userInfo['money']));
            }

            $logMsg = sprintf('黄金自动提现申请成功, 提现Id:%d, userId:%d, 提现金额:%s元', $carryRet, $goldOperateUid, $userInfo['money']);
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, $logMsg)));
            return ['ret'=>true, 'respMsg'=>$logMsg];
        } catch (\Exception $e) {
            $logMsg = '黄金自动提现异常, exceptionMsg:' . $e->getMessage();
            PaymentApi::log(implode(' | ', array(__CLASS__, __FUNCTION__, $logMsg)));
            return ['ret'=>false, 'respMsg'=>$logMsg];
        }
    }

}
