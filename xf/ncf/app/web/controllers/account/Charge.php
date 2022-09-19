<?php
/**
 * 充值网贷账户
 * @author weiwei12<weiwei12@ucfgroup.com>
 */

namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\supervision\SupervisionAccountService;
use core\service\supervision\SupervisionFinanceService;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\account\AccountService;
use core\service\attachment\AttachmentService;
use libs\web\Url;
use NCFGroup\Common\Library\Idworker;
use core\service\conf\ApiConfService;
use core\dao\conf\ApiConfModel;

class Charge extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        try {
            $userId = $GLOBALS['user_info']['id'];
            $userInfo = $GLOBALS['user_info'];
            $accountId = AccountService::getUserAccountId($userId, $userInfo['user_purpose']);
            $siteId = \libs\utils\Site::getId();
            $accountService = new AccountService();
            //$hasPassport = $accountService->hasPassport($userId);
            if (empty($accountId)) {
                return $this->show_error('请先开通网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=registerStandard&return_url=%s', get_domain() . '/account'), 3);
            }
            //检查未激活
            if (AccountService::isUnactivated($accountId)) {
                return $this->show_error('请先激活网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain() . '/account'), 3);
            }

            if(intval($GLOBALS['user_info']['idcardpassed'])==3){
                showErr('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站內信或电子邮件等方式通知您。', 0,'/account' , 0 );
                return;
            }

            //是否存管开户
            $userInfo['isSvUser'] = true;

            // 账户存管余额
            $balanceResult = AccountService::getAccountMoneyById($accountId);
            $userInfo['svFreezeMoney'] = $balanceResult['lockMoney'];
            $userInfo['svCashMoney'] = $balanceResult['money'];
            $userInfo['svTotalMoney'] = $balanceResult['totalMoney'];



            $orderId = Idworker::instance()->getId();
            $this->tpl->assign("orderId", $orderId);
            $this->tpl->assign("user_info",$userInfo);
            $this->tpl->assign("doneBankOperate_url", sprintf('/payment/payCheck?id=%s&type=1', $orderId));

            $bankcardInfo = BankService::getNewCardByUserId($userId, '*');
            $bankcardInfo['hideCard'] = formatBankcard($bankcardInfo['bankcard']);
            if (!empty($bankcardInfo['bank_id']))
            {
                $bankInfo = BankService::getBankInfoByBankId($bankcardInfo['bank_id'],'*');
                if (!empty($bankInfo['img'])) {
                    $imgId = $bankInfo['img'];
                    $attachInfo = AttachmentService::getAttachmentById($imgId);
                    if (!empty($attachInfo['attachment'])) {
                        $bankInfo['logo'] = app_conf('STATIC_HOST').'/'.$attachInfo['attachment'];
                    }
                }
                if (!empty($bankInfo))
                {
                    $bankcardInfo['bankName'] = $bankInfo['name'];
                    $bankcardInfo['bankLogo'] = $bankInfo['logo'];
                }
            }
            $this->tpl->assign('bankcardInfo',$bankcardInfo);

            //平台公告
            $apiConfService = new ApiConfService();
            $noticeConf = $apiConfService->getNoticeConf($siteId,ApiConfModel::NOTICE_PAGE_CHARGEP2P);
            $this->tpl->assign("notice_conf", $noticeConf);

            //网贷大额充值地址
            $sfs = new SupervisionFinanceService();
            $p2pOfflineChargeUrl = $sfs->getOfflineChargeApiUrl($userId, $bankcardInfo);
            $this->tpl->assign('p2pOfflineChargeUrl', $p2pOfflineChargeUrl);
        } catch (\Exception $e) {
            return $this->show_error($e->getMessage(), '操作失败', 0, 0, '/', 3);
        }

        $this->template = "web/views/account/charge_p2p.html";
    }
}
