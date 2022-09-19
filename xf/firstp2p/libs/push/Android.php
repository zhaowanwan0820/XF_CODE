<?php

/**
 * @author zhanglei5@ucfgroup.com
 * @encode UTF-8编码
*/
require_once 'Push.php';
require_once 'bae/Channel.php'; //下载的sdk里的channel.php
class Android extends Push
{
    protected $_send_cnt = 1;

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
        //$optional[Channel::CHANNEL_ID] = $channel_id;
        $optional[Channel::MESSAGE_EXPIRES] = 10;   //  过期时间默认是86400   产品要求即时发送
        $optional[Channel::DEVICE_TYPE] = 3;
        $optional[Channel::MESSAGE_TYPE] = 1;       //  安卓要求是消息
        $title = $params['title'] ?  trim($params['title']) : ' ';
        $content = $params['content'] ?  trim($params['content']) : ' ';
        $url = $params['url'] ?  trim($params['url']) : 'http://www.firstp2p.com';
        $message_arr = array(
            'title' => $title,
            'description' => $content,
//            'notification_builder_id'=>0,
            'notification_basic_style' => 7,
            'open_type' => 2,   //  1: 表示打开Url    2: 表示打开应用
            );

        if (is_array($params['custom_content']) && !empty($params['custom_content'])) {
            $message_arr['custom_content'] = $params['custom_content'];
        }

        $message = json_encode($message_arr);
        $message_key = $params['message_key'];
        $ret = $channel->pushMessage($push_type, $message, $message_key, $optional);
        if (false === $ret) {
            if($this->_send_cnt < 3) {  //加入重发机制
                $this->_send_cnt++;
                $this->push($params,$user_id);
            }
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
