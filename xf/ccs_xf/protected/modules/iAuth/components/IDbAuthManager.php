<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/25
 * Time: 11:09
 */

namespace iauth\components;

use iauth\models\AuthAssignment;
use iauth\models\AuthItem;
use iauth\helpers\Meta;
use iauth\models\User;

/**
 * 权限管理组件
 *   因产品需求个性化配置过多，导致难以复用 Yii 本身的 CDbAuthManager，
 *   故 IDbAuthManager 将不继承该类。
 *
 * Class IDbAuthManager
 * @package iauth\components
 */
class IDbAuthManager extends \CComponent
{
    /**
     * @var string 双因子认证状态 key
     */
    public $dualFactorKey = 'iauth_dual_factor_auth';
    /**
     * @var string 权限管理员，将直接拥有 iauth 模块下所有权限
     */
    public $admin = 'hanfeng';
    /**
     * @var int 错误码
     */
    public $errCode = 0;

    public function getErrCode()
    {
        return $this->errCode;
    }

    /**
     * 检查权限
     * 一般来说，检查分两种情况，由 $willRun 决定
     *  1. 操作执行前的权限判断，需日志
     *  2. 前端页面检查是否有权限并以此显示对应按钮，导航等，不需日志
     * @param string $code
     * @param int $userId
     * @param bool $willRun
     * @return bool
     */
    public function checkAccess($code, $userId, $willRun = true)
    {
//        $user = $this->getUser($userId);
//        if (!$user) {
//            $this->errCode = Meta::C_USER_NOT_FOUND;
//            return false;
//        } else {
//            $code = strtolower(trim($code, '/'));
//            $authItem = $this->getAuthItem($code);
//            $assignments = $this->getAssignments($userId);
//
//            if (!$authItem) {
//                $this->errCode = Meta::C_AUTH_ITEM_NOT_FOUND;
//            } elseif (!$this->runnable($authItem)) {
//                $this->errCode = Meta::C_AUTH_DISABLED;
//            } elseif (!($this->inAssignments($authItem, $assignments) || $this->isAdminAssignment($user, $code))) {
//                $this->errCode = Meta::C_AUTH_FORBIDDEN;
//            } elseif ($this->needDualFactor($authItem)) {
//                $this->errCode = Meta::C_AUTH_NEED_DUAL_FACTOR;
//            } else {
//                $this->errCode = Meta::C_SUCCESS;
//            }
//        }
//
//        if ($willRun) {
//            $item = $authItem ? $authItem : $code;
//            $this->writeLog($item, $user);
//        }
//
//        if ($this->getErrCode()) {
//            return false;
//        } else {
//            /* 每次认证后清空上次双因子认证结果 */
//            $_SESSION[$this->dualFactorKey] = false;
//            return true;
//        }
        return true;
    }

    /**
     * 获取缓存中的用户信息
     * @param $userId
     * @return mixed
     */
    public function getUser($userId)
    {
        $key = 'iauth_auth_user_' . $userId;
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = User::model()
                                ->findByPk($userId, 'status = ' . User::STATUS_ENABLED)
                                ->attributes;
        }

        return $_SESSION[$key];
    }

    /**
     * 获取缓存中权限项目
     * @param $code
     * @return mixed
     */
    public function getAuthItem($code)
    {
        $key = 'iauth_auth_item_' . $code;
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = AuthItem::model()->getOneAuthItem(['code' => $code]);
        }

        return $_SESSION[$key];
    }

    /**
     * 获取缓存中指定用户的授权 ID 列表，
     * @param $userId
     * @return array
     */
    public function getAssignments($userId)
    {
        $key = 'iauth_auth_assignments_' . $userId;
        if (!isset($_SESSION[$key])) {
            $list = AuthAssignment::model()->getList(['user_id' => $userId]);
            $item_ids = [];
            foreach ($list as $item) {
                $item_ids[] = $item['item_id'];
            }
            $_SESSION[$key] = $item_ids;
        }

        return $_SESSION[$key];
    }

    /**
     * 写入日志，包括 Audit 和 Debug 日志
     * @param array|string $item
     * @param $user
     */
    private function writeLog($item, $user)
    {
        if (is_string($item)) {
            $authCode = $item;
            $authName = '不存在的操作';
        } else {
            $authCode = $item['code'];
            $authName = $item['name'];
        }
        $datetime = date('Y-m-d H:i:s');
        $username = $user['username'];
        $realName = $user['realname'];
        $result = $this->getErrCode() ? '认证失败' : '认证成功';
        $reason = $this->getErrCode() ? Meta::getCodeInfo($this->getErrCode()) : '拥有权限';

        /* @var string $info 示例：
         * iAuth: 得文[dewen] 在 2016-01-05 13:53:54 申请授权执行
         * 【编辑用户[iauth.user.edit】操作，认证失败。 原因：权限被停用。
         */
        $info = "iAuth: {$realName}[$username] 在 {$datetime} 申请授权执行 "
            . "【{$authName}[{$authCode}]】操作，{$result}。 "
            . "原因：{$reason}。";
        $parameters = [
            'code' => $authCode,
            'action' => $authName,
            'time' => $datetime,
            'info' => $info
        ];
        $log = [
            'user_id' => $user['id'],
            'action' => 'auth',
            'system' => 'user',
            'resource' => 'user/iauth',
            'result' => $this->getErrCode() ? 'fail' : 'success',
            'parameters' => $parameters,
        ];
        \AuditLog::getInstance()->method('add', $log);
    }

    public function isAdminAssignment($user, $code)
    {
        if ($user['username'] == $this->admin) {
            if (\Yii::app()->controller->module->id == 'iauth' || strpos($code, 'iauth') < 2) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

    /**
     * 判断是否在授权列表中
     * @param $authItem
     * @param $assignments
     * @return bool
     */
    public function inAssignments($authItem, $assignments)
    {
        return in_array($authItem['id'], $assignments);
    }

    /**
     * 判断是否需要双因子认证
     * @param $authItem
     * @return bool
     */
    public function needDualFactor($authItem)
    {
        return $authItem['dual_factor'] && !$_SESSION[$this->dualFactorKey];
    }

    /**
     * 判断权限本身是否可执行
     * @param $authItem
     * @return bool
     */
    public function runnable($authItem)
    {
        return $authItem['status'] == AuthItem::STATUS_ENABLED;
    }


    public function init()
    {
        return true;
    }
}
