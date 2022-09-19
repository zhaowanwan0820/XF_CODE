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
use libs\utils\Rpc;
use core\dao\DealModel;
use core\service\UserService;

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
        $userService = new UserService($user_id);
        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'project_id' => $id,
            'user_id' => $user_id,
            'isEnterprise' => $userService->isEnterpriseUser(),
        );
        $request->setVars($vars);
        $rpc = new Rpc('duotouRpc');
        $response = $rpc->go('\NCFGroup\Duotou\Services\Project','getProjectByIdForBid',$request);
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
            $bonus = $this->rpc->local('BonusService\get_useable_money', array($GLOBALS['user_info']['id']));
            $this->tpl->assign('bonus', $bonus['money']);
            $this->tpl->assign('total_money', bcadd($GLOBALS['user_info']['money'], $bonus['money'], 2));
        }

        //18岁以上投资限制
        $age_check = $this->rpc->local('DealService\allowedBidByCheckAge', array($GLOBALS['user_info']));
        $this->tpl->assign("age_check", $age_check['error'] ? 0 : 1);
        $this->tpl->assign("age_min", \core\dao\DealLoadModel::BID_AGE_MIN);

        $this->tpl->assign("deal", $deal);
        $this->tpl->assign("page_title", "智多新详情页");
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
                    $have_bid = $this->rpc->local('DealLoadService\getUserDealLoad', array($user['id'], $deal_id));
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
