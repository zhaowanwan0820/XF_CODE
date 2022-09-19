<?php
namespace libs\utils;

/**
 * 新OAuth 相关接口操作类
 */
class OAuth
{
    private $_client_id;
    private $_oauth_url;

    /**
     * 构造方法
     * @param type $url
     * @param type $client_id
     */
    function __construct($url="", $client_id='8bd14ebfbf5a958115273470e0dc73ec')
    {
        $this->_oauth_url = empty($url) ? app_conf("NEW_OAUTH_API_URL") . "oauthserver_firstp2p" : $url;
        $this->_client_id = $client_id;
    }

    /**
     * 登录接口
     * @param type $username 用户名
     * @param type $password 密码
     * @return boolean
     * 
     * 返回结果:
     *      {"code":alfjasdfjkaslf,"success":true}
     *  错误编码:
     *      {"code":0001,"reason":"认证失败"}
     *      {"code":20010,"reason":"响应方式不正确"}
     *      {"code":20001,"reason":"用户名不能为空"}
     *      {"code":20002,"reason":"密码不能为空"}
     *      {"code":20003,"reason":"用户名不存在"}
     *      {"code":20004,"reason":"用户密码错误"}
     *      {"code":20005,"reason":"客户端不存在"}
     */
    public function login($username, $password) {
        $api_url = '/rs/userlogin/login?_type=json';

        $data = '<LoginParam>';
        $data .= "<username>{$username}</username>";
        $data .= "<password><![CDATA[{$password}]]></password>";
        $data .= "<response_type>code</response_type>";
        $data .= "<client_id>{$this->_client_id}</client_id>";
        $data .= '</LoginParam> ';

        $ret = $this->httpRequest($this->_oauth_url . $api_url, $data);
        if (!empty($ret)) {
            return json_decode($ret, true);
        }
        return null;
    }

    /**
     * 退出接口
     * @param type $code OAuth Code
     * @return Null
     * 返回结果:
     *     {"result":true}   true : 登出成功， false ： 登出失败
     *  错误编码:
     *     {"code":20005,"reason":"客户端不存在"}
     *     {"code":20006,"reason":"用户未登录"}
     */
    public function logout($code)
    {
        $api_url='/rs/userlogin/logout?_type=json';

        $data='<LoginParam>';
        $data.="<code>{$code}</code>";
        $data.="<client_id>{$this->_client_id}</client_id>";
        $data.='</LoginParam> ';

        $ret=$this->httpRequest($this->_oauth_url.$api_url,$data);
        if(!empty($ret))
        {
            return json_decode($ret,true);
        }
        return null;
    }

    /**
     * 用户修改密码
     * @param type $oldpwd 旧密码
     * @param type $newpwd 新密码
     * @param type $sessionid OAuth Code
     * @return null
     * 返回结果:
     *      {"oldpwd":111111,"pwd":222222,"sessionid":286}
     *  错误编码:
     *      {"code":201,"reason":"数据不存在"}
     */
    public function updateuserpwd($oldpwd,$newpwd,$sessionid)
    {
        $api_url='/rs/user/updateuserpwd?_type=json';

        $data='<PwdUser>';
        $data.="<oldpwd>{$oldpwd}</oldpwd>";
        $data.="<pwd>{$newpwd}</pwd>";
        $data.="<sessionid>{$sessionid}</sessionid>";
        $data.='</PwdUser> ';

        $ret=$this->httpRequest($this->_oauth_url.$api_url,$data);
        if(!empty($ret))
        {
            return json_decode($ret,true);
        }
        return null;
    }

    /**
     * 找回密码
     * @param type $code OAuth Code
     * @param type $pwd 新密码
     * @param type $tele 手机
     * @param type $email 邮箱
     * @param type $userid 用户id
     * @param type $passportid OAuth的passportid
     * @return null
     * 返回结果:
     *      {"oldpwd":111111,"pwd":222222,"sessionid":286}
     *  错误编码:
     *      {"code":312,"reason":"新密码不能为空"}
     *      {"code":316,"reason":"未输入手机"}
     *      {"code":317,"reason":"未输入邮箱"}
     *      {"code":314,"reason":"邮箱未校验"}
     *      {"code":313,"reason":"手机未校验"}
     *      {"code":201,"reason":"数据不存在"}
     */
    public function finduserpwd($code,$pwd,$tele,$email,$userid='',$passportid='')
    {
        $api_url='/rs/user/finduserpwd?_type=json';

        $data='<PwdUser>';
        $data.="<userid>{$userid}</userid>";
        $data.="<passportid>{$passportid}</passportid>";
        $data.="<pwd>{$pwd}</pwd>";
        $data.="<code>{$code}</code>";
        $data.="<tele>{$tele}</tele>";
        $data.="<email>{$email}</email>";
        $data.="<findpwdType>1</findpwdType>";
        $data.='</PwdUser> ';

        $ret=$this->httpRequest($this->_oauth_url.$api_url,$data);
        if(!empty($ret))
        {
            return json_decode($ret,true);
        }
        return null;
    }

    /**
     * 注册添加用户信息
     * @param type $username 用户名
     * @param type $password 密码
     * @param type $email 邮箱
     * @param type $phone 手机号
     * @return Null
     * 返回结果:
     *      {"createTime":"2013-07-11T13:30:51+08:00","deleted":1,"email":"zp.q@163.com","id":221,"idcard":11111,"loginTime":"2013-07-17T10:44:03+08:00","nickName":"qzp","pwd":"96E79218965EB72C92A549DD5A330112","sex":0,"state":0,"truename":111,"updateTime":"2013-07-17T10:44:03+08:00","username":"qzp","usertype":1，“passportid”：12 }
     *  必返回：passportid
     *  错误编码:
     *      {"code":500,"reason":"服务端内部出错"}
     *      {"code":303,"reason":"用户名被占用"}
     *      {"code":304,"reason":"电话号码被占用"}
     * 
     */
    public function addUser($username,$password,$email,$phone,$code)
    {
        $api_url='/rs/user/add?_type=json';

        $data='<SysUser>';
        $data.="<username>{$username}</username>";
        $data.="<pwd><![CDATA[{$password}]]></pwd>";
        $data.="<email>{$email}</email>";
        $data.="<telephone>{$phone}</telephone>";
        $data.="<code>{$code}</code>";
        $data.="<client_id>{$this->_client_id}</client_id>";
        $data.='</SysUser> ';

        $ret=$this->httpRequest($this->_oauth_url.$api_url,$data);
        if(!empty($ret))
        {
            return json_decode($ret,true);
        }
        return null;
    }

    /**
     * 发送手机短信验证码
     * @param type $phonenum 手机号
     * @return json
     * 返回结果:
     *      {"result":true}   true : 发送成功， false ： 发送失败
     *  错误编码:
     *      {"code":501,"reason":"请不要频繁发送验证码"}
     */ 
    public function sendVcodeByMobile($phonenum, $behavior="用户注册")
    {
        $api_url='/rs/verifycode/send?_type=json';

        $data="<VerifyCodeParam><telephone>{$phonenum}</telephone><behavior>{$behavior}</behavior></VerifyCodeParam>";
        $ret=$this->httpRequest($this->_oauth_url.$api_url,$data);
        if(!empty($ret))
        {
            return json_decode($ret,true);
        }
        return null;
    }

    /**
     * 校验用户名、邮箱、手机号的唯一性
     * @param type $phone 手机号
     * @param type $email 邮箱
     * @param type $username 用户名
     * @return json
     * 返回结果:
     *      {"resulet":true}
     *      true(数据库中不存在)或者false（数据库中存在）
     *  错误编码:
     *      {"code":500,"reason":"服务端内部出错"}
     *      {"code":20005,"reason":"客户端不存在","option":"下次可以修改的时间"}
     *      {"code":303,"reason":"用户名被占用","option":"下次可以修改的时间"}
     *      {"code":304,"reason":"电话号码被占用","option":"下次可以修改的时间"}
     *      {"code":305,"reason":"邮箱被占用","option":"下次可以修改的时间"}
     * 
     */
    public function checkuserinfor($phone,$email,$username)
    {
        $api_url='/rs/user/checkuserinfor?_type=json';

        $data='<UserInfo>';
        $data.="<client_id>{$this->_client_id}</client_id>";
        $data.="<username>{$username}</username>";
        $data.="<email>{$email}</email>";
        $data.="<telephone>{$phone}</telephone>";
        $data.='</UserInfo>';

        $ret=$this->httpRequest($this->_oauth_url.$api_url,$data);
        if(!empty($ret))
        {
            return json_decode($ret,true);
        }
        return false;
    }

    /**
     * 获取用户信息
     * @param type $code 登录时获取的code
     * @param type $grant_type 目前定值authorization_code
     * @return json
     * 返回结果:
     *  {"birthday":"Tue Nov 05 00:00:00 CST 2013","sex":"1","username":"a15923027758","updatetime":"Tue Oct 22 10:41:49 CST 2013","truename":"陈 鑫","email":"admin@qq.com","telephone":"15923027758","id":"1","passportid":"12","expires_in":3600,"access_token":"aa3226a05929683459856e1520161e2"}
     *        Sex : 0=男，1=女
     *        expires_in: code失效时间，暂时未使用
     *        access_token：授权码，暂时未使用
     *        必返回：passportid
     *  错误编码:
     *      {"code":306,"reason":"获取参数出错"}
     *      {"code":307,"reason":"clientid or secret error"}
     *      {"code":308,"reason":"authorization code error"}
     *      {"code":309,"reason":"invalid oauth grant type"}
     *
     */
    public function getUserInfo($code,$grant_type='authorization_code')
    {
        $api_url='/rs/oauth2/token?_type=json';

        $data='<AuthorizeParam>';
        $data.="<client_id>{$this->_client_id}</client_id>";
        $data.="<code>{$code}</code>";
        $data.="<grant_type>{$grant_type}</grant_type>";
        $data.='</AuthorizeParam>';

        $ret=$this->httpRequest($this->_oauth_url.$api_url,$data);
        if(!empty($ret))
        {
            return json_decode($ret,true);
        }
        return false;
    }

    /**
     * 发送请求
     * @param type $url
     * @param string $data
     * @return type
     */
    private function httpRequest($url,$data)
    {
        //添加xml头信息
        $data='<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'.$data;
        //初始化
        $ch = curl_init();

        //设置选项，包括URL
        $header=array();
        $header[0] = "Accept: text/xml,application/xml,application/xhtml+xml,";   
        $header[0] .= "text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5";   
        $header[] = "Cache-Control: max-age=0";   
        $header[] = "Connection: keep-alive";   
        $header[] = "Keep-Alive:300"; 
        $header[] = "Content-type: application/xml";
        $header[] = "Content-Length:".strlen($data);

        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'firstp2p.com');
        curl_setopt($ch, CURLOPT_HTTPHEADER,$header);
        curl_setopt($ch, CURLOPT_TIMEOUT,30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        curl_setopt($ch, CURLOPT_HEADER,0);
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        //执行并获取文档内容
        $output = curl_exec($ch);
        curl_close($ch);
        return $output;
    }
}