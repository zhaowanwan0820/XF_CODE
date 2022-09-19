<?php
/**
 * generate_data.php
 *
 * @date 2015年03月02日
 * @author yangqing <yangqing@ucfgroup.com>
 * #定时任务脚本，计算网站各个时间段的注册总数和投资总数，存入缓存
 */

namespace scripts;

use libs\db\MysqlDb;
use core\data\UserData,
    core\data\DealData;
use core\service\EarningService;
use core\dao\UserModel,
    core\dao\DealLoadModel;

echo "\t*** App start ".date('Y-m-d H:i:s')." ***\n";

set_time_limit(0);
//error_reporting(E_ERROR);
//ini_set('display_errors', 1);

require(dirname(__FILE__) . '/../../app/init.php');
class GenerateData {
    private $_cacheLog = null;
    private $_dataInstance = array(
        'user' => null,
        'deal' => null,
    );
    private $_cacheKey = array(
        'reg_count'=>'CREDIT_CHINA_REG_COUNT',
        'deal_load_count'=>'CREDIT_CHINA_DEAL_LOAD_COUNT',
        'reg_log'=>'CREDIT_CHINA_REG_LOG',
        'deal_load_log'=>'CREDIT_CHINA_DEAL_LOAD_LOG',
    );
    private $_timeout = 60; //缓存过期时间

    public function __construct() {
        $this->_cacheLog = array();
        $this->_log("Init Data ...");
        $this->_dataInstance['user'] = new UserData();
        $this->_dataInstance['deal'] = new DealData();
        // 从库
        //$GLOBALS['db'] = new MysqlDb(app_conf('DB_SLAVE_HOST').":".app_conf('DB_SLAVE_PORT'), app_conf('DB_SLAVE_USER'),app_conf('DB_SLAVE_PWD'),app_conf('DB_NAME'),'utf8', 0, 1);
        $GLOBALS['db'] = MysqlDb::getInstance('firstp2p', 'slave');
        if($GLOBALS['db']->link_id === false){
            $this->_error("Can't Connect MySQL Server !!!");
            exit();
        }
    }

    public function run() {
        $nowYear = mktime(0, 0, 0, 1, 1, date('Y')); //获取当年第一天
        $nowMonth = mktime(0, 0, 0, date('n'), 1, date('Y')); //获取当月第一天
        $nowDay = mktime(0, 0, 0, date('n'), date('j'), date('Y')); //获取今天第一秒

        // ***** 注册用户统计  start ******
        $this->_log('reg data ...');
        $totalRegCount = UserModel::instance()->getCount('idcardpassed=1 AND is_delete = 0');

        $yearRegCount = UserModel::instance()->getCount("idcardpassed=1 AND is_delete = 0 AND idcardpassed_time>={$nowYear}");
        $monthRegCount = UserModel::instance()->getCount("idcardpassed=1 AND is_delete = 0 AND idcardpassed_time>={$nowMonth}");
        $dayRegCount = UserModel::instance()->getCount("idcardpassed=1 AND is_delete = 0 AND idcardpassed_time>={$nowDay}");
        $regData = array(
            'TotalRegCount' => $totalRegCount,
            'YearRegCount' => $yearRegCount,
            'MonthRegCount' => $monthRegCount,
            'DayRegCount' => $dayRegCount,
        );
        $this->_setData('user', 'setCreditRegCount', $regData);
        // ***** 注册用户统计 end ******

        $this->_log('deal load data ...');
        // ***** 投资统计  start ******
        $totalLoadCount = DealLoadModel::instance()->countViaSlave('1=1');

        $yearLoadCount = DealLoadModel::instance()->countViaSlave("create_time>={$nowYear}");
        $monthLoadCount = DealLoadModel::instance()->countViaSlave("create_time>={$nowMonth}");
        $dayLoadCount = DealLoadModel::instance()->countViaSlave("create_time>={$nowDay}");

        $loadData = array(
            'TotalLoadCount' => $totalLoadCount,
            'YearLoadCount' => $yearLoadCount,
            'MonthLoadCount' => $monthLoadCount,
            'DayLoadCount' => $dayLoadCount,
        );

        $this->_setData('deal', 'setCreditLoadCount', $loadData);
        // ***** 投资统计 end ******

        $this->_log("Success!!!");
        exit("\r\n\t###### FINISH ######\t\n");
    }

    // 写入redis
    private function _setData($dataName, $fun, $params){
        if(isset($this->_dataInstance[$dataName]) && !empty($this->_dataInstance[$dataName])){
            return $this->_dataInstance[$dataName]->$fun($params);
        }else{
            $this->_error('data instance not found');
            return false;
        }
    }

    // 输出日志
    private function _log($msg) {
        echo "[".date('Y-m-d H:i:s')."]$msg\n";
    }

    // 输出错误日志
    private function _error($msg){
        echo "\n**** ERROR : $msg ****\n";
    }
}

//define('SQL_DEBUG', true);
(new GenerateData())->run();

