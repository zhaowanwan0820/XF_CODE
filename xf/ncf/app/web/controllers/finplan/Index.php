<?php
/**
 * 多投宝标的详情页
 * Index.php
 *
 * @author wangyiming@ucfgroup.com
 */

namespace web\controllers\finplan;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\deal\DealModel;
use core\service\user\UserService;
use core\service\duotou\DuotouService;
use core\service\deal\DealService;
use core\service\bonus\BonusService;
use core\service\deal\DealLoadService;
use core\service\account\AccountService;
use core\enum\DealLoadEnum;

class Index extends BaseAction {

    public function init() {
        if(app_conf('DUOTOU_SWITCH') == '0') {
            $this->show_tips("系统维护中，请稍后再试！","系统维护");
            exit;
        }
        if(!is_duotou_inner_user()) {
            $this->show_tips("没有权限,仅内部员工可以查看智多新内容！","没有权限");
            exit;
        }
        $this->form = new Form();
        $this->form->rules = array(
            'id' => array('filter' => 'int'),
            'debug' => array('filter' => 'string'),
        );
        if (!$this->form->validate()) {
            return app_redirect(url("index"));
        }
    }

    public function invoke() {
        $id = $this->form->data['id'];
        //目前强制跳转第一个项目
        if(!empty($id)){
            $host = get_host();
            //获取标的ID，进行跳转Bid
            header("Location: http://".$host."/finplan/bid/".$id);
            exit;
        }else{
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
        }

        $user_id = $GLOBALS['user_info'] ? $GLOBALS['user_info']['id'] : 0;
        $request = array(
            'project_id' => $id,
            'user_id' => $user_id,
            'isEnterprise' => UserService::isEnterprise($user_id)
        );

        $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\Project','getProjectByIdForBid',$request));
        if(!$response) {
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
        }
        $deal = $response['data'];

        if (empty($deal)) {
            return app_redirect(url("Bid"));
        }
        if ($deal['isFull'] && $deal['userLoanMoney'] == 0) {
            return $this->show_error('额度已满，仅允许持有用户查看', "", 0, 0, url("index"));
        }

        $deal['show_tips'] = get_wordnum($deal['name']) > 25 ? 1 : 0;

        $dealModel = new DealModel();
        $deal['rate_year'] = "".$deal['rateYear'];

        //当前登录用户
        if ($GLOBALS['user_info']) {
            $this->tpl->assign('user_info', $GLOBALS['user_info']);
            $bonus = BonusService::getUsableBonus($GLOBALS['user_info']['id'], false, 0, false, $GLOBALS['user_info']['is_enterprise_user']);
            //获取存管余额
            $accountId = AccountService::getUserAccountId($userId, $userInfo['user_purpose']);
            $accountMoney = AccountService::getAccountMoneyById($accountId);
            $this->tpl->assign('bonus', $bonus['money']);
            $this->tpl->assign('total_money', bcadd($accountMoney['money'], $bonus['money'], 2));
        }

        //18岁以上投资限制
        $dealService = new DealService();
        $age_check = $dealService->allowedBidByCheckAge($GLOBALS['user_info']);
        $this->tpl->assign("age_check", $age_check['error'] ? 0 : 1);
        $this->tpl->assign("age_min", DealLoadEnum::BID_AGE_MIN);

        $this->tpl->assign("deal", $deal);
        $this->set_nav(array("智多新" => url("index", "finplan/lists"), $deal['name']));
        return true;
    }

    /**
     * 该标详情页是否可以查看
     *
     * @param $deal_id
     * @param $deal_status
     * @return boolean
     */
    protected function isView($deal_id, $deal_status, $debug){

        //状态为2,3,4的标直接跳转首页（add by wangyiming 20140701）
        if (app_conf("SWITCH_DEAL_INFO_DISPLAY") == 1) {
            if ( in_array($deal_status, array(2,3,4)) ) {
                return app_redirect(url("index"));
            }
        }

        if (!in_array($deal_status, array(0, 1)) ) {
            $code = app_conf("DEAL_DETAIL_VIEW_CODE");
            $debug = empty($debug) ? '' : rtrim(urldecode($debug), '?');
            if(empty($code) || $debug != $code){
                $user = $GLOBALS['user_info'];
                if($user){
                    $dealLoadService = new DealLoadService();
                    $have_bid = $dealLoadService->getUserDealLoad($user['id'], $deal_id);
                    if(!$have_bid){
                        return $this->show_error('仅允许投资人查看。', "", 0, 0, url("index"));
                    }
                }else{
                    return $this->show_error('请登录后查看。', "", 0, 0, url("user/login"));
                }
            }
        }
        return true;
    }
}
