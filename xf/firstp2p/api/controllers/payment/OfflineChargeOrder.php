<?php
namespace api\controllers\payment;

use NCFGroup\Common\Library\Idworker;
use libs\web\Form;
use libs\utils\Logger;
use api\controllers\H5BaseAction;
use core\service\PaymentService;
use core\service\QrCodeService;
use core\service\UserBankcardService;
use core\dao\SupervisionTransferModel;
use api\conf\ConstDefine;

// 网信-大额充值页面
class OfflineChargeOrder extends H5BaseAction {
    const IS_H5 = true;

    public function init() {
        parent::init();
        $this->form = new Form('get');
        $this->form->rules = array(
            'token' => array('filter' => 'required', 'message' => 'ERR_AUTH_FAIL'),
            'bankCardId' => array('filter' => 'string', 'option' => array('optional' => true)),
            'money' => array('filter' => 'int', 'option' => array('optional' => true)),
            'ref' => array('filter' => 'string', 'option' => array('optional' => true)),
            'ver' => array('filter' => 'int', 'option' => array('optional' => true)),
        );
        if (!$this->form->validate()) {
            $this->setErr('ERR_PARAMS_ERROR');
            return false;
        }
    }

    public function invoke() {
        $data = $this->form->data;
        $appVersion = $this->getAppVersion();
        $version = !empty($data['ver']) ? (int)$data['ver'] : $appVersion;
        if ($version >= ConstDefine::VERSION_MULTI_CARD) {
            return $this->invokeNew();
        }
        return $this->invokeOld();
    }

    public function invokeNew() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken(false);
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = (int)$userInfo['id'];
        // 获取用户支付卡列表中的指定bankcardid的卡数据
        $bankcardServ = new UserBankcardService();
        $bankCardInfo = $bankcardServ->queryBankCardsList($userId, false, $data['bankCardId']);
        if (empty($bankCardInfo['list'])) {
            $this->setErr('ERR_MANUAL_REASON', '用户指定的银行卡信息无效,请返回充值界面重新选择银行卡');
            return false;
        }
        // 用户是否可以使用大额充值
        $isBig = PaymentService::isBigCharge($userId, $bankCardInfo['list']['bankCode']);
        if ( ! $isBig) {
            $this->setErr('ERR_BIGCHARGE_NOTLIST');
            return false;
        }

        // 充值金额，单位分
        $moneyCent = isset($data['money']) ? (int)$data['money'] : 0;
        // 充值金额，单位元
        $money = $moneyCent > 0 ? bcdiv($moneyCent, 100, 2) : '';
        $this->tpl->assign('money', $money);

        // 入口来源，区分是否已经创建了充值订单（快捷超限额、大额充值）
        $chargeRef = !empty($data['ref']) ? $data['ref'] : '';
        $isSaveOrder = $chargeRef === QrCodeService::QRREF_QUICK ? 1 : 0;
        $this->tpl->assign('isSaveOrder', $isSaveOrder);

        $orderId = Idworker::instance()->getId();
        $this->tpl->assign('orderId', $orderId);
        $this->tpl->assign('returnUrl', 'firstp2p://api?type=closeallpage');
        // 网信APP 版本号
        $this->tpl->assign('wxVersion', $this->getAppVersion());
        // 银行卡绑卡标识
        $this->tpl->assign('bankCardId', $data['bankCardId']);

        // 网信理财账户余额
        $this->tpl->assign('userInfo', $userInfo);
        // 获取网新大额充值账户名称
        $offlineChargeName = str_replace(['余额', '理财'], '', SupervisionTransferModel::P2P_NAME);
        $this->tpl->assign('offlineChargeName', $offlineChargeName);

        // 读取用户未支付的订单个数
        $service = new PaymentService();
        $cnt = $service->getMyOfflineOrderNum($userId);
        $this->tpl->assign('offlineChargeOrderCnt',$cnt);

        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $data['token'])));
        $this->template = $this->getTemplate('wx_offline_charge');
        return true;
    }

    // 临时支持旧版本
    public function invokeOld() {
        $data = $this->form->data;
        $userInfo = $this->getUserByToken(false);
        if (empty($userInfo)) {
            $this->setErr('ERR_GET_USER_FAIL');
            return false;
        }

        $userId = (int)$userInfo['id'];
        // 检查用户绑定的银行是否在大额充值银行的白名单里
        $largeAmountOpen = PaymentService::isOfflineBankList($userId);
        if (!$largeAmountOpen) {
            $this->setErr('ERR_BIGCHARGE_NOTLIST');
            return false;
        }
        // 用户在黑名单
        $inBlackList = PaymentService::inBlackList($userId);
        if ($inBlackList) {
            $this->setErr('ERR_BIGCHARGE_NOTLIST');
            return false;
        }

        $orderId = Idworker::instance()->getId();
        $this->tpl->assign('orderId', $orderId);
        $this->tpl->assign('returnUrl', 'firstp2p://api?type=closeallpage');
        // 网信APP 版本号
        $this->tpl->assign('wxVersion', $this->getAppVersion());

        // 网信理财账户余额
        $this->tpl->assign('userInfo', $userInfo);
        // 获取网新大额充值账户名称
        $offlineChargeName = str_replace(['余额', '理财'], '', SupervisionTransferModel::P2P_NAME);
        $this->tpl->assign('offlineChargeName', $offlineChargeName);

        // 读取用户未支付的订单个数
        $service = new PaymentService();
        $cnt = $service->getMyOfflineOrderNum($userId);
        $this->tpl->assign('offlineChargeOrderCnt',$cnt);

        // 记录日志
        Logger::info(implode(' | ', array(__CLASS__, __FUNCTION__, APP, $data['token'])));
        $this->template = $this->getTemplate('wx_offline_charge');
        return true;
    }
}