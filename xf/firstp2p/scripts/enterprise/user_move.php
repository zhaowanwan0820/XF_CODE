<?php
/**
 * 企业用户一期迁二期
 * php scripts/enterprise/user_move.php [-t] [param]
 * [-t]  param为userId字符串 多个id使用逗号分隔
 *
 * @param 迁移用户的csv文件地址(文件每行一个用户id)
 */

ini_set("display_errors", 1);
error_reporting(E_ALL);

set_time_limit(0);
ini_set('memory_limit', '512M');

require_once dirname(__FILE__).'/../../app/init.php';

use libs\utils\Curl;
use core\service\UserService;
use core\service\EnterpriseService;
use NCFGroup\Protos\Ptp\Enum\UserAccountEnum;
use core\dao\EnterpriseModel;
use core\dao\EnterpriseContactModel;
use core\dao\UserModel;

class EnterpriseTask {
    private $url = '';
    private $userId = array();

    const PARAM_TYPE_USERID = 1;
    const PARAM_TYPE_URL    = 2;

    public function __construct($param, $type = self::PARAM_TYPE_URL) {
        if (empty($param)) {
            echo 'param is empty' . PHP_EOL;
            exit;
        }

        if ($type == self::PARAM_TYPE_URL) {
            $this->url = $param;
        } else {
            $userIds = explode(',', $param);
            if (empty($userIds) || !is_array($userIds)) {
                echo 'the format of userIds is wrong';
                exit;
            }
            $this->userId = $userIds;
        }
    }

    public function run() {
        $result = $this->moveUser();
        echo implode(PHP_EOL, $result).PHP_EOL;
    }

    private function moveUser() {
        // download csv
        $userIds = $this->userId;
        if (empty($userIds)) {
            $userIds = $this->getCsv();
        }

        if (!is_array($userIds) || empty($userIds)) {
            echo "userIds are empty";
            return false;
        }
        $fields = 'id, user_name, real_name, idno, user_purpose';
        // TODO 就在这行
        $users = (new UserService())->getUserInfoByIds($userIds, $fields);
        $existUsers = array_keys($users);
        $notExistUsers = array_diff($userIds, $existUsers);
        echo '部分id未查询到用户信息:'.implode(',', $notExistUsers).PHP_EOL;
        $enterpriseService = new EnterpriseService();
        $needMoveUsers = array();
        foreach ($users as $user) {
            // 唯一性验证
            if (EnterpriseModel::instance()->getEnterpriseInfoByUserID($user['id']) || !$enterpriseService->canCredentialsNo($user['idno'], $user['id'], $user['user_purpose'])) {
                echo "用户{$user['id']}唯一性验证失败".PHP_EOL;
                continue;
            }
            $needMoveUsers[] = $user;
            echo "用户{$user['id']}唯一性验证成功".PHP_EOL;
        }
        return $this->insertAll($needMoveUsers);
    }

    private function getCsv() {
        $file = fopen($this->url,'r');
        if (empty($file)) {
            echo 'url错误，csv文件下载失败' . PHP_EOL;
            exit;
        }
        $userIds = array();
        while($line = fgetcsv($file)) {
            $userIds[] = $line[0];      // 每行一个id
        }
        return $userIds;
    }

    /**
     * 批量新增企业用户
     *
     * @author sunxuefeng@ucfgroup.com
     * @date   2018.10.23
     */
    private function insertAll(array $userInfoArrs) {
        $res = array();
        $userService = new UserService();
        foreach ($userInfoArrs as $user) {
            $GLOBALS['db']->startTrans();
            try {
                $data = array();                                                                        // 个人 -> 企业
                // 可能为0
                isset($user['user_purpose']) && ($data['company_purpose'] = $user['user_purpose']);     // 账户类型 -> 账户类型
                !empty($user['id']) && ($data['user_id'] = $user['id']);
                !empty($user['user_name']) && ($data['identifier'] = $user['user_name']);               // 会员名称 -> 企业会员标识
                !empty($user['real_name']) && ($data['company_name'] = $user['real_name']);             // 姓名 -> 企业全称
                !empty($user['create_time']) && ($data['create_time'] = $user['create_time']);
                $data['update_time'] = time();
                if (!empty($user['idno'])) {                                                            // 证件号 -> 企业证件号码
                    $data['credentials_no'] = $user['idno'];
                    // 9开头是的三合一
                    $data['credentials_type'] = (substr($user['idno'], 0, 1) == 9)
                        ? UserAccountEnum::CREDENTIALS_TYPE_LICENSE_NEW
                        : UserAccountEnum::CREDENTIALS_TYPE_LICENSE;
                }
                $result = EnterpriseModel::instance()->addEnterpriseInfo($data);
                if (empty($result)) {
                    throw new \Exception('企业用户基本信息更新失败');
                }
                if (empty($user['id'])) {
                    throw new \Exception('userId is empty');
                }
                $params = array(
                    'user_id' => $user['id'],
                    'create_time' => time(),
                );
                $params['update_time'] = $params['create_time'];
                $result = EnterpriseContactModel::instance()->addEnterpriseContact($params);
                if (empty($result)) {
                    throw new \Exception('企业用户联系人信息更新失败');
                }
                $userParams = array(
                    'id' => $user['id'],
                    'user_type' => UserModel::USER_TYPE_ENTERPRISE,
                );
                $result = $userService->updateInfo($userParams);
                if (empty($result)) {
                    throw new \Exception('用户类型更新失败');
                }

                $GLOBALS['db']->commit();
                $loginfo = "用户{$user['id']}迁移成功";
            } catch (\Exception $e) {
                $GLOBALS['db']->rollback();
                $loginfo = "用户{$user['id']}迁移失败, message:".$e->getMessage();
                continue;
            }
            $res[] = $loginfo;
        }
        return $res;
    }
}

$option = getopt('t:');
$param = isset($option['t']) ? $option['t'] : $argv[1];
if (isset($option['t'])) {
    $param = $option['t'];
    $type = EnterpriseTask::PARAM_TYPE_USERID;
} else {
    $param = $argv[1];
    $type = EnterpriseTask::PARAM_TYPE_URL;
}

echo date('Y-m-d H-i-s').' 数据迁移开始'.PHP_EOL;
$enterpriseTask = new EnterpriseTask($param, $type);
$enterpriseTask->run();
echo date('Y-m-d H-i-s').' 数据迁移结束'.PHP_EOL;

