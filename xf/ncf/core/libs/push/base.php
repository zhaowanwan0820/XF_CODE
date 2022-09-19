<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
 */
include('bae/Channel.php');
class Base
{
    private $_api_key = 'pWBkRu2RscBTN0Ni5hVZYHFX';
    private $_secret_key = 'b82xtmY07nmN5DNuBhtPaCralqWz4e9D';

    public function getDeviceUser($user_id)
    {
        return 0;
    }

/*
    protected function _pushMessageIos($user_id, $channel_id, $content, $params)
    {

        $channel = new Channel($this->_api_key, $this->_secret_key);
        $push_type = 1;
        $optional[Channel::USER_ID] = $user_id;
        $optional[Channel::CHANNEL_ID] = $channel_id;
        $optional[Channel::DEVICE_TYPE] = 4;
        $optional[Channel::MESSAGE_TYPE] = 1;
        //$optional[Channel::DEPLOY_STATUS] = $this->getDeploy();
        $optional[Channel::DEPLOY_STATUS] = 2;
        $badge = intval($params['badge']) ? intval($params['badge']) : 1;
        if (!$content || $content == '') {
            $message = json_encode($params['custom_content']);
        } else {
            $aps = array('aps' => array( 'alert' => $content, 'sound' => '', 'badge' => $badge));
            if (is_array($params['custom_content']) && !empty($params['custom_content'])) {
                $message = json_encode(array_merge($aps, $params['custom_content']));
            } else {
                $message = json_encode($aps);
            }
        }

        $message_key = $user_id . '_' . M::D('NOW_TIME');
        $ret = $channel->pushMessage($push_type, $message, $message_key, $optional);
        if (false === $ret) {
            $error_output = 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!!';
            $error_output .= ', ERROR NUMBER: ' . $channel->errno();
            $error_output .= ', ERROR MESSAGE: ' . $channel->errmsg();
            $error_output .= ', REQUEST ID: ' . $channel->getRequestId();
            return $ret = array('error' => $error_output);
        }else{
            return $ret;
        }
    }
*/
    public function pushMessageAndroid ($params,$user_id = 0)
    {
        $channel = new Channel($this->_api_key, $this->_secret_key);
        //推送消息到某个user，设置push_type = 1;
        //推送消息到一个tag中的全部user，设置push_type = 2;  不知道什么时候该使用tag.
        //推送消息到该app中的全部user，设置push_type = 3;
        if($user_id > 0) {
            $push_type = 1; //推送单播消息
            $optional[Channel::USER_ID] = $user_id;
        }else { // 群体发送
            $push_type = 3;
        }


        //$optional[Channel::CHANNEL_ID] = $channel_id;
        $optional[Channel::DEVICE_TYPE] = 3;
        $optional[Channel::MESSAGE_TYPE] = 1;
        $title = $params['title'] ?  trim($params['title']) : ' ';
        $content = $params['content'] ?  trim($params['content']) : ' ';
        $url = $params['url'] ?  trim($params['url']) : 'http://www.firstp2p.com';
        $message_arr = array(
            'title' => $title,
            'description' => $content,
            'notification_basic_style' => 7,
            'open_type' => 1,
 //           'custom_content' => $params['custom_content'],
            'url'=>$url
            );
        $message = json_encode($message_arr);
        $message_key = time();
        $ret = $channel->pushMessage($push_type, $message, $message_key, $optional);
        if (false === $ret) {
            $error_output = 'WRONG, ' . __FUNCTION__ . ' ERROR!!!!!';
            $error_output .= ', ERROR NUMBER: ' . $channel->errno();
            $error_output .= ', ERROR MESSAGE: ' . $channel->errmsg();
            $error_output .= ', REQUEST ID: ' . $channel->getRequestId();
            return $ret = array('error' => $error_output);
        }else{
            return $ret;
        }
    }
/*
    public function setTag($tag_name, $user_id, $channel_id)
    {
        $channel = new Channel($this->_api_key, $this->_secret_key);
        $optional[Channel::USER_ID] = $user_id;
        $optional[Channel::CHANNEL_ID] = $channel_id;
        $ret = $channel->setTag($tag_name, $optional);
        if (false === $ret) {
            return false;
        }else{
            return true;
        }
    }

    public function deviceType($channel_id)
    {
        $channel = new Channel($this->_api_key, $this->_secret_key);
        $ret = $channel->queryDeviceType($channel_id);
        if (false === $ret) {
            return false;
        } else {
            return $ret['response_params']['device_type'];
        }
    }
*/
    public function setKey($api_key,$secret_key) {
    	$this->_api_key = $api_key;
    	$this->_secret_key = $secret_key;
    }
}
