<?php

/**
 * DealMsgListService
 *
 * @packaged default
 * @author zhanglei5@ucfgroup.com
 **/

namespace core\service;

use core\dao\DealMsgListModel;
use core\dao\EmailModel;
use core\service\AttachmentService;
use libs\mail\Mail;
use libs\utils\Logger;


class DealMsgListService extends BaseService {

    const SUCC_EMAIL = 1; // 处理成功
    const ERR_QUEUE_INSERT = 0; // 没有成功插入队列（redis故障）
    const ERR_DAEMON = 2; // 守护进程死亡或僵尸
    const ERR_EMAIL_SEND = 3; // 调用发送邮件类失败

    /**
     * 通过时间获取发送列表
     * @param $time
     * @param string $type
     * @param string $title
     * @param int $send_type
     * @return array
     */
    function getNotSendByTime($time,$type='>=',$title='',$send_type=1) {
        $list = DealMsgListModel::instance()->getNotSendByTime($time,$type,$title,$send_type);
        $list = count($list) ? $list : array();
        return $list;
    }

    /**
     * 使用标题获取发送失败的列表
     * @param $title
     * @param int $send_type
     * @return array
     */
    function getFaildByTitle($title,$send_type=1) {
        $list = DealMsgListModel::instance()->getFaildByTitle($title,$send_type);
        $list = count($list)>0 ? $list : array();
        return $list;
    }

    /**
     * getBySCId 
     * 根据sendcloud id 查找 msg
     * @author zhanglei5 <zhanglei5@group.com> 
     * @param mixed $sc_id
     * @access public
     * @return void
     */
    function getBySCId($sc_id) {
        if (strlen($sc_id) > 0) {
            $row = DealMsgListModel::instance()->find(array('sc_id'=>$sc_id));
            $row = count($row)>0 ? $row: array();
            return $row;
        } else {
            return false;
        }
    }

    /**
     * 通过 sid 更新邮件队列数据
     * @param $data
     * @param $scId
     * @return mixed
     */
    function updateStatusBySCId($data,$scId) {
        $rs = DealMsgListModel::instance()->updateStatusBySCId($data,$scId);
        return $rs;
    }

    /**
     * getListByTitleAndEmail  根据title和email 查询结果
     * 指定 邮件发送失败报警脚本用 script/email_monitor.php
     * @author zhanglei5 <zhanglei5@group.com> 
     * 
     * @param string $title 
     * @param array $emails 
     * @access public
     * @return void
     */
    public function getListByTitleAndEmail($title,$emails) {
        $rs = DealMsgListModel::instance()->getListByTitleAndEmail($title,$emails);
        return $rs;
    }

    /**
     * 发送邮件
     * @param $msg
     * @return array
     */
    public function sendMsg($msg){
        //处理队列字段名不同的问题
        $rs = array('is_send'=>false,'msg'=>'');
        if (empty($msg['address']) || empty($msg['content']) || empty($msg['title'])) {
            $rs['msg'] = "address or content or title emtpy";
            return $rs;
        }
        \libs\utils\Monitor::add("EMAIL_START",1);//开始邮件
        //处理附件
        $attachments = array();
        if (!empty($msg['attachment']) && !is_array($msg['attachment'])) {
            $attachments = explode(',', $msg['attachment']);
        }
        $files = array();
        foreach ($attachments as $item) {
            if (is_numeric($item)) {
                //add by zhanglei5    判断是否从vfs读取 附件
                if (isset($msg['is_vfs']) && ($msg['is_vfs'] == 1)) {
                    $attach_service = new AttachmentService();
                    //获得下载文件路径
                    $attachment['path'] = $attach_service->getAttrVfs($item);
                    logger::info("email_attachment_name | path | " . json_encode($attachment));
                    $name = basename($attachment['path']);
                    logger::info("email_attachment_name | base_name | " . $name);
                    $attachment['name'] = iconv("utf-8", "gbk", $name);
                    logger::info("email_attachment_name | gbk_name | " . $attachment['name']);

                    if(isset($msg['is_basename']) && ($msg['is_basename'] == 1)){
                        $attachment['name'] = preg_replace('/^.+[\\\\\\/]/', '', $attachment['path']);
                        logger::info("email_attachment_name | preg_name | " . json_encode($attachment));
                    }
                } else { // 原来的逻辑
                    $arr_attach = get_attr($item, false, true, true);
                    $attachment['path'] = $arr_attach['attachment'];
                    $attachment['name'] = $arr_attach['filename'];
                }
            } else {
                $path_parts = pathinfo($item);
                $attachment['path'] = $item;
                $attachment['name'] = $path_parts['basename'];//附件文件名
            }
            if (file_exists($attachment['path'])) {
                $files[] = $attachment;
            } else {
                logger::error($this->FormatLog($msg) . " | '" . $attachment['path'] . "' file not exists");
            }
        }
        // 发送邮件
        $mail = new Mail();
        $mail_to = explode(",", $msg['address']);
        //是否需要设置发件人信息
        $fromEmailData = $msg['fromEmailData'];
        if(!empty($fromEmailData)){
            $mail->setFrom($fromEmailData['from'], $fromEmailData['sender']);
        }
        // 是否发送告警邮件
        $is_send_alert_email = true;
        // 尝试失败 3次重发
        $result_send = $mail->send($msg['title'], $msg['content'], $mail_to, $files);
        logger::info("email_attachment | " . json_encode($files));


        if (!empty($result_send['error'])) {
            // 符合特定的错误消息，不需要重试和发送告警邮件
            $is_send_alert_email = $this->errorMsgIsAlertMail($result_send['error']);

            if(!empty($msg['id'])){
                $this->updateDealMsgStatus(self::ERR_EMAIL_SEND, $result_send['error'], $msg['id'],'');
            }

            if ($is_send_alert_email === true){
                // 正常的错误，发送失败发送告警邮件

                $this->alertMail($msg['address'], $result_send['error']);
            }
            $rs['msg'] = $result_send['error'];
            logger::error($this->FormatLog($msg) . " | " . $rs['msg']);
            return $rs;
        }else {
            if(!empty($msg['id'])){
                $this->updateDealMsgStatus(self::SUCC_EMAIL, '', $msg['id'],$result_send['emailId']);
            }
            \libs\utils\Monitor::add("EMAIL_SUCCEED",1);//邮件成功
            logger::info($this->FormatLog($msg));
        }
        $rs['is_send'] = true;
        return $rs;
    }

    /**
     * 格式化log
     * @param $msg
     * @return string
     */
    private function FormatLog($msg) {
        unset($msg['title'], $msg['content'], $msg['fromEmailData']);
        return implode(" | ", $msg);
    }

    /**
     * 更新发邮件状态
     * updateDealMsgStatus
     * modify by zhanglei5 <zhanglei5@group.com>
     * @param int $is_success 是否发送成功
     * @param string $msg 消息
     * @param int $id 邮件 id
     * @param string $sc_id  sendcloud 的回执id  emailId
     * @access private
     * @return void
     */
    private function updateDealMsgStatus($is_success, $msg = '', $id = '', $sc_id = '') {
        if (!$id) {
            return true;
        }
        //mysql数据处理，根据id类型判断，11位以下整数为mysql的id
        if (preg_match("/^\d{1,11}$/", $id)) {
            return $this->updateDealMsgStatusMysql($is_success, $msg, $id, $sc_id);
        }

        $email_model = EmailModel::get($id);
        $email_model['sc_id'] = DealMsgListModel::escape_string($sc_id);
        $email_model['is_success'] = $is_success;
        $email_model['is_send'] = 1;
        $email_model['send_time'] = get_gmtime();
        if (!empty($msg)) {
            $email_model['result'] = $msg;
        }
        return $email_model->save();
    }

    private function updateDealMsgStatusMysql($is_success, $msg = '', $id='' ,$sc_id='') {
        if (!$id) {
            return true;
        }
        $msgList = new DealMsgListModel();
        $sc_id = $msgList->escape($sc_id);
        $msgList = $msgList->find($id, "id");
        if(!$msgList){
            logger::info(__CLASS__ . " | " . __FUNCTION__ . " | before escape | " . $sc_id.' | '.$id.'|没有找到对应的邮件内容');
            return false;
        }
        $msgList->sc_id = $sc_id;
        $msgList->is_success = $is_success;
        if (!empty($msg)){
            $msgList->result = $msg;
        }
        $msgList->is_send = 1;
        $msgList->send_time = get_gmtime();
        $rs = $msgList->save("SILENT");
        return $rs;
    }

    /**
     * 如果发送邮件失败，则根据配置发送报警邮件
     * @param $address
     * @param $msg
     * @return bool
     */
    public function alertMail($address, $msg) {
        \FP::import("libs.common.dict");
        $arr_address = \dict::get("MSG_WARN_EMAIL");
        if (!$arr_address) { // 如果没有配置失败报警，则不执行报警
            return false;
        }
        $content = date("Y-m-d H:i:s") . " 发送邮件失败，目标邮箱：{$address}，错误信息：{$msg} 【网信理财】";
        $mail = new Mail();
        $mail->send("邮件发送失败报警".$address, $content, $arr_address);
    }
    /**
     * 检查是否不需要发送告警和重试
     * @param string $error_msg
     * @return bool
     */
    public function errorMsgIsAlertMail($error_msg){
        if (empty($error_msg)){
            \libs\utils\Monitor::add("EMAIL_FAIL_PARAMETER",1);//邮件参数错误
            return true;
        }
        $error_msg_list = array(
                 'EMAIL_FAIL_WHITE_LIST' => 'empty tos after white list',
                 'EMAIL_FAIL_INVALID'=>'Parameter to has invalid emails',
                    );
        $ret = true;
        $key='EMAIL_FAIL';
        foreach ($error_msg_list as $k=>$v){
            if (stripos($error_msg, $v) !== false){
                $ret = false;
                $key = $k;
                break;
            }
        }
        \libs\utils\Monitor::add($key,1);//邮件打点
        return $ret;
    }
} // END class Deal
