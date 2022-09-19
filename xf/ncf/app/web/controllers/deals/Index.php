<?php
/**
 * 列表页.
 *
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\deals;

use libs\web\Form;
use libs\utils\Page;
use web\controllers\BaseAction;
use core\enum\DealEnum;
use core\service\user\PassportService;
use core\service\deal\DealService;
use core\service\duotou\DuotouService;
use core\service\duotou\DtDealService;
use core\service\duotou\DtActivityRulesService;
use libs\utils\Logger;

class Index extends BaseAction
{
    public function init()
    {
        $this->form = new Form();
        $this->form->rules = array(
            'cate' => array('filter' => 'int'),
            'p' => array('filter' => 'int'),
            'type' => array('filter' => 'int'),
            'field' => array('filter' => 'int'),
            'tab' => array('filter' => 'string'),
            'product_class_type' => array('filter' => 'int', 'option' => array('optional' => true)), //产品大类
            'loan_user_customer_type' => array('filter' => 'int', 'option' => array('optional' => true)), //借款客群
        );
        $this->form->validate();
    }

    public function invoke()
    {
        $data = $this->form->data;
        $page = empty($data['p']) ? 0 : intval($data['p']);
        $cate = intval($data['cate']);
        $page = ($page <= 0) ? 1 : $page;
        $p2pBeyondPageForbid = false; //普惠超过页码限制数据返回

        // 默认只显示后台配置页数
        $pageLimit = intval(app_conf('DEAL_LIST_LIMIT_PAGES'));
        if (0 != $pageLimit) {
            $page = $page > $pageLimit ? $pageLimit : $page;
        }

        $tab = 'p2p';
        $option = array();
        if (isset($data['p']) && (intval($data['p']) > $page)) {
            $p2pBeyondPageForbid = true;
        }

        $option['product_class_type'] = isset($data['product_class_type']) ? intval($data['product_class_type']) : 0; //产品大类
        $option['loan_user_customer_type'] = isset($data['loan_user_customer_type']) ? intval($data['loan_user_customer_type']) : 0; //借款客群

        $dealService = new DealService();
        $p2pSiteTags = \SiteApp::init()->dataCache->call($dealService, 'getP2pSiteTags', array(), 30, false, false);
        $this->tpl->assign('product_class_types', $p2pSiteTags['product_class_types']);
        $this->tpl->assign('loan_user_customer_types', $p2pSiteTags['loan_user_customer_types']);

        // 平台贷款收益统计展示
        $this->tpl->assign('is_publish_effect', intval(app_conf('IS_PUBLISH_EFFECT'))); // 是否展示开关
        $statistics_arr = explode(',', app_conf('CN_PLATFORM_DEAL_STATISTICS')); // admin 系统配置 格式：交易总额,交易总笔数,投资人总数,累计为投资者带来利息收入
        $deals_income_view['borrow_amount_total'] = number_format(array_shift($statistics_arr), 2);
        $deals_income_view['buy_count_total'] = number_format(array_shift($statistics_arr));
        $deals_income_view['distinct_user_total'] = number_format(array_shift($statistics_arr));
        $deals_income_view['income_expected_sum'] = number_format(array_shift($statistics_arr), 2);
        $this->tpl->assign('deals_income_view', $deals_income_view);
        $option['deal_type'] = DealEnum::DEAL_TYPE_ALL_P2P;
        $cacheKey = 'ncfph_pc_list_' . $page.'_'.$option['product_class_type'].'_'.$option['loan_user_customer_type'];
        $deals = \SiteApp::init()->dataCache->call($dealService, 'getDealsList', array(null, $page, 0, false, 0, $option), 3000, false, false,$cacheKey);
        if ($p2pBeyondPageForbid) {
            $deals['list']['list'] = array();
            $deals['count'] = 0;
        }

        //修改首页 列表页面 缓存引起标的状态有问题的情况
        $deals['list']['list'] = $dealService->UserDealStatusSwitch($deals['list']['list']);
        $deals['list']['list'] = $dealService->EncryptDealIds($deals['list']['list']);

        //暂时把holiday_repay_type改成0值
        foreach($deals['list']['list'] as $k=>$v){
            $deals['list']['list'][$k]['holiday_repay_type'] = 0;
        }

        $currentPageSize = count($deals['list']['list']);
        $limitMaxCount = $pageLimit * $deals['page_size']; //最大访问数量
        $deals['count'] = ($limitMaxCount <= $deals['count']) ? $limitMaxCount : $deals['count'];

        $page_fenzhan = new Page($deals['count'], $deals['page_size'], '', $currentPageSize);
        $this->tpl->assign('pages', ceil($deals['count'] / $deals['page_size']));
        $this->tpl->assign('current_page', (0 == $page) ? 1 : $page);
        $this->tpl->assign('deal_list', $deals['list']);
        $this->tpl->assign('count', isset($deals['deal_type']) ? count($deals['deal_type']) : 0);
        $this->tpl->assign('deal_type', isset($deals['deal_type']) ? $deals['deal_type'] : null);
        $this->tpl->assign('cate', isset($deals['cate']) ? $deals['cate'] : null);
        $this->tpl->assign('sort', isset($deals['cate']) ? $deals['sort'] : null);
        $brief = isset($deals['cate']) && isset($deals['deal_type'][$deals['cate']]['brief']) ? $deals['deal_type'][$deals['cate']]['brief'] : '';
        $this->tpl->assign('brief', $brief);
        //添加 rss
        $this->tpl->assign('rss_title', (isset($deals['deal_type']) ? $deals['deal_type'][$cate]['name'] : '').'贷款订阅');
        $this->tpl->assign('rss_url', "http://www.firstp2p.com/rss?cate={$cate}");
        $this->tpl->assign('tab', $tab);

        //获取多投相关信息
        $this->_getDuotou();

        if ($pageLimit) {
            $this->tpl->assign('p', $page_fenzhan->show(array('cate' => isset($deals['cate']) ? $deals['cate'] : null)));
            if ($this->is_firstp2p) {
                $this->tpl->assign('pagination', pagination((0 == $page) ? 1 : $page, ceil($deals['count'] / $deals['page_size']), 8, 'p='));
            } else {
                $this->tpl->assign('pagination', pagination((0 == $page) ? 1 : $page, ceil($deals['count'] / $deals['page_size']), 8, 'p=', '&tab='.$tab));
                $this->set_nav('投资列表');
            }
        } else {
            $pageParams = [
                'page' => $page,
                'displayedPages' => 8,
                'pstr' => 'p=',
                'content' => '',
                'pageSize' => $deals['page_size'],
                'currentPageSize' => $currentPageSize,
            ];
            $this->tpl->assign('p', $page_fenzhan->showWithNoCount(array('cate' => isset($deals['cate']) ? $deals['cate'] : null)));
            $this->tpl->assign('pagination', paginationWithNoCount($pageParams));
        }
        // 通行证修改密码后，本地登录成功弹窗提示逻辑
        if (\es_session::get('localNeedVerify')) {
            $this->tpl->assign('showLocalVerifyTips', 1);
            // 登录成功，更新用户验证状态
            try {
                $passportService = new PassportService();
                $passportService->localVerifyPass($GLOBALS['user_info']['mobile']);
            } catch (\Exception $e) {
                Logger::info('用户验证状态更新失败'.$GLOBALS['user_info']['mobile']);
            }
            \es_session::delete('localNeedVerify');
        }

        // 是否是农担分站
        $this->tpl->assign('is_nongdan', is_nongdan_site());
        $this->tpl->assign('appInfo', array('type' => 1));
        //$this->tpl->display('web/views/deals/index.html');
    }



    /**
     * 获取多投相关数据.
     *
     * @return bool
     */
    private function _getDuotou()
    {
        if ('1' == app_conf('DUOTOU_SWITCH') && is_duotou_inner_user()) {
            // 1 project
            $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\Project', 'getProjectEffect', array()));
            if (!$response) {
                Logger::error(implode('  |  ', array(__CLASS__, __FUNCTION__, __LINE__, ' Project getProjectEffect eror ', '系统繁忙，如有疑问，请拨打客服电话：4008909888')));
                return false;
            }
            if (0 != $response['errCode']) {
                Logger::error(implode('  |  ', array(__CLASS__, __FUNCTION__, __LINE__, ' Project getProjectEffect eror ', 'error:'.json_encode($response))));
                return false;
            }
            if (empty($response['data'])) {
                Logger::error(implode('  |  ', array(__CLASS__, __FUNCTION__, __LINE__, ' Project getProjectEffect eror data is empty ', 'error:'.json_encode($response))));
                return false;
            }
            $project = $response['data'];

            // 2 invest user number_format(number)
            $vars = array(
                'projectId' => $response['data']['id'],
            );
            $investUserNumsResponse = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $vars)), 180);
            //$investUserNumsResponse = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\DealLoan', 'getInvestUserNumsByProjectId', $request));
            $investUserNums = array();
            if ($investUserNumsResponse && (0 == $investUserNumsResponse['errCode'])) {
                $investUserNums = $investUserNumsResponse['data'];
            }

            // 3 activity_list
            $siteId = \libs\utils\Site::getId();
            $activityList = \SiteApp::init()->dataCache->call(new DtDealService(), 'getActivityIndexDeals', array($siteId), 60);
            foreach ($activityList as &$activity) {
                $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
                if (1 == $activity['lock_day']) {
                    $activity['invest_user_num'] += intval($investUserNums['0']);
                }
            }

            // 4 是否新手
            $isNewUser = 0;
            if (!empty($GLOBALS['user_info'])) {
                $dtActivityRulesService = new DtActivityRulesService();
                $isNewUser = $dtActivityRulesService->isMatchRule('loadGte3', array('userId' => $GLOBALS['user_info']['id']));
            }

            $duotou = array('project' => $project, 'activity_list' => $activityList, 'isNewUser' => $isNewUser);
            $this->tpl->assign('duotou', $duotou);
        }
    }
}
