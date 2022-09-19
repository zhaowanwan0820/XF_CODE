<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
*/
require_once 'Push.php';
require_once 'bae/Channel.php';
class Ios extends Push
{
    public function push($params,$user_id = 0)
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
        $optional[Channel::MESSAGE_EXPIRES] = 10;
        //$optional[Channel::CHANNEL_ID] = $channel_id;
        $optional[Channel::DEVICE_TYPE] = 4;    //  4代表ios设备
        $optional[Channel::MESSAGE_TYPE] = 1;   //  IOS要求是通知
        //$optional[Channel::DEPLOY_STATUS] = $this->getDeploy();
        $optional[Channel::DEPLOY_STATUS] = 1;  //1：开发状态    2:生产状态
        $title = $params['title'] ?  trim($params['title']) : ' ';
        $content = $params['content'] ?  trim($params['content']) : ' ';
        $url = $params['url'] ?  trim($params['url']) : 'http://www.firstp2p.com';
        $badge = intval($params['badge']) ? intval($params['badge']) : 1;
        if(!$content || $content == '') {
            $message = $params['custom_content'];
        }else {
            $aps = array( 'alert' => $content, 'sound' => '', 'badge' => $badge);
            if (is_array($params['custom_content']) && !empty($params['custom_content'])) {
                $message = $params['custom_content'];
                $message['aps'] = $aps;

                //$message = array_merge($aps, $params['custom_content']);
            } else {
                $message['aps'] = $aps;
            }
        }
        $message = json_encode($message);
        $message_key = $params['message_key'];

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
}
