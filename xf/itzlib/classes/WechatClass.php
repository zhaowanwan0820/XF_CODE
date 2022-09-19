<?php

/**
 * @file WechatClass.php
 * @author zlei
 * @description: 微信消息处理类
 */
class WechatClass
{
    protected static $_config = Null;

    public $userRecord = null;

    private $sendType;

    private $_error = 'SendError: ';

    private $thisPointConfig = null;
    private $queueData = array();

    
    /**
     * [sendToUser 给用户发送微信]
     * @param  [type] $userId   [用户ID]
     * @param  [type] $phone    [手机号]
     * @param  [type] $content  [内容]
     * @param  string $sendType [触发点code]
     * @return [type]           [返回True或False]
     */
    public function sendToUser($userId, $templateid, $openid, $content,$url, $sendType = '', $delayTime = null)
    {
    	if (!$sendType) $sendType = 'system';
    	$this->_error = __FUNCTION__ . ':' . print_r(func_get_args(), true);
    	if (empty($userId) || empty($openid) || empty($content) || (!$this->isOpenid($openid))) return $this->_writeLog();
    	return $this->_Handle($userId, $templateid, $openid, $content, $url, $sendType, $delayTime);
    }

    /**
     * [_Handle 短信处理方法]
     * @param  [type] $userId   [用户ID]
     * @param  [type] $phone    [手机号]
     * @param  [type] $content  [内容]
     * @param  string $sendType [触发点code]
     * @return [type]           [description]
     */
    private function _Handle($userId = '',$templateid='', $openid=0, $content='',$url='', $sendType = '', $delayTime=0)
    {
        $this->sendType = $sendType = ($sendType == 'direct' || empty($sendType)) ? "direct" : $sendType;
        $this->userId = $userId;
        $this->openid = $openid;
        $this->_sendTypeSelect();
        if (!$this->_isAllow() or $this->_grayList()) {
            return false;
        }
        $this->queueData['user_id'] = (String)$userId;
        $this->queueData['openid'] = $this->openid;
        $this->queueData['content'] = $content;
        $this->queueData['templateid'] = $templateid;
        $this->queueData['mtype'] = $this->sendType;
        $this->queueData['url'] = $url;
        $this->queueData['createtime'] = time();
        $this->queueData['delayWechatTime'] = (int) $delayTime;
        return $this->_allowSend();
    }

    /**
     * [_allowSend 允许发送调用的函数]
     * @return [type] [description]
     */
    private function _allowSend()
    {	Yii::log('wechat send data '.print_r($this->queueData,true),'info',__CLASS__);
        return $this->addQueue($this->queueData);
    }


    /**
     * [_grayList 灰名单过滤]
     * @return [type] [description]
     */
    private function _grayList()
    {
    	return false;
    	
        if (!$this->thisPointConfig) return false;
        $config = $this->_getDb()->createCommand()->select('params')->where('pointid = :pointid and type = :type', [':pointid' => $this->thisPointConfig['id'], ':type' => 3])->from('itz_trigger_template')->queryRow();
        if (empty($config)) {
            return $this->_writeLog();
        }
        $this->config = unserialize($config['params']);
        if (!$this->config['filter']) return $this->_writeLog();
        $blacklistservice = new BlackListService();
        $status = $blacklistservice->isInBlackList((String)$this->phone);
        if($status) {
            $this->_error .= " LINE:" . __LINE__ . " Error Reason: grayList Not Allow";
            $this->_writeLog();
        }
        return $status;
    }

    /**
     * [_isAllow 配置是否允许发送微信]
     * @return boolean [description]
     */
    private function _isAllow()
    {
        $this->thisPointConfig = $this->_getDb()->createCommand()->select('id, status')->where('code=:code', [':code' => $this->code])->from('itz_trigger_point')->queryRow();
        if (!$this->thisPointConfig || $this->thisPointConfig['status'] == 0) {
            $this->_error .= " LINE:" . __LINE__ . ' Error Reason：msg system Error';
            return $this->_writeLog();
        }
        return $this->_userNoticeConfigFilter();
    }

    /**
     * [_userNoticeConfigFilter 用户设置是否发送短信]
     * @return [type] [description]
     */
    private function _userNoticeConfigFilter()
    {

        if (!$this->userId) return true;
        $config = $this->userRecord['remind'] ? $this->userRecord['remind'] : current($this->_getDb()->createCommand()->select('remind')->where('user_id=:user_id', [':user_id' => $this->userId])->from('dw_user')->queryRow());
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
			if(array_key_exists('wxmsg', $config[$sendType]) && $config[$sendType]['wxmsg'] != 1) {
				$this->_error .= " LINE:" . __LINE__ . " Error Reason: User Not Allow";
				return $this->_writeLog();
			}
		}
		return true;
    }
    
    /**
     * [_sendTypeSelect 保证平滑上线]
     * @return [type] [description]
     */
    private function _sendTypeSelect()
    {
    	if (is_array($this->sendType)) {
    		$this->queueData['mtype'] = $this->sendType['mtype'];
    		$this->code = $this->sendType['mtype'];
    	} else {
    		$this->code = $this->queueData['mtype'] = $this->sendType;
    	}
    	$this->sendType = $this->queueData['mtype'];
    	return true;
    }

    /**
     * 加入到发送队列
     * @param array $data
     * @return booleanclear
     */
    public function addQueue($data)
    {
    	//延迟消息处理
    	if($data['delayWechatTime'] > time()+120 ) {
    		return Yii::app()->dqueue->rpush('wxmsg_delay', json_encode($data));
    	}
    	
        //正常插入redis
    	return Yii::app()->newceleryqueue->lPush('wxmsg',json_encode($data));
    }
    
    private function _getDb()
    {
    	if (isset(Yii::app()->dwdb)) {
    		return Yii::app()->dwdb;
    	}
    	return Yii::app()->db;
    }
    private function _writeLog($type = 'info') {
    	Yii::log($this->_error, $type, __CLASS__);
    	return false;
    }
    /**
     * 判断openid
     * @param string openid
     * @return boolean
     */
    public function isOpenid($openid){ 
    	return true;
    }
    
}
