<?php

/**
 * Created by PhpStorm.
 * User: gao
 * Date: 2016/6/20
 * Time: 18:00
 */
class HttpRequest extends CHttpRequest
{
    private $_csrfToken;
    public $enableCsrfReferrerValidation = false;
    private $referer = "/^(.*\.)?itouzi\.com$/";
    public $csrfTokenName = 'itz-csrftoken';
    private $secretKey = 'IM!MgxTaEkW$EOLM';
    public $csrfTokenExpireTime = 3000;//秒，50分钟
    public $blackUrl = [];
    /**
     * @var HeaderCollection Collection of request headers.
     */
    private $_headers;

    public function getCsrfToken($action='')
    {
        !$action && $action = explode("?", $this->getUrl())[0];
        $time = time();
        $csrfToken = $this->createCsrfToken($time,$action);
        return $csrfToken;
    }

    public function validateCsrfToken($event)
    {
        if($this->getIsPostRequest() && in_array(explode("?",$this->getUrl())[0], $this->blackUrl))
        {  // only validate POST requests
            //正常post
            if(isset($_POST[$this->csrfTokenName]))
            {
                $csrfTokenFromClient = strval($_POST[$this->csrfTokenName]);

            }
            //ajax post
            elseif($this->getHeaders()->has($this->csrfTokenName)){
                $csrfTokenFromClient = strval($this->getHeaders()->itemAt($this->csrfTokenName));

            }else{
                $csrfTokenFromClient = '';

            }

            $valid = true;
            //referrrer url验证
            if($this->enableCsrfReferrerValidation && !$this->validateCsrReferer()){
                $valid=false;
            }

            //验证
            if($valid && $csrfTokenFromClient){
                //赋值
                $action = explode("?",$this->getUrl())[0];
                $time = explode('&',$csrfTokenFromClient)[0];

                //有效期判断
                if(time()>$time+$this->csrfTokenExpireTime){
                    $valid = false;
                }else{
                    $tokenFromSession = $this->createCsrfToken($time,$action);
                    $valid=$tokenFromSession===$csrfTokenFromClient;
                }
            }else{
                $valid=false;
            }

            if(!$valid)
                $this->csrfInValidRespond();
        }
    }

    private function createCsrfToken($time,$action){
        $action = trim($action,"/");
        //不区分大小写
        $action = strtolower($action);
        $session = Yii::app()->session;
        $csrfToken = $time.'&'.sha1($time.'#'.$session->getSessionID()."#".$action."#".$this->secretKey);
        return $csrfToken;
    }

    /**
     * referrer判断
     * @return bool
     */
    private function validateCsrReferer(){
        $referer = $this->getUrlReferrer();
        if($referer){
            $host = strtolower(parse_url($referer)['host']);
            if(preg_match($this->referer,$host)){
                return true;
            }else{
                return false;
            }
        }else{
            return false;
        }
    }

    private function csrfInValidRespond(){
        if(!$this->getIsAjax()) {
            header('HTTP/1.1 401 Unauthorized');
            Yii::error("Token无效","","5"); exit();
        }
        //echo 'http error:400';
        // throw new CHttpException(400,Yii::t('yii','The CSRF token could not be verified.'));
        header ( "Content-type:application/json; charset=utf-8" );

        echo json_encode([
            'data' => [],
            'code' => 2101,
            'info' => '信息已过期，为安全起见，请重新输入'
        ]);
        Yii::app()->end();
    }

    /**
     * Returns the header arraylist.
     * The header collection contains incoming HTTP headers.
     * @return HeaderCollection the header collection
     */
    public function getHeaders()
    {
        if ($this->_headers === null) {
            $this->_headers = new HeaderCollectionYii1;
            if (function_exists('getallheaders')) {
                $headers = getallheaders();
            } elseif (function_exists('http_get_request_headers')) {
                $headers = http_get_request_headers();
            } else {
                foreach ($_SERVER as $name => $value) {
                    if (strncmp($name, 'HTTP_', 5) === 0) {
                        $name = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                        $this->_headers->add($name, $value);
                    }
                }
                return $this->_headers;
            }
            foreach ($headers as $name => $value) {
                $this->_headers->add($name, $value);
            }
        }
        return $this->_headers;
    }

    /**
     * Returns whether this is an AJAX (XMLHttpRequest) request.
     *
     * Note that jQuery doesn't set the header in case of cross domain 不支持ajax跨域的场景
     * requests: https://stackoverflow.com/questions/8163703/cross-domain-ajax-doesnt-send-x-requested-with-header
     *
     * @return boolean whether this is an AJAX (XMLHttpRequest) request.
     */
    public function getIsAjax()
    {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
    }

}

class HeaderCollectionYii1 extends CMap{
    private $_headers = [];

   /**
     * getcount
     * @return int
     */
    public function getCount()
    {
        return count($this->_headers);
    }

    /**
     * Adds a new header.
     * If there is already a header with the same name, the new one will
     * be appended to it instead of replacing it.
     * @param string $name the name of the header
     * @param string $value the value of the header
     * @return $this the collection object itself
     */
    public function add($name, $value)
    {
        $name = strtolower($name);
        $this->_headers[$name] = $value;
        return $this;
    }

    /**
     * Returns the item with the specified key.
     * This method is exactly the same as {@link offsetGet}.
     * @param mixed $key the key
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function itemAt($key)
    {
        if(isset($this->_headers[$key]))
            return $this->_headers[$key];
        else
            return null;
    }

    /**
     * Returns a value indicating whether the named header exists.
     * @param string $name the name of the header
     * @return boolean whether the named header exists
     */
    public function has($name)
    {
        $name = strtolower($name);
        return isset($this->_headers[$name]);
    }
}
