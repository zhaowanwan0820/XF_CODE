<?php
/**
 * generate_income.php
 *
 * @date 2014年11月3日
 * @author yangqing <yangqing@ucfgroup.com>
 * #定时任务脚本，计算各个分站的收益统计，存入缓存
 */

namespace scripts;

use libs\utils\Logger;
use libs\db\MysqlDb;
use core\data\DealData;
use core\service\EarningService;
use core\dao\DealModel,
    core\dao\DealLoanRepayModel,
    core\dao\DealLoadModel,
    core\dao\DealLoanTypeModel;

echo "\t*** App start ".date('Y-m-d H:i:s')." ***\n";

set_time_limit(0);
//error_reporting(0);
//ini_set('display_errors', 1);

require(dirname(__FILE__) . '/../app/init.php');
class GenerateIncome {
    //private $_cacheLog = null;
    //private $_incomeSite = array(2,8,11,12);
    //private $_incomeSite = array();
    private $_earningService = null;

    public function __construct() {
        //$this->_cacheLog = array();
        $this->_log("Init mysql");
        // 从库
        //$GLOBALS['db'] = new MysqlDb(app_conf('DB_SLAVE_HOST').":".app_conf('DB_SLAVE_PORT'), app_conf('DB_SLAVE_USER'),app_conf('DB_SLAVE_PWD'),app_conf('DB_NAME'),'utf8', 0, 1);
        $GLOBALS['db'] = MysqlDb::getInstance('firstp2p', 'slave');
        if($GLOBALS['db']->link_id === false){
            $this->_error("Can't Connect MySQL Server !!!");
            exit();
        }
        $this->_earningService = new EarningService();
    }
    public function process() {
        $siteKeys = array(
             'DEAL_SITE_ALLOW',
             'MIN_INCOME_RATE',
             'MAX_INCOME_RATE'
         );
        $log['class'] = __CLASS__;
        $log['function'] = __FUNCTION__;
        $log['deal_income_start_script'] = 'start: '.date('Y-m-d H:i:s');
        $result = $this->setIncome();
        if ($result) {
            $log['success'] = 'success';
        } else {
            $log['failed'] = 'failed';
        }
        $this->_log("Success!!!");
        $log['deal_income_end_script'] = 'end: '.date('Y-m-d H:i:s');
        logger::info(implode(' | ', $log));
        exit("\r\n\t###### FINISH ######\t\n");
    }
    // 写入redis
    private function setIncome () {
       $resetCache = true;
       $deals_income_view =  $this->_earningService->getIncomeViewNew($resetCache,'0,1');
       if ($deals_income_view) {
           return true;
       } else {
           return false;
       }
    }
/*
    public function process() {
        $siteKeys = array(
            'DEAL_SITE_ALLOW',
            'MIN_INCOME_RATE',
            'MAX_INCOME_RATE'
            );
        $siteList = $GLOBALS['sys_config']['TEMPLATE_LIST'];

        // 遍历所有分站站点
        foreach($siteList as $siteTpl => $siteID){
            $this->_log("Generate site #{$siteTpl}({$siteID})#:");
            // 设置对应分站的属性
            foreach($siteKeys as $confKey){
                $GLOBALS['sys_config'][$confKey] = get_config_db($confKey, $siteID);
                $GLOBALS['sys_config']['TEMPLATE_ID'] = $siteID;
            }
            $deal_site_allow = app_conf('DEAL_SITE_ALLOW');
            if(array_search(app_conf('TEMPLATE_ID'),$this->_incomeSite) !== false){
                $cacheID = empty($deal_site_allow)?app_conf("TEMPLATE_ID"):$deal_site_allow;
                $deals_income_view = $this->_setIncome($cacheID,false);
            }else{
                $cacheID = 0;
                $deals_income_view = $this->_setIncome($cacheID,true);
            }
            $cacheCateID = $cacheID.'_0';
            $deals_income_view = $this->_setIncome($cacheCateID, false, 0);//rss收益统计数据
            // 生成主站的所有分类统计
            if($siteID == '1'){
                $this->_log("Load cate income:");
                $cateList = DealLoanTypeModel::instance()->getDealTypes();
                $cacheCateID = '';
                foreach($cateList['data'] as $cate){
                    if(isset($cate['id']) && is_numeric($cate['id'])){
                        $cacheCateID = $cacheID.'_'.$cate['id'];
                        $deals_income_view = $this->_setIncome($cacheCateID, false, $cate['id']);
                    }
                }
            }
        }
        $this->_log("Success!!!");
        exit("\r\n\t###### FINISH ######\t\n");
    }

    // 写入redis
    private function _setIncome($cacheID, $showAll, $cateID = null){
        if(isset($this->_cacheLog[$cacheID])){
            $this->_log("Cache id {$cacheID} is exist");
            return true;
        }else{
            $this->_log("Load cache id {$cacheID} ...");
            if($cateID === null){
                $deals_income_view = $this->_earningService->getIncomeView($showAll, true);
            }else{
                $deals_income_view = $this->_earningService->getIncomeViewByCate($showAll, $cateID, true);
            }
            if(isset($deals_income_view) && !empty($deals_income_view)){
                $this->_log("Load cache id {$cacheID} finish");
                $this->_cacheLog[$cacheID] = $deals_income_view;
                return true;
            }else{
                $this->_error("load cache id {$cacheID} fail");
                return false;
            }
        }
    }
 */
    // 输出日志
    private function _log($msg) {
        echo "[".date('Y-m-d H:i:s')."]$msg\n";
    }

    // 输出错误日志
    private function _error($msg){
        echo "\n**** ERROR : $msg ****\n";
    }
}

(new GenerateIncome)->process();

