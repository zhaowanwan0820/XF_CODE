<?php
/**
 * IMail.php
 *
 * @date 2014-08-01
 * @author liangqiang <liangqiang@ucfgroup.com>
 */

namespace libs\mail;

/**
 * 邮件接口，具体邮件实现类需要实现接口方法
 *
 * Class IMail
 * @package libs\mail
 */
interface IMail {

    /**
     * 设置发件人信息
     * 如果不设置则采用默认值
     *
     * @param $from
     * @param $from_name
     * @return mixed
     */
    public function setFrom($from, $from_name);

    /**
     * 邮件发送
     *
     * @param $subject 标题
     * @param $content 正文内容
     * @param $to 收件人email地址或者多个收件人email地址数组
     * @param $files 附件数组，{{'path'=>'xxx', 'name'=>'xxx'},{'path'=>'xxx', 'name'=>'xxx'}}
     * @return mixed 发送结果 {'message'=>'success'} {'message'=>'error', 'error'=>'xxx'}
     */
    public function send($subject, $content, $to, $files);

}