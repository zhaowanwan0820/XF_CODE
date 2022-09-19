<?php
/**
 * 发送消息类
 */



class MessageService {
    private static  $url = '';
    private static  $smsUrl = '/api/v1/BusinessNotify/sendSms';
    private static  $emailUrl = '/api/v1/BusinessNotify/SendEmail';
    private static  $header = ["x-itz-apptoken:r&ht0E@*aGeJkNg3d6X3gOM&WWEbGCgO"];
    private static  $warningUserEmail=['lirongze@huanhuanyiwu.com','wangyanan@huanhuanyiwu.com'];

    /**
     * 公共调用方法
     * MessageService::sendEmail($data) 发邮件
     * MessageService::sendSms($data)	 发短信
     * @param $name
     * @param $data
     * @return array|bool|mixed|string
     * @throws \Exception
     */
    public  static function __callStatic($name, $data) {
        $res 	= [];
        $pos 	= strrpos( $name, "send" );
        if($pos === false) {
            throw new \Exception ( "undefined method:$name" );
        }
        $data 	= current($data);
        self::makeUrl($name);
        self::check($name,$data);
        if(isset($data['email'])){
            if(is_array($data['email'])){
                foreach ($data['email'] as $email){
                    $data['email']  = $email;
                    $res = curl_request(self::$url,'POST',$data,self::$header);
                }
            }else{
                $res = curl_request(self::$url,'POST',$data,self::$header);
            }
        }
        if(isset($data['phone'])){
            self::$url = self::getRequestIp().self::$smsUrl;
            if(is_array($data['phone'])){
                foreach ($data['phone'] as $phone){
                    $data['phone']  = $phone;
                    $res = curl_request(self::$url,'POST',$data,self::$header);
                }
            }else{
                $res = curl_request(self::$url,'POST',$data,self::$header);
            }
        }
        return $res;
    }


    /**
     * 报警邮件方法
     * @param $title
     * @param $content
     * @param $email
     * @return mixed
     */
    public static function warningEmail($title,$content,$email=[]){
        $remind = [];
        $remind['receive_user'] = -1;
        $remind['email'] = $email?:self::$warningUserEmail;
        $remind['data']['alert_email_title'] = $title; //title
        $remind['data']['alert_msg'] = $content;//content
        $remind['mtype'] = "program_110";
        $res = MessageService::sendEmail($remind);
        return $res;
    }

    /**
     * 获取请求地址
     * @param $func
     * @throws \Exception
     */
    private static function makeUrl($func){
        switch ($func){
            case 'sendEmail':
                self::$url = self::getRequestIp().self::$emailUrl;
                break;
            case 'sendSms':
                self::$url = self::getRequestIp().self::$smsUrl;
                break;
            case 'warningEmail':
                self::$url = self::getRequestIp().self::$emailUrl;
                break;
            default:
                throw new \Exception('当前仅支持邮件、短信');
        }
    }


    /**
     * 校验参数
     * @param $name
     * @param $data
     * @throws \Exception
     */
    private static function check($name,$data){
        if (!isset($data['receive_user']) || empty($data['receive_user'])) {
            throw new \Exception ('Parameter "receive_user" cannot be empty');
        }
        if ($name=='sendSms' && (!isset($data['phone']) || empty($data['phone']))) {
            throw new \Exception ('Parameter "phone" cannot be empty');
        }
        if ($name=='sendEmail' && (!isset($data['email']) || empty($data['email']))) {
            throw new \Exception ('Parameter "email" cannot be empty');
        }
        if (!isset($data['mtype']) || empty($data['mtype'])) {
            throw new \Exception ('Parameter "mtype" cannot be empty');
        }
    }

    /**
     * 获取i_message 请求ip
     * @return mixed
     */
    private static function getRequestIp(){
        return '118.178.33.58:23587';
    }

}
