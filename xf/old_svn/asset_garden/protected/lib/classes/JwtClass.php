<?php

/**
 * PHP实现jwt
 */
class JwtClass
{
    //头部
    private static $header = array(
        'alg' => 'HS256', //生成signature的算法
        'typ' => 'JWT'  //类型
    );

    /**
     * 获取jwt token
     * @param array $payload jwt载荷  格式如下非必须
     * [
     * 'iss'=>'jwt_admin', //该JWT的签发者
     * 'iat'=>time(), //签发时间
     * 'exp'=>time()+7200, //过期时间
     * 'nbf'=>time()+60, //该时间之前不接收处理该Token
     * 'sub'=>'www.admin.com', //面向的用户
     * 'jti'=>md5(uniqid('JWT').time()) //该Token唯一标识
     * ]
     * @return bool|string
     */
    public static function getToken(array $payload , $sign = '')
    {
        if (is_array($payload)) {
            if (!isset($payload['exp'])) {
                $payload['exp'] = time() + 86400;//1天过期
            }
            if (empty($sign)) {
                $sign = ConfUtil::get('Jwtkey');
            }
            $base64header = self::base64UrlEncode(json_encode(self::$header, JSON_UNESCAPED_UNICODE));
            $base64payload = self::base64UrlEncode(json_encode($payload, JSON_UNESCAPED_UNICODE));
            $token = $base64header . '.' . $base64payload . '.' . self::signature($base64header . '.' . $base64payload, $sign, self::$header['alg']);
            return $token;
        } else {
            return false;
        }
    }


    /**
     * 验证token是否有效,默认验证exp,nbf,iat时间
     * @param string $Token 需要验证的token
     * @param string sigin 秘钥
     * @return bool|string
     */
    public static function verifyToken($Token,$sigin = '')
    {
        if(empty($sigin)){
            $sigin = ConfUtil::get('Jwtkey');
        }
        //去掉Bearer
        if(strpos($Token,"Bearer") !== false){
            $Token = trim(str_replace("Bearer","",$Token));
        }
        $tokens = explode('.', $Token);
        if (count($tokens) != 3)
            return false;
        list($base64header, $base64payload, $sign) = $tokens;

        //获取jwt算法
        $base64decodeheader = json_decode(self::base64UrlDecode($base64header), JSON_OBJECT_AS_ARRAY);
        if (empty($base64decodeheader['alg']))
            return false;

        //签名验证
        if (self::signature($base64header . '.' . $base64payload, $sigin, $base64decodeheader['alg']) !== $sign)
            return false;

        $payload = json_decode(self::base64UrlDecode($base64payload), JSON_OBJECT_AS_ARRAY);

        //签发时间大于当前服务器时间验证失败
        if (isset($payload['iat']) && $payload['iat'] > time())
            return false;

        //过期时间小宇当前服务器时间验证失败
        if (isset($payload['exp']) && $payload['exp'] < time())
            return false;

        //该nbf时间之前不接收处理该Token
        if (isset($payload['nbf']) && $payload['nbf'] > time())
            return false;

        return $payload;
    }

    /**
     * base64UrlEncode  https://jwt.io/ 中base64UrlEncode编码实现
     * @param string $input 需要编码的字符串
     * @return string
     */
    private static function base64UrlEncode($input)
    {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    /**
     * base64UrlEncode https://jwt.io/ 中base64UrlEncode解码实现
     * @param string $input 需要解码的字符串
     * @return bool|string
     */
    private static function base64UrlDecode($input)
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $addlen = 4 - $remainder;
            $input .= str_repeat('=', $addlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * HMACSHA256签名  https://jwt.io/ 中HMACSHA256签名实现
     * @param string $input 为base64UrlEncode(header).".".base64UrlEncode(payload)
     * @param string $key
     * @param string $alg 算法方式
     * @return mixed
     */
    private static function signature($input, $key, $alg = 'HS256')
    {
        $alg_config = array(
            'HS256' => 'sha256'
        );
        return self::base64UrlEncode(hash_hmac($alg_config[$alg], $input, $key, true));
    }
    /**
     * 获取header头部信息
     */
    static public function getHeader($name = 'AUTHORIZATION') {
        $headers = array();

        foreach ($_SERVER as $key => $value) {
            if ('HTTP_' == substr($key, 0, 5)) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
            if (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $header['AUTHORIZATION'] = $_SERVER['PHP_AUTH_DIGEST'];
            } elseif (isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
                $header['AUTHORIZATION'] = base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $_SERVER['PHP_AUTH_PW']);
            }
            if (isset($_SERVER['CONTENT_LENGTH'])) {
                $header['CONTENT-LENGTH'] = $_SERVER['CONTENT_LENGTH'];
            }
            if (isset($_SERVER['CONTENT_TYPE'])) {
                $header['CONTENT-TYPE'] = $_SERVER['CONTENT_TYPE'];
            }
        }
        //null字符串转换为空
        if(isset($headers[$name]) && strtolower($headers[$name]) == 'null'){
            $headers[$name] = '';
        }
        return $headers;
    }

    /**
     * 请求更新token
     * @return bool|string
     */
    public static function refresh()
    {
        $header = JwtClass::getHeader();
        if(isset($header['AUTHORIZATION']) && !empty($header['AUTHORIZATION'])){
            $payload = JwtClass::verifyToken($header['AUTHORIZATION']);
            // 超过1小时
            if (isset($payload['exp'])) {
                if ($payload['exp'] - time()  < 3600) {
                    return self::newToken($payload);
                }
            }
        }
        return false;
    }

    /**
     * 获取新的token
     * @param $payload
     * @return bool|string
     */
    private static function newToken($payload)
    {
        return self::getToken([
            'userId'            => isset($payload['userId'])?$payload['userId']:'',
            'platformId'        => isset($payload['platformId'])?$payload['platformId']:'',
            'platformUserId'    => isset($payload['platformUserId'])?$payload['platformUserId']:'',
        ]);
    }
}
