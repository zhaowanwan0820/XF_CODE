<?php
/**
 * 老机构用户迁移到企业用户
 */
require_once dirname(__FILE__).'/../app/init.php';
use libs\utils\Logger;
use libs\db\Db;
use core\service\UserService;
use core\dao\UserModel;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseContactModel;
use core\dao\DealAgencyModel;
use core\dao\AgencyUserModel;
use core\dao\UserCompanyModel;
use core\dao\UserBankcardModel;
use core\dao\BanklistModel;
set_time_limit(0);
ini_set('memory_limit', '1024M');
define('MIGRATE_LOG_PATH', '/tmp/enterprise/');
define('ROLLBACK_LOG_PATH', '/tmp/enterprise/rollback/');
error_reporting(E_ALL);

class EnterpriseMigrate {
    /**
     * 导出的企业列表缓存文件名
     * @var string
     */
    private $cacheFileName = 'cache_company_list.php';

    /**
     * 要迁移的机构列表
     * @var array
     */
    private $companyList = array();

    /**
     * 企业账户用途映射
     * @var array
     */
    private static $companyPurposeMap = array(
        1 => '投资',
        2 => '融资',
        3 => '咨询',
        4 => '担保',
        5 => '渠道',
        0 => '其他',
    );

    /**
     * 关联公司-营业执照号前缀
     * @var string
     */
    private $licensePrefix = '9';

    /**
     * 机构用户迁移完成之后的备注字段
     * @var string
     */
    private $companyMemo = '由手机号为[%s]的个人列表企业户迁移';

    public function __construct($companyList = array())
    {
        // 创建log目录
        self::_mkdirs(MIGRATE_LOG_PATH);
        // 要迁移的机构列表
        $this->companyList = $companyList;
        // 机构用户迁移回滚的日志文件
        $this->migrateFile = ROLLBACK_LOG_PATH . sprintf('migrate_%s.log', date('YmdHis'));
        // 当前时间
        $this->nowTime = date('Y-m-d H:i:s');
        // 开始时间
        $this->startTime = microtime(true);
    }

    /**
     * 刷新企业用户在user表的idno字段，更新为企业证件号码
     * 控制台命令： php enterprise_migrate.php refresh_enterprise_idno 1 500
     *
     */
    public function refresh_enterprise_idno($argv)
    {
        if(!isset($argv[2]) || !isset($argv[3]) || !is_numeric($argv[2]) || !is_numeric($argv[3])) {
            exit('参数错误: enterprise_migrate.php refresh_enterprise_idno 1 500' . PHP_EOL);
        }
        $count = (int)$argv[3];
        $page = ((int)$argv[2] - 1) * $count;
        $enterPriseList = EnterpriseModel::instance()->findAllViaSlave(sprintf('id > 0 AND user_id > 0 ORDER BY id ASC LIMIT %d, %d', $page, $count), true, 'id,user_id,credentials_no');
        if (!empty($enterPriseList)) {
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新企业用户idno]-start-------------------------');
            foreach ($enterPriseList as $item) {
                $userInfo = UserModel::instance()->find($item['user_id']);
                if (empty($userInfo)) {
                    self::_log('['.__METHOD__.']-['.$item['user_id'].']用户信息不存在');
                    continue;
                }

                // 检查user表的idno字段，是否已经更新成企业证件号码
                if (!empty($userInfo['idno']) && $userInfo['idno'] === $item['credentials_no']) {
                    self::_log('['.__METHOD__.']-企业['.$item['id'].']-用户['.$item['user_id'].']idno更新成企业证件号码-已经更新');
                    continue;
                }

                // 更新用户的idno字段
                $updateUserData = array(
                    'id' => $item['user_id'],
                    'idno' => $item['credentials_no'], //企业证件号码
                    'update_time' => get_gmtime(),
                );
                $userServiceObj = new UserService();
                $ret = $userServiceObj->updateInfo($updateUserData);
                if ($ret) {
                    // 记录sql-rollback用
                    self::_writeSql(ROLLBACK_LOG_PATH . 'refresh_idno.log', sprintf('UPDATE `firstp2p_user` SET `idno`=\'%s\',`update_time`=\'%s\' WHERE `id`=\'%d\';', $userInfo['idno'], $userInfo['update_time'], $item['user_id']));
                    self::_log('['.__METHOD__.']-企业['.$item['id'].']-用户['.$item['user_id'].']-旧idno['.$userInfo['idno'].']-新idno['.$item['credentials_no'].']-idno更新成企业证件号码-成功');
                }else{
                    self::_log('['.__METHOD__.']-企业['.$item['id'].']-用户['.$item['user_id'].']-旧idno['.$userInfo['idno'].']-新idno['.$item['credentials_no'].']-idno更新成企业证件号码-失败');
                }
            }
            self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[更新企业用户idno]-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end--------------');
            exit;
        }
        self::_log('['.__METHOD__.']-没有查询到符合条件的企业用户');
    }

    /**
     * 把txt文件中的机构用户数据，导成可用的数组并存入文件
     * 控制台命令： php enterprise_migrate.php export_data enterprise
     * 
     */
    public function export_data($argv)
    {
        if (empty($argv[2])) {
            exit("参数错误：enterprise_migrate.php export_data {$argv[2]}" . PHP_EOL);
        }
        // 文件全路径
        $fileName = MIGRATE_LOG_PATH . $argv[2] . '.txt';
        if(!file_exists($fileName)) {
            exit('------------------------['.$fileName.']文件不存在-------------------------' . PHP_EOL);
        }
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[导出数组文件]-start-------------------------');
        $fpr = fopen($fileName, 'r') or die("Unable to open [{$fileName}]!");
        $companyList = array();
        while (!feof($fpr)) {
            $oneLine = fgets($fpr);
            self::_log($oneLine);
            $oneItem = explode("\t", $oneLine);
            // 跳过不符合的数据
            if (!isset($oneItem[0]) || !is_numeric($oneItem[0]) || count($oneItem) < 16) {
                continue;
            }

            $companyPurpose = $companyPurposeArray = array();
            // 企业用途
            $companyPurposeArray = !empty($oneItem[3]) ? explode('、', $oneItem[3]) : array();
            // 企业用途映射关系
            $companyPurposeList = array_flip(self::$companyPurposeMap);
            foreach ($companyPurposeArray as $item) {
                isset($companyPurposeList[$item]) && $companyPurpose[] = $companyPurposeList[$item];
            }
            // 法定代表人证件类别
            if (!empty($GLOBALS['dict']['ID_TYPE'])) {
                $companyPurposeArray = array_flip($GLOBALS['dict']['ID_TYPE']);
                $companyPurposeArray['身份证'] = $companyPurposeArray['内地居民身份证'];
                $companyPurposeArray['台胞证'] = $companyPurposeArray['台湾居民往来大陆通行证'];
                $companyPurposeArray['香港身份证'] = $companyPurposeArray['其他'];
            }

            // 企业用户UID
            $companyUserId = trim($oneItem[0]);
            $companyList[$companyUserId] = array(
                'companyUserId' => $companyUserId, // 企业用户UID
                'companyUserName' => trim($oneItem[1]), // 企业用户名
                'companyName' => trim($oneItem[2]), // 企业名称
                'companyPurpose' => $companyPurpose, // 企业用途
                'companyBelong' => trim($oneItem[4]), // 归属方
                'agentUserId' => (!empty($oneItem[5]) && $oneItem[5] != '无') ? trim($oneItem[5]) : '', // 代理人ID
                'credentialsExpireDate' => !empty($oneItem[6]) ? date('Y-m-d', strtotime(trim($oneItem[6]))) : '', // 企业证件有效期起始
                'credentialsExpireAt' => !empty($oneItem[7]) ? date('Y-m-d', strtotime(trim($oneItem[7]))) : '', // 企业证件有效期终止
                'legalbodyName' => trim($oneItem[8]), // 法定代表人姓名
                'legalbodyCredentialsType' => isset($companyPurposeArray[$oneItem[9]]) ? $companyPurposeArray[$oneItem[9]] : $companyPurposeArray['其他'], // 法定代表人证件类别
                'legalbodyCredentialsNo' => trim($oneItem[10]), // 法定代表人证件号码
                'legalbodyMobile' => trim($oneItem[11]), // 法定代表人手机号
                'majorName' => (!empty($oneItem[12]) && $oneItem[12] === '可自动抓取') ? 'auto' : trim($oneItem[12]), // 企业账户负责人姓名
                'majorCondentialsType' => (!empty($oneItem[13]) && $oneItem[13] === '可自动抓取') ? 'auto' : (isset($companyPurposeArray[$oneItem[13]]) ? $companyPurposeArray[$oneItem[13]] : $companyPurposeArray['其他']), // 企业账户负责人证件类别
                'majorCondentialsNo' => (!empty($oneItem[14]) && $oneItem[14] === '可自动抓取') ? 'auto' : trim($oneItem[14]), // 企业账户负责人证件号码
                'majorMobile' => (!empty($oneItem[15]) && $oneItem[15] === '可自动抓取') ? 'auto' : trim($oneItem[15]), // 企业账户负责人手机号
                'memo' => trim($oneItem[16]), // 备注
            );
        }
        fclose($fpr);
        // 写入文件
        $content = '<?php return ' . var_export($companyList, true) . ';';
        file_put_contents(MIGRATE_LOG_PATH . $this->cacheFileName, $content, LOCK_EX);
        clearstatcache();
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-[导出数组文件]-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-------------------------');
    }

    /**
     * 机构用户迁移
     * 控制台命令： php enterprise_migrate.php migrate
     * 
     */
    public function migrate($argv) {
        if (file_exists(MIGRATE_LOG_PATH . $this->cacheFileName)) {
            $this->companyList = include(MIGRATE_LOG_PATH . $this->cacheFileName);
        }
        $userId = isset($argv[2]) ? $argv[2] : 0;
        // 需要迁移的机构用户数量
        $totalNum = count($this->companyList);
        $successNum = $failNum = 0;
        $logMsg = '------------------------['.$this->nowTime.']['.__METHOD__.']-[机构用户迁移]-start-------------------------';
        self::_log($logMsg);
        self::_log($logMsg, 'errorlog');
        if (!empty($this->companyList)) {
            if ($userId > 0 && !empty($this->companyList[$userId])) {
                // 迁移单个机构用户到企业用户
                $this->_company_move($this->companyList[$userId], $successNum, $failNum);
            } else {
                foreach ($this->companyList as $item) {
                    self::_log('---------------------------机构用户UID['.$item['companyUserId'].']-start---------------------------');
                    // 仅迁移指定机构用户
                    if (empty($item['companyUserId'])) {
                        continue;
                    }
                    // 迁移机构用户到企业用户
                    $this->_company_move($item, $successNum, $failNum);
                    self::_log('---------------------------机构用户UID['.$item['companyUserId'].']-end---------------------------');
                }
            }
        }else{
            //暂无需要迁移的
            self::_log('------------------------[待迁移的机构用户列表为空，无法进行迁移]-start-------------------------');
        }
        $logMsg = '------------------------['.$this->nowTime.']['.__METHOD__.']-[机构用户迁移]-[迁移总数-'.$totalNum.']-[迁移成功-'.$successNum.']-[迁移失败-'.$failNum.']-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-------------------------';
        self::_log($logMsg);
        self::_log($logMsg, 'errorlog');
    }

    /**
     * 根据记录的回滚sql，进行回滚处理
     * 控制台命令： 
     *     php enterprise_migrate.php rollback_enterprise refresh_idno
     *     php enterprise_migrate.php rollback_enterprise migrate_20161025114002
     *     php enterprise_migrate.php rollback_enterprise rollback_error
     *     php enterprise_migrate.php rollback_enterprise rollback_exception
     *
     */
    public function rollback_enterprise($argv)
    {
        if(!isset($argv[2]) || empty($argv[2])) {
            exit('参数错误: enterprise_migrate.php rollback_enterprise [refresh_idno|migrate|rollback_error|rollback_exception]' . PHP_EOL);
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
                self::_log('['.$oneLine . ']-'.$msg.'-' . $queryMsg);
            } catch (\Exception $e) {
                self::_writeSql(ROLLBACK_LOG_PATH . 'rollback_exception.log', $oneLine);
                self::_log('['.$oneLine.']|'.$e->getMessage());
            }
        }
        fclose($fpr);
        self::_log('------------------------['.$this->nowTime.']['.__METHOD__.']-'.$msg.'-[耗时:'.round(microtime(true) - $this->startTime, 4).']-end-----------------');
    }

    /**
     * 迁移机构用户到企业用户
     * @param array $item
     */
    private function _company_move($item, &$successNum, &$failNum) {
        // 开启事务
        $db = Db::getInstance('firstp2p');
        // 事务开始时间
        $this->transactionStartTime = microtime(true);
        $db->startTrans();
        $commitSql = '';
        $rollbackSql = '';
        try {
            // 查询该用户的信息
            $userInfo = UserModel::instance()->find($item['companyUserId']);
            if (empty($userInfo)) {
                ++$failNum;
                throw new \Exception('用户信息不存在，迁移失败');
            }
            // 会员类型检查，避免重复迁移
            if (!empty($userInfo['user_type']) && $userInfo['user_type'] == UserModel::USER_TYPE_ENTERPRISE) {
                ++$successNum;
                throw new \Exception('已经迁移完毕');
            }

            // 企业证件号码
            $credentialsNo = '';
            // 企业证件类型
            $credentialsType = 1;
            // 帐号不是融资账户（咨询/担保/渠道/投资账户）
            // 根据账户用途，查询机构用户的证件类型、证件号码
            if (!in_array(EnterpriseModel::COMPANY_PURPOSE_FINANCE, $item['companyPurpose'])) {
                // 咨询/担保账户
                // 获取该机构用户在[贷款管理]-[机构列表]的数据
                $dealAgencyList = DealAgencyModel::instance()->findAllViaSlave('user_id=:user_id', true, 'id,name,user_id,license', array(':user_id' => $userInfo['id']));
                if (empty($dealAgencyList)) {
                    ++$failNum;
                    throw new \Exception('咨询/担保账户-获取[firstp2p_deal_agency]机构列表数据失败，迁移失败');
                }
                // 当有机构用户有多个用途时
                if (count($dealAgencyList) > 1) {
                    foreach ($dealAgencyList as $agencyItem) {
                        // 检查营业执照号是否为空、是否一致
                        foreach ($dealAgencyList as $agencyItem2) {
                            if (empty($agencyItem['license']) || empty($agencyItem2['license']) || $agencyItem['license'] != $agencyItem2['license']) {
                                ++$failNum;
                                throw new \Exception('担保机构ID['.$agencyItem['id'].']-担保机构名称['.$agencyItem['name'].']-咨询/担保账户[营业执照号]为空或不一致，迁移失败');
                            }
                        }
                        // 营业执照号
                        empty($credentialsNo) && $credentialsNo = $agencyItem['license'];
                    }
                } else {
                    if (!isset($dealAgencyList[0]['license']) || empty($dealAgencyList[0]['license'])) {
                        ++$failNum;
                        throw new \Exception('咨询/担保账户[营业执照号]为空，迁移失败');
                    }
                    // 营业执照号
                    $credentialsNo = $dealAgencyList[0]['license'];
                }

                // 贷款管理-机构列表-编辑-机构确认帐号修改为机构用户的[会员名称]
                foreach ($dealAgencyList as $agencyItem) {
                    $agencyUserTableName = AgencyUserModel::instance()->tableName();
                    // 查询所有的担保人信息-rollback用
                    $agencyUserList = AgencyUserModel::instance()->findAllViaSlave('agency_id=:agency_id', true, 'user_id,user_name,agency_id', array(':agency_id' => $agencyItem['id']));
                    if (empty($agencyUserList)) {
                        ++$failNum;
                        throw new \Exception('担保机构ID['.$agencyItem['id'].']-担保机构名称['.$agencyItem['name'].']-咨询/担保账户-获取[firstp2p_agency_user]记录失败，迁移失败');
                    }

                    if (count($agencyUserList) > 1) {
                        // 先根据担保机构的ID，删除所有担保人的信息
                        $sql = sprintf('DELETE FROM `%s` WHERE `agency_id` = \'%d\'', $agencyUserTableName, $agencyItem['id']);
                        $deleteAgencyUserRet = $db->query($sql);
                        if (!$deleteAgencyUserRet) {
                            ++$failNum;
                            throw new \Exception('担保机构ID['.$agencyItem['id'].']-担保机构名称['.$agencyItem['name'].']-咨询/担保账户-['.$sql.']-存在多个担保人-删除[firstp2p_agency_user]记录失败，迁移失败');
                        }
                        // commit用
                        $commitSql .= $sql . PHP_EOL;

                        // 新插入一条担保人的信息
                        $sql = sprintf('INSERT INTO `%s`(`user_id`,`user_name`,`agency_id`) VALUES(\'%s\', \'%s\', \'%d\');', $agencyUserTableName, $userInfo['id'], $userInfo['user_name'], $agencyItem['id']);
                        $insertAgencyUserRet = $db->query($sql);
                        if (!$insertAgencyUserRet) {
                            ++$failNum;
                            throw new \Exception('担保机构ID['.$agencyItem['id'].']-担保机构名称['.$agencyItem['name'].']-咨询/担保账户-存在多个担保人-创建[firstp2p_agency_user]记录失败，迁移失败');
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
                        $sql = sprintf('UPDATE `%s` SET `user_id`=\'%s\', `user_name`=\'%s\' WHERE `agency_id`=\'%d\';', $agencyUserTableName, $userInfo['id'], $userInfo['user_name'], $agencyItem['id']);
                        $updateAgencyUserRet = $db->query($sql);
                        if (!$updateAgencyUserRet) {
                            ++$failNum;
                            throw new \Exception('担保机构ID['.$agencyItem['id'].']-担保机构名称['.$agencyItem['name'].']-咨询/担保账户-['.$sql.']-只有1个担保人-更新[firstp2p_agency_user]记录失败，迁移失败');
                        }
                        // commit用
                        $commitSql .= $sql . PHP_EOL;

                        // rollback用
                        $rollbackSql .= sprintf('UPDATE `%s` SET `user_id`=\'%s\', `user_name`=\'%s\' WHERE `agency_id`=\'%d\';', $agencyUserTableName, $agencyUserList[0]['user_id'], $agencyUserList[0]['user_name'], $agencyItem['id']) . PHP_EOL;
                    }
                }
                // 营业执照号码以9开头，否则取该机构的证件号码
                if (!empty($credentialsNo) && substr($credentialsNo, 0, 1) == $this->licensePrefix) {
                    $credentialsType = 3; // 类型-三证合一
                }else{
                    $credentialsType = 1; // 类型-营业执照
                }
            } else {
                // 融资账户
                $credentialsNo = $userInfo['idno'];
                $credentialsType = 1; // 类型-营业执照
                // 获取该机构用户的代理人-关联公司的数据
                if (!empty($item['agentUserId'])) {
                    $userCompanyModel = new UserCompanyModel();
                    $userCompanyInfo = $userCompanyModel->findByUserId($item['agentUserId']);
                    // 营业执照号码以9开头，否则取该机构的证件号码
                    if (!empty($userCompanyInfo['license']) && substr($userCompanyInfo['license'], 0, 1) == $this->licensePrefix) {
                        $credentialsNo = $userCompanyInfo['license'];
                        $credentialsType = 3; // 类型-三证合一
                    }
                }
            }

            // 迁移之前，校验几个关键数据
            if (empty($userInfo['real_name']) || empty($credentialsNo) || empty($item['legalbodyName'])
                || empty($item['legalbodyCredentialsNo']) || empty($item['legalbodyMobile']) || empty($userInfo['email'])) {
                ++$failNum;
                throw new \Exception('企业全称|企业证件号码|法人姓名|法人证件号码|法人手机号|法人邮箱不能为空，迁移失败');
            }

            // 当前时间
            $timestamp = get_gmtime();
            // 更新绑卡表数据
            $userBankCardInfo = UserBankcardModel::instance()->getOneCardByUser($userInfo['id']);
            if (!empty($userBankCardInfo)) {
                if (!empty($userBankCardInfo['bankzone'])) {
                    // 获取银行联行号码
                    $bankInfo = BanklistModel::instance()->findBy('name = \':name\'', 'bank_id', array(':name' => $userBankCardInfo['bankzone']));
                    if (!empty($bankInfo)) {
                        // 更新绑卡表的联行号码
                        $sql = sprintf('UPDATE `firstp2p_user_bankcard` SET `branch_no`=\'%s\', `update_time`=\'%s\' WHERE `id`=\'%d\' AND `user_id`=\'%d\';', $bankInfo['bank_id'], $timestamp, $userBankCardInfo['id'], $userInfo['id']);
                        $updateUserBankCardRet = $db->query($sql);
                        if (!$updateUserBankCardRet) {
                            ++$failNum;
                            throw new \Exception('绑卡ID['.$userBankCardInfo['id'].']-开户网点['.$userBankCardInfo['bankzone'].']-['.$sql.']-更新[firstp2p_user_bankcard]记录失败，迁移失败');
                        }
                        // commit用
                        $commitSql .= $sql . PHP_EOL;
                        // rollback用
                        $rollbackSql .= sprintf('UPDATE `firstp2p_user_bankcard` SET `branch_no`=\'%s\', `update_time`=\'%s\' WHERE `id`=\'%d\' AND `user_id`=\'%d\';', $userBankCardInfo['branch_no'], $userBankCardInfo['update_time'], $userBankCardInfo['id'], $userInfo['id']) . PHP_EOL;
                    }else{
                        $errorMsg = '['.__METHOD__.']-机构用户ID['.$item['companyUserId'].']-机构会员名称['.$item['companyUserName'].']-企业名称['.$item['companyName'].']-绑卡ID['.$userBankCardInfo['id'].']-开户网点['.$userBankCardInfo['bankzone'].']-获取[firstp2p_banklist]的联行号码失败，需要业务线下处理，迁移继续';
                        self::_log($errorMsg);
                        self::_log($errorMsg, 'errorlog');
                    }
                }else{
                    $errorMsg = '['.__METHOD__.']-机构用户ID['.$item['companyUserId'].']-机构会员名称['.$item['companyUserName'].']-企业名称['.$item['companyName'].']-绑卡ID['.$userBankCardInfo['id'].']-开户网点为空，需要业务线下处理，迁移继续';
                    self::_log($errorMsg);
                    self::_log($errorMsg, 'errorlog');
                }
            }else{
                $errorMsg = '['.__METHOD__.']-机构用户ID['.$item['companyUserId'].']-机构会员名称['.$item['companyUserName'].']-企业名称['.$item['companyName'].']-获取[firstp2p_user_bankcard]的绑卡记录为空，无需处理，迁移继续';
                self::_log($errorMsg);
                self::_log($errorMsg, 'errorlog');
            }

            // 录入企业基本信息表
            $enterpriseModel = new EnterpriseModel();
            $enterpriseModel->user_id = $userInfo['id']; // 企业用户UID
            $enterpriseModel->company_purpose = is_array($item['companyPurpose']) ? join(',', $item['companyPurpose']) : ''; // 企业账户用途
            $enterpriseModel->company_name = $userInfo['real_name']; // 企业全称
            $enterpriseModel->company_shortname = $userInfo['real_name']; // 企业简称
            $enterpriseModel->credentials_type = $credentialsType; // 企业证件类型
            $enterpriseModel->credentials_no = $credentialsNo; // 企业证件号码
            $enterpriseModel->credentials_expire_date = $item['credentialsExpireDate']; // 企业证件有效期起始
            $enterpriseModel->credentials_expire_at = $item['credentialsExpireAt']; // 企业证件有效期终止
            $enterpriseModel->legalbody_name = $item['legalbodyName']; // 法定代表人姓名
            $enterpriseModel->legalbody_credentials_type = $item['legalbodyCredentialsType']; // 法定代表人证件类别
            $enterpriseModel->legalbody_credentials_no = $item['legalbodyCredentialsNo']; // 法定代表人证件号码
            $enterpriseModel->legalbody_mobile_code = '86'; // 法定代表人手机号码区号
            $enterpriseModel->legalbody_mobile = $item['legalbodyMobile']; // 法定代表人手机号
            $enterpriseModel->legalbody_email = $userInfo['email']; // 法定代表人邮箱
            $enterpriseModel->registration_region = '1,2,52,500'; // 企业注册地址-省市区(中国/北京/北京/东城区)
            $enterpriseModel->registration_address = $userInfo['address']; // 企业注册地址-详细地址
            $enterpriseModel->memo = sprintf($this->companyMemo, $userInfo['mobile']); // 备注字段
            $enterpriseModel->create_time = $userInfo['create_time']; // 创建时间
            $enterRet = $enterpriseModel->insert();
            if (!$enterRet) {
                ++$failNum;
                throw new \Exception('创建企业用户基本信息失败，迁移失败');
            }
            $sql = sprintf('INSERT INTO `firstp2p_enterprise`(`user_id`,`company_purpose`,`company_name`,`company_shortname`,
                `credentials_type`,`credentials_no`,`credentials_expire_date`,`credentials_expire_at`,legalbody_name`,
                `legalbody_credentials_type`,`legalbody_credentials_no`,`legalbody_mobile_code`,`legalbody_mobile`,
                `legalbody_email`,`registration_region`,`registration_address`,`memo`,`create_time`) VALUES(\'%s\',\'%s\',\'%s\',
                \'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');',
                $enterpriseModel->user_id, $enterpriseModel->company_purpose, $enterpriseModel->company_name,
                $enterpriseModel->company_shortname, $enterpriseModel->credentials_type, $enterpriseModel->credentials_no,
                $enterpriseModel->credentials_expire_date,$enterpriseModel->credentials_expire_at,$enterpriseModel->legalbody_name,
                $enterpriseModel->legalbody_credentials_type, $enterpriseModel->legalbody_credentials_no,$enterpriseModel->legalbody_mobile_code,
                $enterpriseModel->legalbody_mobile,$enterpriseModel->legalbody_email,$enterpriseModel->registration_region,
                $enterpriseModel->registration_address,$enterpriseModel->memo, $enterpriseModel->create_time);
            // commit用
            $commitSql .= $sql . PHP_EOL;
            // rollback用
            $rollbackSql .= sprintf('DELETE FROM `%s` WHERE user_id = \'%s\';', 'firstp2p_enterprise', $userInfo['id']) . PHP_EOL;

            // 获取机构用户代理人的用户信息
            $agentUserInfo = array();
            if (!empty($item['agentUserId'])) {
                $agentUserInfo = UserModel::instance()->find($item['agentUserId']);
            }
            // 代理人手机号区号
            $agentUserMobileCode = !empty($agentUserInfo['mobile_code']) ? $agentUserInfo['mobile_code'] : '86';
            // 企业用户负责人手机区号
            $majorMobileCode = !empty($item['agentUserId']) ? $agentUserMobileCode : '86';
            // 企业账户负责人手机号码
            $majorMobile = !empty($item['agentUserId']) ? (!empty($agentUserInfo['mobile']) ? $agentUserInfo['mobile'] : '') : $item['majorMobile'];
            // 接受短信通知号码(法人手机号+账户负责人手机号)
            $receiveMsgMobileList = array();
            strlen($enterpriseModel->legalbody_mobile) > 0 && $receiveMsgMobileList[$enterpriseModel->legalbody_mobile_code . '-' . $enterpriseModel->legalbody_mobile] = 1;
            strlen($majorMobile) > 0 && $receiveMsgMobileList[$majorMobileCode . '-' . $majorMobile] = 1;
            // 录入企业联系人信息表
            $enterpriseContactModel = new EnterpriseContactModel();
            $enterpriseContactModel->user_id = $userInfo['id'];// 企业用户UID
            $enterpriseContactModel->major_name = !empty($item['agentUserId']) ? (!empty($agentUserInfo['real_name']) ? $agentUserInfo['real_name'] : '') : $item['majorName']; // 企业账户负责人姓名
            $enterpriseContactModel->major_condentials_type = !empty($item['agentUserId']) ? (!empty($agentUserInfo['id_type']) ? $agentUserInfo['id_type'] : 1) : $item['majorCondentialsType']; // 企业用户负责人证件类别
            $enterpriseContactModel->major_condentials_no = !empty($item['agentUserId']) ? (!empty($agentUserInfo['idno']) ? $agentUserInfo['idno'] : '') : $item['majorCondentialsNo']; // 企业用户负责人证件号码
            $enterpriseContactModel->major_mobile_code = $majorMobileCode; // 企业用户负责人手机区号
            $enterpriseContactModel->major_mobile = $majorMobile; // 企业用户负责人手机号码
            $enterpriseContactModel->receive_msg_mobile = join(',', array_keys($receiveMsgMobileList)); // 接收短信通知号码
            $enterpriseContactModel->create_time = $userInfo['create_time']; // 创建时间
            $enterContractRet = $enterpriseContactModel->insert();
            if (!$enterContractRet) {
                ++$failNum;
                throw new \Exception('创建企业用户联系人信息失败，迁移失败');
            }
            $sql = sprintf('INSERT INTO `firstp2p_enterprise_contact`(`user_id`,`major_name`,`major_condentials_type`,
                `major_condentials_no`,`major_mobile_code`,`major_mobile`,`receive_msg_mobile`,`create_time`) 
                VALUES(\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\',\'%s\');',
                $enterpriseContactModel->user_id, $enterpriseContactModel->major_name, $enterpriseContactModel->major_condentials_type,
                $enterpriseContactModel->major_condentials_no, $enterpriseContactModel->major_mobile_code, $enterpriseContactModel->major_mobile,
                $enterpriseContactModel->receive_msg_mobile, $enterpriseContactModel->create_time);
            // commit用
            $commitSql .= $sql . PHP_EOL;
            // rollback用
            $rollbackSql .= sprintf('DELETE FROM `%s` WHERE user_id = \'%s\';', 'firstp2p_enterprise_contact', $userInfo['id']) . PHP_EOL;

            // 更新user表的user_type为企业用户
            $updateUserData = array(
                'id' => $userInfo['id'],
                //'idno' => $enterpriseModel->credentials_no, //企业证件号码
                'user_type' => UserModel::USER_TYPE_ENTERPRISE, //企业用户
                'update_time' => $timestamp,
            );
            $userServiceObj = new UserService();
            $userRet = $userServiceObj->updateInfo($updateUserData);
            if (!$userRet) {
                ++$failNum;
                throw new \Exception('更新firstp2p_user[idno/user_type]失败，迁移失败');
            }
            $sql = sprintf('UPDATE `firstp2p_user` SET `user_type` = \'%d\',`update_time`=\'%s\' WHERE id = \'%s\';', UserModel::USER_TYPE_ENTERPRISE, $timestamp, $userInfo['id']);
            // commit用
            $commitSql .= $sql . PHP_EOL;
            // rollback用
            $rollbackSql .= sprintf('UPDATE `firstp2p_user` SET `user_type` = \'%d\',`update_time`=\'%s\' WHERE id = \'%s\';', UserModel::USER_TYPE_NORMAL, $userInfo['update_time'], $userInfo['id']);

            // 提交事务
            $db->commit();
            ++$successNum;
            // 记录sql-commit用
            self::_log($commitSql);
            // 记录日志
            self::_log('['.__METHOD__.']-机构用户ID['.$userInfo['id'].']-机构会员名称['.$userInfo['user_name'].']-机构名称['.$userInfo['real_name'].']-[事务耗时:'.round(microtime(true) - $this->transactionStartTime, 4).']-迁移完成');
            // 记录sql-rollback用
            self::_writeSql($this->migrateFile, $rollbackSql);
        } catch (\Exception $e) {
            // 回滚事务
            $db->rollback();
            // 记录日志
            $errMsg = '['.__METHOD__.']-机构用户ID['.$item['companyUserId'].']-机构会员名称['.$item['companyUserName'].']-企业名称['.$item['companyName'].']-[事务耗时:'.round(microtime(true) - $this->transactionStartTime, 4).']-' . $e->getMessage();
            self::_log($errMsg);
            self::_log($errMsg, 'errorlog');
        }
    }

    /**
     * 记录日志
     * @param string $message
     * @param string $filePrefix
     */
    private static function _log($message, $filePrefix = 'log', $path = '', $suffix = '') {
        echo $message . PHP_EOL;
        $path = empty($path) ? MIGRATE_LOG_PATH : $path;
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
// 导出的企业列表
$companyList = queryShowCompanyList();

// 执行具体方法
$obj = new EnterpriseMigrate($companyList);
if (!method_exists($obj, $argv[1])) {
    die("method:{$argv[1]} is not found." . PHP_EOL);
}
if (!is_callable(array($obj, $argv[1]))) {
    die("method:{$argv[1]} is access forbidden." . PHP_EOL);
}
$obj->$argv[1]($argv);


/**
 * 需要迁移的机构用户数组
 */
function queryShowCompanyList() {
    return array (
      4164 =>
      array (
        'companyUserId' => '4164',
        'companyUserName' => 'jg_lhdb',
        'companyName' => '联合创业担保集团有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4641',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-05-07',
        'legalbodyName' => '刘平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204197210115816',
        'legalbodyMobile' => '13826537775',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4165 =>
      array (
        'companyUserId' => '4165',
        'companyUserName' => 'jg_kydb',
        'companyName' => '北京联合开元融资担保有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1394226',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '韩光磊',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '230107197308190213',
        'legalbodyMobile' => '13591199461',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4169 =>
      array (
        'companyUserId' => '4169',
        'companyUserName' => 'jg_hyjk',
        'companyName' => '北京汇源先锋资本控股有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '789',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-07-24',
        'legalbodyName' => '李焕香',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '120112197103163321',
        'legalbodyMobile' => '18910571888',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4170 =>
      array (
        'companyUserId' => '4170',
        'companyUserName' => 'jg_ccyh',
        'companyName' => '北京盈华财富投资管理股份有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
          1 => 5,
        ),
        'companyBelong' => '资产管理部、资金管理部',
        'agentUserId' => '1882',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2041-04-10',
        'legalbodyName' => '刘苗苗',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204198110275665',
        'legalbodyMobile' => '13998460205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4172 =>
      array (
        'companyUserId' => '4172',
        'companyUserName' => 'jg_lcdb',
        'companyName' => '联合创业担保集团有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4641',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-05-07',
        'legalbodyName' => '刘平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204197210115816',
        'legalbodyMobile' => '13826537775',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4263 =>
      array (
        'companyUserId' => '4263',
        'companyUserName' => 'jg_hyjkdb',
        'companyName' => '北京汇源先锋资本控股有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '789',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-07-24',
        'legalbodyName' => '李焕香',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '120112197103163321',
        'legalbodyMobile' => '18910571888',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      11269 =>
      array (
        'companyUserId' => '11269',
        'companyUserName' => 'jg_dyfd',
        'companyName' => '深圳壹房壹贷信息技术服务有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '736107',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2044-03-12',
        'legalbodyName' => '沈剑',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '413001197511202526',
        'legalbodyMobile' => '13601357839',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      11780 =>
      array (
        'companyUserId' => '11780',
        'companyUserName' => 'jg_jdth',
        'companyName' => '北京京都天和投资咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '338',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2028-10-21',
        'legalbodyName' => '王立峰',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '15232619721208002X',
        'legalbodyMobile' => '13910270998',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      12381 =>
      array (
        'companyUserId' => '12381',
        'companyUserName' => 'jg_xfgt',
        'companyName' => '北京泓实资产管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '8892',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2022-07-05',
        'legalbodyName' => '曲璐',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '371002198501020522',
        'legalbodyMobile' => '15001370804',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      12866 =>
      array (
        'companyUserId' => '12866',
        'companyUserName' => 'jg_yltz',
        'companyName' => '耀莱投资',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '12858',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2028-10-26',
        'legalbodyName' => '綦建虹',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101196705282017',
        'legalbodyMobile' => '13681595903',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      14731 =>
      array (
        'companyUserId' => '14731',
        'companyUserName' => 'jg_shwtwx',
        'companyName' => '上海外滩网信互联网金融信息服务有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
          2 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '18490',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-11-28',
        'legalbodyName' => '冯雪青',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '432923197804280384',
        'legalbodyMobile' => '18601080555',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      34422 =>
      array (
        'companyUserId' => '34422',
        'companyUserName' => 'jg_szxfcy',
        'companyName' => '深圳先锋产业金融发展有限公司北京分公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '20012',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '郭海霞',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410303197603040524',
        'legalbodyMobile' => '13910605329',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      46774 =>
      array (
        'companyUserId' => '46774',
        'companyUserName' => '北京汇源饮料食品集团有限公司',
        'companyName' => '北京汇源饮料食品集团有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2032201',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2051-06-27',
        'legalbodyName' => '朱燕彤',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101196906164517',
        'legalbodyMobile' => '18611028215',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      62287 =>
      array (
        'companyUserId' => '62287',
        'companyUserName' => 'jg_gdxfwl',
        'companyName' => '广东先锋物流投资咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
          2 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '51145',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '刘平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204197210115816',
        'legalbodyMobile' => '15626154081',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      142392 =>
      array (
        'companyUserId' => '142392',
        'companyUserName' => 'jg_zgrzzlyxgs',
        'companyName' => '中国融资租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
          2 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '7203',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2018-12-04',
        'legalbodyName' => '张利群',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '15020419730518181X',
        'legalbodyMobile' => '18612027859',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      152748 =>
      array (
        'companyUserId' => '152748',
        'companyUserName' => 'jg_zcdlc',
        'companyName' => '联合创业担保集团有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '672',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-05-07',
        'legalbodyName' => '刘平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204197210115816',
        'legalbodyMobile' => '13826537775',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      155721 =>
      array (
        'companyUserId' => '155721',
        'companyUserName' => 'jg_jlblc',
        'companyName' => '联合创业担保集团有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '7989',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-05-07',
        'legalbodyName' => '刘平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204197210115816',
        'legalbodyMobile' => '13826537775',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      170674 =>
      array (
        'companyUserId' => '170674',
        'companyUserName' => 'jg_dflhkj',
        'companyName' => '北京东方联合科技有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '6995',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-06-18',
        'legalbodyName' => '王宇宁',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101197810130034',
        'legalbodyMobile' => '13901396210',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      171955 =>
      array (
        'companyUserId' => '171955',
        'companyUserName' => 'jg_zfbl',
        'companyName' => '上海中锋商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '153433',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2042-12-16',
        'legalbodyName' => '李煜宜',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '610321198205240429',
        'legalbodyMobile' => '15921420800',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      308706 =>
      array (
        'companyUserId' => '308706',
        'companyUserName' => 'jg_szbh',
        'companyName' => '深圳百禾商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '12721',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '岳振华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '513030198806030012',
        'legalbodyMobile' => '13656669898',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      422535 =>
      array (
        'companyUserId' => '422535',
        'companyUserName' => 'jg_fhzc',
        'companyName' => '凤凰资产管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
          2 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '8005',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2019-01-14',
        'legalbodyName' => '赵苗苗',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110102198009102346',
        'legalbodyMobile' => '13621106060',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      716197 =>
      array (
        'companyUserId' => '716197',
        'companyUserName' => 'jg_dzhh',
        'companyName' => '山东岱宗会资产管理股份有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
          2 => 5,
          3 => 1,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '7927',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '郭雷',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '230303197711054321',
        'legalbodyMobile' => '13591812736',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      777261 =>
      array (
        'companyUserId' => '777261',
        'companyUserName' => 'jg_sxrt',
        'companyName' => '陕西荣投信息科技有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '681638',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '龙毅涛',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '621521197404300576',
        'legalbodyMobile' => '13991367450',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      801068 =>
      array (
        'companyUserId' => '801068',
        'companyUserName' => 'jg_shdy',
        'companyName' => '北京盛辉鼎业投资管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5411',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2042-04-26',
        'legalbodyName' => '王总',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '220702198006200221',
        'legalbodyMobile' => '13581770882',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      872401 =>
      array (
        'companyUserId' => '872401',
        'companyUserName' => 'jg_xarh',
        'companyName' => '西安荣华集团有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '360629',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '崔荣华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '610102196010091225',
        'legalbodyMobile' => '13909219363',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      1242123 =>
      array (
        'companyUserId' => '1242123',
        'companyUserName' => 'jg_wxkg',
        'companyName' => '五星控股集团有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1125165',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '汪建国',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '320106196007241655',
        'legalbodyMobile' => '18963609768',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      1264162 =>
      array (
        'companyUserId' => '1264162',
        'companyUserName' => 'jg_dfjh',
        'companyName' => '北京东方佳禾投资管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '509980',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2019-05-13',
        'legalbodyName' => '李硕',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101198001092095',
        'legalbodyMobile' => '13911086036',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      1367869 =>
      array (
        'companyUserId' => '1367869',
        'companyUserName' => 'jg_qt_dzlhdl',
        'companyName' => '北京大众联合投资管理有限公司大连分公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '8726',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2018-08-13',
        'legalbodyName' => '孙颖',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210202197310180102',
        'legalbodyMobile' => '13500769799',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      1370483 =>
      array (
        'companyUserId' => '1370483',
        'companyUserName' => 'jg_rz_qfkd',
        'companyName' => '北京全峰快递有限责任公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1348684',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2030-11-29',
        'legalbodyName' => '陈加海',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '341102197610086278',
        'legalbodyMobile' => '13901768599',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      1396566 =>
      array (
        'companyUserId' => '1396566',
        'companyUserName' => 'jg_rz_zshq',
        'companyName' => '中视环球汽车赛事管理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '398743',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2042-06-11',
        'legalbodyName' => '汪超涌',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '11010819650126141x',
        'legalbodyMobile' => '13810764344',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      1414910 =>
      array (
        'companyUserId' => '1414910',
        'companyUserName' => 'jg_shht',
        'companyName' => '江苏商户通资本控股有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 5,
        ),
        'companyBelong' => '资产管理部、项目管理部（分站）',
        'agentUserId' => '2556743',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '郭海霞',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410303197603040524',
        'legalbodyMobile' => '13910605329',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      1435945 =>
      array (
        'companyUserId' => '1435945',
        'companyUserName' => 'jg_htd',
        'companyName' => '汇通达网络有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '280760',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '徐秀贤',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '320106196303051231',
        'legalbodyMobile' => '15996318484',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2134075 =>
      array (
        'companyUserId' => '2134075',
        'companyUserName' => 'jg_jbr',
        'companyName' => '北京京宝融投资担保有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6389328',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2031-09-26',
        'legalbodyName' => '杨和平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110110195509191513',
        'legalbodyMobile' => '13501231632',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2381627 =>
      array (
        'companyUserId' => '2381627',
        'companyUserName' => 'jg_rz_qdhy',
        'companyName' => '青岛国际海洋产权交易中心股份有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5551',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '薛传星',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '370211197411231010',
        'legalbodyMobile' => '13854216069',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2466359 =>
      array (
        'companyUserId' => '2466359',
        'companyUserName' => 'jg_rz_hlcy',
        'companyName' => '黑龙江汇良餐饮投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5907',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '王永彬',
        'legalbodyCredentialsType' => 6,
        'legalbodyCredentialsNo' => '220102196806243375',
        'legalbodyMobile' => '15164039753',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2576096 =>
      array (
        'companyUserId' => '2576096',
        'companyUserName' => 'jg_rz_hln',
        'companyName' => '深圳市火烈鸟网络科技有限责任公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2351339',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '唐昆',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210602197201093570',
        'legalbodyMobile' => '13302936229',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2577900 =>
      array (
        'companyUserId' => '2577900',
        'companyUserName' => 'jg_rz_syy',
        'companyName' => '深圳市烁艺洋国际货运代理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '7787',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '唐景谊',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440301197610193628',
        'legalbodyMobile' => '13823397802',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2604285 =>
      array (
        'companyUserId' => '2604285',
        'companyUserName' => 'jg_rz_bhbl',
        'companyName' => '深圳百禾商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '12721',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '岳振华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '513030198806030012',
        'legalbodyMobile' => '13656669898',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2611088 =>
      array (
        'companyUserId' => '2611088',
        'companyUserName' => 'jg_rz_mfzb',
        'companyName' => '深圳美福珠宝饰品投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '82787',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '王威',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210802197806151041',
        'legalbodyMobile' => '13656669898',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2611153 =>
      array (
        'companyUserId' => '2611153',
        'companyUserName' => 'jg_rz_yczb',
        'companyName' => '深圳元昌珠宝饰品有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '7502',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2052-11-20',
        'legalbodyName' => '任冬宁',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510902197211019515',
        'legalbodyMobile' => '18319039553',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2611193 =>
      array (
        'companyUserId' => '2611193',
        'companyUserName' => 'jg_rz_jyhj',
        'companyName' => '深圳嘉盈黄金制品有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1036703',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张卫东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510721196906119313',
        'legalbodyMobile' => '13990166566',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2625642 =>
      array (
        'companyUserId' => '2625642',
        'companyUserName' => 'jg_qdkt',
        'companyName' => '青岛快通国际酒店有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3880774',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '王凤鸣',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '370206195910140818',
        'legalbodyMobile' => '18661978949',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2625931 =>
      array (
        'companyUserId' => '2625931',
        'companyUserName' => 'jg_rz_yrt',
        'companyName' => '益融通商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6522928',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '吴敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '421087199105195948',
        'legalbodyMobile' => '18620776411',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2653033 =>
      array (
        'companyUserId' => '2653033',
        'companyUserName' => 'jg_szwx',
        'companyName' => '深圳东方网信互联网金融信息服务有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3751086',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '王元夫',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210302196005252115',
        'legalbodyMobile' => '13940971722',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2684594 =>
      array (
        'companyUserId' => '2684594',
        'companyUserName' => 'jg_rz_csd',
        'companyName' => '昌顺达汽车产业（深圳）控股有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2515252',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2054-10-20',
        'legalbodyName' => '温建文',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '441427198306240017',
        'legalbodyMobile' => '18682084639',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2739486 =>
      array (
        'companyUserId' => '2739486',
        'companyUserName' => 'jg_rz_tdhf',
        'companyName' => '深圳市天德汇丰贵金属有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2737226',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张升',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '371326198312094917',
        'legalbodyMobile' => '18200391030',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2744997 =>
      array (
        'companyUserId' => '2744997',
        'companyUserName' => 'jg_rz_drs',
        'companyName' => '深圳市德瑞斯商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2612189',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张秀菊',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '371326198102094927',
        'legalbodyMobile' => '13126334805',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2776639 =>
      array (
        'companyUserId' => '2776639',
        'companyUserName' => 'jg_rz_sat',
        'companyName' => '深圳市深港顺安通汽车租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2897317',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2024-11-01',
        'legalbodyName' => '刘焕新',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '442521196808171217',
        'legalbodyMobile' => '15927478700',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      2926010 =>
      array (
        'companyUserId' => '2926010',
        'companyUserName' => 'jg_rz_jqhg',
        'companyName' => '深圳键桥华冠通讯技术有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1709653',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-06-11',
        'legalbodyName' => '范绍林',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '342122197906232031',
        'legalbodyMobile' => '13662139023',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '不再开展相关业务',
      ),
      3069644 =>
      array (
        'companyUserId' => '3069644',
        'companyUserName' => 'jg_rz_sszc',
        'companyName' => '首山资产管理（上海）有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3052116',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-09-17',
        'legalbodyName' => '梁晓伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410426198510050015',
        'legalbodyMobile' => '15026636939',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3071901 =>
      array (
        'companyUserId' => '3071901',
        'companyUserName' => 'jg_ssjrxx',
        'companyName' => '首山金融信息服务（上海）有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6292522',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2034-01-08',
        'legalbodyName' => '梁晓伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410426198510050015',
        'legalbodyMobile' => '15026636939',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3072694 =>
      array (
        'companyUserId' => '3072694',
        'companyUserName' => 'jg_rz_hnyt',
        'companyName' => '海南第一投资控股集团有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3070631',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2049-10-07',
        'legalbodyName' => '蒋会成',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '340103196807232532',
        'legalbodyMobile' => '18907582888',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3114063 =>
      array (
        'companyUserId' => '3114063',
        'companyUserName' => 'jg_rz_dctz',
        'companyName' => '海南大成投资开发有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3108808',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2035-07-12',
        'legalbodyName' => '莫航',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '460100195009270335',
        'legalbodyMobile' => '13976002918',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3114168 =>
      array (
        'companyUserId' => '3114168',
        'companyUserName' => 'jg_rz_nyds',
        'companyName' => '海口南洋大厦有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3109689',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2061-08-13',
        'legalbodyName' => '李跃建',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '650300195806201216',
        'legalbodyMobile' => '13807586584',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3133319 =>
      array (
        'companyUserId' => '3133319',
        'companyUserName' => 'jg_rz_hlhy',
        'companyName' => '虎林市珍宝岛汇源生态农业有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '778855',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '江旭',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '372828196202132718',
        'legalbodyMobile' => '18910289889',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3142223 =>
      array (
        'companyUserId' => '3142223',
        'companyUserName' => 'jg_rz_zgmc',
        'companyName' => '中国木材（集团）有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '9121',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '关文焯',
        'legalbodyCredentialsType' => 99,
        'legalbodyCredentialsNo' => '707024293625',
        'legalbodyMobile' => '18910985481',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3160357 =>
      array (
        'companyUserId' => '3160357',
        'companyUserName' => 'jg_rz_mych',
        'companyName' => '北京铭岳辰辉商贸有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '8907',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2028-11-27',
        'legalbodyName' => '闫兰桃',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '152601197310253122',
        'legalbodyMobile' => '18301615653',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3214831 =>
      array (
        'companyUserId' => '3214831',
        'companyUserName' => 'jg_rz_hbcf',
        'companyName' => '淮北市灿烽贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3131427',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2063-09-16',
        'legalbodyName' => '刘科科',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410882198706118539',
        'legalbodyMobile' => '18756183472',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3249460 =>
      array (
        'companyUserId' => '3249460',
        'companyUserName' => 'jg_rz_hdx',
        'companyName' => '济宁恒德信国际贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1387508',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-03-15',
        'legalbodyName' => '刘倩男',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '371326198603271215',
        'legalbodyMobile' => '13910690683',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3300957 =>
      array (
        'companyUserId' => '3300957',
        'companyUserName' => 'jg_rz_gmdq',
        'companyName' => '北京国美电器有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3263321',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2029-04-05',
        'legalbodyName' => '曾婵贞',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440524194307234847',
        'legalbodyMobile' => '13910380160',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3301088 =>
      array (
        'companyUserId' => '3301088',
        'companyUserName' => 'jg_rz_gmdc',
        'companyName' => '国美地产控股有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3263989',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2035-02-24',
        'legalbodyName' => '刘春林',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110107196206232441',
        'legalbodyMobile' => '13693584881',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3309050 =>
      array (
        'companyUserId' => '3309050',
        'companyUserName' => 'jg_zsz',
        'companyName' => '租上租（深圳）金融服务有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '79839',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2045-07-14',
        'legalbodyName' => '康忠芹',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '21022119701012006X',
        'legalbodyMobile' => '18600070559',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3319812 =>
      array (
        'companyUserId' => '3319812',
        'companyUserName' => 'jg_rz_ylx',
        'companyName' => '固阳县亿隆兴商贸有限责任公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3334441',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2024-05-28',
        'legalbodyName' => '黄英科',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '612425197802166317',
        'legalbodyMobile' => '15174905879',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3322043 =>
      array (
        'companyUserId' => '3322043',
        'companyUserName' => 'jg_rz_bjxb',
        'companyName' => '北京鑫佰贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3130513',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2034-06-15',
        'legalbodyName' => '尤瑞林',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '411282198603235589',
        'legalbodyMobile' => '13366708884',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3383257 =>
      array (
        'companyUserId' => '3383257',
        'companyUserName' => 'jg_rz_pqtz',
        'companyName' => '广州鹏祺投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3357195',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '李建群',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '11010419711016163X',
        'legalbodyMobile' => '13621010000',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3471851 =>
      array (
        'companyUserId' => '3471851',
        'companyUserName' => 'jg_rz_dshl',
        'companyName' => '店商互联（北京）科技发展有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3454896',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2044-01-20',
        'legalbodyName' => '宋宁宝',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '320924197209300116',
        'legalbodyMobile' => '13161662020',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3515905 =>
      array (
        'companyUserId' => '3515905',
        'companyUserName' => 'jg_rz_zfsybl',
        'companyName' => '上海中锋商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '153433',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2042-12-16',
        'legalbodyName' => '李煜宜',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '610321198205240429',
        'legalbodyMobile' => '15921420800',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3520292 =>
      array (
        'companyUserId' => '3520292',
        'companyUserName' => 'jg_rz_hrys',
        'companyName' => '海润影视制作有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3470512',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2031-04-05',
        'legalbodyName' => '刘燕铭',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110104196308112038',
        'legalbodyMobile' => '13601387330',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3536439 =>
      array (
        'companyUserId' => '3536439',
        'companyUserName' => 'jg_qfkd',
        'companyName' => '北京全峰快递有限责任公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1348684',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2030-11-29',
        'legalbodyName' => '陈加海',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '341102197610086278',
        'legalbodyMobile' => '13901768599',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3547052 =>
      array (
        'companyUserId' => '3547052',
        'companyUserName' => 'jg_tjxfzcgl',
        'companyName' => '天津先锋资产管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '15966',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2023-06-18',
        'legalbodyName' => '伍敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '42010219760807244X',
        'legalbodyMobile' => '13911826163',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3556135 =>
      array (
        'companyUserId' => '3556135',
        'companyUserName' => 'jg_szlhhbcf',
        'companyName' => '深圳联合货币财富管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3915',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '刘静',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '320683198211248220',
        'legalbodyMobile' => '15021000663',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3559682 =>
      array (
        'companyUserId' => '3559682',
        'companyUserName' => 'jg_rz_myhp',
        'companyName' => '深圳茂业和平商厦有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3520657',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2049-01-13',
        'legalbodyName' => '张静',
        'legalbodyCredentialsType' => 2,
        'legalbodyCredentialsNo' => 'P0157102',
        'legalbodyMobile' => '13502896165',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3559800 =>
      array (
        'companyUserId' => '3559800',
        'companyUserName' => 'jg_rz_xhqc',
        'companyName' => '深圳市兴华汽车运输有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3521621',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张静',
        'legalbodyCredentialsType' => 2,
        'legalbodyCredentialsNo' => 'P0157102',
        'legalbodyMobile' => '13502896165',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3566456 =>
      array (
        'companyUserId' => '3566456',
        'companyUserName' => 'jg_rz_szxhsy',
        'companyName' => '深圳兴华实业股份有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3522339',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张静',
        'legalbodyCredentialsType' => 2,
        'legalbodyCredentialsNo' => 'P0157102',
        'legalbodyMobile' => '13502896165',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3566531 =>
      array (
        'companyUserId' => '3566531',
        'companyUserName' => 'jg_rz_httzzx',
        'companyName' => '珠海市辉通投资咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3554829',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '姚辉镇',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '44040119640221611X',
        'legalbodyMobile' => '13809233138',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3571276 =>
      array (
        'companyUserId' => '3571276',
        'companyUserName' => 'jg_wxzc',
        'companyName' => '北京网信众筹网络科技有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '19805',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2046-01-05',
        'legalbodyName' => '盛佳',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '321001198007310019',
        'legalbodyMobile' => '18513418809',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3608003 =>
      array (
        'companyUserId' => '3608003',
        'companyUserName' => 'jg_rz_jnzy',
        'companyName' => '济宁正雅商贸有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '423683',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '周珍林',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '340823197910196515',
        'legalbodyMobile' => '13910492530',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3618692 =>
      array (
        'companyUserId' => '3618692',
        'companyUserName' => 'jg_szswajr',
        'companyName' => '深圳市网爱金融服务有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1814',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '米泽东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '420111197610265693',
        'legalbodyMobile' => '15001029330',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3631005 =>
      array (
        'companyUserId' => '3631005',
        'companyUserName' => 'jg_wxzcwlkj',
        'companyName' => '北京网信众筹网络科技有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1374640',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2046-01-05',
        'legalbodyName' => '盛佳',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '321001198007310019',
        'legalbodyMobile' => '18513418809',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3651659 =>
      array (
        'companyUserId' => '3651659',
        'companyUserName' => 'jg_rz_zffdc',
        'companyName' => '重庆中房房地产开发有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3644546',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2039-08-14',
        'legalbodyName' => '刘春林',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110107196206232441',
        'legalbodyMobile' => '13681598074',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3724914 =>
      array (
        'companyUserId' => '3724914',
        'companyUserName' => 'jg_rz_ssyl',
        'companyName' => '山水环境科技股份有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3840947',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2026-03-02',
        'legalbodyName' => '袁建伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410726198210080016',
        'legalbodyMobile' => '18803732223',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3750401 =>
      array (
        'companyUserId' => '3750401',
        'companyUserName' => 'jg_rz_yhl',
        'companyName' => '重庆玉豪龙实业集团江麟房地产开发有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3656441',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '黄秀虹',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440524197302164943',
        'legalbodyMobile' => '13911655645',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3753743 =>
      array (
        'companyUserId' => '3753743',
        'companyUserName' => 'jg_ydf',
        'companyName' => '广州盈德丰融资担保有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3750899',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '柯余华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '44082119761022081X',
        'legalbodyMobile' => '18620620377',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3762821 =>
      array (
        'companyUserId' => '3762821',
        'companyUserName' => 'jg_rz_dws',
        'companyName' => '北京达沃森投资管理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3726381',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2026-03-07',
        'legalbodyName' => '李周',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '352102197903050415',
        'legalbodyMobile' => '13288622376',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3776344 =>
      array (
        'companyUserId' => '3776344',
        'companyUserName' => 'jg_rz_cyqc',
        'companyName' => '珠海市昌运汽车租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '508',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '赵国栋',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '211004195008133018',
        'legalbodyMobile' => '15019401205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3835658 =>
      array (
        'companyUserId' => '3835658',
        'companyUserName' => 'jg_rz_hhkj',
        'companyName' => '广州市花卉科技园有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2727968',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2045-07-06',
        'legalbodyName' => '俞军',
        'legalbodyCredentialsType' => 4,
        'legalbodyCredentialsNo' => 'P212307(3)',
        'legalbodyMobile' => '18520474702',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3839611 =>
      array (
        'companyUserId' => '3839611',
        'companyUserName' => 'jg_rz_kqtz',
        'companyName' => '深圳市酷奇投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3635168',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '杨兴财',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '362232197404031418',
        'legalbodyMobile' => '18998942905',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3879048 =>
      array (
        'companyUserId' => '3879048',
        'companyUserName' => 'jg_rz_gstz',
        'companyName' => '深圳市广森投资集团有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3881490',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '李森',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440902196304120054',
        'legalbodyMobile' => '13802262215',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3894156 =>
      array (
        'companyUserId' => '3894156',
        'companyUserName' => 'jg_yflx',
        'companyName' => '深圳一房立信金融服务有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3377194',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '张媛源',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '420583198408252221',
        'legalbodyMobile' => '13581523029',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3950968 =>
      array (
        'companyUserId' => '3950968',
        'companyUserName' => 'jg_rz_dhwd',
        'companyName' => '大河五地（北京）国际影视文化传媒有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '29032',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2030-06-28',
        'legalbodyName' => '凌飞',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110102195310042318',
        'legalbodyMobile' => '13611329422',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3962323 =>
      array (
        'companyUserId' => '3962323',
        'companyUserName' => 'jg_rz_jsc',
        'companyName' => '沁阳市金世昌贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3132171',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-09-03',
        'legalbodyName' => '杜志伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410824197104301530',
        'legalbodyMobile' => '18790249218',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      3996648 =>
      array (
        'companyUserId' => '3996648',
        'companyUserName' => 'jg_rz_kfwjzy',
        'companyName' => '开封万锦置业有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3990995',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2018-07-30',
        'legalbodyName' => '陆伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410305196606270518',
        'legalbodyMobile' => '15803800275',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4031088 =>
      array (
        'companyUserId' => '4031088',
        'companyUserName' => 'jg_wxyc',
        'companyName' => '网信友车（天津）资产管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2358219',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2045-09-23',
        'legalbodyName' => '李焕香',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '120112197103163321',
        'legalbodyMobile' => '13323375777',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4106968 =>
      array (
        'companyUserId' => '4106968',
        'companyUserName' => 'jg_hhswxx',
        'companyName' => '陕西汇宏商务信息咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '359963',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '黄劲尧',
        'legalbodyCredentialsType' => 6,
        'legalbodyCredentialsNo' => '02490137',
        'legalbodyMobile' => '13811165547',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4107994 =>
      array (
        'companyUserId' => '4107994',
        'companyUserName' => 'jg_bjwxcm',
        'companyName' => '网信传媒有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3934757',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2115-08-12',
        'legalbodyName' => '周国华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510212197204274117',
        'legalbodyMobile' => '13381228788',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4168291 =>
      array (
        'companyUserId' => '4168291',
        'companyUserName' => 'jg_rz_xhtz',
        'companyName' => '北京兴汇投资有限责任公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '29665',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2044-07-02',
        'legalbodyName' => '孙群玲',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '652201195309081244',
        'legalbodyMobile' => '13811762126',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4231470 =>
      array (
        'companyUserId' => '4231470',
        'companyUserName' => 'jg_rz_nsgtc',
        'companyName' => '深圳市南山罐头厂有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4225209',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '李森',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440902196304120054',
        'legalbodyMobile' => '13802262215',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4232621 =>
      array (
        'companyUserId' => '4232621',
        'companyUserName' => 'jg_rz_zhtzzx',
        'companyName' => '深圳市资慧投资咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '29911',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2023-06-21',
        'legalbodyName' => '张卫东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510721196906119313',
        'legalbodyMobile' => '13501195079',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4281109 =>
      array (
        'companyUserId' => '4281109',
        'companyUserName' => 'jg_rz_jsld',
        'companyName' => '江苏乐动贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4091924',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '陈黄兵',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '320626197208046212',
        'legalbodyMobile' => '13801576665',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4286943 =>
      array (
        'companyUserId' => '4286943',
        'companyUserName' => 'jg_rz_qytx',
        'companyName' => '深圳起源天下科技有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '759860',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2064-11-25',
        'legalbodyName' => '王政',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '220381198201164610',
        'legalbodyMobile' => '18689357353',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4338751 =>
      array (
        'companyUserId' => '4338751',
        'companyUserName' => 'jg_rz_rdmh',
        'companyName' => '鄂尔多斯市瑞德煤化有限责任公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-06-04',
        'legalbodyName' => '刘燕',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '152726196009080312',
        'legalbodyMobile' => '15044731119',
        'majorName' => '刘燕',
        'majorCondentialsType' => 1,
        'majorCondentialsNo' => '152726196009080312',
        'majorMobile' => '15044731119',
        'memo' => '',
      ),
      4394206 =>
      array (
        'companyUserId' => '4394206',
        'companyUserName' => 'jg_rz_sbrq',
        'companyName' => '霸州市胜霸燃气有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4160321',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-02-20',
        'legalbodyName' => '孙河忠',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '132903196110029012',
        'legalbodyMobile' => '18630663799',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4402409 =>
      array (
        'companyUserId' => '4402409',
        'companyUserName' => 'jg_rz_zqbs',
        'companyName' => '河南中启保税商贸有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4101202',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2022-06-03',
        'legalbodyName' => '徐平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '412827197008110129',
        'legalbodyMobile' => '13676943182',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4402551 =>
      array (
        'companyUserId' => '4402551',
        'companyUserName' => 'jg_rz_kygj',
        'companyName' => '河南开元国际经贸有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4340594',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2021-01-20',
        'legalbodyName' => '徐超锋',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '412827197811020077',
        'legalbodyMobile' => '13525579377',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4419883 =>
      array (
        'companyUserId' => '4419883',
        'companyUserName' => 'jg_szlhhbcfgl',
        'companyName' => '深圳联合货币财富管理有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3915',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '刘静',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '320683198211248220',
        'legalbodyMobile' => '15021000663',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4437759 =>
      array (
        'companyUserId' => '4437759',
        'companyUserName' => 'jg_tjlhcytzdb',
        'companyName' => '天津联合创业投资担保有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
          1 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3895',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2028-05-28',
        'legalbodyName' => '高雁',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210212197111110024',
        'legalbodyMobile' => '13842621071',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4457260 =>
      array (
        'companyUserId' => '4457260',
        'companyUserName' => 'jg_rz_ctyy',
        'companyName' => '天津春天影业投资发展有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4426869',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-01-11',
        'legalbodyName' => '张学敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '120105197011031201',
        'legalbodyMobile' => '18601111111',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4472502 =>
      array (
        'companyUserId' => '4472502',
        'companyUserName' => 'jg_rz_yhsw',
        'companyName' => '天津盈华商务汽车租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '19372',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-09-16',
        'legalbodyName' => '李子东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210824197012040831',
        'legalbodyMobile' => '18910989458',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4580902 =>
      array (
        'companyUserId' => '4580902',
        'companyUserName' => 'jg_rz_ynlatz',
        'companyName' => '云南力奥投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4571984',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2016-11-23',
        'legalbodyName' => '蔡旭东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '44050519700106101X',
        'legalbodyMobile' => '15887817305',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4682477 =>
      array (
        'companyUserId' => '4682477',
        'companyUserName' => 'jg_rz_zyyx',
        'companyName' => '正杨映像（北京）文化传播有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3068915',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-09-17',
        'legalbodyName' => '杨力',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '430111197610110941',
        'legalbodyMobile' => '13007427552',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4745794 =>
      array (
        'companyUserId' => '4745794',
        'companyUserName' => 'jg_rz_gxxedk',
        'companyName' => '贵溪市广信小额贷款股份有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4809521',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '夏明',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '362232197107290018',
        'legalbodyMobile' => '13970189115',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4791451 =>
      array (
        'companyUserId' => '4791451',
        'companyUserName' => 'jg_rz_yhqczl',
        'companyName' => '深圳盈华商务汽车租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4450179',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2052-12-11',
        'legalbodyName' => '曾锋',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '441622197406203111',
        'legalbodyMobile' => '15019401205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4809488 =>
      array (
        'companyUserId' => '4809488',
        'companyUserName' => 'jg_rz_shdpgj',
        'companyName' => '上海大鹏国际贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4453925',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2043-06-25',
        'legalbodyName' => '王瑞贞',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '11010219641101197X',
        'legalbodyMobile' => '15321203981',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4809819 =>
      array (
        'companyUserId' => '4809819',
        'companyUserName' => 'jg_rz_zgysjs',
        'companyName' => '中国有色金属工业再生资源有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4070501',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '代威',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '220402196407151437',
        'legalbodyMobile' => '15321203981',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      4904579 =>
      array (
        'companyUserId' => '4904579',
        'companyUserName' => 'jg_bjyhcf',
        'companyName' => '北京盈华财富投资管理股份有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4064015',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2041-04-10',
        'legalbodyName' => '刘苗苗',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204198110275665',
        'legalbodyMobile' => '13998460205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5228408 =>
      array (
        'companyUserId' => '5228408',
        'companyUserName' => 'jg_zbdhy',
        'companyName' => '虎林市珍宝岛汇源生态农业有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '778855',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '江旭',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '372828196202132718',
        'legalbodyMobile' => '18910289889',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5230234 =>
      array (
        'companyUserId' => '5230234',
        'companyUserName' => 'jg_sszc',
        'companyName' => '首山资产管理（上海）有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3052116',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-09-17',
        'legalbodyName' => '梁晓伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410426198510050015',
        'legalbodyMobile' => '15026636939',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5451128 =>
      array (
        'companyUserId' => '5451128',
        'companyUserName' => 'jg_rz_rysybl',
        'companyName' => '深圳市睿耀商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4031323',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '公维广',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '371326198701188810',
        'legalbodyMobile' => '18620776411',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5684582 =>
      array (
        'companyUserId' => '5684582',
        'companyUserName' => 'jg_wxxyr',
        'companyName' => '网信新影人（北京）投资管理有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5724',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2065-12-10',
        'legalbodyName' => '王硕',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101198601080514',
        'legalbodyMobile' => '13910722117',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5762604 =>
      array (
        'companyUserId' => '5762604',
        'companyUserName' => 'jg_rz_sxzsazgc',
        'companyName' => '陕西中胜安装工程有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5698556',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '黄广颖',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440301196410304130',
        'legalbodyMobile' => '18009185690',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5766718 =>
      array (
        'companyUserId' => '5766718',
        'companyUserName' => 'jg_rz_twbc',
        'companyName' => '北京天维宝辰化学产品有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5659440',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2046-03-31',
        'legalbodyName' => '王义',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '11010219570719191x',
        'legalbodyMobile' => '13801056511',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5797959 =>
      array (
        'companyUserId' => '5797959',
        'companyUserName' => 'jg_rz_tjytxny',
        'companyName' => '天津宜通新能源汽车租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '8548',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2045-04-29',
        'legalbodyName' => '温少敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440105197011262710',
        'legalbodyMobile' => '15019401205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5879980 =>
      array (
        'companyUserId' => '5879980',
        'companyUserName' => 'jg_rz_zckj',
        'companyName' => '北京智充科技有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5325413',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2065-05-03',
        'legalbodyName' => '丁锐',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '370481198612070932',
        'legalbodyMobile' => '13621039944',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      5880251 =>
      array (
        'companyUserId' => '5880251',
        'companyUserName' => 'jg_rz_yfysm',
        'companyName' => '深圳市盈丰源商贸有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '192777',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张爱华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '442525197009034435',
        'legalbodyMobile' => '15921420800',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6024021 =>
      array (
        'companyUserId' => '6024021',
        'companyUserName' => 'jg_rz_shdr',
        'companyName' => '上海鼎瑞贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5949284',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-05-16',
        'legalbodyName' => '龚一香',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '310230195905020466',
        'legalbodyMobile' => '13918416721',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6060633 =>
      array (
        'companyUserId' => '6060633',
        'companyUserName' => 'jg_rz_shyfzlsb',
        'companyName' => '上海昱丰制冷设备有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6046650',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2016-07-16',
        'legalbodyName' => '施慧卿',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '320624197508260226',
        'legalbodyMobile' => '15821651196',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6091905 =>
      array (
        'companyUserId' => '6091905',
        'companyUserName' => 'jg_rz_zjddn',
        'companyName' => '浙江大东南进出口有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6082362',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2025-02-15',
        'legalbodyName' => '黄生祥',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '339011195004288395',
        'legalbodyMobile' => '13606563821',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6105275 =>
      array (
        'companyUserId' => '6105275',
        'companyUserName' => 'jg_rz_zhcyqcys',
        'companyName' => '珠海市昌运汽车运输有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '158038',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '彭忠连',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '430221196801121110',
        'legalbodyMobile' => '15019401205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6115378 =>
      array (
        'companyUserId' => '6115378',
        'companyUserName' => 'jg_rz_qhcr',
        'companyName' => '深圳市前海承润资产管理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '金鑫',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101198709111028',
        'legalbodyMobile' => '13910393994',
        'majorName' => '金鑫',
        'majorCondentialsType' => 1,
        'majorCondentialsNo' => '110101198709111028',
        'majorMobile' => '13910393994',
        'memo' => '',
      ),
      6132017 =>
      array (
        'companyUserId' => '6132017',
        'companyUserName' => 'jg_rz_dljw',
        'companyName' => '大连伟佳国际贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4282',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2065-06-14',
        'legalbodyName' => '陈伟佳',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '230803198106070819',
        'legalbodyMobile' => '15714054520',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6134019 =>
      array (
        'companyUserId' => '6134019',
        'companyUserName' => 'jg_rz_hmcf',
        'companyName' => '汉美财富世纪（北京）投资管理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-01-18',
        'legalbodyName' => '甘涛',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110102197306043333',
        'legalbodyMobile' => '13041129702',
        'majorName' => '甘涛',
        'majorCondentialsType' => 1,
        'majorCondentialsNo' => '110102197306043333',
        'majorMobile' => '13041129702',
        'memo' => '',
      ),
      6136660 =>
      array (
        'companyUserId' => '6136660',
        'companyUserName' => 'jg_rz_cmyl',
        'companyName' => '海南成美医疗投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6094363',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2042-08-15',
        'legalbodyName' => '林士泉',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '340103196912202511',
        'legalbodyMobile' => '13976903228',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6142545 =>
      array (
        'companyUserId' => '6142545',
        'companyUserName' => 'jg_rz_ytjrgc',
        'companyName' => '深圳市益田假日广场有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6128693',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2052-10-24',
        'legalbodyName' => '黎志强',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440301196309031131',
        'legalbodyMobile' => '18603023930',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6144962 =>
      array (
        'companyUserId' => '6144962',
        'companyUserName' => 'jg_rz_gysw',
        'companyName' => '大连广垠生物农药有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2105061',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2021-12-10',
        'legalbodyName' => '王京源',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210211196811123159',
        'legalbodyMobile' => '13252981030',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6179347 =>
      array (
        'companyUserId' => '6179347',
        'companyUserName' => 'jg_cctdjx',
        'companyName' => '长春泰德机械设备有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '32018',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2020-04-06',
        'legalbodyName' => '黎杰',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '222303193102010042',
        'legalbodyMobile' => '13581770882',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6179879 =>
      array (
        'companyUserId' => '6179879',
        'companyUserName' => 'jg_rz_ccyc',
        'companyName' => '长春业昶经贸有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3753',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2021-12-11',
        'legalbodyName' => '顾金达',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210522198606204114',
        'legalbodyMobile' => '15811237982',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6199155 =>
      array (
        'companyUserId' => '6199155',
        'companyUserName' => 'jg_rz_bfgk',
        'companyName' => '北方高科（天津）实业有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '362480',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2025-08-28',
        'legalbodyName' => '胡伯亮',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '120223198303193532',
        'legalbodyMobile' => '13072205678',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6202481 =>
      array (
        'companyUserId' => '6202481',
        'companyUserName' => 'jg_rz_dlds',
        'companyName' => '大连得升医疗仪器有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5082005',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2025-05-08',
        'legalbodyName' => '王刚',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101196303195745',
        'legalbodyMobile' => '18904115333',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6207391 =>
      array (
        'companyUserId' => '6207391',
        'companyUserName' => 'jg_rz_hnzlq',
        'companyName' => '海南棕榈泉实业发展有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6403388',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2056-05-14',
        'legalbodyName' => '王治国',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '150203197010110030',
        'legalbodyMobile' => '13518890808',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6215886 =>
      array (
        'companyUserId' => '6215886',
        'companyUserName' => 'jg_rz_dlyst',
        'companyName' => '大连雅士泰生物工程有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4423323',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2020-08-03',
        'legalbodyName' => '葛旭',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210212196209074514',
        'legalbodyMobile' => '13942837887',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6244335 =>
      array (
        'companyUserId' => '6244335',
        'companyUserName' => 'jg_rz_lndzgc',
        'companyName' => '辽宁地质工程勘探施工集团公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '李春明',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210403196403273917',
        'legalbodyMobile' => '15141263111',
        'majorName' => '张丽',
        'majorCondentialsType' => 1,
        'majorCondentialsNo' => '21040419751215212X',
        'majorMobile' => '13940245366',
        'memo' => '',
      ),
      6244573 =>
      array (
        'companyUserId' => '6244573',
        'companyUserName' => 'jg_rz_lnzdqy',
        'companyName' => '辽宁中鼎企业发展顾问有限责任公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6198265',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2020-05-27',
        'legalbodyName' => '左英田',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210102194911101538',
        'legalbodyMobile' => '13674251455',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6244904 =>
      array (
        'companyUserId' => '6244904',
        'companyUserName' => 'jg_rz_shzdjm',
        'companyName' => '上海泽典经贸发展有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6162232',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2023-07-06',
        'legalbodyName' => '练秀仙',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '352104197010035069',
        'legalbodyMobile' => '13818999948',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6279917 =>
      array (
        'companyUserId' => '6279917',
        'companyUserName' => 'jg_rz_szsdfyz',
        'companyName' => '深圳市东方银座酒店管理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6268833',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '庞梓焜',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '44080219540609001X',
        'legalbodyMobile' => '18948766896',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6283442 =>
      array (
        'companyUserId' => '6283442',
        'companyUserName' => 'jg_rz_zxsy',
        'companyName' => '中星神鹰置业投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4801',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2034-02-13',
        'legalbodyName' => '刘东粤',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110108198302245416',
        'legalbodyMobile' => '13911089757',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6293918 =>
      array (
        'companyUserId' => '6293918',
        'companyUserName' => 'jg_rz_drty',
        'companyName' => '天津大容铜业有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6268438',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2049-08-05',
        'legalbodyName' => '王金祥',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '120106195912040536',
        'legalbodyMobile' => '15321203981',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6293960 =>
      array (
        'companyUserId' => '6293960',
        'companyUserName' => 'jg_rz_whtz',
        'companyName' => '浙江万海投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5906725',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2028-04-15',
        'legalbodyName' => '胡利华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '330824197711274212',
        'legalbodyMobile' => '13295705888',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6294046 =>
      array (
        'companyUserId' => '6294046',
        'companyUserName' => 'jg_rz_wlzb',
        'companyName' => '深圳市威廉珠宝首饰有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3586098',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2024-12-07',
        'legalbodyName' => '蔡世潮',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '44522419880415035X',
        'legalbodyMobile' => '13990166566',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6307234 =>
      array (
        'companyUserId' => '6307234',
        'companyUserName' => 'jg_rz_qddybz',
        'companyName' => '青岛德音包装有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4393715',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2017-12-22',
        'legalbodyName' => '孙德春',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '370222196801270052',
        'legalbodyMobile' => '15192660567',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6307623 =>
      array (
        'companyUserId' => '6307623',
        'companyUserName' => 'jg_qddyjck',
        'companyName' => '青岛德音进出口有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4700522',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '孙德春',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '370222196801270052',
        'legalbodyMobile' => '15192660567',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6317625 =>
      array (
        'companyUserId' => '6317625',
        'companyUserName' => 'jg_rz_szsdrs',
        'companyName' => '深圳市德瑞斯商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2612189',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张秀菊',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '371326198102094927',
        'legalbodyMobile' => '13126334805',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6318204 =>
      array (
        'companyUserId' => '6318204',
        'companyUserName' => 'jg_rz_hnzlqxs',
        'companyName' => '海南棕榈泉香水湾旅业发展有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6219508',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2038-05-15',
        'legalbodyName' => '杨海金',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101197605154512',
        'legalbodyMobile' => '13901130678',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6321076 =>
      array (
        'companyUserId' => '6321076',
        'companyUserName' => 'jg_rz_tcwl',
        'companyName' => '海城市天成物流运输有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '511893',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2023-09-11',
        'legalbodyName' => '王守成',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210381196303262915',
        'legalbodyMobile' => '15141263111',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6326717 =>
      array (
        'companyUserId' => '6326717',
        'companyUserName' => 'jg_rz_bjycnx',
        'companyName' => '北京远驰诺信贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6321811',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2025-06-28',
        'legalbodyName' => '田兴成',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '342123198106083438',
        'legalbodyMobile' => '15311839770',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6327281 =>
      array (
        'companyUserId' => '6327281',
        'companyUserName' => 'jg_rz_dljtmy',
        'companyName' => '大连金田贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6312988',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2065-01-04',
        'legalbodyName' => '刘彬',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210222197507061743',
        'legalbodyMobile' => '18904082006',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6348573 =>
      array (
        'companyUserId' => '6348573',
        'companyUserName' => 'jg_rz_hzsy',
        'companyName' => '深圳市宏兆实业发展有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6326711',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2022-10-10',
        'legalbodyName' => '姚年跃',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440102199001034431',
        'legalbodyMobile' => '13632858666',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6355863 =>
      array (
        'companyUserId' => '6355863',
        'companyUserName' => 'jg_pgb',
        'companyName' => '西安皮个布企业管理咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6294397',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '张铭',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '610104197709271155',
        'legalbodyMobile' => '18629029222',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6366803 =>
      array (
        'companyUserId' => '6366803',
        'companyUserName' => 'jg_rz_szzqzl',
        'companyName' => '深圳中汽租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '7785',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '温少敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440105197011262710',
        'legalbodyMobile' => '15019401205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6366949 =>
      array (
        'companyUserId' => '6366949',
        'companyUserName' => 'jg_rz_zqys',
        'companyName' => '深圳市中汽运输有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1413697',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2043-11-15',
        'legalbodyName' => '温少敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440105197011262710',
        'legalbodyMobile' => '15019401205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6367488 =>
      array (
        'companyUserId' => '6367488',
        'companyUserName' => 'jg_rz_zhhxbl',
        'companyName' => '珠海华信保理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '53009',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '周国华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510212197204274117',
        'legalbodyMobile' => '18612485095',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6367924 =>
      array (
        'companyUserId' => '6367924',
        'companyUserName' => 'jg_xalf',
        'companyName' => '西安立丰企业发展投资（集团）有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6383425',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '颜明',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '610103195910130017',
        'legalbodyMobile' => '13909217309',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6392542 =>
      array (
        'companyUserId' => '6392542',
        'companyUserName' => 'jg_bhsybl',
        'companyName' => '深圳百禾商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '12721',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '岳振华',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '513030198806030012',
        'legalbodyMobile' => '13656669898',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6398506 =>
      array (
        'companyUserId' => '6398506',
        'companyUserName' => 'jg_yrtsy',
        'companyName' => '益融通商业保理有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6522928',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '吴敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '421087199105195948',
        'legalbodyMobile' => '18620776411',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6403000 =>
      array (
        'companyUserId' => '6403000',
        'companyUserName' => 'jg_rz_szwet',
        'companyName' => '深圳市维尔特供应链管理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1356',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2039-08-25',
        'legalbodyName' => '贾娜',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '130402198203272429',
        'legalbodyMobile' => '18675593066',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6403115 =>
      array (
        'companyUserId' => '6403115',
        'companyUserName' => 'jg_rz_nbhs',
        'companyName' => '宁波函数资产管理有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4347275',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '余超',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '413026198706266337',
        'legalbodyMobile' => '13811146201',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6405160 =>
      array (
        'companyUserId' => '6405160',
        'companyUserName' => 'jg_rz_dlhlf',
        'companyName' => '大连海陆丰远洋渔业开发有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1182406',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2024-07-29',
        'legalbodyName' => '张耘豪',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210203198610086012',
        'legalbodyMobile' => '15566937209',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6407400 =>
      array (
        'companyUserId' => '6407400',
        'companyUserName' => 'jg_rz_zhxx',
        'companyName' => '庄河鑫鑫汽车租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3205854',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2029-05-24',
        'legalbodyName' => '宋雪梅',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210225196911020382',
        'legalbodyMobile' => '15541150172',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6442574 =>
      array (
        'companyUserId' => '6442574',
        'companyUserName' => 'jg_bjwxyf',
        'companyName' => '北京网信云服信息科技有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6999',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2046-01-05',
        'legalbodyName' => '盛佳',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '321001198007310019',
        'legalbodyMobile' => '18513418809',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6449778 =>
      array (
        'companyUserId' => '6449778',
        'companyUserName' => 'jg_bjywjf',
        'companyName' => '北京悦网金服信息科技有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1486049',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2035-11-15',
        'legalbodyName' => '郭海霞',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410303197603040524',
        'legalbodyMobile' => '13910605329',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6449946 =>
      array (
        'companyUserId' => '6449946',
        'companyUserName' => 'jg_bjwxjd',
        'companyName' => '北京网信奇点投资有限公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '378',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '崔玲',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204197108086764',
        'legalbodyMobile' => '18611120200',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6450311 =>
      array (
        'companyUserId' => '6450311',
        'companyUserName' => 'jg_rz_lffdc',
        'companyName' => '立丰（西安）房地产开发有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6456424',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2042-10-08',
        'legalbodyName' => '颜明',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '610103195910130017',
        'legalbodyMobile' => '13909217309',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6451847 =>
      array (
        'companyUserId' => '6451847',
        'companyUserName' => 'jg_ddnxcl',
        'companyName' => '浙江大东南新材料有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6452385',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2031-11-06',
        'legalbodyName' => '童培根',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '330625196707168397',
        'legalbodyMobile' => '13606563821',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6460188 =>
      array (
        'companyUserId' => '6460188',
        'companyUserName' => 'jg_qhcr',
        'companyName' => '深圳市前海承润资产管理有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1394',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '金鑫',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101198709111028',
        'legalbodyMobile' => '13910393994',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6498766 =>
      array (
        'companyUserId' => '6498766',
        'companyUserName' => 'jg_rz_fsty',
        'companyName' => '湖南飞速体育产业发展有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4167489',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2059-11-16',
        'legalbodyName' => '毛青山',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '430123196501115517',
        'legalbodyMobile' => '13907497532',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6511697 =>
      array (
        'companyUserId' => '6511697',
        'companyUserName' => 'jg_cskrl',
        'companyName' => '长沙凯瑞力体育用品有限责任公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4157929',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2064-01-19',
        'legalbodyName' => '毛经伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '430123195710040051',
        'legalbodyMobile' => '13786165136',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6512444 =>
      array (
        'companyUserId' => '6512444',
        'companyUserName' => 'jg_wxyfdyfgs',
        'companyName' => '北京网信云服信息科技有限公司北京第一分公司',
        'companyPurpose' =>
        array (
          0 => 3,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '804781',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '吕威',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101198012174010',
        'legalbodyMobile' => '13911100123',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6513377 =>
      array (
        'companyUserId' => '6513377',
        'companyUserName' => 'jg_rz_lhgj',
        'companyName' => '上海龙核国际贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6501159',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2029-02-01',
        'legalbodyName' => '郑火秀',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '352123197110085126',
        'legalbodyMobile' => '18621950409',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6529761 =>
      array (
        'companyUserId' => '6529761',
        'companyUserName' => 'jg_rz_xmdd',
        'companyName' => '鹰潭市当代投资集团有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6279828',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2025-04-17',
        'legalbodyName' => '王春芳',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '359002196910112019',
        'legalbodyMobile' => '13600905099',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6529971 =>
      array (
        'companyUserId' => '6529971',
        'companyUserName' => 'jg_rz_dlsf',
        'companyName' => '深圳鼎立四方科技有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1326723',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '温少敏',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440105197011262710',
        'legalbodyMobile' => '15019401205',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6530247 =>
      array (
        'companyUserId' => '6530247',
        'companyUserName' => 'jg_csd',
        'companyName' => '昌顺达汽车产业（深圳）控股有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2515252',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2054-10-20',
        'legalbodyName' => '温建文',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '441427198306240017',
        'legalbodyMobile' => '18682084639',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6541137 =>
      array (
        'companyUserId' => '6541137',
        'companyUserName' => 'jg_sgsat',
        'companyName' => '深圳市深港顺安通汽车租赁有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2897317',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2024-11-01',
        'legalbodyName' => '刘焕新',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '442521196808171217',
        'legalbodyMobile' => '15927478700',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6572222 =>
      array (
        'companyUserId' => '6572222',
        'companyUserName' => 'jg_szyczbsp',
        'companyName' => '深圳元昌珠宝饰品有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '7502',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2052-11-20',
        'legalbodyName' => '任冬宁',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510902197211019515',
        'legalbodyMobile' => '18319039553',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6573731 =>
      array (
        'companyUserId' => '6573731',
        'companyUserName' => 'jg_jyhj',
        'companyName' => '深圳嘉盈黄金制品有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1036703',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张卫东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510721196906119313',
        'legalbodyMobile' => '13990166566',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6581662 =>
      array (
        'companyUserId' => '6581662',
        'companyUserName' => 'jg_rz_yckm',
        'companyName' => '宿州市赢创科贸发展有限责任公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6791675',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '张玉玲',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '342201196812050041',
        'legalbodyMobile' => '18955708199',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6583805 =>
      array (
        'companyUserId' => '6583805',
        'companyUserName' => 'jg_mfzb',
        'companyName' => '深圳美福珠宝饰品投资有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '82787',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '王威',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210802197806151041',
        'legalbodyMobile' => '13656669898',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6629333 =>
      array (
        'companyUserId' => '6629333',
        'companyUserName' => 'jg_rz_lsjxdb',
        'companyName' => '乐山吉象地板制品有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6626720',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2047-11-02',
        'legalbodyName' => '林子濬',
        'legalbodyCredentialsType' => 6,
        'legalbodyCredentialsNo' => 'Z100008080',
        'legalbodyMobile' => '13518200882',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6635389 =>
      array (
        'companyUserId' => '6635389',
        'companyUserName' => 'jg_rz_egkj',
        'companyName' => '浙江尔格科技股份有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6601756',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '黎贤钛',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '332626196208070676',
        'legalbodyMobile' => '15958679276',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6637202 =>
      array (
        'companyUserId' => '6637202',
        'companyUserName' => 'jg_rz_xcctz',
        'companyName' => '深圳市新城灿投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6628575',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2023-09-26',
        'legalbodyName' => '庞杰',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '411123197908270019',
        'legalbodyMobile' => '18312349511',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6637727 =>
      array (
        'companyUserId' => '6637727',
        'companyUserName' => 'jg_db_qcly',
        'companyName' => '深圳市侨城旅游运输有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '4209538',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '黄琳',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440301197103257519',
        'legalbodyMobile' => '13723795330',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6649677 =>
      array (
        'companyUserId' => '6649677',
        'companyUserName' => 'jg_rz_hykg',
        'companyName' => '北京汇源控股有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '46408',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2024-04-18',
        'legalbodyName' => '李家莹',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '37032319900206264X',
        'legalbodyMobile' => '13911471947',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6652393 =>
      array (
        'companyUserId' => '6652393',
        'companyUserName' => 'jg_rz_twaq',
        'companyName' => '江西省天网安全防范科技有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5692660',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2028-09-22',
        'legalbodyName' => '赵滨',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110101196802152538',
        'legalbodyMobile' => '13911214814',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6653021 =>
      array (
        'companyUserId' => '6653021',
        'companyUserName' => 'jg_rz_yjyg',
        'companyName' => '沅江阳光大地有机农业有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6627745',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2023-08-19',
        'legalbodyName' => '郭立荣',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '432301196203102034',
        'legalbodyMobile' => '13672433888',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6663306 =>
      array (
        'companyUserId' => '6663306',
        'companyUserName' => 'jg_wet',
        'companyName' => '深圳市维尔特供应链管理有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1356',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2039-08-25',
        'legalbodyName' => '贾娜',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '130402198203272429',
        'legalbodyMobile' => '18675593066',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6663358 =>
      array (
        'companyUserId' => '6663358',
        'companyUserName' => 'jg_rz_xmtx',
        'companyName' => '深圳市西美通信科技有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '27679',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '项忠义',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '130205197202130051',
        'legalbodyMobile' => '13603086939',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6717396 =>
      array (
        'companyUserId' => '6717396',
        'companyUserName' => 'jg_rz_syxs',
        'companyName' => '沈阳市信生肉鸡加工厂',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6091722',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2019-03-11',
        'legalbodyName' => '郑忠生',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210111197310235012',
        'legalbodyMobile' => '13898880831',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6758807 =>
      array (
        'companyUserId' => '6758807',
        'companyUserName' => 'jg_ydtgyl',
        'companyName' => '深圳市永达通供应链有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6701712',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '颜世龙',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440922195508261917',
        'legalbodyMobile' => '18948766407',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6759087 =>
      array (
        'companyUserId' => '6759087',
        'companyUserName' => 'jg_rz_szhtl',
        'companyName' => '深圳市鸿泰利机械设备有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6725381',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '颜世龙',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440922195508261917',
        'legalbodyMobile' => '18948766407',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6759282 =>
      array (
        'companyUserId' => '6759282',
        'companyUserName' => 'jg_rz_rxjkj',
        'companyName' => '深圳市瑞讯嘉科技有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6722235',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '王凯龙',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '410223198805265531',
        'legalbodyMobile' => '18948766407',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6760365 =>
      array (
        'companyUserId' => '6760365',
        'companyUserName' => 'jg_rz_rqzs',
        'companyName' => '深圳市润强装饰工程有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6721957',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '高叶飞',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '330421197102074724',
        'legalbodyMobile' => '18948766407',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6760567 =>
      array (
        'companyUserId' => '6760567',
        'companyUserName' => 'jg_rz_szgld',
        'companyName' => '深圳市冠来达建筑安装工程有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6725221',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '颜世龙',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440922195508261917',
        'legalbodyMobile' => '18948766407',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6779396 =>
      array (
        'companyUserId' => '6779396',
        'companyUserName' => 'jg_bjdws',
        'companyName' => '北京达沃森投资管理有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '3726381',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2026-03-07',
        'legalbodyName' => '李周',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '352102197903050415',
        'legalbodyMobile' => '13288622376',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6788889 =>
      array (
        'companyUserId' => '6788889',
        'companyUserName' => 'jg_rz_jxdmy',
        'companyName' => '深圳市吉迅达贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '5770840',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2020-11-01',
        'legalbodyName' => '陈远超',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440882198402104715',
        'legalbodyMobile' => '13751163489',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6804285 =>
      array (
        'companyUserId' => '6804285',
        'companyUserName' => 'jg_rz_jlkj',
        'companyName' => '深圳市进林科技有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6720011',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2018-05-12',
        'legalbodyName' => '李继平',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '360104196105311519',
        'legalbodyMobile' => '15800891603',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6804791 =>
      array (
        'companyUserId' => '6804791',
        'companyUserName' => 'jg_rz_rewh',
        'companyName' => '天津市瑞恩文化艺术传播有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6825027',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2025-09-11',
        'legalbodyName' => '张建',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '12010119790526503X',
        'legalbodyMobile' => '18222836666',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6811644 =>
      array (
        'companyUserId' => '6811644',
        'companyUserName' => 'jg_rz_zahk',
        'companyName' => '中安（天津）航空设备有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6791050',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2029-10-19',
        'legalbodyName' => '马玉山',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '110105195605297130',
        'legalbodyMobile' => '13901116842',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6812193 =>
      array (
        'companyUserId' => '6812193',
        'companyUserName' => 'jg_rz_bjrtz',
        'companyName' => '深圳市佰佳瑞投资有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6717247',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2023-09-17',
        'legalbodyName' => '庞杰',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '411123197908270019',
        'legalbodyMobile' => '18312349511',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6812785 =>
      array (
        'companyUserId' => '6812785',
        'companyUserName' => 'jg_ygtx',
        'companyName' => '深圳易股天下互联网金融服务有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '2410675',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '陆小兰',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '430526198205201787',
        'legalbodyMobile' => '18616553280',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6812875 =>
      array (
        'companyUserId' => '6812875',
        'companyUserName' => 'jg_zkcjr',
        'companyName' => '中科创金融控股集团有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6761343',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '张伟',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '230306197208164055',
        'legalbodyMobile' => '18312349511',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6818048 =>
      array (
        'companyUserId' => '6818048',
        'companyUserName' => 'jg_rz_ssht',
        'companyName' => '河北盛世辉腾体育用品有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6818478',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2028-08-17',
        'legalbodyName' => '马连军',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '130605196903201518',
        'legalbodyMobile' => '13315296888',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6818110 =>
      array (
        'companyUserId' => '6818110',
        'companyUserName' => 'jg_yxty',
        'companyName' => '石家庄市跃翔体育用品销售有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6823055',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '马建军',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '130602197110162171',
        'legalbodyMobile' => '18932678898',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6823630 =>
      array (
        'companyUserId' => '6823630',
        'companyUserName' => 'jg_rz_tdjx',
        'companyName' => '长春泰德机械设备有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '32018',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2020-04-06',
        'legalbodyName' => '黎杰',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '222303193102010042',
        'legalbodyMobile' => '13581770882',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6826801 =>
      array (
        'companyUserId' => '6826801',
        'companyUserName' => 'jg_rz_lhby',
        'companyName' => '北京力鸿勃阳商贸有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6806562',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2033-01-23',
        'legalbodyName' => '秦红艳',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '642102197410201544',
        'legalbodyMobile' => '13811685740',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6846738 =>
      array (
        'companyUserId' => '6846738',
        'companyUserName' => 'jg_rz_ljyncp',
        'companyName' => '深圳市绿嘉源农产品有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '7085',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '潘家传',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210724199008052617',
        'legalbodyMobile' => '15840690208',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6848536 =>
      array (
        'companyUserId' => '6848536',
        'companyUserName' => 'jg_rz_dfyz',
        'companyName' => '深圳市东方银座集团有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6843411',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2116-01-01',
        'legalbodyName' => '李森',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440902196304120054',
        'legalbodyMobile' => '13802262215',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6848660 =>
      array (
        'companyUserId' => '6848660',
        'companyUserName' => 'jg_rz_szgsf',
        'companyName' => '深圳市高盛丰酒店用品有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6722389',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '颜世龙',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '440922195508261917',
        'legalbodyMobile' => '18948766407',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6850607 =>
      array (
        'companyUserId' => '6850607',
        'companyUserName' => 'jg_rz_xhzy',
        'companyName' => '兴河置业（大连）有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '91621',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2034-02-16',
        'legalbodyName' => '李庆颜',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '210204196804024861',
        'legalbodyMobile' => '13904262392',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6885188 =>
      array (
        'companyUserId' => '6885188',
        'companyUserName' => 'jg_rz_clcw',
        'companyName' => '深圳市车来车往信息咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '959875',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张卫东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510721196906119313',
        'legalbodyMobile' => '13990166566',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6885282 =>
      array (
        'companyUserId' => '6885282',
        'companyUserName' => 'jg_szclcw',
        'companyName' => '深圳市车来车往信息咨询有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '959875',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2099-01-01',
        'legalbodyName' => '张卫东',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '510721196906119313',
        'legalbodyMobile' => '13990166566',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6890501 =>
      array (
        'companyUserId' => '6890501',
        'companyUserName' => 'jg_rz_dyfdc',
        'companyName' => '深圳市德友房地产有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6839887',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2021-03-23',
        'legalbodyName' => '庞杰',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '411123197908270019',
        'legalbodyMobile' => '18312349511',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6903619 =>
      array (
        'companyUserId' => '6903619',
        'companyUserName' => 'jg_rz_assp',
        'companyName' => '爱莎尚品（天津）服饰贸易有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6873349',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2031-05-31',
        'legalbodyName' => '王莹',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '120104197601016025',
        'legalbodyMobile' => '13821900889',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6913251 =>
      array (
        'companyUserId' => '6913251',
        'companyUserName' => 'jg_rz_swym',
        'companyName' => '大连生威玉米有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '6618370',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2019-04-01',
        'legalbodyName' => '祝明耀',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '220103196511041035',
        'legalbodyMobile' => '13304116580',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '',
      ),
      6923618 =>
      array (
        'companyUserId' => '6923618',
        'companyUserName' => 'jg_rz_hrtx',
        'companyName' => '深圳键桥华瑞通讯技术有限公司',
        'companyPurpose' =>
        array (
          0 => 2,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1709176',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-06-11',
        'legalbodyName' => '范绍林',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '342122197906232031',
        'legalbodyMobile' => '15999562340',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '不再开展相关业务',
      ),
      6923765 =>
      array (
        'companyUserId' => '6923765',
        'companyUserName' => 'jg_hgtx',
        'companyName' => '深圳键桥华冠通讯技术有限公司',
        'companyPurpose' =>
        array (
          0 => 4,
        ),
        'companyBelong' => '资产管理部',
        'agentUserId' => '1709653',
        'credentialsExpireDate' => '2010-01-01',
        'credentialsExpireAt' => '2032-06-11',
        'legalbodyName' => '范绍林',
        'legalbodyCredentialsType' => 1,
        'legalbodyCredentialsNo' => '342122197906232031',
        'legalbodyMobile' => '13662139023',
        'majorName' => 'auto',
        'majorCondentialsType' => 'auto',
        'majorCondentialsNo' => 'auto',
        'majorMobile' => 'auto',
        'memo' => '不再开展业务',
      ),
    );
}