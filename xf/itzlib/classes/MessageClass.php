<?php

/*
 * 消息
 */

class MessageClass
{
    private $_error = 'SendError: ';

    public $userRecord = null;
    protected static $_isAllow = null;

    /**
     * 获取未读消息
     **/
    public function getMessages($user_id)
    {
        //90天前的零点时间戳
        $timeNinetyDaysAgo = (string)strtotime('midnight -89 day');

        $MessageModel = new Message();
        $criteria = new CDbCriteria;
        $criteria->condition = "addtime >= '$timeNinetyDaysAgo'";
        $attributes = array(
            "receive_user" => $user_id,
            "status" => 0,
            "deltype" => 0,
        );
        $MessageResult = $MessageModel->findAllByAttributes($attributes, $criteria);
        return $MessageResult;
    }

    /**
     * 获取未读消息数量
     */
    public function getCount($user_id)
    {
        /******************读取缓存中未读消息的数量(开始)*******************/
        $user_id = $this->getUserID($user_id);
        $unread = RedisService::getInstance()->get($this->getUnReadCountKey($user_id));
        if ($unread == '') {
            $unread = $this->setUnreadMsgCountToCache($user_id);
        }
        return $unread;
        /******************读取缓存中未读消息的数量(结束)*******************/
    }


    public function send($receiveUserId, $sentUserId, $name, $content, $type = 'system', $status = '0')
    {

        if (empty($type)) {
            $type = 'system';
        }
        if (is_array($type)) $type = $type['type'];
        $this->userId = $receiveUserId;
        $this->sendType = $type;
        $this->_error .= __FUNCTION__ . " " . print_r(func_get_args(), true);
        if (!$this->_isAllow()) {
            return false;
        }
		Yii::log('message send data '.print_r(func_get_args(),true),'info',__CLASS__);
		$time = time();
        $ip = FunctionUtil::ip_address();
        $sql = "INSERT INTO `dw_message` (`sent_user`, `receive_user`, `status`, `type`, `name`, `content`, `addtime`, `addip`) VALUES (:sent_user, :receive_user, :status, :type, :name, :content, :addtime, :addip)";
        $connection = $this->_getDb();
        $command = $connection->createCommand($sql);
        $command->bindParam(':sent_user', $sentUserId, PDO::PARAM_INT);
        $command->bindParam(':receive_user', $receiveUserId, PDO::PARAM_INT);
        $command->bindParam(':status', $status, PDO::PARAM_INT);
        $command->bindParam(':type', $type, PDO::PARAM_STR);
        $command->bindParam(':name', $name, PDO::PARAM_STR);
        $command->bindParam(':content', $content, PDO::PARAM_STR);
        $command->bindParam(':addtime', $time, PDO::PARAM_INT);
        $command->bindParam(':addip', $ip, PDO::PARAM_STR);
        if ($command->execute() == false) {
            return false;
        } else {

            /******************读取消息时删除缓存(开始)*******************/
            $this->delUnReadMessageCountCache($receiveUserId);
            /******************读取消息时删除缓存(结束)*******************/
            return true;
        }
    }


    /**
     * 将未读消息的数量存入缓存中,并返回未读消息的数量
     */
    public function setUnreadMsgCountToCache($user_id)
    {
        //90天前的零点时间戳
        $timeNinetyDaysAgo = (string)strtotime('midnight -89 day');

        $user_id = $this->getUserID($user_id);
        /************************将未读消息数量写入缓存（开始）**********************/
        $returnResult = array(
            "unread_message_num" => 0,    //未读消息数量
        );
        $CDbCriteria = new CDbCriteria;
        $CDbCriteria->select = "count(1)";
        $CDbCriteria->condition = "addtime >= '$timeNinetyDaysAgo'";
        $attributes["receive_user"] = $user_id;
        $status_condition = array(
            'unread' => array(0),
        );
        $CDbCriteria->addInCondition("status", $status_condition['unread']);
        $unread_message_num = BaseCrudService::getInstance()->count("Message", "", 0, "ALL", "", $attributes, $CDbCriteria);

        RedisService::getInstance()->set($this->getUnReadCountKey($user_id), $unread_message_num, 3600);
        return $unread_message_num;
        /************************将未读消息数量写入缓存（结束）**********************/
    }


    //获取user_id
    public function getUserID($user_id = '')
    {
        if (empty($user_id)) {
            $user_id = $this->user_id;
        }
        return $user_id;
    }

    //获取未读消息数量的redis中key值
    public function getUnReadCountKey($user_id)
    {
        return 'unread_message_count' . $user_id;
    }

    //删除缓存中未读消息的数量
    public function delUnReadMessageCountCache($user_id = '')
    {
        $user_id = $this->getUserID($user_id);
        return RedisService::getInstance()->del($this->getUnReadCountKey($user_id));
    }


    /**
     * [_isAllow 配置是否允许发送站内信]
     * @return boolean [description]
     */
    private function _isAllow()
    {
        if (!self::$_isAllow) {
            $this->thisPointConfig = $this->_getDb()->createCommand()->select('id, status')->where('code=:code', [':code' => $this->sendType])->from('itz_trigger_point')->queryRow();
            self::$_isAllow = $this->thisPointConfig;
        }
        $this->thisPointConfig = self::$_isAllow;

        if (!$this->thisPointConfig || $this->thisPointConfig['status'] == 0) {
            $this->_error .= ' LINE: ' . __LINE__ . ' Has been deactivated or not Exists';
            return $this->_writeLog();
        }
        return $this->_userNoticeConfigFilter();
    }

    /**
     * [_userNoticeConfigFilter 用户设置是否发送站内信]
     * @return [type] [description]
     */
    private function _userNoticeConfigFilter()
    {
        if (!$this->userId) return true;
        $config = isset($this->userRecord['remind']) ? $this->userRecord['remind'] : current($this->_getDb()->createCommand()->select('remind')->where('user_id=:user_id', [':user_id' => $this->userId])->from('dw_user')->queryRow());
        if ($config == null) return true;
		$sendType = $this->sendType;
		$config = unserialize($config);
		$noticeConfig = Yii::app()->params['userNoticeConfig']['showNotice'];
		foreach ($noticeConfig as $k => $item) {
			if(in_array($sendType,$item['list'])){
				$sendType = $k ;
			}
		}
		if(array_key_exists($sendType, $config)) {
			if(array_key_exists('message', $config[$sendType]) && $config[$sendType]['message'] != 1) {
				$this->_error .= " LINE:" . __LINE__ . " Error Reason: User Not Allow";
				return $this->_writeLog();
			}
		}
		return true;
    }

    private function _getDb()
    {
        if (isset(Yii::app()->dwdb)) {
            return Yii::app()->dwdb;
        }
        return Yii::app()->db;
    }
    private function _writeLog($type = 'info')
    {
        Yii::log($this->_error, $type, __CLASS__);
        return false;
    }
}
