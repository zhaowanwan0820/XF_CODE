<?php
namespace web\controllers\account;

use web\controllers\BaseAction;
use core\dao\UserBankcardModel;
use core\dao\BankModel;
use core\dao\ApiConfModel;
use core\service\UserCarryService;
use core\service\UserService;
use libs\web\Url;

/**
 * 个人中心提现
 * @author caolong<caolong@ucfgroup.com>
 */
class Carry extends BaseAction {

    public function init() {
        return $this->check_login();
    }

    public function invoke() {

        $user_info = $GLOBALS['user_info'];
        $userId = $GLOBALS['user_info']['id'];
        // 用户绑卡开户状态验证
        $userService = new UserService($userId);
        $userCheck = $userService->isBindBankCard();
        if ($userCheck['ret'] !== true)
        {
            // 企业用户给提示
            if ($userService->isEnterprise() && ($userCheck['respCode'] == UserService::STATUS_BINDCARD_UNBIND || $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID))
            {
                return app_redirect(Url::gene('deal','promptCompany'));
            }

            $siteId = \libs\utils\Site::getId();
            $hasPassport = $this->rpc->local('AccountService\hasPassport', array($user_id));
            // 白名单中的分站 大陆用户和已绑卡未验证的港澳台用户跳转到先锋支付绑卡/验卡
            if (($siteId == 1 || \libs\web\Open::checkOpenSwitch()) && (empty($hasPassport) || (!empty($hasPassport) && $userCheck['respCode'] == UserService::STATUS_BINDCARD_UNVALID)))
            {
                return $this->show_payment_tips($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
            }
            return $this->show_error($userCheck['respMsg'], '操作失败', 0, 0, '/account/addbank', 3);
        }

        if(intval($GLOBALS['user_info']['idcardpassed'])==3){
            showErr('您的身份信息正在审核中，预计1到3个工作日内审核完成。审核结果将以短信、站內信或电子邮件等方式通知您。', 0,'/account' , 0 );
            return;
        }


        $bankcard_info = UserBankcardModel::instance()->findByViaSlave(" user_id = '{$userId}' ");
        // 读取银行卡logo
        if (!empty($bankcard_info['bank_id'])) {
            $bankcard_info['logo'] = $this->rpc->local('BankService\getBankLogo', array($bankcard_info['bank_id']));
        } else {
            $bankcard_info['logo'] = null;
        }

        //支付支持的可以不用填写支行信息的银行直接跳过这个流程
        //$bankService = new \core\service\BankService;
        //$hideExtra = $bankService->isHideExtraBank($bankcard_info['bank_id']);
        //if (!$hideExtra) {
        //    if(!$bankcard_info['bankzone'] || !$bankcard_info['bank_id'] || !$bankcard_info['region_lv2']){
        //        return $this->show_error('请先补全银行卡信息！', "", 0,0, url("account/editbank?type=1"), 3);
        //    }
        //}
        // 检测身份证信息,没有认证就跳转填写
        if ($GLOBALS['user_info']['idcardpassed'] != 1) {
            $idTypes = getIdTypes();
            $this->tpl->assign("idTypes", $idTypes);
            $this->tpl->assign("page_title","成为投资者");
            $this->tpl->assign("agrant_id",200);
            $this->template = "web/views/account/mobilepaseed.html";
            return;
        }
        make_delivery_region_js();
        $bank_list = $this->rpc->local("BankService\getAllByStatusOrderByRecSortId");

        if($bankcard_info){
            $bankcard_info['hideCard'] = $this->getHideCard($bankcard_info['bankcard']);
            foreach($bank_list as $k=>$v){
                if($v['id'] == $bankcard_info['bank_id']){
                    $bankcard_info['is_rec'] = $v['is_rec'];
                    $bankcard_info['bankName'] = $v['name'];
                    break;
                }
            }
            $this->tpl->assign('bankcard_info',$bankcard_info);
        }

        //是否开户
        $user_info['isSvUser'] = $this->rpc->local('SupervisionAccountService\isSupervisionUser', array($user_info['id']));

        //平台公告
        $siteId = \libs\utils\Site::getId();
        $noticeConf = $this->rpc->local("ApiConfService\getNoticeConf", array($siteId, ApiConfModel::NOTICE_PAGE_CARRYWX));
        $this->tpl->assign("notice_conf", $noticeConf);

        // 获取提现时效配置
        $apiConfObj = new \core\service\ApiConfService();
        $withdrawTimeConf = $apiConfObj->getWithdrawTime();
        $this->tpl->assign("withdrawTime", $withdrawTimeConf);

        $this->tpl->assign("page_title",$GLOBALS['lang']['UC_CARRY']);
        $this->tpl->assign("inc_file","web/views/account/carry.html");
        $this->tpl->assign("bank_list",$bank_list);
        $this->tpl->assign("user_info",$user_info);
        $this->template = "web/views/account/carry.html";
    }

    private function getHideCard($card='') {
        return formatBankcard($card);
    }
}
