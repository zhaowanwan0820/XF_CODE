<?php
/**
 * 列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\touzi;

use libs\web\Form;
use web\controllers\BaseAction;
use libs\utils\Rpc;
//分页重写，使用jquery.ui.widget/paginate 分站的依然用之前的分页 引用page.php
require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Index extends BaseAction {

    public function init() {
        // 页面已无用
        return app_redirect('/');

        $this->form = new Form();
        $this->form->rules = array(
            // 第几页
            "p" => array("filter"=>"int"),
        );
        $this->form->validate();
    }

    public function invoke() {
//        $rpc = new Rpc('duotouRpc');
//        $request = new \NCFGroup\Protos\Duotou\RequestCommon();
//        $res = $rpc->go('\NCFGroup\Duotou\Services\Deal','getDealLastest',$request);
//        if(!$res) {
//            return $this->show_error('系统繁忙，如有疑问，请拨打客服电话：4008909888', "", 0, 0, url("index"));
//        }

//        $response = $res['data'];
//        if ($response) {
//            $response['rate'] = number_format($response['projectInfo']['rateYear'], 2);
//            $response['min_loan_money'] = number_format($response['projectInfo']['minLoanMoney'], 0);
//            $this->tpl->assign("duotou", $response);
//        }

        $data = $this->form->data;
        $page = intval($data['p'])==0?1:intval($data['p']);
        $count = 10;
        $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getBXTList', array($page,$count)), 30);
        $deals['list'] = $this->rpc->local('DealService\EncryptDealIds', array($deals['list']));
        //修改首页 列表页面 缓存引起标的状态有问题的情况
        $deals['list'] = $this->rpc->local('DealService\UserDealStatusSwitch', array($deals['list']));
        $page_fenzhan = new \Page($deals['count'], $deals['page_size']);
        $this->tpl->assign("pages", ceil($deals['count'] / $deals['page_size']));
        $this->tpl->assign("current_page", ($page == 0) ? 1 : $page);
        $this->tpl->assign("page_title", $GLOBALS['lang']['FINANCIAL_MANAGEMENT']);
        $this->tpl->assign("deal_list", $deals['list']);
        $this->tpl->assign("count", count($deals['deal_type']));
        $this->tpl->assign("cate", $deals['cate']);
        $this->tpl->assign("sort", $deals['sort']);
        $brief = isset($deals['deal_type'][$deals['cate']]['brief']) ? $deals['deal_type'][$deals['cate']]['brief'] : "";
        $this->tpl->assign("brief", $brief);
        //添加 rss
        $this->tpl->assign("pagination",pagination(($page == 0) ? 1 : $page, ceil($deals['count'] / $deals['page_size']), 8, 'p='));
        $this->set_nav("专享理财");
        $this->template = "web/views/v2/deals/tzlc.html";
    }
}
