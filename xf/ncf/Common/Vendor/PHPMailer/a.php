<?php
require 'PHPMailerAutoload.php';

$mail = new PHPMailer();

//$mail->SMTPDebug = 3;                               // Enable verbose debug output

//$mail->isSMTP();                                      // Set mailer to use SMTP
//$mail->Host = 'smtp.qq.com';  // Specify main and backup SMTP servers
//$mail->SMTPAuth = true;                               // Enable SMTP authentication
//$mail->Username = '617137379@qq.com';                 // SMTP username
//$mail->Password = '';                           // SMTP password
////$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
//$mail->Port = 25;                                    // TCP port to connect to

$mail->From = '617137379@qq.com';
$mail->FromName = 'jingxu';
$mail->addAddress('jingxu@ucfgroup.com', 'Joe User');     // Add a recipient
//$mail->addReplyTo('info@example.com', 'Information');
//$mail->addCC('cc@example.com');
//$mail->addBCC('bcc@example.com');

//$mail->WordWrap = 50;                                 // Set word wrap to 50 characters
//$mail->addAttachment('/tmp/a.txt', 'new.jpg');    // Optional name
$mail->isHTML(true);                                  // Set email format to HTML

$mail->Subject = 'Here is the subject';
$mail->Body    = 'This is the HTML message body <b>in bold!</b> 1';
//$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';

if (!$mail->send()) {
    echo 'Message could not be sent.';
    echo 'Mailer Error: ' . $mail->ErrorInfo;
} else {
    echo 'Message has been sent';
}
