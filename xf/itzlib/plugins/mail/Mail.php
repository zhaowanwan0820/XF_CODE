<?php
/**
 * @file mail.php
 * @author (guyun@xxx.com)
 * @date 2013/12/15
 * 发送邮件
 **/

include_once( WWW_DIR .'/thirdlib/phpmailer/PHPMailerAutoload.php' );

class Mail {

        //错误信息
        protected $ErrorMsg;

        //编码
        protected $CharSet;

        //是否需要SMTP认证，true需要，false不需要
        protected $SMTPAuth;

        //SMTP Host
        protected $Host;

        //SMTP Username 
        protected $Username;

        //SMTP Password
        protected $Password;

        //发件地址
        protected $From;

        /* 送达地址
         * 邮件接收者 array(
         *      array(mail_address, mail_name)
         * )
         */
        protected $To = array();

        //发送者
        protected $FromName;

        //主题
        protected $Subject;

        //邮件内容
        protected $Body;

        /* 附件
         * 发送的附件文件路径 array(
         *      array('/path/to/file.zip', '/path/to/image.jpg')
         * )
         */
        protected $Attachment = array();
        
        protected static $_config = Null;
        
        public function __construct() {
            $this->CharSet = 'UTF-8';
            $this->SMTPAuth = true;
            
        }
        
        public function setGateway($gatewayData) {
        	$this->Host       = $gatewayData['host'];
        	$this->Username   = $gatewayData['username'];
        	$this->Password   = $gatewayData['password'];
        	if(!empty($gatewayData['from'])) {
        		$this->From	  = $gatewayData['from'];
        	}
        	if(!empty($gatewayData['fromName'])) {
        		$this->FromName	= $gatewayData['fromName'];
        	}
        }
        
        /**
         * 设置SMTP
         * @param string $host
         * @param string $username
         * @param string $password
         * @param string $from
         * @param string $fromName
         */
        public function setSMTP($host, $username, $password, $from = '', $fromName = '') {
        	$this->Host       = $host;
        	$this->Username   = $username;
        	$this->Password   = $password;
        	if(!empty($from)) {
        		$this->From	  = $from;
        	}
        	if(!empty($fromName)) {
        		$this->FromName	= $fromName;
        	}
        }
        
        /**
         * 设置发送者邮件地址
         * @param string $from
         * @param string $fromName
         */
        public function setFrom($from, $fromName = '') {
        	$this->From	  = $from;
        	if(!empty($fromName)) {
        		$this->FromName	= $fromName;
        	}
        }
        
        /**
         * 设置接受者地址
         * @param array $to
         */
        public function setTo($to) {
        	$this->To = $to;
        }
        
        /**
         * 设置发送数据
         * @param array $to
         * @param string $subject
         * @param string $body
         * @param array $attachment
         */
        public function setSendData($to, $subject, $body, $attachment = array()) {
        	$this->To = $to;
        	$this->Subject = $subject;
        	$this->Body = $body;
        	if( !empty($attachment) && !is_array($attachment)) {
        		$this->Attachment = array($attachment);
        	} else {
        		$this->Attachment = $attachment;
        	}
        }

        /**
         * 发送邮件
         * @return bool
         */
        public function Send () {

                $mail = new PHPMailer();

                $mail->CharSet = $this->CharSet;
                $mail->isSMTP();
                $mail->SMTPAuth   = $this->SMTPAuth;
                $mail->Host       = $this->Host;
                $mail->Username   = $this->Username;
                $mail->Password   = $this->Password;
                $mail->From       = $this->From;
                $mail->FromName   = $this->FromName;
                // 必填，邮件标题（主题）
                $mail->Subject    = $this->Subject;
                // 邮件内容
                $mail->Body       = $this->Body;
                // 可选，纯文本形势下用户看到的内容
                $mail->AltBody    = "";
                // 自动换行的字数
                $mail->WordWrap   = 50;
                // I don't know what is this
                //$mail->MsgHTML(str_replace('__MAILTO__',$mail->From,$this->Body));
                // 回复邮箱地址
                $mail->addReplyTo($mail->From, $mail->FromName);
                // 添加附件,注意路径
                if(!empty($this->Attachment)) {
	                foreach($this->Attachment as $attpath) {
	                        if(file_exists($attpath)){
	                                $mail->AddAttachment($attpath);
	                        }
	                }
                }
                // 添加发送地址
                foreach($this->To as $to) {
                        if(is_array($to)){
                                $mail->addAddress($to[0],isset($to[1])?$to[1]:'');//('josh@example.net', 'Josh Adams')
                        }else{
                                $mail->addAddress($to);//('ellen@example.com')
                        }
                }
                // 是否以HTML形式发送，如果不是，请删除此行
                $mail->isHTML(true);

                if(!$mail->Send()) {
                        $this->ErrorMsg = $mail->ErrorInfo;
                        return false;
                }

                return true;
        }

        public function GetErrorMsg(){
                return $this->ErrorMsg;
        }
        
        public function getFrom() {
        	return $this->From;
        }
}
?>
