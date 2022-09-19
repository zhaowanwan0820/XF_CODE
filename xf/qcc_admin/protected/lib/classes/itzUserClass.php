<?php

/**
 * BorrowClass .
 *
 * 后台用户类，封装一些常用的操作
 */

class itzUserClass {
    //通过职位名称获得email
    public function getEmailsByName($nameArray) {
        $emailArray = array();
        foreach ($nameArray as $value) {
            $ItzAuthItemModel = ItzAuthAssignment::model() -> with('userInfo') -> findAllByAttributes(array('itemname' => $value));
            foreach ($ItzAuthItemModel as $v) {
                $emailArray[] = $v -> userInfo -> email;
            }
        }
        return $emailArray;
    }

    //给指定职位的所有员工的邮箱发送信息
    public function sendEmailToName($nameArray, $title, $content) {
        $emailArray = $this -> getEmailsByName($nameArray);
        foreach ($emailArray as $k => $v) {
            $mailSender = new MailClass();
            $mailSender -> sendToUser(1, $v, $title, $content);
        }
    }
    //给运营部的全部人员发邮件
    public function SendEmailToCustomer($title, $content){
        $mailSender = new MailClass();
        $mailSender -> sendToUser(1, "o@zichanhuayuan.com,lvning@zichanhuayuan.com", $title, $content,$attachment = array(), $sendType = 'system', $direct = false);
    }
}
