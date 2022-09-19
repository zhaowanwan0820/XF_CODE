<?php
/**
 * 多投宝列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\finplan;

use libs\web\Form;
use NCFGroup\Common\Library\Logger;
use web\controllers\BaseAction;
use NCFGroup\Protos\Duotou\Enum\DealEnum;
use libs\utils\Rpc;

require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Lists extends BaseAction {

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
            // 第几页
            "p" => array("filter"=>"int"),
        );
        $this->form->validate();
    }

    public function invoke() {
        $data = $this->form->data;
        $page = intval($data['p'])==0 ? 1 : intval($data['p']);
        $pageSize = 10;

        /**
         * 粗粒度缓存
         * $list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('CouponService\getShortAliasUsed', array($user_id)),self::$_cache_time);
         */

        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
        $vars = array(
            'pageNum' => $page,
            'pageSize' => $pageSize,
            'isShow' => DealEnum::DEAL_IS_SHOW_YES,
            'isEffect' => DealEnum::DEAL_IS_EFFECT_OPEN,
        );
        $request->setVars($vars);

        $rpc = new Rpc('duotouRpc');
        $response = \SiteApp::init()->dataCache->call($rpc, 'go', array('\NCFGroup\Duotou\Services\Project','listProjectFrontend',$request), 30);

        if(!$response) {
            $this->show_error("系统繁忙，如有疑问，请拨打客服电话：4008909888");
        }
        if($response['errCode'] != 0) {
            Logger::error("errCode:".$response['errCode']." errMsg:".$response['errMsg']);
            $this->show_error("errMsg:" .$response['errMsg']);
        }
        foreach($response['data']  as &$project){
            if($vars['isEnterprise'] == true){
                //企业处理
                $project['min_loan_money'] = number_format($project['singleEnterpriseMinLoanMoney'], 0, ",", "");
                $project['max_loan_money'] = number_format($project['singleEnterpriseMaxLoanMoney'], 0, ",", "");
                $project['day_redemption'] = number_format($project['enterpriseMaxDayRedemption'], 0, ",", "");
                $project['single'] = $project['enterpriseLoanCount'];
            }else{
                //个人处理
                $project['min_loan_money'] = number_format($project['singleMinLoanMoney'], 0, ",", "");
                $project['max_loan_money'] = number_format($project['singleMaxLoanMoney'], 0, ",", "");
                $project['day_redemption'] =  number_format($project['maxDayRedemption'], 0, ",", "");
                $project['single'] = $project['loanCount'];
            }
        }

       //目前强制跳转第一个项目
        $projectId = $response['data'][0]['id'];
        if(!empty($projectId)){
            $host = get_host();
            //获取标的ID，进行跳转Bid
            header("Location: http://".$host."/finplan/bid/".$projectId);
            exit;
        }else{
            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
        }
        $this->tpl->assign("pages", $response['data']['totalPage']);
        $this->tpl->assign("current_page", ($page == 0) ? 1 : $page);
        $this->tpl->assign("page_title", $GLOBALS['lang']['FINANCIAL_MANAGEMENT']);
        $this->tpl->assign("deal_list", $response['data']);
        //添加 rss
        $this->tpl->assign("pagination",pagination(($page == 0) ? 1 : $page, ceil($response['data']['totalNum'] / $pageSize), 8, 'p='));
        $this->set_nav("智多新");
        $this->template = "web/views/finplan/lists.html";
    }
}
