<?php
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\service\account\AccountService;
use core\service\user\UserService;
use core\service\user\BankService;
use core\service\conf\ApiConfService;
use core\dao\conf\ApiConfModel;
use libs\web\Url;

/**
 * 存管提现
 * @author weiwei12<weiwei12@ucfgroup.com>
 */
class Carry extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {
        try{
            $userInfo = $GLOBALS['user_info'];
            $userId = $GLOBALS['user_info']['id'];
            if (intval($userInfo['idcardpassed']) == 3) {
                showErr('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站內信或电子邮件等方式通知您。', 0,'/account' , 0 );
                return;
            }

            $accountId = AccountService::getUserAccountId($userInfo['id'], $userInfo['user_purpose']);
            //检查开户
            if (empty($accountId)) {
                return $this->show_error('请先开通网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=registerStandard&return_url=%s', get_domain() . '/account'), 3);
            }
            //检查未激活
            if (AccountService::isUnactivated($accountId)) {
                return $this->show_error('请先激活网贷P2P账户', '操作失败', 0, 0, sprintf('/payment/transit?srv=register&return_url=%s', get_domain() . '/account'), 3);
            }

            $userInfo['isSvUser'] = true;
            $userInfo['accountId'] = $accountId;
            //资产中心余额
            $balanceResult = AccountService::getAccountMoneyById($accountId);
            $userInfo['svCashMoney'] = $balanceResult['money'];
            $userInfo['svFreezeMoney'] = $balanceResult['lockMoney'];
            $userInfo['svTotalMoney'] = $balanceResult['totalMoney'];

            $bankcardInfo = BankService::getNewCardByUserId($userId);
            // 读取银行卡logo
            if (!empty($bankcardInfo['bank_id'])) {
                $bankcardInfo['logo'] = BankService::getBankLogo($bankcardInfo['bank_id']);
                $bankcardInfo['hideCard'] = formatBankcard($bankcardInfo['bankcard']);
            } else {
                $bankcardInfo['logo'] = null;
            }

            //平台公告
            $siteId = \libs\utils\Site::getId();
            $apiConfService = new ApiConfService();
            $noticeConf = $apiConfService->getNoticeConf($siteId, ApiConfModel::NOTICE_PAGE_CARRYP2P);
            $this->tpl->assign("notice_conf", $noticeConf);

            $this->tpl->assign('bankcard_info',$bankcardInfo);
            $this->tpl->assign("user_info",$userInfo);
            $this->tpl->assign("doneBankOperate_url", '/account');
            $this->template = "web/views/account/carry.html";
        } catch (\Exception $e) {
            return $this->show_error($e->getMessage(), '操作失败', 0, 0, '/', 3);
        }
    }

    private function getHideCard($card='') {
        return formatBankcard($card);
    }
}
