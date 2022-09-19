<?php
/**
 * 首页
 * @author 王一鸣<wangyiming@ucfgroup.com>
 **/

namespace web\controllers\index;

use web\controllers\BaseAction;

use core\service\UserService;
use libs\utils\Aes;
use libs\utils\Logger;
use libs\payment\unitebank\Unitebank;
use libs\utils\PaymentApi;
use core\service\PassportService;
use core\service\deal\DealService;
use core\enum\DealEnum;
use core\service\link\LinkService;
use core\service\deal\EarningService;
use core\service\conf\ApiConfService;
use core\service\duotou\DuotouService;
use core\service\duotou\DtDealService;
use core\service\duotou\DtActivityRulesService;
use core\service\disclosure\DisclosureService;

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

        //$setParmas = (array) json_decode($appInfo['setParams'], true);
        //$this->tpl->assign("app_set_params", $setParmas);
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

    /**
     *  普惠首页
     */
    public function invoke(){

        // 平台贷款收益统计展示
        $this->tpl->assign("is_publish_effect",intval(app_conf('IS_PUBLISH_EFFECT'))); // 是否展示开关
        $service = new DisclosureService();
        $result = $service->getShowData();
        $deals_income_view['borrow_amount_total'] = number_format($result['borrow_amount'],2) ; //'累计借款金额',
        $deals_income_view['buy_count_total'] = number_format($result['borrow_count']) ; //'累计借款笔数',
        $deals_income_view['distinct_user_total'] = number_format($result['loaner_number']) ; //'累计出借人数量',
        $this->tpl->assign("deals_income_view",$deals_income_view);

        $option['deal_type'] = DealEnum::DEAL_TYPE_ALL_P2P;
        $page = 1;
        $dealService = new DealService();
        $deals = \SiteApp::init()->dataCache->call($dealService, 'getDealsList', array(null, $page,2,false,0,$option), 30, false, false,'ncfph_pc_index');

        //修改首页 列表页面 缓存引起标的状态有问题的情况
        $deals['list']['list'] = $dealService->UserDealStatusSwitch($deals['list']['list']);
        $deals['list']['list'] = $dealService->EncryptDealIds($deals['list']['list']);

        //暂时把holiday_repay_type改成0值
        foreach($deals['list']['list'] as $k=>$v){
            $deals['list']['list'][$k]['holiday_repay_type'] = 0;
        }

        $this->tpl->assign("deals_list", $deals['list']);

        //平台公告
        $siteId = \libs\utils\Site::getId();
        $apiConfService = new ApiConfService();
        $noticeConf = $apiConfService->getNoticeConf($siteId,1);
        $this->tpl->assign("notice_conf", $noticeConf);

        $this->_getDuotou();
        $duotou_list = isset($this->tpl->_var['duotou']) ? $this->tpl->_var['duotou'] : [];
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

        $this->template = '';
        $this->tpl->assign("appInfo", array("type"=>1));
        $this->tpl->display("web/views/index/index_p2p.html");
    }

    private function _getDuotou() {
        if (is_qiye_site()) {
            return;
        }

        // 多投宝开始，企业站点不现实智多鑫
        if (app_conf('DUOTOU_SWITCH') == '1' && is_duotou_inner_user()) {
            // 1 project
            $response = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\Project', "getProjectEffect", array()));
            if(!$response) {
                Logger::error(implode("  |  ", array(__CLASS__,__FUNCTION__,__LINE__, " Project getProjectEffect eror ", "系统繁忙，如有疑问，请拨打客服电话：4008909888")));
                return false;
            }
            if ($response['errCode'] != 0) {
                Logger::error(implode("  |  ", array(__CLASS__,__FUNCTION__,__LINE__, " Project getProjectEffect eror ", "error:".json_encode($response))));
                return false;
            }
            if (empty($response['data'])) {
                Logger::error(implode("  |  ", array(__CLASS__,__FUNCTION__,__LINE__, " Project getProjectEffect eror data is empty ", "error:".json_encode($response))));
                return false;
            }
            $project = $response['data'];

            // 2 invest user number
            $request = array(
                'projectId' => $response['data']['id'],
            );
            $investUserNumsResponse = \SiteApp::init()->dataCache->call(new DuotouService() , 'call', array(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request)), 180);
            //$investUserNumsResponse = DuotouService::callByObject(array('\NCFGroup\Duotou\Services\DealLoan', "getInvestUserNumsByProjectId", $request));
            $investUserNums = array();
            if($investUserNumsResponse && ($investUserNumsResponse['errCode'] == 0)) {
                $investUserNums = $investUserNumsResponse['data'];
            }

            // 3 activity_list
            $siteId = \libs\utils\Site::getId();
            $activityList = \SiteApp::init()->dataCache->call(new DtDealService(), 'getActivityIndexDeals', array($siteId), 60);
            foreach ($activityList as & $activity) {
                $activity['invest_user_num'] = intval($investUserNums[$activity['id']]);
                if($activity['lock_day'] == 1) {
                    $activity['invest_user_num'] += intval($investUserNums['0']);
                }
            }
            // 4 是否新手
            $isNewUser = 0;
            if(!empty($GLOBALS['user_info'])){
                $dtActivityRulesService = new DtActivityRulesService();
                $isNewUser = $dtActivityRulesService->isMatchRule('loadGte3', array('userId' => $GLOBALS['user_info']['id']));}

            $duotou = array('project'=>$project, 'activity_list'=>$activityList, 'isNewUser'=>$isNewUser);
            $this->tpl->assign("duotou", $duotou);
        }
        // 多投宝结束
    }
}
