<?php
/**
 * MailSender.php
 *
 * @date 2014-08-01
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace libs\mail;

\FP::import("libs.utils.es_mail");

/**
 * Class MailSender
 * @package libs\mail
 *
 * 邮件服务
 */
class MailSender implements IMail {

    /**
     * 邮件发送
     *
     * @param $subject 标题
     * @param $content 正文内容
     * @param $to 收件人email地址或者多个收件人email地址数组
     * @param $files 附件数组，{{'path'=>'xxx', 'name'=>'xxx'},{'path'=>'xxx', 'name'=>'xxx'}}
     * @return mixed 发送结果 {'message'=>'success'} {'message'=>'error', 'error'=>'xxx'}
     */
    public function send($subject, $content, $to, $files = false) {
        $mail = new \mail_sender();
        $mail->Subject = $subject;
        $mail->Body = $content;
        $mail->IsHTML(1);

        //收件人
        foreach ($to as $to_address) {
            $mail->AddAddress($to_address);
        }

        //附件
        foreach ($files as $file) {
            if (file_exists($file['path'])) {
                $mail->AddAttachment($file['path'], $file['name']);
            }
        }

        if ($mail->Send()) {
            $result = array('message' => 'success');
        } else {
            $result = array('message' => 'error', 'error' => $mail->ErrorInfo);
        }
        return $result;
    }

    /**
     * 设置发件人信息
     * 如果不设置则采用默认值
     *
     * @param $from
     * @param $from_name
     * @return mixed
     */
    public function setFrom($from, $from_name) {

    }

}