<?php
/**
 * 列表页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\deals;

use libs\web\Form;
use web\controllers\BaseAction;
use core\dao\DealLoanTypeModel;
use core\dao\DealModel;
use core\service\PassportService;
use libs\utils\Logger;
use core\service\ncfph\DuotouService;

//分页重写，使用jquery.ui.widget/paginate 分站的依然用之前的分页 引用page.php
require_once(dirname(__FILE__) . "/../../../app/Lib/page.php");

class Index extends BaseAction
{

    //专享标tab参数
    const TAB_ZX = 'zx';

    //P2P标tab参数
    const TAB_P2P = 'p2p';

    //小贷标tab参数
    const TAB_XD = 'xd';

    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            "cate" => array("filter"=>"int"),
            "p" => array("filter"=>"int"),
            "type" => array("filter"=>"int"),
            "field" => array("filter"=>"int"),
            'tab' => array('filter' => 'string'),
            "product_class_type" => array("filter"=>"int",'option' => array('optional' => true)), //产品大类
            "loan_user_customer_type" =>  array("filter"=>"int",'option' => array('optional' => true)), //借款客群
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $data = $this->form->data;
        $page = intval($data['p']);
        $cate = intval($data['cate']);
        // 可能未配置以下两个字段 isset 以消除notice
        $product_class_type = isset($data['product_class_type']) ? intval($data['product_class_type']) : ''; //产品大类
        $loan_user_customer_type = isset($data['loan_user_customer_type']) ? intval($data['loan_user_customer_type']) : ''; //借款客群
        $page = ($page <= 0) ? 1 : $page;
        $p2pBeyondPageForbid = false;//普惠超过页码限制数据返回

        // 默认只显示后台配置页数
        $pageLimit = intval(app_conf('DEAL_LIST_LIMIT_PAGES'));
        if ($pageLimit != 0) {
            $page = $page > $pageLimit ? $pageLimit : $page;
        }

        //显示专享还是P2P
        $tab = empty($data['tab']) ? ($this->is_firstp2p ? self::TAB_P2P : self::TAB_ZX) : htmlspecialchars($_GET['tab']);
        $option = array();

        if ($tab === self::TAB_ZX) {

            // 没有列表直接跳转到产品页
            header(sprintf('location://%s', $this->getWxlcDomain() ).'/product');
            exit;
            /*$option['deal_type'] = DealModel::DEAL_TYPE_EXCLUSIVE . ",".DealModel::DEAL_TYPE_EXCHANGE;
            // 用户未登录状态不显示专项的标
            if (empty($GLOBALS ['user_info'])){
                $option['deal_type'] = DealModel::DEAL_TYPE_EXCHANGE;
            }*/

        }elseif ($tab === self::TAB_XD) {
            $option['deal_type'] = DealModel::DEAL_TYPE_PETTYLOAN;
        }else {
            if ($this->is_wxlc) {
                $url = sprintf('//%s/deals', app_conf('FIRSTP2P_CN_DOMAIN'));
                return $this->redirectToP2P($url);
            }
            if(intval($data['p'])> $page) {
                $p2pBeyondPageForbid = true;
            }

            $option['product_class_type'] = intval($data['product_class_type']); //产品大类
            $option['loan_user_customer_type'] = intval($data['loan_user_customer_type']); //借款客群

            $p2pSiteTags = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getP2pSiteTags', array()), 30, false, false);
            $this->tpl->assign("product_class_types", $p2pSiteTags['product_class_types']);
            $this->tpl->assign("loan_user_customer_types", $p2pSiteTags['loan_user_customer_types']);

            // 平台贷款收益统计展示
            $this->tpl->assign("is_publish_effect",intval(app_conf('IS_PUBLISH_EFFECT'))); // 是否展示开关
            $statistics_arr = explode(',', app_conf('CN_PLATFORM_DEAL_STATISTICS')); // admin 系统配置 格式：交易总额,交易总笔数,投资人总数,累计为投资者带来利息收入
            $deals_income_view['borrow_amount_total'] = number_format(array_shift($statistics_arr), 2);
            $deals_income_view['buy_count_total'] = number_format(array_shift($statistics_arr));
            $deals_income_view['distinct_user_total'] = number_format(array_shift($statistics_arr));
            $deals_income_view['income_expected_sum'] = number_format(array_shift($statistics_arr), 2);
            $this->tpl->assign("deals_income_view",$deals_income_view);
            $option['deal_type'] = DealModel::DEAL_TYPE_ALL_P2P;
        }

        if(app_conf('APP_SITE') == 'nongdan'){
            $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getNdList', array(null, $page,0,false,0,$option)), 30, false, false);
        }else{
            if((int)app_conf('SUPERVISION_SWITCH') === 1){
                $option['isHitSupervision'] = true;
                $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, $page,0,false,0,$option)), 30, false, false);
            }else{
                $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, $page,0,false,0,$option)), 30, false, false);
            }
        }


        if($p2pBeyondPageForbid) {
            $deals['list']['list'] = array();
            $deals['count'] = 0;
        }


        //修改首页 列表页面 缓存引起标的状态有问题的情况
        $deals['list']['list'] = $this->rpc->local('DealService\UserDealStatusSwitch', array($deals['list']['list']));
        $deals['list']['list'] = $this->rpc->local('DealService\EncryptDealIds', array($deals['list']['list']));

        $currentPageSize = count($deals['list']['list']);
        $limitMaxCount = $pageLimit * $deals['page_size']; //最大访问数量
        $deals['count'] = ($limitMaxCount <= $deals['count']) ? $limitMaxCount : $deals['count'];

        $page_fenzhan = new \Page($deals['count'], $deals['page_size'], '', $currentPageSize);
        $this->tpl->assign("pages", ceil($deals['count'] / $deals['page_size']));
        $this->tpl->assign("current_page", ($page == 0) ? 1 : $page);
        if (!$this->is_firstp2p) {
            $this->tpl->assign("page_title", $GLOBALS['lang']['FINANCIAL_MANAGEMENT']);
        }
        $this->tpl->assign("deal_list", $deals['list']);
        $this->tpl->assign("count", isset($deals['deal_type']) ? count($deals['deal_type']) : 0);
        $this->tpl->assign("deal_type", isset($deals['deal_type']) ? $deals['deal_type'] : null);
        $this->tpl->assign("cate", isset($deals['cate']) ? $deals['cate'] : null);
        $this->tpl->assign("sort", isset($deals['cate']) ? $deals['sort'] : null);
        $brief = isset($deals['cate']) && isset($deals['deal_type'][$deals['cate']]['brief']) ? $deals['deal_type'][$deals['cate']]['brief'] : "";
        $this->tpl->assign("brief", $brief);
        //添加 rss
        $this->tpl->assign("rss_title", (isset($deals['deal_type']) ? $deals['deal_type'][$cate]['name'] : '')."贷款订阅");
        $this->tpl->assign("rss_url", "http://www.firstp2p.com/rss?cate={$cate}");
        $this->tpl->assign('tab', $tab);

        //获取多投相关信息
        $this->_getDuotou();

        if ($pageLimit)  {
            $this->tpl->assign("p", $page_fenzhan->show(array("cate"=> isset($deals['cate']) ? $deals['cate'] : null)));
            if ($this->is_firstp2p) {
                $this->tpl->assign("pagination",pagination(($page == 0) ? 1 : $page, ceil($deals['count'] / $deals['page_size']), 8, 'p='));
            } else {
                $this->tpl->assign("pagination",pagination(($page == 0) ? 1 : $page, ceil($deals['count'] / $deals['page_size']), 8, 'p=', '&tab='.$tab));
                $this->set_nav("投资列表");
            }
        } else {
            $pageParams = [
                'page' => $page,
                'displayedPages' => 8,
                'pstr' => 'p=',
                'content' => '',
                'pageSize' => $deals['page_size'],
                'currentPageSize' => $currentPageSize
            ];
            $this->tpl->assign("p", $page_fenzhan->showWithNoCount(array("cate"=> isset($deals['cate']) ? $deals['cate'] : null)));
            if ($this->is_firstp2p) {
                $this->tpl->assign("pagination",paginationWithNoCount($pageParams));
            } else {
                $pageParams['content'] = '&tab=' . $tab;
                $this->tpl->assign("pagination",paginationWithNoCount($pageParams));
                $this->set_nav("投资列表");
            }
        }
        // 通行证修改密码后，本地登录成功弹窗提示逻辑
        if (\es_session::get('localNeedVerify')) {
            $this->tpl->assign('showLocalVerifyTips', 1);
            // 登录成功，更新用户验证状态
            try {
                $passportService = new PassportService();
                $passportService->localVerifyPass($GLOBALS['user_info']['mobile']);
            } catch(\Exception $e) {
                Logger::info('用户验证状态更新失败'. $GLOBALS['user_info']['mobile']);
            }
            \es_session::delete('localNeedVerify');
        }

        // 是否是农担分站
        $this->tpl->assign('is_nongdan', is_nongdan_site());
        $this->tpl->display();
    }

    /**
     * 获取多投相关数据
     * @return bool
     */
    private function _getDuotou(){
         $duotou = DuotouService::getDuotouActivityList();
         $this->tpl->assign("duotou", $duotou);
    }
}
