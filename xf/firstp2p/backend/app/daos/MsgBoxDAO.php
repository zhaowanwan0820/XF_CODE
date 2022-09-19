<?php
namespace NCFGroup\Ptp\daos;

use NCFGroup\Ptp\models\firstp2p_msg_box\Firstp2pMsgBox;
use NCFGroup\Ptp\models\Firstp2pConf;
use NCFGroup\Protos\Ptp\Enum\MsgBoxEnum;

class MsgBoxDAO
{
    /**
     * 创建消息
     */
    public function create($userId, $typeId, $title, $content) {

        $groupArr = array(0, $userId);
        sort($groupArr);
        $groupArr[] = $typeId;
        $msg = new Firstp2pMsgBox();
        $msg->toUserId = $userId;
        $msg->type = $typeId;
        $msg->title = $title;
        $msg->content = $content;
        $msg->fromUserId = 0;
        // 这里是因为老数据都是-8小时的时间
        $msg->createTime = time() - 28800;
        $msg->isNotice = intval($typeId);
        $msg->groupKey = implode('_', $groupArr);
        $msg->systemMsgId = 0;
        $msg->favId = 0;
        $msg->save();
        return true;
    }

    /**
     * 移动端获取未读消息数
     */
    public function getUnreadCount($userId) {

        $condition = ' toUserId = "'.$userId.'" AND isRead = '.MsgBoxEnum::MSG_STATUS_UNREAD.' AND isNotice IN (' . implode(',', array_keys(MsgBoxEnum::$appType)) . ')';
        return Firstp2pMsgBox::count($condition);
    }
}
