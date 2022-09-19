<?php

namespace NCFGroup\Ptp\daos;

use \Assert\Assertion as Assert;
use NCFGroup\Ptp\models\firstp2p_push\PushUser;

/**
 * Push
 * 推送相关数据库操作
 * @package default
 */
class PushDAO
{

    const STATUS_SIGNIN = 0;

    const STATUS_SIGNOUT = 1;

    /**
     * 添加用户设备
     */
    public static function addUser($appId, $appUserId, $baiduChannelId, $data)
    {
        $db = getDI()->get('firstp2p_push');

        $data['appId'] = $appId;
        $data['appUserId'] = $appUserId;
        $data['baiduChannelId'] = $baiduChannelId;
        $data['status'] = self::STATUS_SIGNIN;
        $data['signtime'] = time();
        $data['updatetime'] = time();

        try {
            $db->begin();

            //登出其他用户
            $db->update('push_user', array('status', 'updatetime'), array(self::STATUS_SIGNOUT, time()),
                        "appId='{$appId}' AND baiduChannelId='{$baiduChannelId}' AND appUserId!='{$appUserId}'");

            //是否已存在
            $where = "appId='{$appId}' AND baiduChannelId='{$baiduChannelId}' AND appUserId='{$appUserId}'";
            $ret = $db->fetchOne("SELECT id FROM push_user WHERE {$where}");
            if (empty($ret)) {
                $data['createtime'] = time();
                $db->insert('push_user', array_values($data), array_keys($data));
            } else {
                $db->update('push_user', array_keys($data), array_values($data), $where);
            }

            $db->commit();
        } catch (\Exception $e) {
            \libs\utils\Logger::error('PushDAO_addUser:'.$e->getMessage());
            $db->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * 删除用户设备
     */
    public static function deleteUser($appId, $appUserId, $baiduChannelId)
    {
        $db = (new PushUser())->getDI()->get('firstp2p_push');
        $db->update('push_user', array('status', 'updatetime'), array(self::STATUS_SIGNOUT, time()),
                "appId='{$appId}' AND appUserId='{$appUserId}' AND baiduChannelId='{$baiduChannelId}' AND status=0");
        return $db->affectedRows();
    }

    /**
     * 查询用户绑定状态
     */
    public static function getStatusByUser($appId, $appUserId)
    {
        $db = (new PushUser())->getDI()->get('firstp2p_push');

        $ret = $db->fetchAll("SELECT id FROM push_user WHERE appId='{$appId}' AND appUserId='{$appUserId}' AND status=0 LIMIT 1");
        return empty($ret) ? false : true;
    }

    /**
     * 获取所有Channel
     */
    public static function getUserChannels($appId, $appUserId)
    {
        $builder = PushUser::query()
                ->columns('*')
                ->where('appId=:appId:', array('appId' => intval($appId)))
                ->andWhere('appUserId=:appUserId:', array('appUserId' => intval($appUserId)))
                ->andWhere('status=0');
        return $builder->execute()->toArray();
    }

}
