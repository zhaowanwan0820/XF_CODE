<?php
/** 存管系统上线前，刷新线上用户的账户类型
 *  刷新【全量个人用户】的账户类型：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php user_all 1000
 *  刷新【指定UID区间范围的用户】的账户类型：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php user_range 10010 10086
 *  刷新【文件里面指定的用户】的账户类型：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php user_appoint
 *  刷新【文件里指定的机构】：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php refresh_agency
 *  导出机构列表数据：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php export_agency_list
 *  导出机构用户数据：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php export_userpurpose_list
 *  回滚【全量个人用户】数据：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php rollback refresh_user_all_20170412132701
 *  回滚【指定UID区间范围的用户】数据：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php rollback refresh_user_range_20170412132701
 *  回滚【文件里面指定的用户】数据：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php rollback refresh_user_appoint_20170412132701
 *  回滚【文件里指定的机构】数据：/apps/product/php/bin/php /apps/product/nginx/htdocs/firstp2p/scripts/refresh_user_purpose.php rollback refresh_agency_list_20170412132701
 * @author guofeng3 2017-04-13
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
use libs\db\Db;
use core\service\UserService;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\dao\DealAgencyModel;
use core\dao\AgencyUserModel;

set_time_limit(0);
ini_set('memory_limit', '1024M');
define('USERPURPOSE_LOG_PATH', '/tmp/userpurpose/');
define('ROLLBACK_LOG_PATH', '/tmp/userpurpose/rollback/');
error_reporting(E_ALL);
ini_set('display_errors', 1);

class refresh_user_purpose {
    /**
     * 导出的机构用户列表缓存文件名
     * @var string
     */
    private $userPurposeCacheFileName = 'cache_userpurpose_list.php';
    /**
     * 导出的机构列表缓存文件名
     * @var string
     */
    private $agencyCacheFileName = 'cache_agency_list.php';
    /**
     * 用户账户类型的列表
     * @var array
     */
    private $userPurposeList = array();
    /**
     * 机构列表
     * @var array
     */
    private $agencyList = array();
    /**
     * 每次获取的数量
     * @var int
     */
    private $pageSize = 0;
    /**
     * 当前时间戳
     * @var int
     */
    private $currentTime = 0;

    private $argv, $method, $startTime;

    public function __construct($userPurposeList = array(), $agencyList = array()) {
        // 创建log目录
        self::_mkdirs(USERPURPOSE_LOG_PATH);
        // 用户账户类型的列表
        $this->userPurposeList = $userPurposeList;
        // 机构列表
        $this->agencyList = $agencyList;
        // 当前时间
        $this->nowTime = date('Y-m-d H:i:s');
        // 开始时间
        $this->startTime = microtime(true);
    }

    /**
     * 刷新【全量个人用户】的账户类型
     * 控制台命令：php refresh_user_purpose.php user_all 1000
     */
    public function user_all($argv) {
        try{
            if(!isset($argv[2]) || !is_numeric($argv[2])) {
                exit('参数错误: refresh_user_purpose.php user_all 1000' . PHP_EOL);
            }
            $totalNum = $successNum = $failNum = 0;
            $this->pageSize = !empty($argv[2]) ? (int)$argv[2] : 1000;
            $userModel = UserModel::instance();
            // 借贷混合用户
            $userPurpose = EnterpriseModel::COMPANY_PURPOSE_MIX;
            $paymentCheckService = new \core\service\PaymentCheckService();
            $maxUserId = $paymentCheckService->getMaxUserId();
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新普通用户账户类型-全量用户]-[最大用户UID：'.$maxUserId.']-start-------------------------');
            for ($startUid = 0; $startUid <= $maxUserId; $startUid += $this->pageSize)
            {
                $userList = $userModel->findAllViaSlave('id > :startUid AND id <= :endUid', true, 'id,user_name,user_purpose,update_time', array(':startUid'=>$startUid, ':endUid'=>($startUid + $this->pageSize)));
                if (!empty($userList)) {
                    foreach ($userList as $userInfo) {
                        ++$totalNum;
                        if (empty($userInfo)) {
                            ++$failNum;
                            self::_log('['.__METHOD__.']-用户信息不存在');
                            continue;
                        }
                        // 用户的账户类型
                        if (strlen($userInfo['user_purpose']) > 0) {
                            ++$failNum;
                            self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-账户类型['.$userInfo['user_purpose'].']-用户账户类型字段已经更新，无需处理');
                            continue;
                        }
                        // 更新用户的账户类型
                        $userModel->updateInfo(['id'=>$userInfo['id'], 'user_purpose'=>$userPurpose], 'update');
                        if ($userModel->db->affected_rows() > 0) {
                            ++$successNum;
                            self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-旧user_purpose['.$userInfo['user_purpose'].']-新user_purpose['.$userPurpose.']-刷新[个人用户账户类型]-全量用户-成功');
                        }else{
                            ++$failNum;
                            self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-旧user_purpose['.$userInfo['user_purpose'].']-新user_purpose['.$userPurpose.']-刷新[个人用户账户类型]-全量用户-失败');
                        }
                    }
                }
            }
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新普通用户账户类型-全量用户]-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end--------------');
        }catch (\Exception $e){
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新普通用户账户类型-全量用户]异常，exceptionCode:'.$e->getCode().'，exceptionMsg:'.$e->getMessage());
        }
    }

    /**
     * 刷新【指定UID区间范围的用户】的账户类型
     * 控制台命令：php refresh_user_purpose.php user_range 10010 10086
     */
    public function user_range($argv) {
        try{
            if(!isset($argv[2]) || !is_numeric($argv[2]) || !isset($argv[3]) || !is_numeric($argv[3])) {
                exit('参数错误: refresh_user_purpose.php user_range 10010 10086' . PHP_EOL);
            }
            $existUid = [];
            $totalNum = $successNum = $failNum = 0;
            $this->pageSize = !empty($argv[4]) ? (int)$argv[4] : 1000;
            $userModel = UserModel::instance();
            // 借贷混合用户
            $userPurpose = EnterpriseModel::COMPANY_PURPOSE_MIX;
            $minUserId = (int)$argv[2];
            $maxUserId = max($minUserId, (int)$argv[3]);
            $userLoop = ceil(($maxUserId - $minUserId) / $this->pageSize);
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新普通用户账户类型-指定用户UID范围]-[最小用户UID：'.$minUserId.']-[最大用户UID：'.$maxUserId.']-[次数：'.$userLoop.']-start-------------------------');
            for ($startUid = $minUserId; $startUid <= $maxUserId; $startUid += $userLoop)
            {
                $endUid = min($maxUserId, $startUid + $userLoop);
                $userList = $userModel->findAllViaSlave('id >= :startUid AND id <= :endUid', true, 'id,user_name,user_purpose,update_time', array(':startUid'=>$startUid, ':endUid'=>$endUid));
                if (!empty($userList)) {
                    foreach ($userList as $userInfo) {
                        ++$totalNum;
                        if (empty($userInfo)) {
                            ++$failNum;
                            self::_log('['.__METHOD__.']-用户信息不存在');
                            continue;
                        }
                        if (isset($existUid[$userInfo['id']])) {
                            --$totalNum;
                            continue;
                        }
                        $existUid[$userInfo['id']] = 1;
                        // 用户的账户类型
                        if (strlen($userInfo['user_purpose']) > 0) {
                            ++$failNum;
                            self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-账户类型['.$userInfo['user_purpose'].']-用户账户类型字段已经更新，无需处理');
                            continue;
                        }
                        // 更新用户的账户类型
                        $userModel->updateInfo(['id'=>$userInfo['id'], 'user_purpose'=>$userPurpose], 'update');
                        if ($userModel->db->affected_rows() > 0) {
                            ++$successNum;
                            self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-旧user_purpose['.$userInfo['user_purpose'].']-新user_purpose['.$userPurpose.']-刷新[个人用户账户类型]-指定用户UID范围-成功');
                        }else{
                            ++$failNum;
                            self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-旧user_purpose['.$userInfo['user_purpose'].']-新user_purpose['.$userPurpose.']-刷新[个人用户账户类型]-指定用户UID范围-失败');
                        }
                    }
                }
            }
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新普通用户账户类型-指定用户UID范围]-[刷新总数-'.$totalNum.']-[更新成功-'.$successNum.']-[更新失败-'.$failNum.']-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end--------------');
        }catch (\Exception $e){
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新普通用户账户类型-指定用户UID范围]异常，exceptionCode:'.$e->getCode().'，exceptionMsg:'.$e->getMessage());
        }
    }

    /**
     * 刷新【文件里面指定的用户】的账户类型
     * 控制台命令： php refresh_user_purpose.php user_appoint
     *
     */
    public function user_appoint() {
        if (file_exists(USERPURPOSE_LOG_PATH . $this->userPurposeCacheFileName)) {
            $this->userPurposeList = include(USERPURPOSE_LOG_PATH . $this->userPurposeCacheFileName);
        }
        // 需要迁移的机构用户数量
        $totalNum = count($this->userPurposeList);
        $successNum = $failNum = 0;
        $logMsg = '------------------------['.$this->nowTime.']['.__METHOD__.']-[更新机构用户的账户类型]-start-------------------------';
        self::_log($logMsg);
        if (!empty($this->userPurposeList)) {
            $userModel = UserModel::instance();
            foreach ($this->userPurposeList as $item) {
                self::_log('---------------------------用户ID['.$item['userId'].']-start---------------------------');
                // 用户账户类型
                $purposeMapList = $GLOBALS['dict']['ENTERPRISE_PURPOSE'];
                $userPurpose = (int)$item['purposeId'];
                if (empty($purposeMapList[$userPurpose]) || $purposeMapList[$userPurpose]['bizName'] != $item['purposeName']) {
                    ++$failNum;
                    throw new \Exception('序号ID['.$item['id'].']-用户ID['.$item['userId'].']-账户用途ID['.$userPurpose.']-账户用途['.$item['purposeName'].'-账户用途不存在或账户用途名称不符合');
                }

                // 查询该用户的信息
                $userInfo = UserModel::instance()->find($item['userId'], 'id,user_name,user_purpose,update_time', true);
                if (empty($userInfo)) {
                    ++$failNum;
                    self::_log('['.__METHOD__.']-用户ID['.$item['userId'].']-用户信息不存在');
                    continue;
                }
                // 更新用户的账户类型
                $userModel->updateInfo(['id'=>$userInfo['id'], 'user_purpose'=>$userPurpose], 'update');
                if ($userModel->db->affected_rows() > 0) {
                    ++$successNum;
                    self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-旧user_purpose['.$userInfo['user_purpose'].']-新user_purpose['.$userPurpose.']-更新[机构用户账户类型]-指定文件中的用户-成功');
                }else{
                    ++$failNum;
                    self::_log('['.__METHOD__.']-用户ID['.$userInfo['id'].']-旧user_purpose['.$userInfo['user_purpose'].']-新user_purpose['.$userPurpose.']-更新[机构用户账户类型]-指定文件中的用户-失败');
                }
                self::_log('---------------------------用户ID['.$item['userId'].']-end---------------------------');
            }
        }else{
            //暂无需要更新的
            self::_log('------------------------[待更新机构用户列表为空，无法进行更新]-start-------------------------');
        }
        $logMsg = '------------------------['.$this->nowTime.']['.__METHOD__.']-[更新机构用户的账户类型]-[刷新总数-'.$totalNum.']-[更新成功-'.$successNum.']-[更新失败-'.$failNum.']-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-------------------------';
        self::_log($logMsg);
    }

    /**
     * 刷新【文件里指定的机构】
     * 控制台命令： php refresh_user_purpose.php refresh_agency
     *
     */
    public function refresh_agency() {
        if (file_exists(USERPURPOSE_LOG_PATH . $this->agencyCacheFileName)) {
            $this->agencyList = include(USERPURPOSE_LOG_PATH . $this->agencyCacheFileName);
        }
        // 需要迁移的机构数量
        $totalNum = count($this->agencyList);
        $successNum = $failNum = 0;
        $logMsg = '------------------------['.$this->nowTime.']['.__METHOD__.']-[更新机构的关联用户等数据]-start-------------------------';
        self::_log($logMsg);
        if (!empty($this->agencyList)) {
            foreach ($this->agencyList as $item) {
                self::_log('---------------------------机构名称['.$item['agencyName'].']-机构类型['.$item['typeName'].']-关联用户ID['.$item['relatedUserId'].']-start---------------------------');
                if (empty($item['agencyName'])) {
                    continue;
                }
                // 刷新机构的数据
                $this->_refresh_agency($item, $successNum, $failNum);
                self::_log('---------------------------机构名称['.$item['agencyName'].']-机构类型['.$item['typeName'].']-关联用户ID['.$item['relatedUserId'].']-end---------------------------');
            }
        }else{
            //暂无需要迁移的
            self::_log('------------------------[待更新机构列表为空，无法进行更新]-end-------------------------');
        }
        $logMsg = '------------------------['.$this->nowTime.']['.__METHOD__.']-[更新机构的关联用户等数据]-[刷新总数-'.$totalNum.']-[更新成功-'.$successNum.']-[更新失败-'.$failNum.']-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-------------------------';
        self::_log($logMsg);
    }

    /**
     * 刷新文件里指定的机构
     * @param array $item
     */
    private function _refresh_agency($item, &$successNum, &$failNum)
    {
        // 开启事务
        $db = Db::getInstance('firstp2p');
        // 事务开始时间
        $this->transactionStartTime = microtime(true);
        $db->startTrans();
        $commitSql = '';
        $rollbackSql = '';
        try {
            // 机构列表
            $organizeMapList = array_flip($GLOBALS['dict']['ORGANIZE_TYPE']);
            if (!isset($organizeMapList[$item['typeName']]) || $organizeMapList[$item['typeName']] != $item['typeId']) {
                ++$failNum;
                throw new \Exception('序号ID['.$item['id'].']-机构名称['.$item['agencyName'].']-机构类型ID['.$item['typeId'].'-机构类型['.$item['typeName'].'-机构类型ID不存在或机构类型名称不符合');
            }

            // 根据机构名称、机构类型，获取机构数据
            $dealAgencyList = DealAgencyModel::instance()->getListByAgencyName($item['typeId'], $item['agencyName']);
            if (empty($dealAgencyList)) {
                ++$failNum;
                throw new \Exception('获取[firstp2p_deal_agency]机构列表数据失败');
            }
            // 根据机构确认账户，获取用户的基本信息
            $agencyUserInfo = UserModel::instance()->getInfoByName($item['agencyConfirmUserName'], 'id,user_name', true);
            if (empty($agencyUserInfo)) {
                ++$failNum;
                throw new \Exception('用户会员名称['.$item['agencyConfirmUserName'].']-获取机构确认帐号的用户数据失败');
            }

            foreach ($dealAgencyList as $agencyItem) {
                // 更新机构列表的数据
                $dealAgencyTableName = DealAgencyModel::instance()->tableName();
                $sql = sprintf('UPDATE `%s` SET `user_id`=\'%s\', `agency_user_id`=\'%s\' WHERE `id`=\'%d\';', $dealAgencyTableName, $item['relatedUserId'], $item['agentUserId'], $agencyItem['id']);
                $updateDealAgencyRet = $db->query($sql);
                if (!$updateDealAgencyRet) {
                    ++$failNum;
                    throw new \Exception('机构ID['.$agencyItem['id'].']-机构名称['.$agencyItem['name'].']-['.$sql.']-更新[firstp2p_deal_agency]记录失败');
                }
                // commit用
                $commitSql .= $sql . PHP_EOL;
                // rollback用
                $rollbackSql .= sprintf('UPDATE `%s` SET `user_id`=\'%s\', `agency_user_id`=\'%s\' WHERE `id`=\'%d\';', $dealAgencyTableName, $agencyItem['user_id'], $agencyItem['agency_user_id'], $agencyItem['id']) . PHP_EOL;

                $agencyUserTableName = AgencyUserModel::instance()->tableName();
                // 查询所有的担保人信息-rollback用
                $agencyUserList = AgencyUserModel::instance()->findAllViaSlave('agency_id=:agency_id', true, 'user_id,user_name,agency_id', array(':agency_id' => $agencyItem['id']));
                if (empty($agencyUserList)) {
                    ++$failNum;
                    throw new \Exception('机构ID['.$agencyItem['id'].']-机构名称['.$agencyItem['name'].']-获取[firstp2p_agency_user]记录失败');
                }

                if (count($agencyUserList) > 1) {
                    // 先根据担保机构的ID，删除所有担保人的信息
                    $sql = sprintf('DELETE FROM `%s` WHERE `agency_id` = \'%d\'', $agencyUserTableName, $agencyItem['id']);
                    $deleteAgencyUserRet = $db->query($sql);
                    if (!$deleteAgencyUserRet) {
                        ++$failNum;
                        throw new \Exception('机构ID['.$agencyItem['id'].']-机构名称['.$agencyItem['name'].']-['.$sql.']-存在多个担保人-删除[firstp2p_agency_user]记录失败');
                    }
                    // commit用
                    $commitSql .= $sql . PHP_EOL;

                    // 新插入一条担保人的信息
                    $sql = sprintf('INSERT INTO `%s`(`user_id`,`user_name`,`agency_id`) VALUES(\'%s\', \'%s\', \'%d\');', $agencyUserTableName, $agencyUserInfo['id'], $agencyUserInfo['user_name'], $agencyItem['id']);
                    $insertAgencyUserRet = $db->query($sql);
                    if (!$insertAgencyUserRet) {
                        ++$failNum;
                        throw new \Exception('机构ID['.$agencyItem['id'].']-机构名称['.$agencyItem['name'].']-['.$sql.']-存在多个担保人-创建[firstp2p_agency_user]记录失败');
                    }
                    // commit用
                    $commitSql .= $sql . PHP_EOL;

                    // rollback用
                    $rollbackSql .= sprintf('DELETE FROM `%s` WHERE agency_id = \'%d\';', $agencyUserTableName, $agencyItem['id']) . PHP_EOL;
                    if (!empty($agencyUserList)) {
                        foreach ($agencyUserList as $agencyUserItem) {
                            // rollback用
                            $rollbackSql .= sprintf('INSERT INTO `%s`(`user_id`,`user_name`,`agency_id`) VALUES(\'%s\', \'%s\', \'%d\');', $agencyUserTableName, $agencyUserItem['user_id'], $agencyUserItem['user_name'], $agencyUserItem['agency_id']) . PHP_EOL;
                        }
                    }
                } else {
                    // 更新该担保机构的担保人信息
                    $sql = sprintf('UPDATE `%s` SET `user_id`=\'%s\', `user_name`=\'%s\' WHERE `agency_id`=\'%d\';', $agencyUserTableName, $agencyUserInfo['id'], $agencyUserInfo['user_name'], $agencyItem['id']);
                    $updateAgencyUserRet = $db->query($sql);
                    if (!$updateAgencyUserRet) {
                        ++$failNum;
                        throw new \Exception('机构ID['.$agencyItem['id'].']-机构名称['.$agencyItem['name'].']-['.$sql.']-只有1个担保人-更新[firstp2p_agency_user]记录失败');
                    }
                    // commit用
                    $commitSql .= $sql . PHP_EOL;

                    // rollback用
                    $rollbackSql .= sprintf('UPDATE `%s` SET `user_id`=\'%s\', `user_name`=\'%s\' WHERE `agency_id`=\'%d\';', $agencyUserTableName, $agencyUserList[0]['user_id'], $agencyUserList[0]['user_name'], $agencyItem['id']) . PHP_EOL;
                }
            }

            // 提交事务
            $db->commit();
            ++$successNum;
            // 记录sql-commit用
            self::_log($commitSql);
            // 记录日志
            self::_log('['.__METHOD__.']-[更新机构用户账户类型]成功-序号ID['.$item['id'].'-机构名称['.$item['agencyName'].']-机构类型ID['.$item['typeId'].'-机构类型['.$item['typeName'].']-[事务耗时:'.round(microtime(true) - $this->transactionStartTime, 4).']-更新完成');
            // 记录sql-rollback用
            self::_writeSql(ROLLBACK_LOG_PATH . sprintf('refresh_agency_list_%s_%s.log', Logger::getLogId(), date('YmdHi')), $rollbackSql);
        } catch (\Exception $e) {
            // 回滚事务
            $db->rollback();
            // 记录日志
            $errMsg = '['.__METHOD__.']-[更新机构用户账户类型]异常-[事务耗时:'.round(microtime(true) - $this->transactionStartTime, 4).']-' . $e->getMessage();
            self::_log($errMsg);
        }
    }

    /**
     * 根据记录的回滚sql，进行回滚处理
     * 控制台命令：
     *     php refresh_user_purpose.php rollback refresh_user_all_20170412132701
     *     php refresh_user_purpose.php rollback refresh_user_range_20170412132701
     *     php refresh_user_purpose.php rollback refresh_agency_list_20170412132701
     *     php refresh_user_purpose.php rollback refresh_user_appoint_20170412132701
     *     php refresh_user_purpose.php rollback rollback_error
     *     php refresh_user_purpose.php rollback rollback_exception
     *
     */
    public function rollback($argv)
    {
        if(!isset($argv[2]) || empty($argv[2])) {
            exit('参数错误: refresh_user_purpose.php rollback [refresh_user_all_20170412132701|refresh_user_range_20170412132701|rollback_error|rollback_exception]' . PHP_EOL);
        }
        $msg = '[回滚数据-' . $argv[2] . ']';
        // 文件全路径
        $fileName = ROLLBACK_LOG_PATH . $argv[2] . '.log';
        if(!file_exists($fileName)) {
            exit('------------------------['.$fileName.']文件不存在-------------------------' . PHP_EOL);
        }

        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-'.$msg.'-start-------------------------');
        $fpr = fopen($fileName, 'r') or die("Unable to open [{$fileName}]!" . PHP_EOL);
        $db = Db::getInstance('firstp2p');
        while (!feof($fpr)) {
            $oneLine = fgets($fpr);
            $oneLine = str_replace(PHP_EOL, '', $oneLine);
            // 跳过不符合的数据
            if (empty($oneLine)) {
                continue;
            }

            try {
                // 执行回滚sql
                $queryRet = $db->query($oneLine);
                if (!$queryRet) {
                    self::_writeSql(ROLLBACK_LOG_PATH . 'rollback_error.log', $oneLine);
                    $queryMsg = '回滚失败';
                }else{
                    $queryMsg = '回滚成功';
                }
                self::_log('['.__METHOD__.']-['.$oneLine . ']-'.$msg.'-' . $queryMsg);
            } catch (\Exception $e) {
                self::_writeSql(ROLLBACK_LOG_PATH . 'rollback_exception.log', $oneLine);
                self::_log('['.__METHOD__.']-['.$oneLine.']|'.$e->getMessage());
            }
        }
        fclose($fpr);
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-'.$msg.'-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-----------------');
    }

    /**
     * 把txt文件中的机构类型数据，导成可用的数组并存入文件
     * 控制台命令： php refresh_user_purpose.php export_agency_list
     *
     */
    public function export_agency_list()
    {
        // 文件全路径
        $fileName = USERPURPOSE_LOG_PATH . 'agency_list.txt';
        if(!file_exists($fileName)) {
            exit('------------------------['.$fileName.']文件不存在-------------------------' . PHP_EOL);
        }
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[导出数组文件]-start-------------------------');
        $fpr = fopen($fileName, 'r') or die("Unable to open [{$fileName}]!");
        $list = array();
        while (!feof($fpr)) {
            $oneLine = fgets($fpr);
            self::_log($oneLine);
            $oneItem = explode("\t", $oneLine);
            // 跳过不符合的数据
            if (!isset($oneItem[0]) || !is_numeric($oneItem[0])) {
                continue;
            }

            $id = (int)$oneItem[0];
            $list[$id] = array(
                'id' => $id, // 序号ID
                'agencyName' => trim($oneItem[1]), // 机构名称
                'typeName' => trim($oneItem[2]), // 机构类型
                'typeId' => trim($oneItem[3]), // 机构类型ID
                'relatedUserId' => trim($oneItem[4]), // 关联用户ID
                'agencyConfirmUserName' => trim($oneItem[5]), // 机构确认账户
                'agentUserId' => trim($oneItem[6]), // 机构代理人ID
            );
        }
        fclose($fpr);
        // 写入文件
        $content = '<?php return ' . var_export($list, true) . ';';
        file_put_contents(USERPURPOSE_LOG_PATH . $this->agencyCacheFileName, $content, LOCK_EX);
        clearstatcache();
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[导出数组文件]-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-------------------------');
    }

    /**
     * 把txt文件中的机构用户数据，导成可用的数组并存入文件
     * 控制台命令： php refresh_user_purpose.php export_userpurpose_list
     *
     */
    public function export_userpurpose_list()
    {
        // 文件全路径
        $fileName = USERPURPOSE_LOG_PATH . 'userpurpose_list.txt';
        if(!file_exists($fileName)) {
            exit('------------------------['.$fileName.']文件不存在-------------------------' . PHP_EOL);
        }
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[导出数组文件]-start-------------------------');
        $fpr = fopen($fileName, 'r') or die("Unable to open [{$fileName}]!");
        $list = array();
        while (!feof($fpr)) {
            $oneLine = fgets($fpr);
            self::_log($oneLine);
            $oneItem = explode("\t", $oneLine);
            // 跳过不符合的数据
            if (!isset($oneItem[0]) || !is_numeric($oneItem[0])) {
                continue;
            }

            $userId = (int)$oneItem[1];
            $list[$userId] = array(
                'id' => (int)$oneItem[0], // 序号ID
                'userId' => $userId, // 用户ID
                'purposeName' => trim($oneItem[2]), // 账户用途名称
                'purposeId' => trim($oneItem[3]), // 账户用途ID
            );
        }
        fclose($fpr);
        // 写入文件
        $content = '<?php return ' . var_export($list, true) . ';';
        file_put_contents(USERPURPOSE_LOG_PATH . $this->userPurposeCacheFileName, $content, LOCK_EX);
        clearstatcache();
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[导出数组文件]-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-------------------------');
    }

    /**
     * 记录日志
     * @param string $message
     * @param string $filePrefix
     */
    private static function _log($message, $filePrefix = 'log', $path = '', $suffix = '') {
        echo $message . PHP_EOL;
        $path = empty($path) ? USERPURPOSE_LOG_PATH : $path;
        $suffix = empty($suffix) ? date('y_m_d') : $suffix;
        Logger::wLog($message, Logger::INFO, Logger::FILE, $path . $filePrefix . '_' . $suffix . '.log');
    }

    /**
     * 创建目录
     * @param string $dir
     * @param number $mode
     * @throws Exception
     */
    private static function _mkdirs($dir, $mode = 0777)
    {
        if (!is_dir($dir))
        {
            $ret = @mkdir($dir, $mode, true);
            if (!$ret)
            {
                throw new Exception(sprintf('Create dir "%s" failed.', $dir));
            }
        }
        return true;
    }

    /**
     * 写入文件
     * @param string $filename 要写入的文件全路径名
     * @param string $writetext 文件内容
     * @param string $openmod 文件打开的mode
     * @return boolean
     */
    private static function _writeSql($filename, $writetext, $openmod='ab+')
    {
        self::_mkdirs(dirname($filename), 0755);
        if($fp = fopen($filename, $openmod))
        {
            flock($fp, LOCK_EX);
            fwrite($fp, $writetext . PHP_EOL);
            flock($fp, LOCK_UN);
            fclose($fp);
            return TRUE;
        }else{
            exit("ERROR=>File: {$filename} write error.");
            return FALSE;
        }
    }
}


if(!isset($argv[1])){
    die("未输入[处理函数]参数");
}
// 导出的用户账户类型的列表
$userPurposeList = getUserPurposeList();
// 导出的机构列表
$agencyList = getAgencyList();

// 执行具体方法
$obj = new refresh_user_purpose($userPurposeList, $agencyList);
if (!method_exists($obj, $argv[1])) {
    die("method:{$argv[1]} is not found." . PHP_EOL);
}
if (!is_callable(array($obj, $argv[1]))) {
    die("method:{$argv[1]} is access forbidden." . PHP_EOL);
}
$obj->$argv[1]($argv);

/**
 * 需要刷新的机构列表数组
 */
function getAgencyList() {
    return [
        1 => array ('id' => 1,'agencyName' => '北京大众联合投资管理有限公司','typeName' => '咨询机构','typeId' => '2','relatedUserId' => '8160427','agencyConfirmUserName' => 'm89801814099','agentUserId' => '4104811',),
        2 => array ('id' => 2,'agencyName' => '北京大众联合投资管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160437','agencyConfirmUserName' => 'yanyuguo','agentUserId' => '11779',),
        3 => array ('id' => 3,'agencyName' => '北京鼎闻信息咨询有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160446','agencyConfirmUserName' => 'm70799844479','agentUserId' => '4090770',),
        4 => array ('id' => 4,'agencyName' => '北京东方佳禾投资管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160459','agencyConfirmUserName' => 'candyfox_p2p','agentUserId' => '509980',),
        5 => array ('id' => 5,'agencyName' => '北京东方联合科技有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160475','agencyConfirmUserName' => 'dzsfd','agentUserId' => '6995',),
        6 => array ('id' => 6,'agencyName' => '北京东方时代投资管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160494','agencyConfirmUserName' => 'wangjiefan','agentUserId' => '21352',),
        7 => array ('id' => 7,'agencyName' => '北京泓实资产管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160505','agencyConfirmUserName' => 'evajack','agentUserId' => '8892',),
        8 => array ('id' => 8,'agencyName' => '北京汇源先锋资本控股有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160547','agencyConfirmUserName' => 'creeping','agentUserId' => '1514088',),
        9 => array ('id' => 9,'agencyName' => '北京京都天和投资咨询有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160561','agencyConfirmUserName' => '史丽美','agentUserId' => '1814',),
        10 => array ('id' => 10,'agencyName' => '北京开元融资租赁有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160567','agencyConfirmUserName' => 'm13523854101','agentUserId' => '3929730',),
        11 => array ('id' => 11,'agencyName' => '北京联合常春藤资产管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160590','agencyConfirmUserName' => 'kangherong','agentUserId' => '20182',),
        12 => array ('id' => 12,'agencyName' => '北京联合开元融资担保有限公司','typeName' => '咨询机构','typeId' => '2','relatedUserId' => '8160601','agencyConfirmUserName' => 'sunshinepan','agentUserId' => '12784',),
        13 => array ('id' => 13,'agencyName' => '北京联合开元融资担保有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160605','agencyConfirmUserName' => 'm22296891617','agentUserId' => '7589072',),
        14 => array ('id' => 14,'agencyName' => '北京盛辉鼎业投资管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160614','agencyConfirmUserName' => 'zongzongzong','agentUserId' => '5411',),
        15 => array ('id' => 15,'agencyName' => '北京网信奇点投资有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160627','agencyConfirmUserName' => 'liuhanli','agentUserId' => '37882',),
        16 => array ('id' => 16,'agencyName' => '北京网信奇点投资有限公司','typeName' => '代充值机构','typeId' => '8','relatedUserId' => '8160636','agencyConfirmUserName' => 'a15804253916','agentUserId' => '378',),
        17 => array ('id' => 17,'agencyName' => '北京网信众利信息科技有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160647','agencyConfirmUserName' => 'aibayifk','agentUserId' => '961403',),
        18 => array ('id' => 18,'agencyName' => '北京艺值财富投资管理咨询有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160656','agencyConfirmUserName' => 'm93992768283','agentUserId' => '6214892',),
        19 => array ('id' => 19,'agencyName' => '北京悦网金服信息科技有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160662','agencyConfirmUserName' => 'henhegen','agentUserId' => '1486049',),
        20 => array ('id' => 20,'agencyName' => '北京掌众金融信息服务有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160671','agencyConfirmUserName' => 'm43859856533','agentUserId' => '7391613',),
        21 => array ('id' => 21,'agencyName' => '北京掌众金融信息服务有限公司','typeName' => '代充值机构','typeId' => '8','relatedUserId' => '8160679','agencyConfirmUserName' => 'm31805298142','agentUserId' => '7428491',),
        22 => array ('id' => 22,'agencyName' => '晨新资产管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160689','agencyConfirmUserName' => 'wyb01','agentUserId' => '22259',),
        23 => array ('id' => 23,'agencyName' => '大连中联创投资有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160822','agencyConfirmUserName' => 'xingfuvip','agentUserId' => '1110271',),
        24 => array ('id' => 24,'agencyName' => '凤凰资产管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160836','agencyConfirmUserName' => '13581749335','agentUserId' => '553',),
        25 => array ('id' => 25,'agencyName' => '福建省百泓农业发展有限公司','typeName' => '担保机构','typeId' => '1','relatedUserId' => '8160842','agencyConfirmUserName' => 'm12466547717','agentUserId' => '7024237',),
        26 => array ('id' => 26,'agencyName' => '广东先锋物流投资咨询有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160857','agencyConfirmUserName' => 'Eling','agentUserId' => '77834',),
        27 => array ('id' => 27,'agencyName' => '广东先锋物流投资咨询有限公司','typeName' => '担保机构','typeId' => '1','relatedUserId' => '8160864','agencyConfirmUserName' => 'flyingcat2014','agentUserId' => '51145',),
        28 => array ('id' => 28,'agencyName' => '广州市中汽租赁有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160875','agencyConfirmUserName' => 'xiaolong00123','agentUserId' => '1413961',),
        29 => array ('id' => 29,'agencyName' => '江苏商户通资本控股有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160883','agencyConfirmUserName' => 'ssy999521','agentUserId' => '2556743',),
        30 => array ('id' => 30,'agencyName' => '峻岭物业顾问（上海）有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160898','agencyConfirmUserName' => 'm29490483407','agentUserId' => '4197535',),
        31 => array ('id' => 31,'agencyName' => '峻岭物业顾问（上海）有限公司','typeName' => '咨询机构','typeId' => '2','relatedUserId' => '8160903','agencyConfirmUserName' => 'Severuslty','agentUserId' => '61784',),
        32 => array ('id' => 32,'agencyName' => '联合创业担保集团有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160912','agencyConfirmUserName' => 'duck316','agentUserId' => '7989',),
        33 => array ('id' => 33,'agencyName' => '联合创业担保集团有限公司','typeName' => '咨询机构','typeId' => '2','relatedUserId' => '8160917','agencyConfirmUserName' => 'duck316','agentUserId' => '7989',),
        34 => array ('id' => 34,'agencyName' => '内蒙古开元金控控股有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160925','agencyConfirmUserName' => 'linling6','agentUserId' => '281',),
        35 => array ('id' => 35,'agencyName' => '山东联合融资担保有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160958','agencyConfirmUserName' => 'musicbox','agentUserId' => '13765',),
        36 => array ('id' => 36,'agencyName' => '陕西荣投信息科技有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160968','agencyConfirmUserName' => 'shuo080723','agentUserId' => '681638',),
        37 => array ('id' => 37,'agencyName' => '上海峻屹商务咨询有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160975','agencyConfirmUserName' => 'sheng910218','agentUserId' => '2573362',),
        38 => array ('id' => 38,'agencyName' => '上海峻屹商务咨询有限公司','typeName' => '担保机构','typeId' => '1','relatedUserId' => '8160977','agencyConfirmUserName' => 'ucf2012','agentUserId' => '105',),
        39 => array ('id' => 39,'agencyName' => '上海外滩网信互联网金融信息服务有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160983','agencyConfirmUserName' => 'fangjinyan','agentUserId' => '63',),
        40 => array ('id' => 40,'agencyName' => '上海外滩网信互联网金融信息服务有限公司','typeName' => '担保机构','typeId' => '1','relatedUserId' => '8160987','agencyConfirmUserName' => 'waitanwangxin001','agentUserId' => '18490',),
        41 => array ('id' => 41,'agencyName' => '深圳百禾商业保理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8160994','agencyConfirmUserName' => 'yuezhenhua','agentUserId' => '5124',),
        42 => array ('id' => 42,'agencyName' => '深圳东方网信互联网金融信息服务有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161000','agencyConfirmUserName' => 'm52997904308','agentUserId' => '3751086',),
        43 => array ('id' => 43,'agencyName' => '深圳联合货币财富管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161014','agencyConfirmUserName' => 'zhuyuting','agentUserId' => '3915',),
        44 => array ('id' => 44,'agencyName' => '深圳市网爱金融服务有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161021','agencyConfirmUserName' => 'm15001029330','agentUserId' => '2555959',),
        45 => array ('id' => 45,'agencyName' => '深圳先锋产业金融发展有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161028','agencyConfirmUserName' => 'wangyongna','agentUserId' => '4663',),
        46 => array ('id' => 46,'agencyName' => '深圳先锋产业金融发展有限公司北京分公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161033','agencyConfirmUserName' => '13910605329','agentUserId' => '20012',),
        47 => array ('id' => 47,'agencyName' => '深圳一房立信金融服务有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161043','agencyConfirmUserName' => 'm40593902780','agentUserId' => '3377194',),
        48 => array ('id' => 48,'agencyName' => '深圳壹房壹贷信息技术服务有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161047','agencyConfirmUserName' => 'kagura','agentUserId' => '736107',),
        49 => array ('id' => 49,'agencyName' => '首山金融信息服务（上海）有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161056','agencyConfirmUserName' => 'm78549111732','agentUserId' => '6292522',),
        50 => array ('id' => 50,'agencyName' => '首山资产管理（上海）有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161064','agencyConfirmUserName' => 'm10907918531','agentUserId' => '6471910',),
        51 => array ('id' => 51,'agencyName' => '首山资产管理（上海）有限公司','typeName' => '代充值机构','typeId' => '8','relatedUserId' => '8161066','agencyConfirmUserName' => 'm49740631440','agentUserId' => '6357875',),
        52 => array ('id' => 52,'agencyName' => '台州市银鑫担保有限公司','typeName' => '担保机构','typeId' => '1','relatedUserId' => '8161077','agencyConfirmUserName' => 'cnbl','agentUserId' => '35574',),
        53 => array ('id' => 53,'agencyName' => '天津联合创业投资担保有限公司','typeName' => '咨询机构','typeId' => '2','relatedUserId' => '8161082','agencyConfirmUserName' => '13512262505','agentUserId' => '3895',),
        54 => array ('id' => 54,'agencyName' => '天津联合创业投资担保有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161085','agencyConfirmUserName' => '13512262505','agentUserId' => '3895',),
        55 => array ('id' => 55,'agencyName' => '天津先锋资产管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161094','agencyConfirmUserName' => '712nana','agentUserId' => '15966',),
        56 => array ('id' => 56,'agencyName' => '天津先锋资产管理有限公司','typeName' => '受托机构','typeId' => '7','relatedUserId' => '8161098','agencyConfirmUserName' => '13821269866','agentUserId' => '870',),
        57 => array ('id' => 57,'agencyName' => '网信传媒有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161103','agencyConfirmUserName' => '13581535735','agentUserId' => '17317',),
        58 => array ('id' => 58,'agencyName' => '网信新影人（北京）投资管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161109','agencyConfirmUserName' => 'metalwork','agentUserId' => '5724',),
        59 => array ('id' => 59,'agencyName' => '网信友车（天津）资产管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161115','agencyConfirmUserName' => 'sam621','agentUserId' => '2358219',),
        60 => array ('id' => 60,'agencyName' => '浙江甬贷投资咨询有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8161134','agencyConfirmUserName' => 'tanglu','agentUserId' => '410608',),
        61 => array ('id' => 61,'agencyName' => '上海昂励投资管理有限公司','typeName' => '代垫机构','typeId' => '6','relatedUserId' => '8169744','agencyConfirmUserName' => 'm52641651378','agentUserId' => '7996783',),
        62 => array ('id' => 62,'agencyName' => '上海昂励投资管理有限公司','typeName' => '代充值机构','typeId' => '8','relatedUserId' => '8169752','agencyConfirmUserName' => 'm52641651378','agentUserId' => '7996783',),
    ];
}

/**
 * 需要刷新的用户账户类型的列表数组
 */
function getUserPurposeList() {
    return [
        200393 => array ('id' => 1,'userId' => 200393,'purposeName' => '红包户','purposeId' => '15',),
        5865743 => array ('id' => 2,'userId' => 5865743,'purposeName' => '投资户','purposeId' => '1',),
        6903619 => array ('id' => 3,'userId' => 6903619,'purposeName' => '融资户','purposeId' => '2',),
        710030 => array ('id' => 4,'userId' => 710030,'purposeName' => '渠道户','purposeId' => '5',),
        4394206 => array ('id' => 5,'userId' => 4394206,'purposeName' => '融资户','purposeId' => '2',),
        5383802 => array ('id' => 6,'userId' => 5383802,'purposeName' => '融资户','purposeId' => '2',),
        2272731 => array ('id' => 7,'userId' => 2272731,'purposeName' => '融资户','purposeId' => '2',),
        1514473 => array ('id' => 8,'userId' => 1514473,'purposeName' => '担保户','purposeId' => '4',),
        7087163 => array ('id' => 9,'userId' => 7087163,'purposeName' => '融资户','purposeId' => '2',),
        6199155 => array ('id' => 10,'userId' => 6199155,'purposeName' => '融资户','purposeId' => '2',),
        7411075 => array ('id' => 11,'userId' => 7411075,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        6951559 => array ('id' => 12,'userId' => 6951559,'purposeName' => '担保户','purposeId' => '4',),
        3722421 => array ('id' => 13,'userId' => 3722421,'purposeName' => '担保户','purposeId' => '4',),
        5545933 => array ('id' => 14,'userId' => 5545933,'purposeName' => '投资户','purposeId' => '1',),
        7057452 => array ('id' => 15,'userId' => 7057452,'purposeName' => '融资户','purposeId' => '2',),
        3762821 => array ('id' => 16,'userId' => 3762821,'purposeName' => '融资户','purposeId' => '2',),
        6779396 => array ('id' => 17,'userId' => 6779396,'purposeName' => '担保户','purposeId' => '4',),
        650505 => array ('id' => 18,'userId' => 650505,'purposeName' => '渠道户','purposeId' => '5',),
        5477 => array ('id' => 19,'userId' => 5477,'purposeName' => '担保户','purposeId' => '4',),
        8160427 => array ('id' => 20,'userId' => 8160427,'purposeName' => '咨询户','purposeId' => '3',),
        8160437 => array ('id' => 21,'userId' => 8160437,'purposeName' => '代垫户','purposeId' => '8',),
        1367869 => array ('id' => 22,'userId' => 1367869,'purposeName' => '咨询户','purposeId' => '3',),
        6806786 => array ('id' => 23,'userId' => 6806786,'purposeName' => '融资户','purposeId' => '2',),
        4547601 => array ('id' => 24,'userId' => 4547601,'purposeName' => '担保户','purposeId' => '4',),
        7378701 => array ('id' => 25,'userId' => 7378701,'purposeName' => '咨询户','purposeId' => '3',),
        7378734 => array ('id' => 26,'userId' => 7378734,'purposeName' => '咨询户','purposeId' => '3',),
        8160446 => array ('id' => 27,'userId' => 8160446,'purposeName' => '代垫户','purposeId' => '8',),
        3763625 => array ('id' => 28,'userId' => 3763625,'purposeName' => '渠道户','purposeId' => '5',),
        3763371 => array ('id' => 29,'userId' => 3763371,'purposeName' => '渠道户','purposeId' => '5',),
        1264162 => array ('id' => 30,'userId' => 1264162,'purposeName' => '咨询户','purposeId' => '3',),
        8160459 => array ('id' => 31,'userId' => 8160459,'purposeName' => '代垫户','purposeId' => '8',),
        170674 => array ('id' => 32,'userId' => 170674,'purposeName' => '咨询户','purposeId' => '3',),
        8160475 => array ('id' => 33,'userId' => 8160475,'purposeName' => '代垫户','purposeId' => '8',),
        8160482 => array ('id' => 34,'userId' => 8160482,'purposeName' => '投资券户','purposeId' => '14',),
        4074483 => array ('id' => 35,'userId' => 4074483,'purposeName' => '咨询户','purposeId' => '3',),
        4082847 => array ('id' => 36,'userId' => 4082847,'purposeName' => '渠道户','purposeId' => '5',),
        6049335 => array ('id' => 37,'userId' => 6049335,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        6172781 => array ('id' => 38,'userId' => 6172781,'purposeName' => '渠道户','purposeId' => '5',),
        6257793 => array ('id' => 39,'userId' => 6257793,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        6289794 => array ('id' => 40,'userId' => 6289794,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        6851240 => array ('id' => 41,'userId' => 6851240,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        4159 => array ('id' => 42,'userId' => 4159,'purposeName' => '平台户','purposeId' => '11',),
        2686721 => array ('id' => 43,'userId' => 2686721,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        3995400 => array ('id' => 44,'userId' => 3995400,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        3997357 => array ('id' => 45,'userId' => 3997357,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        3997376 => array ('id' => 46,'userId' => 3997376,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        4558798 => array ('id' => 47,'userId' => 4558798,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        10578 => array ('id' => 48,'userId' => 10578,'purposeName' => '咨询户','purposeId' => '3',),
        8160494 => array ('id' => 49,'userId' => 8160494,'purposeName' => '代垫户','purposeId' => '8',),
        7030832 => array ('id' => 50,'userId' => 7030832,'purposeName' => '担保户','purposeId' => '4',),
        948786 => array ('id' => 51,'userId' => 948786,'purposeName' => '渠道户','purposeId' => '5',),
        6649288 => array ('id' => 52,'userId' => 6649288,'purposeName' => '渠道户','purposeId' => '5',),
        3300957 => array ('id' => 53,'userId' => 3300957,'purposeName' => '融资户','purposeId' => '2',),
        7458963 => array ('id' => 54,'userId' => 7458963,'purposeName' => '融资户','purposeId' => '2',),
        2956618 => array ('id' => 55,'userId' => 2956618,'purposeName' => '担保户','purposeId' => '4',),
        7862491 => array ('id' => 56,'userId' => 7862491,'purposeName' => '融资户','purposeId' => '2',),
        7309728 => array ('id' => 57,'userId' => 7309728,'purposeName' => '担保户','purposeId' => '4',),
        12381 => array ('id' => 58,'userId' => 12381,'purposeName' => '咨询户','purposeId' => '3',),
        8160505 => array ('id' => 59,'userId' => 8160505,'purposeName' => '代垫户','purposeId' => '8',),
        1785289 => array ('id' => 60,'userId' => 1785289,'purposeName' => '渠道户','purposeId' => '5',),
        6649677 => array ('id' => 61,'userId' => 6649677,'purposeName' => '融资户','purposeId' => '2',),
        7400533 => array ('id' => 62,'userId' => 7400533,'purposeName' => '担保户','purposeId' => '4',),
        4263 => array ('id' => 63,'userId' => 4263,'purposeName' => '担保户','purposeId' => '4',),
        1131097 => array ('id' => 64,'userId' => 1131097,'purposeName' => '渠道户','purposeId' => '5',),
        8160534 => array ('id' => 65,'userId' => 8160534,'purposeName' => '红包户','purposeId' => '15',),
        8160539 => array ('id' => 66,'userId' => 8160539,'purposeName' => '投资券户','purposeId' => '14',),
        4169 => array ('id' => 67,'userId' => 4169,'purposeName' => '咨询户','purposeId' => '3',),
        8160547 => array ('id' => 68,'userId' => 8160547,'purposeName' => '代垫户','purposeId' => '8',),
        7971721 => array ('id' => 69,'userId' => 7971721,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7971744 => array ('id' => 70,'userId' => 7971744,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7437633 => array ('id' => 71,'userId' => 7437633,'purposeName' => '融资户','purposeId' => '2',),
        6441914 => array ('id' => 72,'userId' => 6441914,'purposeName' => '担保户','purposeId' => '4',),
        1456787 => array ('id' => 73,'userId' => 1456787,'purposeName' => '投资户','purposeId' => '1',),
        2134075 => array ('id' => 74,'userId' => 2134075,'purposeName' => '担保户','purposeId' => '4',),
        723865 => array ('id' => 75,'userId' => 723865,'purposeName' => '渠道户','purposeId' => '5',),
        11780 => array ('id' => 76,'userId' => 11780,'purposeName' => '咨询户','purposeId' => '3',),
        8160561 => array ('id' => 77,'userId' => 8160561,'purposeName' => '代垫户','purposeId' => '8',),
        6996875 => array ('id' => 78,'userId' => 6996875,'purposeName' => '渠道户','purposeId' => '5',),
        7307872 => array ('id' => 79,'userId' => 7307872,'purposeName' => '平台户','purposeId' => '11',),
        1385927 => array ('id' => 80,'userId' => 1385927,'purposeName' => '担保户','purposeId' => '4',),
        8160567 => array ('id' => 81,'userId' => 8160567,'purposeName' => '代垫户','purposeId' => '8',),
        6790042 => array ('id' => 82,'userId' => 6790042,'purposeName' => '担保户','purposeId' => '4',),
        56369 => array ('id' => 83,'userId' => 56369,'purposeName' => '咨询户','purposeId' => '3',),
        6248139 => array ('id' => 84,'userId' => 6248139,'purposeName' => '渠道户','purposeId' => '5',),
        8160577 => array ('id' => 85,'userId' => 8160577,'purposeName' => '红包户','purposeId' => '15',),
        8160585 => array ('id' => 86,'userId' => 8160585,'purposeName' => '投资券户','purposeId' => '14',),
        4878454 => array ('id' => 87,'userId' => 4878454,'purposeName' => '融资户','purposeId' => '2',),
        6826801 => array ('id' => 88,'userId' => 6826801,'purposeName' => '融资户','purposeId' => '2',),
        2295396 => array ('id' => 89,'userId' => 2295396,'purposeName' => '投资户','purposeId' => '1',),
        5574337 => array ('id' => 90,'userId' => 5574337,'purposeName' => '融资户','purposeId' => '2',),
        7411057 => array ('id' => 91,'userId' => 7411057,'purposeName' => '融资户','purposeId' => '2',),
        7409632 => array ('id' => 92,'userId' => 7409632,'purposeName' => '融资户','purposeId' => '2',),
        324199 => array ('id' => 93,'userId' => 324199,'purposeName' => '咨询户','purposeId' => '3',),
        8160590 => array ('id' => 94,'userId' => 8160590,'purposeName' => '代垫户','purposeId' => '8',),
        152673 => array ('id' => 95,'userId' => 152673,'purposeName' => '渠道户','purposeId' => '5',),
        54748 => array ('id' => 96,'userId' => 54748,'purposeName' => '渠道户','purposeId' => '5',),
        4165 => array ('id' => 97,'userId' => 4165,'purposeName' => '担保户','purposeId' => '4',),
        8160601 => array ('id' => 98,'userId' => 8160601,'purposeName' => '咨询户','purposeId' => '3',),
        8160605 => array ('id' => 99,'userId' => 8160605,'purposeName' => '代垫户','purposeId' => '8',),
        157106 => array ('id' => 100,'userId' => 157106,'purposeName' => '渠道户','purposeId' => '5',),
        1202228 => array ('id' => 101,'userId' => 1202228,'purposeName' => '渠道户','purposeId' => '5',),
        6502780 => array ('id' => 102,'userId' => 6502780,'purposeName' => '咨询户','purposeId' => '3',),
        502993 => array ('id' => 103,'userId' => 502993,'purposeName' => '渠道户','purposeId' => '5',),
        6951685 => array ('id' => 104,'userId' => 6951685,'purposeName' => '融资户','purposeId' => '2',),
        3160357 => array ('id' => 105,'userId' => 3160357,'purposeName' => '融资户','purposeId' => '2',),
        4988451 => array ('id' => 106,'userId' => 4988451,'purposeName' => '渠道户','purposeId' => '5',),
        7719727 => array ('id' => 107,'userId' => 7719727,'purposeName' => '融资户','purposeId' => '2',),
        1370483 => array ('id' => 108,'userId' => 1370483,'purposeName' => '融资户','purposeId' => '2',),
        3536439 => array ('id' => 109,'userId' => 3536439,'purposeName' => '担保户','purposeId' => '4',),
        2298343 => array ('id' => 110,'userId' => 2298343,'purposeName' => '融资户','purposeId' => '2',),
        5066368 => array ('id' => 111,'userId' => 5066368,'purposeName' => '融资户','purposeId' => '2',),
        801068 => array ('id' => 112,'userId' => 801068,'purposeName' => '咨询户','purposeId' => '3',),
        8160614 => array ('id' => 113,'userId' => 8160614,'purposeName' => '代垫户','purposeId' => '8',),
        5208154 => array ('id' => 114,'userId' => 5208154,'purposeName' => '投资户','purposeId' => '1',),
        5766718 => array ('id' => 115,'userId' => 5766718,'purposeName' => '融资户','purposeId' => '2',),
        7044701 => array ('id' => 116,'userId' => 7044701,'purposeName' => '担保户','purposeId' => '4',),
        3895913 => array ('id' => 117,'userId' => 3895913,'purposeName' => '渠道户','purposeId' => '5',),
        7072101 => array ('id' => 118,'userId' => 7072101,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7072117 => array ('id' => 119,'userId' => 7072117,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7072123 => array ('id' => 120,'userId' => 7072123,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7462493 => array ('id' => 121,'userId' => 7462493,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7462507 => array ('id' => 122,'userId' => 7462507,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7462517 => array ('id' => 123,'userId' => 7462517,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7462525 => array ('id' => 124,'userId' => 7462525,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7462537 => array ('id' => 125,'userId' => 7462537,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732923 => array ('id' => 126,'userId' => 7732923,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732926 => array ('id' => 127,'userId' => 7732926,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732929 => array ('id' => 128,'userId' => 7732929,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732931 => array ('id' => 129,'userId' => 7732931,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732940 => array ('id' => 130,'userId' => 7732940,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732947 => array ('id' => 131,'userId' => 7732947,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732949 => array ('id' => 132,'userId' => 7732949,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732952 => array ('id' => 133,'userId' => 7732952,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732955 => array ('id' => 134,'userId' => 7732955,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        7732956 => array ('id' => 135,'userId' => 7732956,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        8160625 => array ('id' => 136,'userId' => 8160625,'purposeName' => '投资券户','purposeId' => '14',),
        7949561 => array ('id' => 137,'userId' => 7949561,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        8135967 => array ('id' => 138,'userId' => 8135967,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        1494632 => array ('id' => 139,'userId' => 1494632,'purposeName' => '渠道户','purposeId' => '5',),
        7189806 => array ('id' => 140,'userId' => 7189806,'purposeName' => '渠道户','purposeId' => '5',),
        7295772 => array ('id' => 141,'userId' => 7295772,'purposeName' => '咨询户','purposeId' => '3',),
        7307870 => array ('id' => 142,'userId' => 7307870,'purposeName' => '平台户','purposeId' => '11',),
        7352028 => array ('id' => 143,'userId' => 7352028,'purposeName' => '渠道户','purposeId' => '5',),
        6523830 => array ('id' => 144,'userId' => 6523830,'purposeName' => '担保户','purposeId' => '4',),
        6449946 => array ('id' => 145,'userId' => 6449946,'purposeName' => '咨询户','purposeId' => '3',),
        8160627 => array ('id' => 146,'userId' => 8160627,'purposeName' => '代垫户','purposeId' => '8',),
        8160636 => array ('id' => 147,'userId' => 8160636,'purposeName' => '代充值户','purposeId' => '16',),
        8160639 => array ('id' => 148,'userId' => 8160639,'purposeName' => '投资券户','purposeId' => '14',),
        4681304 => array ('id' => 149,'userId' => 4681304,'purposeName' => '渠道户','purposeId' => '5',),
        4071940 => array ('id' => 150,'userId' => 4071940,'purposeName' => '渠道户','purposeId' => '5',),
        6442574 => array ('id' => 151,'userId' => 6442574,'purposeName' => '咨询户','purposeId' => '3',),
        6822590 => array ('id' => 152,'userId' => 6822590,'purposeName' => '渠道户','purposeId' => '5',),
        6823060 => array ('id' => 153,'userId' => 6823060,'purposeName' => '渠道户','purposeId' => '5',),
        6823306 => array ('id' => 154,'userId' => 6823306,'purposeName' => '渠道户','purposeId' => '5',),
        6512444 => array ('id' => 155,'userId' => 6512444,'purposeName' => '咨询户','purposeId' => '3',),
        1690547 => array ('id' => 156,'userId' => 1690547,'purposeName' => '渠道户','purposeId' => '5',),
        3571276 => array ('id' => 157,'userId' => 3571276,'purposeName' => '咨询户','purposeId' => '3',),
        3631005 => array ('id' => 158,'userId' => 3631005,'purposeName' => '担保户','purposeId' => '4',),
        1491781 => array ('id' => 159,'userId' => 1491781,'purposeName' => '渠道户','purposeId' => '5',),
        4419978 => array ('id' => 160,'userId' => 4419978,'purposeName' => '担保户','purposeId' => '4',),
        4416490 => array ('id' => 161,'userId' => 4416490,'purposeName' => '咨询户','purposeId' => '3',),
        8160647 => array ('id' => 162,'userId' => 8160647,'purposeName' => '代垫户','purposeId' => '8',),
        7057329 => array ('id' => 163,'userId' => 7057329,'purposeName' => '渠道户','purposeId' => '5',),
        4300052 => array ('id' => 164,'userId' => 4300052,'purposeName' => '担保户','purposeId' => '4',),
        948360 => array ('id' => 165,'userId' => 948360,'purposeName' => '渠道户','purposeId' => '5',),
        7862394 => array ('id' => 166,'userId' => 7862394,'purposeName' => '担保户','purposeId' => '4',),
        3322043 => array ('id' => 167,'userId' => 3322043,'purposeName' => '融资户','purposeId' => '2',),
        2532164 => array ('id' => 168,'userId' => 2532164,'purposeName' => '渠道户','purposeId' => '5',),
        1248400 => array ('id' => 169,'userId' => 1248400,'purposeName' => '渠道户','purposeId' => '5',),
        4168291 => array ('id' => 170,'userId' => 4168291,'purposeName' => '融资户','purposeId' => '2',),
        5654642 => array ('id' => 171,'userId' => 5654642,'purposeName' => '投资户','purposeId' => '1',),
        7050237 => array ('id' => 172,'userId' => 7050237,'purposeName' => '融资户','purposeId' => '2',),
        7312103 => array ('id' => 173,'userId' => 7312103,'purposeName' => '融资户','purposeId' => '2',),
        4618060 => array ('id' => 174,'userId' => 4618060,'purposeName' => '渠道户','purposeId' => '5',),
        7034689 => array ('id' => 175,'userId' => 7034689,'purposeName' => '咨询户','purposeId' => '3',),
        8160656 => array ('id' => 176,'userId' => 8160656,'purposeName' => '代垫户','purposeId' => '8',),
        3148986 => array ('id' => 177,'userId' => 3148986,'purposeName' => '渠道户','purposeId' => '5',),
        5360727 => array ('id' => 178,'userId' => 5360727,'purposeName' => '渠道户','purposeId' => '5',),
        2425740 => array ('id' => 179,'userId' => 2425740,'purposeName' => '担保户','purposeId' => '4',),
        4904579 => array ('id' => 180,'userId' => 4904579,'purposeName' => '咨询户','purposeId' => '3',),
        5352251 => array ('id' => 181,'userId' => 5352251,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5352375 => array ('id' => 182,'userId' => 5352375,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5352460 => array ('id' => 183,'userId' => 5352460,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5352563 => array ('id' => 184,'userId' => 5352563,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5352639 => array ('id' => 185,'userId' => 5352639,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5353997 => array ('id' => 186,'userId' => 5353997,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5354079 => array ('id' => 187,'userId' => 5354079,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5354145 => array ('id' => 188,'userId' => 5354145,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5354238 => array ('id' => 189,'userId' => 5354238,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5354318 => array ('id' => 190,'userId' => 5354318,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5354377 => array ('id' => 191,'userId' => 5354377,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5354443 => array ('id' => 192,'userId' => 5354443,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5354496 => array ('id' => 193,'userId' => 5354496,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779640 => array ('id' => 194,'userId' => 5779640,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779689 => array ('id' => 195,'userId' => 5779689,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779728 => array ('id' => 196,'userId' => 5779728,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779843 => array ('id' => 197,'userId' => 5779843,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779871 => array ('id' => 198,'userId' => 5779871,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779898 => array ('id' => 199,'userId' => 5779898,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779935 => array ('id' => 200,'userId' => 5779935,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779969 => array ('id' => 201,'userId' => 5779969,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5779995 => array ('id' => 202,'userId' => 5779995,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780071 => array ('id' => 203,'userId' => 5780071,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780105 => array ('id' => 204,'userId' => 5780105,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780158 => array ('id' => 205,'userId' => 5780158,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780217 => array ('id' => 206,'userId' => 5780217,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780274 => array ('id' => 207,'userId' => 5780274,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780338 => array ('id' => 208,'userId' => 5780338,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780428 => array ('id' => 209,'userId' => 5780428,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780593 => array ('id' => 210,'userId' => 5780593,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780695 => array ('id' => 211,'userId' => 5780695,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780813 => array ('id' => 212,'userId' => 5780813,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780897 => array ('id' => 213,'userId' => 5780897,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5780949 => array ('id' => 214,'userId' => 5780949,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5781105 => array ('id' => 215,'userId' => 5781105,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5781156 => array ('id' => 216,'userId' => 5781156,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5781206 => array ('id' => 217,'userId' => 5781206,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5781444 => array ('id' => 218,'userId' => 5781444,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5781555 => array ('id' => 219,'userId' => 5781555,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5781673 => array ('id' => 220,'userId' => 5781673,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5782021 => array ('id' => 221,'userId' => 5782021,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5782092 => array ('id' => 222,'userId' => 5782092,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5782209 => array ('id' => 223,'userId' => 5782209,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5782282 => array ('id' => 224,'userId' => 5782282,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        5782344 => array ('id' => 225,'userId' => 5782344,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        4170 => array ('id' => 226,'userId' => 4170,'purposeName' => '渠道户','purposeId' => '5',),
        5968792 => array ('id' => 227,'userId' => 5968792,'purposeName' => '融资户','purposeId' => '2',),
        2048358 => array ('id' => 228,'userId' => 2048358,'purposeName' => '投资户','purposeId' => '1',),
        3952661 => array ('id' => 229,'userId' => 3952661,'purposeName' => '渠道户','purposeId' => '5',),
        1942975 => array ('id' => 230,'userId' => 1942975,'purposeName' => '融资户','purposeId' => '2',),
        6326717 => array ('id' => 231,'userId' => 6326717,'purposeName' => '融资户','purposeId' => '2',),
        5301941 => array ('id' => 232,'userId' => 5301941,'purposeName' => '渠道户','purposeId' => '5',),
        6598234 => array ('id' => 233,'userId' => 6598234,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        6598614 => array ('id' => 234,'userId' => 6598614,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        6598643 => array ('id' => 235,'userId' => 6598643,'purposeName' => '渠道虚拟户','purposeId' => '6',),
        6449778 => array ('id' => 236,'userId' => 6449778,'purposeName' => '咨询户','purposeId' => '3',),
        8160662 => array ('id' => 237,'userId' => 8160662,'purposeName' => '代垫户','purposeId' => '8',),
        7428521 => array ('id' => 238,'userId' => 7428521,'purposeName' => '保证金户','purposeId' => '12',),
        7409219 => array ('id' => 239,'userId' => 7409219,'purposeName' => '咨询户','purposeId' => '3',),
        8160671 => array ('id' => 240,'userId' => 8160671,'purposeName' => '代垫户','purposeId' => '8',),
        8160679 => array ('id' => 241,'userId' => 8160679,'purposeName' => '代充值户','purposeId' => '16',),
        4214480 => array ('id' => 242,'userId' => 4214480,'purposeName' => '融资户','purposeId' => '2',),
        5879980 => array ('id' => 243,'userId' => 5879980,'purposeName' => '融资户','purposeId' => '2',),
        1705730 => array ('id' => 244,'userId' => 1705730,'purposeName' => '渠道户','purposeId' => '5',),
        4597308 => array ('id' => 245,'userId' => 4597308,'purposeName' => '融资户','purposeId' => '2',),
        3566485 => array ('id' => 246,'userId' => 3566485,'purposeName' => '渠道户','purposeId' => '5',),
        7863982 => array ('id' => 247,'userId' => 7863982,'purposeName' => '代垫户','purposeId' => '8',),
        7965451 => array ('id' => 248,'userId' => 7965451,'purposeName' => '担保户','purposeId' => '4',),
        2208552 => array ('id' => 249,'userId' => 2208552,'purposeName' => '渠道户','purposeId' => '5',),
        7374442 => array ('id' => 250,'userId' => 7374442,'purposeName' => '融资户','purposeId' => '2',),
        6530247 => array ('id' => 251,'userId' => 6530247,'purposeName' => '担保户','purposeId' => '4',),
        2684594 => array ('id' => 252,'userId' => 2684594,'purposeName' => '融资户','purposeId' => '2',),
        4082099 => array ('id' => 253,'userId' => 4082099,'purposeName' => '投资户','purposeId' => '1',),
        4524 => array ('id' => 254,'userId' => 4524,'purposeName' => '担保户','purposeId' => '4',),
        8160689 => array ('id' => 255,'userId' => 8160689,'purposeName' => '代垫户','purposeId' => '8',),
        7215660 => array ('id' => 256,'userId' => 7215660,'purposeName' => '融资户','purposeId' => '2',),
        5310323 => array ('id' => 257,'userId' => 5310323,'purposeName' => '融资户','purposeId' => '2',),
        5826486 => array ('id' => 258,'userId' => 5826486,'purposeName' => '担保户','purposeId' => '4',),
        7215675 => array ('id' => 259,'userId' => 7215675,'purposeName' => '担保户','purposeId' => '4',),
        4706004 => array ('id' => 260,'userId' => 4706004,'purposeName' => '渠道户','purposeId' => '5',),
        8160944 => array ('id' => 261,'userId' => 8160944,'purposeName' => '红包户','purposeId' => '15',),
        8160946 => array ('id' => 262,'userId' => 8160946,'purposeName' => '投资券户','purposeId' => '14',),
        7500264 => array ('id' => 263,'userId' => 7500264,'purposeName' => '融资户','purposeId' => '2',),
        2380166 => array ('id' => 264,'userId' => 2380166,'purposeName' => '担保户','purposeId' => '4',),
        7299914 => array ('id' => 265,'userId' => 7299914,'purposeName' => '融资户','purposeId' => '2',),
        7087193 => array ('id' => 266,'userId' => 7087193,'purposeName' => '担保户','purposeId' => '4',),
        4436543 => array ('id' => 267,'userId' => 4436543,'purposeName' => '担保户','purposeId' => '4',),
        6982618 => array ('id' => 268,'userId' => 6982618,'purposeName' => '融资户','purposeId' => '2',),
        3950968 => array ('id' => 269,'userId' => 3950968,'purposeName' => '融资户','purposeId' => '2',),
        7464685 => array ('id' => 270,'userId' => 7464685,'purposeName' => '融资户','purposeId' => '2',),
        7338764 => array ('id' => 271,'userId' => 7338764,'purposeName' => '融资户','purposeId' => '2',),
        7653768 => array ('id' => 272,'userId' => 7653768,'purposeName' => '融资户','purposeId' => '2',),
        7592391 => array ('id' => 273,'userId' => 7592391,'purposeName' => '融资户','purposeId' => '2',),
        2346303 => array ('id' => 274,'userId' => 2346303,'purposeName' => '融资户','purposeId' => '2',),
        6202481 => array ('id' => 275,'userId' => 6202481,'purposeName' => '融资户','purposeId' => '2',),
        7217969 => array ('id' => 276,'userId' => 7217969,'purposeName' => '融资户','purposeId' => '2',),
        6970480 => array ('id' => 277,'userId' => 6970480,'purposeName' => '融资户','purposeId' => '2',),
        7309708 => array ('id' => 278,'userId' => 7309708,'purposeName' => '融资户','purposeId' => '2',),
        6144962 => array ('id' => 279,'userId' => 6144962,'purposeName' => '融资户','purposeId' => '2',),
        6405160 => array ('id' => 280,'userId' => 6405160,'purposeName' => '融资户','purposeId' => '2',),
        7069153 => array ('id' => 281,'userId' => 7069153,'purposeName' => '融资户','purposeId' => '2',),
        3862691 => array ('id' => 282,'userId' => 3862691,'purposeName' => '融资户','purposeId' => '2',),
        7398296 => array ('id' => 283,'userId' => 7398296,'purposeName' => '融资户','purposeId' => '2',),
        7127447 => array ('id' => 284,'userId' => 7127447,'purposeName' => '融资户','purposeId' => '2',),
        7673659 => array ('id' => 285,'userId' => 7673659,'purposeName' => '融资户','purposeId' => '2',),
        6996803 => array ('id' => 286,'userId' => 6996803,'purposeName' => '融资户','purposeId' => '2',),
        8004566 => array ('id' => 287,'userId' => 8004566,'purposeName' => '融资户','purposeId' => '2',),
        7312080 => array ('id' => 288,'userId' => 7312080,'purposeName' => '融资户','purposeId' => '2',),
        7008924 => array ('id' => 289,'userId' => 7008924,'purposeName' => '融资户','purposeId' => '2',),
        6327281 => array ('id' => 290,'userId' => 6327281,'purposeName' => '融资户','purposeId' => '2',),
        422841 => array ('id' => 291,'userId' => 422841,'purposeName' => '担保户','purposeId' => '4',),
        4987134 => array ('id' => 292,'userId' => 4987134,'purposeName' => '融资户','purposeId' => '2',),
        7524768 => array ('id' => 293,'userId' => 7524768,'purposeName' => '融资户','purposeId' => '2',),
        7103176 => array ('id' => 294,'userId' => 7103176,'purposeName' => '融资户','purposeId' => '2',),
        7044710 => array ('id' => 295,'userId' => 7044710,'purposeName' => '咨询户','purposeId' => '3',),
        7273830 => array ('id' => 296,'userId' => 7273830,'purposeName' => '融资户','purposeId' => '2',),
        7409559 => array ('id' => 297,'userId' => 7409559,'purposeName' => '融资户','purposeId' => '2',),
        6913251 => array ('id' => 298,'userId' => 6913251,'purposeName' => '融资户','purposeId' => '2',),
        6932703 => array ('id' => 299,'userId' => 6932703,'purposeName' => '融资户','purposeId' => '2',),
        3105363 => array ('id' => 300,'userId' => 3105363,'purposeName' => '融资户','purposeId' => '2',),
        6961826 => array ('id' => 301,'userId' => 6961826,'purposeName' => '融资户','purposeId' => '2',),
        3105191 => array ('id' => 302,'userId' => 3105191,'purposeName' => '融资户','purposeId' => '2',),
        6132017 => array ('id' => 303,'userId' => 6132017,'purposeName' => '融资户','purposeId' => '2',),
        8067634 => array ('id' => 304,'userId' => 8067634,'purposeName' => '融资户','purposeId' => '2',),
        7294074 => array ('id' => 305,'userId' => 7294074,'purposeName' => '融资户','purposeId' => '2',),
        6215886 => array ('id' => 306,'userId' => 6215886,'purposeName' => '融资户','purposeId' => '2',),
        7758233 => array ('id' => 307,'userId' => 7758233,'purposeName' => '融资户','purposeId' => '2',),
        3097693 => array ('id' => 308,'userId' => 3097693,'purposeName' => '融资户','purposeId' => '2',),
        7127464 => array ('id' => 309,'userId' => 7127464,'purposeName' => '融资户','purposeId' => '2',),
        6471183 => array ('id' => 310,'userId' => 6471183,'purposeName' => '融资户','purposeId' => '2',),
        777235 => array ('id' => 311,'userId' => 777235,'purposeName' => '渠道户','purposeId' => '5',),
        6964637 => array ('id' => 312,'userId' => 6964637,'purposeName' => '担保户','purposeId' => '4',),
        7113158 => array ('id' => 313,'userId' => 7113158,'purposeName' => '融资户','purposeId' => '2',),
        6996787 => array ('id' => 314,'userId' => 6996787,'purposeName' => '融资户','purposeId' => '2',),
        7874167 => array ('id' => 315,'userId' => 7874167,'purposeName' => '咨询户','purposeId' => '3',),
        8160822 => array ('id' => 316,'userId' => 8160822,'purposeName' => '代垫户','purposeId' => '8',),
        6290671 => array ('id' => 317,'userId' => 6290671,'purposeName' => '融资户','purposeId' => '2',),
        3686206 => array ('id' => 318,'userId' => 3686206,'purposeName' => '投资户','purposeId' => '1',),
        6791213 => array ('id' => 319,'userId' => 6791213,'purposeName' => '担保户','purposeId' => '4',),
        3471851 => array ('id' => 320,'userId' => 3471851,'purposeName' => '融资户','purposeId' => '2',),
        4549107 => array ('id' => 321,'userId' => 4549107,'purposeName' => '融资户','purposeId' => '2',),
        6584705 => array ('id' => 322,'userId' => 6584705,'purposeName' => '渠道户','purposeId' => '5',),
        3400820 => array ('id' => 323,'userId' => 3400820,'purposeName' => '渠道户','purposeId' => '5',),
        34196 => array ('id' => 324,'userId' => 34196,'purposeName' => '红包户','purposeId' => '15',),
        34190 => array ('id' => 325,'userId' => 34190,'purposeName' => '咨询户','purposeId' => '3',),
        34194 => array ('id' => 326,'userId' => 34194,'purposeName' => '支付户','purposeId' => '13',),
        34195 => array ('id' => 327,'userId' => 34195,'purposeName' => '红包户','purposeId' => '15',),
        136042 => array ('id' => 328,'userId' => 136042,'purposeName' => '红包户','purposeId' => '15',),
        6281152 => array ('id' => 329,'userId' => 6281152,'purposeName' => '融资户','purposeId' => '2',),
        7002211 => array ('id' => 330,'userId' => 7002211,'purposeName' => '担保户','purposeId' => '4',),
        7379536 => array ('id' => 331,'userId' => 7379536,'purposeName' => '融资户','purposeId' => '2',),
        4320181 => array ('id' => 332,'userId' => 4320181,'purposeName' => '投资户','purposeId' => '1',),
        4338751 => array ('id' => 333,'userId' => 4338751,'purposeName' => '融资户','purposeId' => '2',),
        7186992 => array ('id' => 334,'userId' => 7186992,'purposeName' => '融资户','purposeId' => '2',),
        4316012 => array ('id' => 335,'userId' => 4316012,'purposeName' => '投资户','purposeId' => '1',),
        7403961 => array ('id' => 336,'userId' => 7403961,'purposeName' => '担保户','purposeId' => '4',),
        422535 => array ('id' => 337,'userId' => 422535,'purposeName' => '咨询户','purposeId' => '3',),
        8160836 => array ('id' => 338,'userId' => 8160836,'purposeName' => '代垫户','purposeId' => '8',),
        6663256 => array ('id' => 339,'userId' => 6663256,'purposeName' => '担保户','purposeId' => '4',),
        7049885 => array ('id' => 340,'userId' => 7049885,'purposeName' => '融资户','purposeId' => '2',),
        8160842 => array ('id' => 341,'userId' => 8160842,'purposeName' => '担保户','purposeId' => '4',),
        7030815 => array ('id' => 342,'userId' => 7030815,'purposeName' => '担保户','purposeId' => '4',),
        4357249 => array ('id' => 343,'userId' => 4357249,'purposeName' => '投资户','purposeId' => '1',),
        3319812 => array ('id' => 344,'userId' => 3319812,'purposeName' => '融资户','purposeId' => '2',),
        5676915 => array ('id' => 345,'userId' => 5676915,'purposeName' => '投资户','purposeId' => '1',),
        5951975 => array ('id' => 346,'userId' => 5951975,'purposeName' => '渠道户','purposeId' => '5',),
        201116 => array ('id' => 347,'userId' => 201116,'purposeName' => '担保户','purposeId' => '4',),
        6965414 => array ('id' => 348,'userId' => 6965414,'purposeName' => '担保户','purposeId' => '4',),
        62287 => array ('id' => 349,'userId' => 62287,'purposeName' => '咨询户','purposeId' => '3',),
        8160857 => array ('id' => 350,'userId' => 8160857,'purposeName' => '代垫户','purposeId' => '8',),
        8160864 => array ('id' => 351,'userId' => 8160864,'purposeName' => '担保户','purposeId' => '4',),
        7069232 => array ('id' => 352,'userId' => 7069232,'purposeName' => '担保户','purposeId' => '4',),
        7440610 => array ('id' => 353,'userId' => 7440610,'purposeName' => '融资户','purposeId' => '2',),
        4791083 => array ('id' => 354,'userId' => 4791083,'purposeName' => '融资户','purposeId' => '2',),
        4791668 => array ('id' => 355,'userId' => 4791668,'purposeName' => '投资户','purposeId' => '1',),
        1514845 => array ('id' => 356,'userId' => 1514845,'purposeName' => '融资户','purposeId' => '2',),
        2338222 => array ('id' => 357,'userId' => 2338222,'purposeName' => '融资户','purposeId' => '2',),
        5723504 => array ('id' => 358,'userId' => 5723504,'purposeName' => '融资户','purposeId' => '2',),
        7351805 => array ('id' => 359,'userId' => 7351805,'purposeName' => '融资户','purposeId' => '2',),
        3383257 => array ('id' => 360,'userId' => 3383257,'purposeName' => '融资户','purposeId' => '2',),
        3835658 => array ('id' => 361,'userId' => 3835658,'purposeName' => '融资户','purposeId' => '2',),
        7355614 => array ('id' => 362,'userId' => 7355614,'purposeName' => '担保户','purposeId' => '4',),
        7961807 => array ('id' => 363,'userId' => 7961807,'purposeName' => '融资户','purposeId' => '2',),
        7258159 => array ('id' => 364,'userId' => 7258159,'purposeName' => '融资户','purposeId' => '2',),
        6951077 => array ('id' => 365,'userId' => 6951077,'purposeName' => '担保户','purposeId' => '4',),
        8160875 => array ('id' => 366,'userId' => 8160875,'purposeName' => '代垫户','purposeId' => '8',),
        7103129 => array ('id' => 367,'userId' => 7103129,'purposeName' => '融资户','purposeId' => '2',),
        3753743 => array ('id' => 368,'userId' => 3753743,'purposeName' => '担保户','purposeId' => '4',),
        5722477 => array ('id' => 369,'userId' => 5722477,'purposeName' => '担保户','purposeId' => '4',),
        4394906 => array ('id' => 370,'userId' => 4394906,'purposeName' => '投资户','purposeId' => '1',),
        4745794 => array ('id' => 371,'userId' => 4745794,'purposeName' => '融资户','purposeId' => '2',),
        8280106 => array ('id' => 372,'userId' => 8280106,'purposeName' => '融资户','purposeId' => '2',),
        7355356 => array ('id' => 373,'userId' => 7355356,'purposeName' => '融资户','purposeId' => '2',),
        7276161 => array ('id' => 374,'userId' => 7276161,'purposeName' => '融资户','purposeId' => '2',),
        4553466 => array ('id' => 375,'userId' => 4553466,'purposeName' => '渠道户','purposeId' => '5',),
        4617412 => array ('id' => 376,'userId' => 4617412,'purposeName' => '投资户','purposeId' => '1',),
        3301088 => array ('id' => 377,'userId' => 3301088,'purposeName' => '融资户','purposeId' => '2',),
        7031414 => array ('id' => 378,'userId' => 7031414,'purposeName' => '担保户','purposeId' => '4',),
        523494 => array ('id' => 379,'userId' => 523494,'purposeName' => '担保户','purposeId' => '4',),
        7002198 => array ('id' => 380,'userId' => 7002198,'purposeName' => '担保户','purposeId' => '4',),
        6321076 => array ('id' => 381,'userId' => 6321076,'purposeName' => '融资户','purposeId' => '2',),
        3114168 => array ('id' => 382,'userId' => 3114168,'purposeName' => '融资户','purposeId' => '2',),
        6136660 => array ('id' => 383,'userId' => 6136660,'purposeName' => '融资户','purposeId' => '2',),
        3114063 => array ('id' => 384,'userId' => 3114063,'purposeName' => '融资户','purposeId' => '2',),
        3072694 => array ('id' => 385,'userId' => 3072694,'purposeName' => '融资户','purposeId' => '2',),
        1753284 => array ('id' => 386,'userId' => 1753284,'purposeName' => '投资户','purposeId' => '1',),
        10586 => array ('id' => 387,'userId' => 10586,'purposeName' => '渠道户','purposeId' => '5',),
        6941842 => array ('id' => 388,'userId' => 6941842,'purposeName' => '融资户','purposeId' => '2',),
        7862572 => array ('id' => 389,'userId' => 7862572,'purposeName' => '融资户','purposeId' => '2',),
        6207391 => array ('id' => 390,'userId' => 6207391,'purposeName' => '融资户','purposeId' => '2',),
        6318204 => array ('id' => 391,'userId' => 6318204,'purposeName' => '融资户','purposeId' => '2',),
        3520292 => array ('id' => 392,'userId' => 3520292,'purposeName' => '融资户','purposeId' => '2',),
        7072506 => array ('id' => 393,'userId' => 7072506,'purposeName' => '融资户','purposeId' => '2',),
        3582491 => array ('id' => 394,'userId' => 3582491,'purposeName' => '渠道户','purposeId' => '5',),
        6134019 => array ('id' => 395,'userId' => 6134019,'purposeName' => '融资户','purposeId' => '2',),
        7758024 => array ('id' => 396,'userId' => 7758024,'purposeName' => '渠道户','purposeId' => '5',),
        3608531 => array ('id' => 397,'userId' => 3608531,'purposeName' => '渠道户','purposeId' => '5',),
        4612383 => array ('id' => 398,'userId' => 4612383,'purposeName' => '渠道户','purposeId' => '5',),
        2943475 => array ('id' => 399,'userId' => 2943475,'purposeName' => '投资户','purposeId' => '1',),
        7255754 => array ('id' => 400,'userId' => 7255754,'purposeName' => '融资户','purposeId' => '2',),
        4615950 => array ('id' => 401,'userId' => 4615950,'purposeName' => '投资户','purposeId' => '1',),
        7996019 => array ('id' => 402,'userId' => 7996019,'purposeName' => '资产收购户','purposeId' => '7',),
        6818048 => array ('id' => 403,'userId' => 6818048,'purposeName' => '融资户','purposeId' => '2',),
        12380 => array ('id' => 404,'userId' => 12380,'purposeName' => '担保户','purposeId' => '4',),
        4402551 => array ('id' => 405,'userId' => 4402551,'purposeName' => '融资户','purposeId' => '2',),
        4083145 => array ('id' => 406,'userId' => 4083145,'purposeName' => '投资户','purposeId' => '1',),
        7363183 => array ('id' => 407,'userId' => 7363183,'purposeName' => '融资户','purposeId' => '2',),
        4333935 => array ('id' => 408,'userId' => 4333935,'purposeName' => '融资户','purposeId' => '2',),
        7023790 => array ('id' => 409,'userId' => 7023790,'purposeName' => '担保户','purposeId' => '4',),
        4402409 => array ('id' => 410,'userId' => 4402409,'purposeName' => '融资户','purposeId' => '2',),
        7355415 => array ('id' => 411,'userId' => 7355415,'purposeName' => '融资户','purposeId' => '2',),
        6290681 => array ('id' => 412,'userId' => 6290681,'purposeName' => '融资户','purposeId' => '2',),
        4617971 => array ('id' => 413,'userId' => 4617971,'purposeName' => '投资户','purposeId' => '1',),
        2466359 => array ('id' => 414,'userId' => 2466359,'purposeName' => '融资户','purposeId' => '2',),
        4159954 => array ('id' => 415,'userId' => 4159954,'purposeName' => '投资户','purposeId' => '1',),
        7018288 => array ('id' => 416,'userId' => 7018288,'purposeName' => '担保户','purposeId' => '4',),
        3751416 => array ('id' => 417,'userId' => 3751416,'purposeName' => '投资户','purposeId' => '1',),
        6498766 => array ('id' => 418,'userId' => 6498766,'purposeName' => '融资户','purposeId' => '2',),
        3133319 => array ('id' => 419,'userId' => 3133319,'purposeName' => '融资户','purposeId' => '2',),
        5228408 => array ('id' => 420,'userId' => 5228408,'purposeName' => '担保户','purposeId' => '4',),
        1954376 => array ('id' => 421,'userId' => 1954376,'purposeName' => '渠道户','purposeId' => '5',),
        3214831 => array ('id' => 422,'userId' => 3214831,'purposeName' => '融资户','purposeId' => '2',),
        7127438 => array ('id' => 423,'userId' => 7127438,'purposeName' => '融资户','purposeId' => '2',),
        7135725 => array ('id' => 424,'userId' => 7135725,'purposeName' => '担保户','purposeId' => '4',),
        1435945 => array ('id' => 425,'userId' => 1435945,'purposeName' => '担保户','purposeId' => '4',),
        7018161 => array ('id' => 426,'userId' => 7018161,'purposeName' => '融资户','purposeId' => '2',),
        4810286 => array ('id' => 427,'userId' => 4810286,'purposeName' => '融资户','purposeId' => '2',),
        324579 => array ('id' => 428,'userId' => 324579,'purposeName' => '红包户','purposeId' => '15',),
        7299923 => array ('id' => 429,'userId' => 7299923,'purposeName' => '担保户','purposeId' => '4',),
        4301612 => array ('id' => 430,'userId' => 4301612,'purposeName' => '投资户','purposeId' => '1',),
        3249460 => array ('id' => 431,'userId' => 3249460,'purposeName' => '融资户','purposeId' => '2',),
        3608003 => array ('id' => 432,'userId' => 3608003,'purposeName' => '融资户','purposeId' => '2',),
        4087439 => array ('id' => 433,'userId' => 4087439,'purposeName' => '渠道户','purposeId' => '5',),
        4281109 => array ('id' => 434,'userId' => 4281109,'purposeName' => '融资户','purposeId' => '2',),
        1414910 => array ('id' => 435,'userId' => 1414910,'purposeName' => '咨询户','purposeId' => '3',),
        8160883 => array ('id' => 436,'userId' => 8160883,'purposeName' => '代垫户','purposeId' => '8',),
        8160894 => array ('id' => 437,'userId' => 8160894,'purposeName' => '投资券户','purposeId' => '14',),
        8093914 => array ('id' => 438,'userId' => 8093914,'purposeName' => '担保户','purposeId' => '4',),
        1785426 => array ('id' => 439,'userId' => 1785426,'purposeName' => '渠道户','purposeId' => '5',),
        7757974 => array ('id' => 440,'userId' => 7757974,'purposeName' => '担保户','purposeId' => '4',),
        1670315 => array ('id' => 441,'userId' => 1670315,'purposeName' => '渠道户','purposeId' => '5',),
        6652393 => array ('id' => 442,'userId' => 6652393,'purposeName' => '融资户','purposeId' => '2',),
        7757967 => array ('id' => 443,'userId' => 7757967,'purposeName' => '融资户','purposeId' => '2',),
        4051816 => array ('id' => 444,'userId' => 4051816,'purposeName' => '投资户','purposeId' => '1',),
        5333691 => array ('id' => 445,'userId' => 5333691,'purposeName' => '渠道户','purposeId' => '5',),
        5065510 => array ('id' => 446,'userId' => 5065510,'purposeName' => '担保户','purposeId' => '4',),
        6948783 => array ('id' => 447,'userId' => 6948783,'purposeName' => '融资户','purposeId' => '2',),
        7968047 => array ('id' => 448,'userId' => 7968047,'purposeName' => '融资户','purposeId' => '2',),
        728021 => array ('id' => 449,'userId' => 728021,'purposeName' => '担保户','purposeId' => '4',),
        7293592 => array ('id' => 450,'userId' => 7293592,'purposeName' => '融资户','purposeId' => '2',),
        7293602 => array ('id' => 451,'userId' => 7293602,'purposeName' => '融资户','purposeId' => '2',),
        1329768 => array ('id' => 452,'userId' => 1329768,'purposeName' => '担保户','purposeId' => '4',),
        8160898 => array ('id' => 453,'userId' => 8160898,'purposeName' => '代垫户','purposeId' => '8',),
        8160903 => array ('id' => 454,'userId' => 8160903,'purposeName' => '咨询户','purposeId' => '3',),
        3996648 => array ('id' => 455,'userId' => 3996648,'purposeName' => '融资户','purposeId' => '2',),
        1796806 => array ('id' => 456,'userId' => 1796806,'purposeName' => '红包户','purposeId' => '15',),
        79574 => array ('id' => 457,'userId' => 79574,'purposeName' => '担保户','purposeId' => '4',),
        7093642 => array ('id' => 458,'userId' => 7093642,'purposeName' => '融资户','purposeId' => '2',),
        6629333 => array ('id' => 459,'userId' => 6629333,'purposeName' => '融资户','purposeId' => '2',),
        7874462 => array ('id' => 460,'userId' => 7874462,'purposeName' => '融资户','purposeId' => '2',),
        6450311 => array ('id' => 461,'userId' => 6450311,'purposeName' => '融资户','purposeId' => '2',),
        4398903 => array ('id' => 462,'userId' => 4398903,'purposeName' => '担保户','purposeId' => '4',),
        4176094 => array ('id' => 463,'userId' => 4176094,'purposeName' => '担保户','purposeId' => '4',),
        4164 => array ('id' => 464,'userId' => 4164,'purposeName' => '担保户','purposeId' => '4',),
        4172 => array ('id' => 465,'userId' => 4172,'purposeName' => '担保户','purposeId' => '4',),
        155721 => array ('id' => 466,'userId' => 155721,'purposeName' => '担保户','purposeId' => '4',),
        8160912 => array ('id' => 467,'userId' => 8160912,'purposeName' => '代垫户','purposeId' => '8',),
        8160917 => array ('id' => 468,'userId' => 8160917,'purposeName' => '咨询户','purposeId' => '3',),
        3846817 => array ('id' => 469,'userId' => 3846817,'purposeName' => '咨询户','purposeId' => '3',),
        25243 => array ('id' => 470,'userId' => 25243,'purposeName' => '红包户','purposeId' => '15',),
        7496450 => array ('id' => 471,'userId' => 7496450,'purposeName' => '融资户','purposeId' => '2',),
        6244335 => array ('id' => 472,'userId' => 6244335,'purposeName' => '融资户','purposeId' => '2',),
        2663248 => array ('id' => 473,'userId' => 2663248,'purposeName' => '渠道户','purposeId' => '5',),
        7002182 => array ('id' => 474,'userId' => 7002182,'purposeName' => '担保户','purposeId' => '4',),
        777191 => array ('id' => 475,'userId' => 777191,'purposeName' => '渠道户','purposeId' => '5',),
        1482589 => array ('id' => 476,'userId' => 1482589,'purposeName' => '渠道户','purposeId' => '5',),
        3773034 => array ('id' => 477,'userId' => 3773034,'purposeName' => '融资户','purposeId' => '2',),
        7078137 => array ('id' => 478,'userId' => 7078137,'purposeName' => '融资户','purposeId' => '2',),
        6244573 => array ('id' => 479,'userId' => 6244573,'purposeName' => '融资户','purposeId' => '2',),
        6910642 => array ('id' => 480,'userId' => 6910642,'purposeName' => '融资户','purposeId' => '2',),
        5898168 => array ('id' => 481,'userId' => 5898168,'purposeName' => '投资户','purposeId' => '1',),
        64360 => array ('id' => 482,'userId' => 64360,'purposeName' => '渠道户','purposeId' => '5',),
        7496921 => array ('id' => 483,'userId' => 7496921,'purposeName' => '担保户','purposeId' => '4',),
        156138 => array ('id' => 484,'userId' => 156138,'purposeName' => '咨询户','purposeId' => '3',),
        8160925 => array ('id' => 485,'userId' => 8160925,'purposeName' => '代垫户','purposeId' => '8',),
        6403115 => array ('id' => 486,'userId' => 6403115,'purposeName' => '融资户','purposeId' => '2',),
        2451546 => array ('id' => 487,'userId' => 2451546,'purposeName' => '融资户','purposeId' => '2',),
        3557602 => array ('id' => 488,'userId' => 3557602,'purposeName' => '融资户','purposeId' => '2',),
        7056408 => array ('id' => 489,'userId' => 7056408,'purposeName' => '融资户','purposeId' => '2',),
        7905958 => array ('id' => 490,'userId' => 7905958,'purposeName' => '融资户','purposeId' => '2',),
        585594 => array ('id' => 491,'userId' => 585594,'purposeName' => '渠道户','purposeId' => '5',),
        6982493 => array ('id' => 492,'userId' => 6982493,'purposeName' => '融资户','purposeId' => '2',),
        3962323 => array ('id' => 493,'userId' => 3962323,'purposeName' => '融资户','purposeId' => '2',),
        6976876 => array ('id' => 494,'userId' => 6976876,'purposeName' => '担保户','purposeId' => '4',),
        6600172 => array ('id' => 495,'userId' => 6600172,'purposeName' => '担保户','purposeId' => '4',),
        6307234 => array ('id' => 496,'userId' => 6307234,'purposeName' => '融资户','purposeId' => '2',),
        6307623 => array ('id' => 497,'userId' => 6307623,'purposeName' => '担保户','purposeId' => '4',),
        6977070 => array ('id' => 498,'userId' => 6977070,'purposeName' => '融资户','purposeId' => '2',),
        2381627 => array ('id' => 499,'userId' => 2381627,'purposeName' => '融资户','purposeId' => '2',),
        6600154 => array ('id' => 500,'userId' => 6600154,'purposeName' => '融资户','purposeId' => '2',),
        2618874 => array ('id' => 501,'userId' => 2618874,'purposeName' => '投资户','purposeId' => '1',),
        2625642 => array ('id' => 502,'userId' => 2625642,'purposeName' => '担保户','purposeId' => '4',),
        3120605 => array ('id' => 503,'userId' => 3120605,'purposeName' => '融资户','purposeId' => '2',),
        6280665 => array ('id' => 504,'userId' => 6280665,'purposeName' => '融资户','purposeId' => '2',),
        2645842 => array ('id' => 505,'userId' => 2645842,'purposeName' => '投资户','purposeId' => '1',),
        3203959 => array ('id' => 506,'userId' => 3203959,'purposeName' => '融资户','purposeId' => '2',),
        3203473 => array ('id' => 507,'userId' => 3203473,'purposeName' => '担保户','purposeId' => '4',),
        6205718 => array ('id' => 508,'userId' => 6205718,'purposeName' => '融资户','purposeId' => '2',),
        6205817 => array ('id' => 509,'userId' => 6205817,'purposeName' => '担保户','purposeId' => '4',),
        6979008 => array ('id' => 510,'userId' => 6979008,'purposeName' => '融资户','purposeId' => '2',),
        6281020 => array ('id' => 511,'userId' => 6281020,'purposeName' => '融资户','purposeId' => '2',),
        1403632 => array ('id' => 512,'userId' => 1403632,'purposeName' => '投资户','purposeId' => '1',),
        5693049 => array ('id' => 513,'userId' => 5693049,'purposeName' => '担保户','purposeId' => '4',),
        7582902 => array ('id' => 514,'userId' => 7582902,'purposeName' => '融资户','purposeId' => '2',),
        1015665 => array ('id' => 515,'userId' => 1015665,'purposeName' => '渠道户','purposeId' => '5',),
        521976 => array ('id' => 516,'userId' => 521976,'purposeName' => '担保户','purposeId' => '4',),
        7331177 => array ('id' => 517,'userId' => 7331177,'purposeName' => '融资户','purposeId' => '2',),
        716197 => array ('id' => 518,'userId' => 716197,'purposeName' => '咨询户','purposeId' => '3',),
        71503 => array ('id' => 519,'userId' => 71503,'purposeName' => '担保户','purposeId' => '4',),
        2072570 => array ('id' => 520,'userId' => 2072570,'purposeName' => '渠道户','purposeId' => '5',),
        3072484 => array ('id' => 521,'userId' => 3072484,'purposeName' => '咨询户','purposeId' => '3',),
        8160958 => array ('id' => 522,'userId' => 8160958,'purposeName' => '代垫户','purposeId' => '8',),
        3724914 => array ('id' => 523,'userId' => 3724914,'purposeName' => '融资户','purposeId' => '2',),
        5887598 => array ('id' => 524,'userId' => 5887598,'purposeName' => '投资户','purposeId' => '1',),
        7947646 => array ('id' => 525,'userId' => 7947646,'purposeName' => '融资户','purposeId' => '2',),
        4032506 => array ('id' => 526,'userId' => 4032506,'purposeName' => '投资户','purposeId' => '1',),
        4106968 => array ('id' => 527,'userId' => 4106968,'purposeName' => '担保户','purposeId' => '4',),
        7957940 => array ('id' => 528,'userId' => 7957940,'purposeName' => '融资户','purposeId' => '2',),
        777261 => array ('id' => 529,'userId' => 777261,'purposeName' => '咨询户','purposeId' => '3',),
        8160968 => array ('id' => 530,'userId' => 8160968,'purposeName' => '代垫户','purposeId' => '8',),
        3440067 => array ('id' => 531,'userId' => 3440067,'purposeName' => '渠道户','purposeId' => '5',),
        3617951 => array ('id' => 532,'userId' => 3617951,'purposeName' => '渠道户','purposeId' => '5',),
        3569626 => array ('id' => 533,'userId' => 3569626,'purposeName' => '渠道户','purposeId' => '5',),
        5762604 => array ('id' => 534,'userId' => 5762604,'purposeName' => '融资户','purposeId' => '2',),
        7999845 => array ('id' => 535,'userId' => 7999845,'purposeName' => '咨询户','purposeId' => '3',),
        8169744 => array ('id' => 536,'userId' => 8169744,'purposeName' => '代垫户','purposeId' => '8',),
        8169752 => array ('id' => 537,'userId' => 8169752,'purposeName' => '代充值户','purposeId' => '16',),
        3973374 => array ('id' => 538,'userId' => 3973374,'purposeName' => '渠道户','purposeId' => '5',),
        3640063 => array ('id' => 539,'userId' => 3640063,'purposeName' => '投资户','purposeId' => '1',),
        2663327 => array ('id' => 540,'userId' => 2663327,'purposeName' => '咨询户','purposeId' => '3',),
        7057208 => array ('id' => 541,'userId' => 7057208,'purposeName' => '融资户','purposeId' => '2',),
        7087219 => array ('id' => 542,'userId' => 7087219,'purposeName' => '融资户','purposeId' => '2',),
        4810036 => array ('id' => 543,'userId' => 4810036,'purposeName' => '融资户','purposeId' => '2',),
        4809488 => array ('id' => 544,'userId' => 4809488,'purposeName' => '融资户','purposeId' => '2',),
        6024021 => array ('id' => 545,'userId' => 6024021,'purposeName' => '融资户','purposeId' => '2',),
        4166 => array ('id' => 546,'userId' => 4166,'purposeName' => '担保户','purposeId' => '4',),
        3711229 => array ('id' => 547,'userId' => 3711229,'purposeName' => '融资户','purposeId' => '2',),
        5771803 => array ('id' => 548,'userId' => 5771803,'purposeName' => '投资户','purposeId' => '1',),
        6293842 => array ('id' => 549,'userId' => 6293842,'purposeName' => '融资户','purposeId' => '2',),
        3057921 => array ('id' => 550,'userId' => 3057921,'purposeName' => '融资户','purposeId' => '2',),
        1698499 => array ('id' => 551,'userId' => 1698499,'purposeName' => '渠道户','purposeId' => '5',),
        7448103 => array ('id' => 552,'userId' => 7448103,'purposeName' => '融资户','purposeId' => '2',),
        7087182 => array ('id' => 553,'userId' => 7087182,'purposeName' => '融资户','purposeId' => '2',),
        3457511 => array ('id' => 554,'userId' => 3457511,'purposeName' => '渠道户','purposeId' => '5',),
        3544204 => array ('id' => 555,'userId' => 3544204,'purposeName' => '渠道户','purposeId' => '5',),
        3655529 => array ('id' => 556,'userId' => 3655529,'purposeName' => '渠道户','purposeId' => '5',),
        8093100 => array ('id' => 557,'userId' => 8093100,'purposeName' => '担保户','purposeId' => '4',),
        3763136 => array ('id' => 558,'userId' => 3763136,'purposeName' => '投资户','purposeId' => '1',),
        59795 => array ('id' => 559,'userId' => 59795,'purposeName' => '咨询户','purposeId' => '3',),
        8160975 => array ('id' => 560,'userId' => 8160975,'purposeName' => '代垫户','purposeId' => '8',),
        8160977 => array ('id' => 561,'userId' => 8160977,'purposeName' => '担保户','purposeId' => '4',),
        3204224 => array ('id' => 562,'userId' => 3204224,'purposeName' => '融资户','purposeId' => '2',),
        1373594 => array ('id' => 563,'userId' => 1373594,'purposeName' => '投资户','purposeId' => '1',),
        6692696 => array ('id' => 564,'userId' => 6692696,'purposeName' => '融资户','purposeId' => '2',),
        6513377 => array ('id' => 565,'userId' => 6513377,'purposeName' => '融资户','purposeId' => '2',),
        2315151 => array ('id' => 566,'userId' => 2315151,'purposeName' => '咨询户','purposeId' => '3',),
        7976848 => array ('id' => 567,'userId' => 7976848,'purposeName' => '融资户','purposeId' => '2',),
        7361215 => array ('id' => 568,'userId' => 7361215,'purposeName' => '融资户','purposeId' => '2',),
        4359562 => array ('id' => 569,'userId' => 4359562,'purposeName' => '担保户','purposeId' => '4',),
        3061081 => array ('id' => 570,'userId' => 3061081,'purposeName' => '融资户','purposeId' => '2',),
        1527190 => array ('id' => 571,'userId' => 1527190,'purposeName' => '投资户','purposeId' => '1',),
        3711526 => array ('id' => 572,'userId' => 3711526,'purposeName' => '担保户','purposeId' => '4',),
        4235622 => array ('id' => 573,'userId' => 4235622,'purposeName' => '投资户','purposeId' => '1',),
        1735046 => array ('id' => 574,'userId' => 1735046,'purposeName' => '投资户','purposeId' => '1',),
        7315078 => array ('id' => 575,'userId' => 7315078,'purposeName' => '融资户','purposeId' => '2',),
        4988600 => array ('id' => 576,'userId' => 4988600,'purposeName' => '投资户','purposeId' => '1',),
        1367035 => array ('id' => 577,'userId' => 1367035,'purposeName' => '投资户','purposeId' => '1',),
        14731 => array ('id' => 578,'userId' => 14731,'purposeName' => '咨询户','purposeId' => '3',),
        8160983 => array ('id' => 579,'userId' => 8160983,'purposeName' => '代垫户','purposeId' => '8',),
        8160987 => array ('id' => 580,'userId' => 8160987,'purposeName' => '担保户','purposeId' => '4',),
        4987979 => array ('id' => 581,'userId' => 4987979,'purposeName' => '投资户','purposeId' => '1',),
        8004512 => array ('id' => 582,'userId' => 8004512,'purposeName' => '融资户','purposeId' => '2',),
        7429688 => array ('id' => 583,'userId' => 7429688,'purposeName' => '融资户','purposeId' => '2',),
        650776 => array ('id' => 584,'userId' => 650776,'purposeName' => '咨询户','purposeId' => '3',),
        2662188 => array ('id' => 585,'userId' => 2662188,'purposeName' => '投资户','purposeId' => '1',),
        7613929 => array ('id' => 586,'userId' => 7613929,'purposeName' => '融资户','purposeId' => '2',),
        1943106 => array ('id' => 587,'userId' => 1943106,'purposeName' => '投资户','purposeId' => '1',),
        7976866 => array ('id' => 588,'userId' => 7976866,'purposeName' => '融资户','purposeId' => '2',),
        4183643 => array ('id' => 589,'userId' => 4183643,'purposeName' => '融资户','purposeId' => '2',),
        4345154 => array ('id' => 590,'userId' => 4345154,'purposeName' => '投资户','purposeId' => '1',),
        7056604 => array ('id' => 591,'userId' => 7056604,'purposeName' => '担保户','purposeId' => '4',),
        6060633 => array ('id' => 592,'userId' => 6060633,'purposeName' => '融资户','purposeId' => '2',),
        7057306 => array ('id' => 593,'userId' => 7057306,'purposeName' => '渠道户','purposeId' => '5',),
        6244904 => array ('id' => 594,'userId' => 6244904,'purposeName' => '融资户','purposeId' => '2',),
        2900284 => array ('id' => 595,'userId' => 2900284,'purposeName' => '投资户','purposeId' => '1',),
        1452451 => array ('id' => 596,'userId' => 1452451,'purposeName' => '融资户','purposeId' => '2',),
        3515905 => array ('id' => 597,'userId' => 3515905,'purposeName' => '融资户','purposeId' => '2',),
        6157955 => array ('id' => 598,'userId' => 6157955,'purposeName' => '渠道户','purposeId' => '5',),
        7831394 => array ('id' => 599,'userId' => 7831394,'purposeName' => '担保户','purposeId' => '4',),
        4456386 => array ('id' => 600,'userId' => 4456386,'purposeName' => '投资户','purposeId' => '1',),
        2604285 => array ('id' => 601,'userId' => 2604285,'purposeName' => '融资户','purposeId' => '2',),
        6392542 => array ('id' => 602,'userId' => 6392542,'purposeName' => '担保户','purposeId' => '4',),
        308706 => array ('id' => 603,'userId' => 308706,'purposeName' => '咨询户','purposeId' => '3',),
        8160994 => array ('id' => 604,'userId' => 8160994,'purposeName' => '代垫户','purposeId' => '8',),
        6529971 => array ('id' => 605,'userId' => 6529971,'purposeName' => '融资户','purposeId' => '2',),
        2663618 => array ('id' => 606,'userId' => 2663618,'purposeName' => '渠道户','purposeId' => '5',),
        2653033 => array ('id' => 607,'userId' => 2653033,'purposeName' => '咨询户','purposeId' => '3',),
        8161000 => array ('id' => 608,'userId' => 8161000,'purposeName' => '代垫户','purposeId' => '8',),
        7398294 => array ('id' => 609,'userId' => 7398294,'purposeName' => '担保户','purposeId' => '4',),
        6284344 => array ('id' => 610,'userId' => 6284344,'purposeName' => '融资户','purposeId' => '2',),
        1387520 => array ('id' => 611,'userId' => 1387520,'purposeName' => '投资户','purposeId' => '1',),
        2611193 => array ('id' => 612,'userId' => 2611193,'purposeName' => '融资户','purposeId' => '2',),
        3343841 => array ('id' => 613,'userId' => 3343841,'purposeName' => '投资户','purposeId' => '1',),
        6573731 => array ('id' => 614,'userId' => 6573731,'purposeName' => '担保户','purposeId' => '4',),
        2926010 => array ('id' => 615,'userId' => 2926010,'purposeName' => '融资户','purposeId' => '2',),
        6923765 => array ('id' => 616,'userId' => 6923765,'purposeName' => '担保户','purposeId' => '4',),
        6923618 => array ('id' => 617,'userId' => 6923618,'purposeName' => '融资户','purposeId' => '2',),
        7018106 => array ('id' => 618,'userId' => 7018106,'purposeName' => '融资户','purposeId' => '2',),
        5479 => array ('id' => 619,'userId' => 5479,'purposeName' => '渠道户','purposeId' => '5',),
        4419883 => array ('id' => 620,'userId' => 4419883,'purposeName' => '担保户','purposeId' => '4',),
        3556135 => array ('id' => 621,'userId' => 3556135,'purposeName' => '咨询户','purposeId' => '3',),
        8161014 => array ('id' => 622,'userId' => 8161014,'purposeName' => '代垫户','purposeId' => '8',),
        7974740 => array ('id' => 623,'userId' => 7974740,'purposeName' => '担保户','purposeId' => '4',),
        3559682 => array ('id' => 624,'userId' => 3559682,'purposeName' => '融资户','purposeId' => '2',),
        2611088 => array ('id' => 625,'userId' => 2611088,'purposeName' => '融资户','purposeId' => '2',),
        6583805 => array ('id' => 626,'userId' => 6583805,'purposeName' => '担保户','purposeId' => '4',),
        2875368 => array ('id' => 627,'userId' => 2875368,'purposeName' => '投资户','purposeId' => '1',),
        4286943 => array ('id' => 628,'userId' => 4286943,'purposeName' => '融资户','purposeId' => '2',),
        6354059 => array ('id' => 629,'userId' => 6354059,'purposeName' => '渠道户','purposeId' => '5',),
        7496298 => array ('id' => 630,'userId' => 7496298,'purposeName' => '融资户','purposeId' => '2',),
        6460406 => array ('id' => 631,'userId' => 6460406,'purposeName' => '咨询户','purposeId' => '3',),
        4989245 => array ('id' => 632,'userId' => 4989245,'purposeName' => '渠道户','purposeId' => '5',),
        6812193 => array ('id' => 633,'userId' => 6812193,'purposeName' => '融资户','purposeId' => '2',),
        2511023 => array ('id' => 634,'userId' => 2511023,'purposeName' => '渠道户','purposeId' => '5',),
        2756714 => array ('id' => 635,'userId' => 2756714,'purposeName' => '投资户','purposeId' => '1',),
        6885188 => array ('id' => 636,'userId' => 6885188,'purposeName' => '融资户','purposeId' => '2',),
        6885282 => array ('id' => 637,'userId' => 6885282,'purposeName' => '担保户','purposeId' => '4',),
        3951184 => array ('id' => 638,'userId' => 3951184,'purposeName' => '渠道户','purposeId' => '5',),
        2744997 => array ('id' => 639,'userId' => 2744997,'purposeName' => '融资户','purposeId' => '2',),
        6317625 => array ('id' => 640,'userId' => 6317625,'purposeName' => '融资户','purposeId' => '2',),
        6890501 => array ('id' => 641,'userId' => 6890501,'purposeName' => '融资户','purposeId' => '2',),
        6848536 => array ('id' => 642,'userId' => 6848536,'purposeName' => '融资户','purposeId' => '2',),
        6279917 => array ('id' => 643,'userId' => 6279917,'purposeName' => '融资户','purposeId' => '2',),
        7496426 => array ('id' => 644,'userId' => 7496426,'purposeName' => '担保户','purposeId' => '4',),
        6959845 => array ('id' => 645,'userId' => 6959845,'purposeName' => '融资户','purposeId' => '2',),
        6848660 => array ('id' => 646,'userId' => 6848660,'purposeName' => '融资户','purposeId' => '2',),
        6760567 => array ('id' => 647,'userId' => 6760567,'purposeName' => '融资户','purposeId' => '2',),
        3879048 => array ('id' => 648,'userId' => 3879048,'purposeName' => '融资户','purposeId' => '2',),
        7267962 => array ('id' => 649,'userId' => 7267962,'purposeName' => '融资户','purposeId' => '2',),
        7306413 => array ('id' => 650,'userId' => 7306413,'purposeName' => '融资户','purposeId' => '2',),
        6945135 => array ('id' => 651,'userId' => 6945135,'purposeName' => '融资户','purposeId' => '2',),
        7166465 => array ('id' => 652,'userId' => 7166465,'purposeName' => '融资户','purposeId' => '2',),
        6959819 => array ('id' => 653,'userId' => 6959819,'purposeName' => '融资户','purposeId' => '2',),
        949187 => array ('id' => 654,'userId' => 949187,'purposeName' => '渠道户','purposeId' => '5',),
        7044731 => array ('id' => 655,'userId' => 7044731,'purposeName' => '咨询户','purposeId' => '3',),
        6348573 => array ('id' => 656,'userId' => 6348573,'purposeName' => '融资户','purposeId' => '2',),
        6759087 => array ('id' => 657,'userId' => 6759087,'purposeName' => '融资户','purposeId' => '2',),
        7222431 => array ('id' => 658,'userId' => 7222431,'purposeName' => '融资户','purposeId' => '2',),
        2576096 => array ('id' => 659,'userId' => 2576096,'purposeName' => '融资户','purposeId' => '2',),
        6788889 => array ('id' => 660,'userId' => 6788889,'purposeName' => '融资户','purposeId' => '2',),
        2888274 => array ('id' => 661,'userId' => 2888274,'purposeName' => '融资户','purposeId' => '2',),
        6804285 => array ('id' => 662,'userId' => 6804285,'purposeName' => '融资户','purposeId' => '2',),
        7193319 => array ('id' => 663,'userId' => 7193319,'purposeName' => '融资户','purposeId' => '2',),
        3839557 => array ('id' => 664,'userId' => 3839557,'purposeName' => '融资户','purposeId' => '2',),
        3839611 => array ('id' => 665,'userId' => 3839611,'purposeName' => '融资户','purposeId' => '2',),
        6846738 => array ('id' => 666,'userId' => 6846738,'purposeName' => '融资户','purposeId' => '2',),
        4231470 => array ('id' => 667,'userId' => 4231470,'purposeName' => '融资户','purposeId' => '2',),
        7970116 => array ('id' => 668,'userId' => 7970116,'purposeName' => '融资户','purposeId' => '2',),
        6115378 => array ('id' => 669,'userId' => 6115378,'purposeName' => '融资户','purposeId' => '2',),
        6460188 => array ('id' => 670,'userId' => 6460188,'purposeName' => '担保户','purposeId' => '4',),
        4811184 => array ('id' => 671,'userId' => 4811184,'purposeName' => '融资户','purposeId' => '2',),
        8002069 => array ('id' => 672,'userId' => 8002069,'purposeName' => '融资户','purposeId' => '2',),
        7075400 => array ('id' => 673,'userId' => 7075400,'purposeName' => '融资户','purposeId' => '2',),
        7947982 => array ('id' => 674,'userId' => 7947982,'purposeName' => '受托资产管理户','purposeId' => '9',),
        6637727 => array ('id' => 675,'userId' => 6637727,'purposeName' => '担保户','purposeId' => '4',),
        7103157 => array ('id' => 676,'userId' => 7103157,'purposeName' => '融资户','purposeId' => '2',),
        7355387 => array ('id' => 677,'userId' => 7355387,'purposeName' => '融资户','purposeId' => '2',),
        6759282 => array ('id' => 678,'userId' => 6759282,'purposeName' => '融资户','purposeId' => '2',),
        5451128 => array ('id' => 679,'userId' => 5451128,'purposeName' => '融资户','purposeId' => '2',),
        6760365 => array ('id' => 680,'userId' => 6760365,'purposeName' => '融资户','purposeId' => '2',),
        2776639 => array ('id' => 681,'userId' => 2776639,'purposeName' => '融资户','purposeId' => '2',),
        6541137 => array ('id' => 682,'userId' => 6541137,'purposeName' => '担保户','purposeId' => '4',),
        7119744 => array ('id' => 683,'userId' => 7119744,'purposeName' => '担保户','purposeId' => '4',),
        2577900 => array ('id' => 684,'userId' => 2577900,'purposeName' => '融资户','purposeId' => '2',),
        3558013 => array ('id' => 685,'userId' => 3558013,'purposeName' => '投资户','purposeId' => '1',),
        5781973 => array ('id' => 686,'userId' => 5781973,'purposeName' => '融资户','purposeId' => '2',),
        2739486 => array ('id' => 687,'userId' => 2739486,'purposeName' => '融资户','purposeId' => '2',),
        3608806 => array ('id' => 688,'userId' => 3608806,'purposeName' => '渠道户','purposeId' => '5',),
        3618692 => array ('id' => 689,'userId' => 3618692,'purposeName' => '担保户','purposeId' => '4',),
        2523883 => array ('id' => 690,'userId' => 2523883,'purposeName' => '咨询户','purposeId' => '3',),
        8161021 => array ('id' => 691,'userId' => 8161021,'purposeName' => '代垫户','purposeId' => '8',),
        6294046 => array ('id' => 692,'userId' => 6294046,'purposeName' => '融资户','purposeId' => '2',),
        6403000 => array ('id' => 693,'userId' => 6403000,'purposeName' => '融资户','purposeId' => '2',),
        6663306 => array ('id' => 694,'userId' => 6663306,'purposeName' => '担保户','purposeId' => '4',),
        7413764 => array ('id' => 695,'userId' => 7413764,'purposeName' => '融资户','purposeId' => '2',),
        6663358 => array ('id' => 696,'userId' => 6663358,'purposeName' => '融资户','purposeId' => '2',),
        7995551 => array ('id' => 697,'userId' => 7995551,'purposeName' => '融资户','purposeId' => '2',),
        6637202 => array ('id' => 698,'userId' => 6637202,'purposeName' => '融资户','purposeId' => '2',),
        7397856 => array ('id' => 699,'userId' => 7397856,'purposeName' => '融资户','purposeId' => '2',),
        7097714 => array ('id' => 700,'userId' => 7097714,'purposeName' => '融资户','purposeId' => '2',),
        5102937 => array ('id' => 701,'userId' => 5102937,'purposeName' => '投资户','purposeId' => '1',),
        3559800 => array ('id' => 702,'userId' => 3559800,'purposeName' => '融资户','purposeId' => '2',),
        2888653 => array ('id' => 703,'userId' => 2888653,'purposeName' => '融资户','purposeId' => '2',),
        2888876 => array ('id' => 704,'userId' => 2888876,'purposeName' => '融资户','purposeId' => '2',),
        7874608 => array ('id' => 705,'userId' => 7874608,'purposeName' => '融资户','purposeId' => '2',),
        7906003 => array ('id' => 706,'userId' => 7906003,'purposeName' => '融资户','purposeId' => '2',),
        6142338 => array ('id' => 707,'userId' => 6142338,'purposeName' => '担保户','purposeId' => '4',),
        6142545 => array ('id' => 708,'userId' => 6142545,'purposeName' => '融资户','purposeId' => '2',),
        6397457 => array ('id' => 709,'userId' => 6397457,'purposeName' => '融资户','purposeId' => '2',),
        5880251 => array ('id' => 710,'userId' => 5880251,'purposeName' => '融资户','purposeId' => '2',),
        6758807 => array ('id' => 711,'userId' => 6758807,'purposeName' => '担保户','purposeId' => '4',),
        7300230 => array ('id' => 712,'userId' => 7300230,'purposeName' => '融资户','purposeId' => '2',),
        7072501 => array ('id' => 713,'userId' => 7072501,'purposeName' => '融资户','purposeId' => '2',),
        2875841 => array ('id' => 714,'userId' => 2875841,'purposeName' => '融资户','purposeId' => '2',),
        7240637 => array ('id' => 715,'userId' => 7240637,'purposeName' => '融资户','purposeId' => '2',),
        8118360 => array ('id' => 716,'userId' => 8118360,'purposeName' => '担保户','purposeId' => '4',),
        7484723 => array ('id' => 717,'userId' => 7484723,'purposeName' => '受托资产管理户','purposeId' => '9',),
        7854352 => array ('id' => 718,'userId' => 7854352,'purposeName' => '融资户','purposeId' => '2',),
        7097738 => array ('id' => 719,'userId' => 7097738,'purposeName' => '担保户','purposeId' => '4',),
        6366949 => array ('id' => 720,'userId' => 6366949,'purposeName' => '融资户','purposeId' => '2',),
        7044757 => array ('id' => 721,'userId' => 7044757,'purposeName' => '融资户','purposeId' => '2',),
        4232621 => array ('id' => 722,'userId' => 4232621,'purposeName' => '融资户','purposeId' => '2',),
        7936071 => array ('id' => 723,'userId' => 7936071,'purposeName' => '担保户','purposeId' => '4',),
        1456339 => array ('id' => 724,'userId' => 1456339,'purposeName' => '担保户','purposeId' => '4',),
        8161028 => array ('id' => 725,'userId' => 8161028,'purposeName' => '代垫户','purposeId' => '8',),
        34422 => array ('id' => 726,'userId' => 34422,'purposeName' => '咨询户','purposeId' => '3',),
        8161033 => array ('id' => 727,'userId' => 8161033,'purposeName' => '代垫户','purposeId' => '8',),
        7057263 => array ('id' => 728,'userId' => 7057263,'purposeName' => '渠道户','purposeId' => '5',),
        3566456 => array ('id' => 729,'userId' => 3566456,'purposeName' => '融资户','purposeId' => '2',),
        3950273 => array ('id' => 730,'userId' => 3950273,'purposeName' => '融资户','purposeId' => '2',),
        3894156 => array ('id' => 731,'userId' => 3894156,'purposeName' => '咨询户','purposeId' => '3',),
        8161043 => array ('id' => 732,'userId' => 8161043,'purposeName' => '代垫户','purposeId' => '8',),
        11269 => array ('id' => 733,'userId' => 11269,'purposeName' => '咨询户','purposeId' => '3',),
        8161047 => array ('id' => 734,'userId' => 8161047,'purposeName' => '代垫户','purposeId' => '8',),
        1891442 => array ('id' => 735,'userId' => 1891442,'purposeName' => '融资户','purposeId' => '2',),
        1324606 => array ('id' => 736,'userId' => 1324606,'purposeName' => '渠道户','purposeId' => '5',),
        6812785 => array ('id' => 737,'userId' => 6812785,'purposeName' => '担保户','purposeId' => '4',),
        4791451 => array ('id' => 738,'userId' => 4791451,'purposeName' => '融资户','purposeId' => '2',),
        6995041 => array ('id' => 739,'userId' => 6995041,'purposeName' => '担保户','purposeId' => '4',),
        6571172 => array ('id' => 740,'userId' => 6571172,'purposeName' => '融资户','purposeId' => '2',),
        2611153 => array ('id' => 741,'userId' => 2611153,'purposeName' => '融资户','purposeId' => '2',),
        6572222 => array ('id' => 742,'userId' => 6572222,'purposeName' => '担保户','purposeId' => '4',),
        6366803 => array ('id' => 743,'userId' => 6366803,'purposeName' => '融资户','purposeId' => '2',),
        8004922 => array ('id' => 744,'userId' => 8004922,'purposeName' => '担保户','purposeId' => '4',),
        2967025 => array ('id' => 745,'userId' => 2967025,'purposeName' => '融资户','purposeId' => '2',),
        7091695 => array ('id' => 746,'userId' => 7091695,'purposeName' => '担保户','purposeId' => '4',),
        6717396 => array ('id' => 747,'userId' => 6717396,'purposeName' => '融资户','purposeId' => '2',),
        7122271 => array ('id' => 748,'userId' => 7122271,'purposeName' => '担保户','purposeId' => '4',),
        6441780 => array ('id' => 749,'userId' => 6441780,'purposeName' => '融资户','purposeId' => '2',),
        6964513 => array ('id' => 750,'userId' => 6964513,'purposeName' => '融资户','purposeId' => '2',),
        6818110 => array ('id' => 751,'userId' => 6818110,'purposeName' => '担保户','purposeId' => '4',),
        7072231 => array ('id' => 752,'userId' => 7072231,'purposeName' => '担保户','purposeId' => '4',),
        3071901 => array ('id' => 753,'userId' => 3071901,'purposeName' => '担保户','purposeId' => '4',),
        8161056 => array ('id' => 754,'userId' => 8161056,'purposeName' => '代垫户','purposeId' => '8',),
        3069644 => array ('id' => 755,'userId' => 3069644,'purposeName' => '融资户','purposeId' => '2',),
        7251619 => array ('id' => 756,'userId' => 7251619,'purposeName' => '保证金户','purposeId' => '12',),
        5230234 => array ('id' => 757,'userId' => 5230234,'purposeName' => '咨询户','purposeId' => '3',),
        8161064 => array ('id' => 758,'userId' => 8161064,'purposeName' => '代垫户','purposeId' => '8',),
        8161066 => array ('id' => 759,'userId' => 8161066,'purposeName' => '代充值户','purposeId' => '16',),
        1526101 => array ('id' => 760,'userId' => 1526101,'purposeName' => '融资户','purposeId' => '2',),
        7057286 => array ('id' => 761,'userId' => 7057286,'purposeName' => '渠道户','purposeId' => '5',),
        7831314 => array ('id' => 762,'userId' => 7831314,'purposeName' => '融资户','purposeId' => '2',),
        7900646 => array ('id' => 763,'userId' => 7900646,'purposeName' => '渠道户','purposeId' => '5',),
        5781589 => array ('id' => 764,'userId' => 5781589,'purposeName' => '渠道户','purposeId' => '5',),
        5310681 => array ('id' => 765,'userId' => 5310681,'purposeName' => '担保户','purposeId' => '4',),
        2879094 => array ('id' => 766,'userId' => 2879094,'purposeName' => '渠道户','purposeId' => '5',),
        4454894 => array ('id' => 767,'userId' => 4454894,'purposeName' => '担保户','purposeId' => '4',),
        6995099 => array ('id' => 768,'userId' => 6995099,'purposeName' => '担保户','purposeId' => '4',),
        7075231 => array ('id' => 769,'userId' => 7075231,'purposeName' => '融资户','purposeId' => '2',),
        37348 => array ('id' => 770,'userId' => 37348,'purposeName' => '咨询户','purposeId' => '3',),
        8161077 => array ('id' => 771,'userId' => 8161077,'purposeName' => '担保户','purposeId' => '4',),
        7576684 => array ('id' => 772,'userId' => 7576684,'purposeName' => '融资户','purposeId' => '2',),
        4457260 => array ('id' => 773,'userId' => 4457260,'purposeName' => '融资户','purposeId' => '2',),
        6293918 => array ('id' => 774,'userId' => 6293918,'purposeName' => '融资户','purposeId' => '2',),
        6164031 => array ('id' => 775,'userId' => 6164031,'purposeName' => '担保户','purposeId' => '4',),
        7353632 => array ('id' => 776,'userId' => 7353632,'purposeName' => '渠道户','purposeId' => '5',),
        3321654 => array ('id' => 777,'userId' => 3321654,'purposeName' => '融资户','purposeId' => '2',),
        4437759 => array ('id' => 778,'userId' => 4437759,'purposeName' => '担保户','purposeId' => '4',),
        8161082 => array ('id' => 779,'userId' => 8161082,'purposeName' => '咨询户','purposeId' => '3',),
        8161085 => array ('id' => 780,'userId' => 8161085,'purposeName' => '代垫户','purposeId' => '8',),
        6804791 => array ('id' => 781,'userId' => 6804791,'purposeName' => '融资户','purposeId' => '2',),
        401545 => array ('id' => 782,'userId' => 401545,'purposeName' => '渠道户','purposeId' => '5',),
        1429524 => array ('id' => 783,'userId' => 1429524,'purposeName' => '渠道户','purposeId' => '5',),
        3547052 => array ('id' => 784,'userId' => 3547052,'purposeName' => '咨询户','purposeId' => '3',),
        8161094 => array ('id' => 785,'userId' => 8161094,'purposeName' => '代垫户','purposeId' => '8',),
        8161098 => array ('id' => 786,'userId' => 8161098,'purposeName' => '受托资产管理户','purposeId' => '9',),
        5797959 => array ('id' => 787,'userId' => 5797959,'purposeName' => '融资户','purposeId' => '2',),
        7051399 => array ('id' => 788,'userId' => 7051399,'purposeName' => '担保户','purposeId' => '4',),
        4472502 => array ('id' => 789,'userId' => 4472502,'purposeName' => '融资户','purposeId' => '2',),
        2661675 => array ('id' => 790,'userId' => 2661675,'purposeName' => '投资户','purposeId' => '1',),
        4107994 => array ('id' => 791,'userId' => 4107994,'purposeName' => '咨询户','purposeId' => '3',),
        8161103 => array ('id' => 792,'userId' => 8161103,'purposeName' => '代垫户','purposeId' => '8',),
        1020179 => array ('id' => 793,'userId' => 1020179,'purposeName' => '渠道户','purposeId' => '5',),
        6141021 => array ('id' => 794,'userId' => 6141021,'purposeName' => '渠道户','purposeId' => '5',),
        3726042 => array ('id' => 795,'userId' => 3726042,'purposeName' => '渠道户','purposeId' => '5',),
        5684582 => array ('id' => 796,'userId' => 5684582,'purposeName' => '咨询户','purposeId' => '3',),
        8161109 => array ('id' => 797,'userId' => 8161109,'purposeName' => '代垫户','purposeId' => '8',),
        5097338 => array ('id' => 798,'userId' => 5097338,'purposeName' => '渠道户','purposeId' => '5',),
        5120635 => array ('id' => 799,'userId' => 5120635,'purposeName' => '投资户','purposeId' => '1',),
        4031088 => array ('id' => 800,'userId' => 4031088,'purposeName' => '咨询户','purposeId' => '3',),
        8161115 => array ('id' => 801,'userId' => 8161115,'purposeName' => '代垫户','purposeId' => '8',),
        4583464 => array ('id' => 802,'userId' => 4583464,'purposeName' => '渠道户','purposeId' => '5',),
        5768123 => array ('id' => 803,'userId' => 5768123,'purposeName' => '投资户','purposeId' => '1',),
        7312073 => array ('id' => 804,'userId' => 7312073,'purposeName' => '融资户','purposeId' => '2',),
        3539072 => array ('id' => 805,'userId' => 3539072,'purposeName' => '融资户','purposeId' => '2',),
        8289012 => array ('id' => 806,'userId' => 8289012,'purposeName' => '融资户','purposeId' => '2',),
        4667416 => array ('id' => 807,'userId' => 4667416,'purposeName' => '投资户','purposeId' => '1',),
        1242123 => array ('id' => 808,'userId' => 1242123,'purposeName' => '担保户','purposeId' => '4',),
        4325132 => array ('id' => 809,'userId' => 4325132,'purposeName' => '投资户','purposeId' => '1',),
        7544516 => array ('id' => 810,'userId' => 7544516,'purposeName' => '融资户','purposeId' => '2',),
        6978268 => array ('id' => 811,'userId' => 6978268,'purposeName' => '担保户','purposeId' => '4',),
        6367924 => array ('id' => 812,'userId' => 6367924,'purposeName' => '担保户','purposeId' => '4',),
        7487245 => array ('id' => 813,'userId' => 7487245,'purposeName' => '担保户','purposeId' => '4',),
        5308286 => array ('id' => 814,'userId' => 5308286,'purposeName' => '投资户','purposeId' => '1',),
        6355863 => array ('id' => 815,'userId' => 6355863,'purposeName' => '担保户','purposeId' => '4',),
        7487217 => array ('id' => 816,'userId' => 7487217,'purposeName' => '融资户','purposeId' => '2',),
        872401 => array ('id' => 817,'userId' => 872401,'purposeName' => '担保户','purposeId' => '4',),
        4360563 => array ('id' => 818,'userId' => 4360563,'purposeName' => '融资户','purposeId' => '2',),
        6926269 => array ('id' => 819,'userId' => 6926269,'purposeName' => '渠道户','purposeId' => '5',),
        3570456 => array ('id' => 820,'userId' => 3570456,'purposeName' => '渠道户','purposeId' => '5',),
        3051908 => array ('id' => 821,'userId' => 3051908,'purposeName' => '渠道户','purposeId' => '5',),
        4570196 => array ('id' => 822,'userId' => 4570196,'purposeName' => '投资户','purposeId' => '1',),
        1109824 => array ('id' => 823,'userId' => 1109824,'purposeName' => '支付户','purposeId' => '13',),
        1692903 => array ('id' => 824,'userId' => 1692903,'purposeName' => '渠道户','purposeId' => '5',),
        3401053 => array ('id' => 825,'userId' => 3401053,'purposeName' => '渠道户','purposeId' => '5',),
        1258336 => array ('id' => 826,'userId' => 1258336,'purposeName' => '红包户','purposeId' => '15',),
        1119185 => array ('id' => 827,'userId' => 1119185,'purposeName' => '渠道户','purposeId' => '5',),
        7009972 => array ('id' => 828,'userId' => 7009972,'purposeName' => '融资户','purposeId' => '2',),
        3720658 => array ('id' => 829,'userId' => 3720658,'purposeName' => '咨询户','purposeId' => '3',),
        3747468 => array ('id' => 830,'userId' => 3747468,'purposeName' => '渠道户','purposeId' => '5',),
        3538821 => array ('id' => 831,'userId' => 3538821,'purposeName' => '担保户','purposeId' => '4',),
        7591727 => array ('id' => 832,'userId' => 7591727,'purposeName' => '融资户','purposeId' => '2',),
        6603490 => array ('id' => 833,'userId' => 6603490,'purposeName' => '融资户','purposeId' => '2',),
        7399313 => array ('id' => 834,'userId' => 7399313,'purposeName' => '担保户','purposeId' => '4',),
        7429757 => array ('id' => 835,'userId' => 7429757,'purposeName' => '融资户','purposeId' => '2',),
        8121523 => array ('id' => 836,'userId' => 8121523,'purposeName' => '融资户','purposeId' => '2',),
        6850607 => array ('id' => 837,'userId' => 6850607,'purposeName' => '融资户','purposeId' => '2',),
        6581662 => array ('id' => 838,'userId' => 6581662,'purposeName' => '融资户','purposeId' => '2',),
        4106855 => array ('id' => 839,'userId' => 4106855,'purposeName' => '投资户','purposeId' => '1',),
        12866 => array ('id' => 840,'userId' => 12866,'purposeName' => '担保户','purposeId' => '4',),
        6964501 => array ('id' => 841,'userId' => 6964501,'purposeName' => '担保户','purposeId' => '4',),
        7069244 => array ('id' => 842,'userId' => 7069244,'purposeName' => '担保户','purposeId' => '4',),
        298444 => array ('id' => 843,'userId' => 298444,'purposeName' => '担保户','purposeId' => '4',),
        2625931 => array ('id' => 844,'userId' => 2625931,'purposeName' => '融资户','purposeId' => '2',),
        6398506 => array ('id' => 845,'userId' => 6398506,'purposeName' => '担保户','purposeId' => '4',),
        7286385 => array ('id' => 846,'userId' => 7286385,'purposeName' => '融资户','purposeId' => '2',),
        8229699 => array ('id' => 847,'userId' => 8229699,'purposeName' => '红包户','purposeId' => '15',),
        7091212 => array ('id' => 848,'userId' => 7091212,'purposeName' => '投资券户','purposeId' => '14',),
        7091228 => array ('id' => 849,'userId' => 7091228,'purposeName' => '红包户','purposeId' => '15',),
        6529761 => array ('id' => 850,'userId' => 6529761,'purposeName' => '融资户','purposeId' => '2',),
        4411015 => array ('id' => 851,'userId' => 4411015,'purposeName' => '投资户','purposeId' => '1',),
        8229594 => array ('id' => 852,'userId' => 8229594,'purposeName' => '投资券户','purposeId' => '14',),
        6142068 => array ('id' => 853,'userId' => 6142068,'purposeName' => '红包户','purposeId' => '15',),
        6385333 => array ('id' => 854,'userId' => 6385333,'purposeName' => '投资券户','purposeId' => '14',),
        6711559 => array ('id' => 855,'userId' => 6711559,'purposeName' => '投资券户','purposeId' => '14',),
        6779859 => array ('id' => 856,'userId' => 6779859,'purposeName' => '红包户','purposeId' => '15',),
        8161128 => array ('id' => 857,'userId' => 8161128,'purposeName' => '投资券户','purposeId' => '14',),
        6279700 => array ('id' => 858,'userId' => 6279700,'purposeName' => '融资户','purposeId' => '2',),
        6653021 => array ('id' => 859,'userId' => 6653021,'purposeName' => '融资户','purposeId' => '2',),
        6265674 => array ('id' => 860,'userId' => 6265674,'purposeName' => '担保户','purposeId' => '4',),
        2571636 => array ('id' => 861,'userId' => 2571636,'purposeName' => '投资户','purposeId' => '1',),
        4580902 => array ('id' => 862,'userId' => 4580902,'purposeName' => '融资户','purposeId' => '2',),
        2663483 => array ('id' => 863,'userId' => 2663483,'purposeName' => '渠道户','purposeId' => '5',),
        6982685 => array ('id' => 864,'userId' => 6982685,'purposeName' => '担保户','purposeId' => '4',),
        6179347 => array ('id' => 865,'userId' => 6179347,'purposeName' => '担保户','purposeId' => '4',),
        6823630 => array ('id' => 866,'userId' => 6823630,'purposeName' => '融资户','purposeId' => '2',),
        6179879 => array ('id' => 867,'userId' => 6179879,'purposeName' => '融资户','purposeId' => '2',),
        6511697 => array ('id' => 868,'userId' => 6511697,'purposeName' => '担保户','purposeId' => '4',),
        1032519 => array ('id' => 869,'userId' => 1032519,'purposeName' => '担保户','purposeId' => '4',),
        6091905 => array ('id' => 870,'userId' => 6091905,'purposeName' => '融资户','purposeId' => '2',),
        6451847 => array ('id' => 871,'userId' => 6451847,'purposeName' => '担保户','purposeId' => '4',),
        6635389 => array ('id' => 872,'userId' => 6635389,'purposeName' => '融资户','purposeId' => '2',),
        6134348 => array ('id' => 873,'userId' => 6134348,'purposeName' => '投资户','purposeId' => '1',),
        4576840 => array ('id' => 874,'userId' => 4576840,'purposeName' => '投资户','purposeId' => '1',),
        2222758 => array ('id' => 875,'userId' => 2222758,'purposeName' => '咨询户','purposeId' => '3',),
        6293960 => array ('id' => 876,'userId' => 6293960,'purposeName' => '融资户','purposeId' => '2',),
        418713 => array ('id' => 877,'userId' => 418713,'purposeName' => '咨询户','purposeId' => '3',),
        8161134 => array ('id' => 878,'userId' => 8161134,'purposeName' => '代垫户','purposeId' => '8',),
        6293665 => array ('id' => 879,'userId' => 6293665,'purposeName' => '担保户','purposeId' => '4',),
        7947778 => array ('id' => 880,'userId' => 7947778,'purposeName' => '融资户','purposeId' => '2',),
        7209562 => array ('id' => 881,'userId' => 7209562,'purposeName' => '融资户','purposeId' => '2',),
        7429882 => array ('id' => 882,'userId' => 7429882,'purposeName' => '融资户','purposeId' => '2',),
        4682477 => array ('id' => 883,'userId' => 4682477,'purposeName' => '融资户','purposeId' => '2',),
        465422 => array ('id' => 884,'userId' => 465422,'purposeName' => '担保户','purposeId' => '4',),
        8242068 => array ('id' => 885,'userId' => 8242068,'purposeName' => '融资户','purposeId' => '2',),
        7030885 => array ('id' => 886,'userId' => 7030885,'purposeName' => '融资户','purposeId' => '2',),
        1519963 => array ('id' => 887,'userId' => 1519963,'purposeName' => '红包户','purposeId' => '15',),
        6811644 => array ('id' => 888,'userId' => 6811644,'purposeName' => '融资户','purposeId' => '2',),
        8004544 => array ('id' => 889,'userId' => 8004544,'purposeName' => '融资户','purposeId' => '2',),
        7341626 => array ('id' => 890,'userId' => 7341626,'purposeName' => '担保户','purposeId' => '4',),
        3113926 => array ('id' => 891,'userId' => 3113926,'purposeName' => '担保户','purposeId' => '4',),
        3142223 => array ('id' => 892,'userId' => 3142223,'purposeName' => '融资户','purposeId' => '2',),
        7018517 => array ('id' => 893,'userId' => 7018517,'purposeName' => '担保户','purposeId' => '4',),
        4523 => array ('id' => 894,'userId' => 4523,'purposeName' => '咨询户','purposeId' => '3',),
        142392 => array ('id' => 895,'userId' => 142392,'purposeName' => '担保户','purposeId' => '4',),
        4809819 => array ('id' => 896,'userId' => 4809819,'purposeName' => '融资户','purposeId' => '2',),
        8100132 => array ('id' => 897,'userId' => 8100132,'purposeName' => '渠道户','purposeId' => '5',),
        6812875 => array ('id' => 898,'userId' => 6812875,'purposeName' => '担保户','purposeId' => '4',),
        6293872 => array ('id' => 899,'userId' => 6293872,'purposeName' => '融资户','purposeId' => '2',),
        1367661 => array ('id' => 900,'userId' => 1367661,'purposeName' => '担保户','purposeId' => '4',),
        7057230 => array ('id' => 901,'userId' => 7057230,'purposeName' => '融资户','purposeId' => '2',),
        2878477 => array ('id' => 902,'userId' => 2878477,'purposeName' => '投资户','purposeId' => '1',),
        1396566 => array ('id' => 903,'userId' => 1396566,'purposeName' => '融资户','purposeId' => '2',),
        3108175 => array ('id' => 904,'userId' => 3108175,'purposeName' => '渠道户','purposeId' => '5',),
        6283442 => array ('id' => 905,'userId' => 6283442,'purposeName' => '融资户','purposeId' => '2',),
        6317456 => array ('id' => 906,'userId' => 6317456,'purposeName' => '平台户','purposeId' => '11',),
        3750401 => array ('id' => 907,'userId' => 3750401,'purposeName' => '融资户','purposeId' => '2',),
        3651659 => array ('id' => 908,'userId' => 3651659,'purposeName' => '融资户','purposeId' => '2',),
        6367488 => array ('id' => 909,'userId' => 6367488,'purposeName' => '融资户','purposeId' => '2',),
        6105275 => array ('id' => 910,'userId' => 6105275,'purposeName' => '融资户','purposeId' => '2',),
        3776344 => array ('id' => 911,'userId' => 3776344,'purposeName' => '融资户','purposeId' => '2',),
        3566531 => array ('id' => 912,'userId' => 3566531,'purposeName' => '融资户','purposeId' => '2',),
        5654919 => array ('id' => 913,'userId' => 5654919,'purposeName' => '投资户','purposeId' => '1',),
        6407400 => array ('id' => 914,'userId' => 6407400,'purposeName' => '融资户','purposeId' => '2',),
        6960013 => array ('id' => 915,'userId' => 6960013,'purposeName' => '融资户','purposeId' => '2',),
        3309050 => array ('id' => 916,'userId' => 3309050,'purposeName' => '咨询户','purposeId' => '3',),
        5102609 => array ('id' => 917,'userId' => 5102609,'purposeName' => '投资户','purposeId' => '1',),
        8332882 => array ('id' => 918,'userId' => 8332882,'purposeName' => '融资户','purposeId' => '2',),
        8346009 => array ('id' => 919,'userId' => 8346009,'purposeName' => '管理户','purposeId' => '19',),
        8355675 => array ('id' => 920,'userId' => 8355675,'purposeName' => '平台户','purposeId' => '11',),
        8368640 => array ('id' => 921,'userId' => 8368640,'purposeName' => '融资户','purposeId' => '2',),
        8369024 => array ('id' => 922,'userId' => 8369024,'purposeName' => '代垫户','purposeId' => '8',),
        8369148 => array ('id' => 923,'userId' => 8369148,'purposeName' => '咨询户','purposeId' => '3',),
        8383722 => array ('id' => 924,'userId' => 8383722,'purposeName' => '融资户','purposeId' => '2',),
        12322 => array ('id' => 925,'userId' => 12322,'purposeName' => '担保户','purposeId' => '4',),
        14203 => array ('id' => 926,'userId' => 14203,'purposeName' => '投资户','purposeId' => '1',),
        14725 => array ('id' => 927,'userId' => 14725,'purposeName' => '担保户','purposeId' => '4',),
        62494 => array ('id' => 928,'userId' => 62494,'purposeName' => '担保户','purposeId' => '4',),
        152748 => array ('id' => 929,'userId' => 152748,'purposeName' => '担保户','purposeId' => '4',),
        171955 => array ('id' => 930,'userId' => 171955,'purposeName' => '咨询户','purposeId' => '3',),
        1885600 => array ('id' => 931,'userId' => 1885600,'purposeName' => '咨询户','purposeId' => '3',),
        3471815 => array ('id' => 932,'userId' => 3471815,'purposeName' => '投资户','purposeId' => '1',),
        3950865 => array ('id' => 933,'userId' => 3950865,'purposeName' => '投资户','purposeId' => '1',),
        3972753 => array ('id' => 934,'userId' => 3972753,'purposeName' => '投资户','purposeId' => '1',),
        7496931 => array ('id' => 935,'userId' => 7496931,'purposeName' => '融资户','purposeId' => '2',),
        8100469 => array ('id' => 936,'userId' => 8100469,'purposeName' => '融资户','purposeId' => '2',),
        8345686 => array ('id' => 937,'userId' => 8345686,'purposeName' => '平台户','purposeId' => '11',),
    ];
}