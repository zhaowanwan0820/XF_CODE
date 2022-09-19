<?php

define("WWW_DIR", "/home/guyun/v2trunk");

require("mail.php");

$mail = new Mail("Test!", "<strong>这是一封测试邮件</strong>", array(array("guyun@xxx.com","Gary"),"kuangjun@xxx.com"));
echo $mail->Send();
echo $mail->GetErrorMsg();
?>
