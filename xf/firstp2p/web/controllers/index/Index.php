<?php
/**
 * 首页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\index;
use core\dao\DealModel;
use core\dao\ApiConfModel;
use web\controllers\BaseAction;

use core\service\UserService;
use libs\utils\Aes;
use libs\utils\Logger;
use libs\payment\unitebank\Unitebank;
use libs\utils\PaymentApi;
use core\service\PassportService;
use core\service\ncfph\DuotouService;
//error_reporting(E_ALL & ~E_WARNING);
//ini_set('display_errors', 1);

class Index extends BaseAction {

    //private $_income_site = array(2,8,11,12);
    private $_income_site = array();

    public function init() {
        $isMobile = $this->isMobile();
        $isMainSite = $this->isMainSite();

        // 如果是手机访问，走逻辑
        if ( $isMobile === true && $isMainSite === true) {
            // 如果是手机，且没有强制访问主站，且没有cookie 或者 有cookie,但是cookie中is_wap不为0(false)
            $domain = app_conf('FIRSTP2P_WAP_DOMAIN');
            if ( !isset($_GET['pc']) && (!isset($_COOKIE['is_wap']) || intval($_COOKIE['is_wap']) !== 0 ) ){
                if( isset($_GET['cn']) && !empty($_GET['cn']) ){
                    $cn = preg_replace("#[^A-Za-z0-9]#",'',$_GET['cn']);
                    header("Location: http://{$domain}/?cn=".$cn);
                }else{
                    header("Location: http://{$domain}");
                }
                exit;
            }else{
                setcookie('is_wap', 0, 0, "/");
            }
        }

        $appInfo = $this->appInfo;
        $setParmas = isset($appInfo['setParams']) ? (array) json_decode($appInfo['setParams'], true) : [];
        $this->tpl->assign("app_set_params", $setParmas);
        if ($isMobile === true && $setParmas['pcWapAdjust'] && !empty($appInfo['usedWapDomain']) && (2 & intval($appInfo['onlineStatus']))) {
            $args = empty($_SERVER['QUERY_STRING']) ? '' : '?' . $_SERVER['QUERY_STRING'];
            $url = sprintf('http://%s/%s', $appInfo['usedWapDomain'], $args);
            header('location:' . $url);
            exit;
        }
    }

    /*
     * 判断是不是手机的访问
     */
    private function isMobile(){
        $user_agent = (!empty($_SERVER['HTTP_USER_AGENT'])) ? $_SERVER['HTTP_USER_AGENT'] : '';
        return (!empty($user_agent) && preg_match('/(up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone|iphone|ipad|ipod|android|xoom)/i', strtolower($user_agent))) ? true : false;
    }
    /*
    * 判断是否为主站
    */
    private function isMainSite(){
        $siteId = $GLOBALS['sys_config']['TEMPLATE_LIST'][app_conf('APP_SITE')];
        setLog(array('site_id' => $siteId));
        if(intval($siteId) === 1){
            return true;
        }else{
            return false;
        }
    }

    public function invoke() {
        // p2p 网站逻辑拆分
        if ($this->is_firstp2p) {

            $this->_indexP2p();
            return true;
        }
        // 区分新版
        $class_path = strtolower(str_replace('\\', '/', get_class($this)));
        $arr_path = explode("/", $class_path);
        $cache_id  = md5($this->tpl->_var['MODULE_NAME'].$this->tpl->_var['ACTION_NAME']);
        $is_old = 0;
        $this->businessLog['source_page'] = '首页';
        if (!empty($_REQUEST['ctl']) && $_REQUEST['ctl'] == 'product'){
            $this->businessLog['source_page'] = '产品页';
            $is_old = 1;
            $cache_id  = md5('productindex');
        }
        $this->tpl->caching = true;
        $this->tpl->cache_lifetime = 600;  //首页缓存10分钟
        if (!$this->tpl->is_cached('index.html', $cache_id)) {

            $isShowReportDeal = true;
            //修改这里的参数时注意同步修改indexDataRefresh
            if (app_conf('PC_LIST_CACHE_SETNX') && !$this->isMainSite()) {
                 // 如果是农担站点，则获取所有状态的标的
                 if(app_conf('APP_SITE') == 'nongdan'){
                    $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getIndexListNdd', array(true)), 10, false, true);
                 }else{
                    $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getIndexList', array(true)), 10, false, true);
                 }
            } else {
                if((int)app_conf('SUPERVISION_SWITCH') === 1){
                    $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getIndexList', array(true)), 30);
                }else{
                    // 存管开关关闭 白名单用户访问不走缓存
                    if($this->isSvOpen){
                        $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getIndexList', array(true)), 30);
                    }else{
                        // 不在白名单用户不能看到报备标的
                        $isShowReportDeal = false;
                        $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getIndexList', array(false)), 30);
                    }
                }
            }

            //修改首页 列表页面 缓存引起标的状态有问题的情况
            $deals['list']['list'] = $this->rpc->local('DealService\UserDealStatusSwitch', array($deals['list']['list']));
            $deals['list']['list'] = $this->rpc->local('DealService\EncryptDealIds', array($deals['list']['list']));
            $deal_custom_user_list = array();
            if (!empty($GLOBALS ['user_info'])) {
                // 必须是企业站且是企业用户
                if (is_qiye_site() && $this->tpl->_var['isEnterprise']){
                    // 读取主站id
                    $deal_custom_user_list = $this->rpc->local('DealCustomUserService\getEnterpriseDealCustomUserList', array($GLOBALS ['user_info']['id'], false, false, 0, array(), false, $isShowReportDeal));
                }else {
                    $deal_custom_user_list = $this->rpc->local('DealCustomUserService\getDealCustomUserList', array($GLOBALS ['user_info']['id'], false, false, 0, array(), false, $isShowReportDeal));
                }
            }else{
                // 用户未登录状态不显示专项标
                if (!empty($deals['zx_list']['list'])){
                    foreach($deals['zx_list']['list'] as $key => $v){
                        if ($v['deal_type'] == DealModel::DEAL_TYPE_EXCLUSIVE){
                            unset($deals['zx_list']['list'][$key]);
                        }
                    }
                    $deals['zx_list']['list'] = array_values($deals['zx_list']['list']);
                }
            }

            /*
             * 起投金额格式优化
             * 专享&p2p
             */
            foreach ($deals as $k => $v) {
                if (is_array($v['list'])) {
                    foreach ($v['list'] as $key => $value) {
                        $deals[$k]['list'][$key]['min_loan'] =  number_format(bcdiv($value['min_loan_money'] , 10000,5),2);
                        $deals[$k]['list'][$key]['holiday_repay_type'] = 0;
                    }
                }
            }
            /* 尊享 */
            foreach ($deal_custom_user_list as $k => $qiye) {
                $deal_custom_user_list[$k]['min_loan'] = number_format(bcdiv($qiye['min_loan_money'] , 10000,5),2);
            }
            $this->tpl->assign("count", isset($deals['deal_type']) ? count($deals['deal_type']) : 0);
            $this->tpl->assign("deals_list", $deals['list']);
            $this->tpl->assign("deal_type", isset($deals['deal_type']) ? $deals['deal_type'] : null);
            $this->tpl->assign("zx_list", $deals['zx_list']);
            $this->tpl->assign("deal_custom_user_list", $deal_custom_user_list);

            //友情链接
            $links = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('LinkService\getLinks', array(false)), 30);
            $links = \libs\web\Url::formatUrlListForDualProtocol($links, 'img_gray');
            $this->tpl->assign("links",$links);

            //首页贷款收益概述
            if (array_search(app_conf('TEMPLATE_ID'),$this->_income_site) !== false) {
                 $deals_income_view = $this->rpc->local("EarningService\getDealsIncomeView",array(false));
            } else {
                $deals_income_view = $this->rpc->local("EarningService\getDealsIncomeView",array());
            }

            //是否显示披露信息 为1显示，其他不显示
            $this->tpl->assign("is_publish_effect",intval(app_conf('IS_PUBLISH_EFFECT')));

            if(app_conf('SHOW_FUNDING') == '1' && app_conf('TEMPLATE_ID') == '1'){
                $fund_list = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('FundService\getList', array(0,3)), 30);
            }else{
                $fund_list = null;
            }
            $this->tpl->assign("fund_list",$fund_list);
            $this->tpl->assign("deals_income_view",$deals_income_view);
            $this->_getDuotou();
            // 增加首页标识
            $this->tpl->assign("is_index", 1);
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

        //添加 rss
        $this->tpl->assign("rss_title", "firstp2p订阅");
        $this->tpl->assign("rss_url", "http://www.firstp2p.com/rss");
        $this->tpl->assign("NEW_YEAR_SKIN_2016", app_conf('NEW_YEAR_SKIN_2016'));

        //添加分站首投标志
        $this->_setFenZhanUserFirstBidFlag();

        //p2p标的列表显示长度，农担分站只显示3个，默认10个
        $siteId = \libs\utils\Site::getId();
        $p2pDisplayLength = ($siteId == $GLOBALS['sys_config']['TEMPLATE_LIST']['nongdan']) ? 3 : 10;
        $this->tpl->assign('p2p_display_length', $p2pDisplayLength);
        $this->tpl->assign("site_id", $siteId);
        $this->tpl->assign('is_nongdan', is_nongdan_site());

        //平台公告
        $noticeConf = $this->rpc->local("ApiConfService\getNoticeConf", array($siteId, ApiConfModel::NOTICE_PAGE_INDEX));
        $this->tpl->assign("notice_conf", $noticeConf);

        $this->tpl->display("index.html",$cache_id);

        // 主站新主页
        if ($this->is_wxlc && $arr_path[2]== 'index' && $arr_path[3] == 'index' && $is_old == 0) {
            $this->template = '';
            $this->tpl->display("web/views/v3/index/index_new.html", $cache_id);
        }


    }

    private function _getDuotou() {
        if (is_qiye_site()) {
            return;
        }

        $duotou = DuotouService::getDuotouActivityList();
        $this->tpl->assign("duotou", $duotou);
    }

    //分站设置用户首投标志
    private function _setFenZhanUserFirstBidFlag() {
        //主站、企业站不用管
        if ($this->is_wxlc || $this->is_firstp2p || is_qiye_site()) {
            return true;
        }

        //未登录就算没有首投
        $isFirstBid = false;
        if(!empty($GLOBALS['user_info'])) {
            $service = new \core\service\OpenService();
            $isFirstBid = $service->isBid($GLOBALS['user_info']['id']);
        }

        //设置到模板
        $this->tpl->assign('is_first_bid', $isFirstBid);
    }

    /**
     *  普惠首页
     */
    private function _indexP2p(){

        // 平台贷款收益统计展示
        $this->tpl->assign("is_publish_effect",intval(app_conf('IS_PUBLISH_EFFECT'))); // 是否展示开关
        $statistics_arr = explode(',', app_conf('CN_PLATFORM_DEAL_STATISTICS')); // admin 系统配置 格式：交易总额,交易总笔数,投资人总数,累计为投资者带来利息收入
        $deals_income_view['borrow_amount_total'] = number_format(array_shift($statistics_arr), 2);
        $deals_income_view['buy_count_total'] = number_format(array_shift($statistics_arr));
        $deals_income_view['distinct_user_total'] = number_format(array_shift($statistics_arr));
        $deals_income_view['income_expected_sum'] = number_format(array_shift($statistics_arr), 2);
        $this->tpl->assign("deals_income_view",$deals_income_view);

        $option['deal_type'] = DealModel::DEAL_TYPE_ALL_P2P;
        $page = 1;
        if((int)app_conf('SUPERVISION_SWITCH') === 1){
            $option['isHitSupervision'] = true;
            $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, $page,2,false,0,$option)), 30, false, false);
        }else{
            if($this->isSvOpen){
                $option['isHitSupervision'] = true;
                $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, $page,2,false,0,$option)), 30, false, false);
            }else{
                $deals = \SiteApp::init()->dataCache->call($this->rpc, 'local', array('DealService\getDealsList', array(null, $page,2,false,0,$option)), 30, false, false);
            }
        }

        //修改首页 列表页面 缓存引起标的状态有问题的情况
        $deals['list']['list'] = $this->rpc->local('DealService\UserDealStatusSwitch', array($deals['list']['list']));
        $deals['list']['list'] = $this->rpc->local('DealService\EncryptDealIds', array($deals['list']['list']));


        $this->tpl->assign("deals_list", $deals['list']);

        $this->_getDuotou();
        $duotou_list = $this->tpl->_var['duotou'];

        if (!empty($duotou_list['activity_list'])){
            $moneySort = array();
            $lockDaySort = array();
            foreach($duotou_list['activity_list'] as $key => $v){

                if ($duotou_list['isNewUser'] && $v['new_user_min_invest_money'] != '0.00' && !empty($v['new_user_min_invest_money']) ){
                    $moneySort[$key] = $v['new_user_min_invest_money'];
                }else{
                    $moneySort[$key] = $v['min_invest_money'];
                }

                $lockDaySort[$key] = $v['lock_day'];

            }
            // 起投额、期限 asc
            array_multisort($moneySort,$lockDaySort,$duotou_list['activity_list']);

            $this->tpl->assign('new_user_duotou',$duotou_list['activity_list']);
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

        $siteId = \libs\utils\Site::getId();
        $noticeConf = $this->rpc->local("ApiConfService\getNoticeConf", array($siteId, ApiConfModel::NOTICE_PAGE_INDEX));
        $this->tpl->assign("notice_conf", $noticeConf);

        $this->template = '';
        $this->tpl->display("web/views/v3/index/index_p2p.html");
    }

}
