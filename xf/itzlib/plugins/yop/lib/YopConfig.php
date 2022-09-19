<?php

class YopConfig
{
    //app config

    public $serverRoot = "https://openapi.yeepay.com/yop-center";
    public $yosServerRoot = "https://yos.yeepay.com/yop-center";

    //public $serverRoot = "http://ycetest.yeepay.com:30228/yop-center";
    //public $yosServerRoot = "http://ycetest.yeepay.com:30228/yop-center";

    //public $serverRoot = "http://127.0.0.1:8064/yop-center";
    //public $yosServerRoot = "http://127.0.0.1:8064/yop-center";

    const MERCHANT_NO  = "10013183371";//商户号
    const PRIVATE_KEY  = "MIIEpQIBAAKCAQEAr7JUtsq6vq+MGwfpW/b0YWZ9s1bS/ZRm/1k1z4WqZLXL1M5TpcPQ+2DRuHpVr3ujh6mJ7AGzItylKcL3ZS7CBM3tLXvZgxwvKhSsZ6Z9a0doA0Zjqsk+mGmUNu66g0dM3bXcHmabSxbYFmxeK4EryQVHIGFVdLM5d0JPPzovftpzsRA24RLL7iHL9GveUIY5zjSff7LcSq3scXB7vG+lPWqxlyaD+19eIa3J+X+LR+GIPeDgqkO2HFSXfoaCWyqLvPHP+ZxQwjgJkAOShsjfiqvEfIBq9kIwsGQwn+/FQltEkJ5v7V/69QTnJM5NuIZmIj7xFBXwiexPw9+y8ZvfIwIDAQABAoIBAGQiJ8PSAOKCnEAfnzEJqzgDuKpIVpGtTZJEXrW6QWWKcvQC74tu8aEDCiOwnTsZJRdBWdjHEzhQNlV5x5PENVGVp5IfntTpcDv0clnUenB0zuPm7xC8B0/IBG/WWThOn5FQf3ZYFjOSfm8xLe5vfOvhdSsQLisHpj3A7fdkCwOpPAiruJyt+bfZ+8FNpnk+itWqS0i4F3CwfVJCKfaQxf9DE3g5Mg6TSm7MCMn6KsqywJlXy71ViiADQWQkztaqt32fk8DH/aQlw+HAhtJxm6xvWvgaGcBun82zDNHZCbHMzKgxY6IsosHvTNhiPIur9aBcxyGMsNxqsl/+kuFEthECgYEA6mGB4a3Z0bjoEiDzr1Y8EdRBOqP0GKaWBGF0FGomCNrokRr+o5DDgaU1Ol5yf3oK/tnuC8Q4QLlrFihiw6gOvjgc+GRcqwPI9Nuwx7NDc36R8NAbCUz6QdDAmJNffth3tJDwjjzd57lunnH1vSdGtDGOLeSEraSSCI4o0VBD4JkCgYEAv+cY7jCpez2YnSy+3oqCYo/kg7bsZzs6rR7iDICUoTrydREgqFUiGEduqPAGoefGvIUVnxg4Yfjrut9KqVbygAiAT3ZMVMOc2BsDOLAHW4PvLBlrp2AesQrXFW9dDxqQuuB1mvzWkEj8bBunvO7sqHWEKudUG6ZVheJNgqgnBxsCgYEAoBnRHbaixqaXJ+MIcmnmiItDr2nVUI1ihkWHhHZp2rymBpC5BkPZuJKAhImFjtxv7FwzRihYAKZnpvAZXutKftdXurjbsBnayJ/U5uTmG1uHF0cgYL8LZ6/QD6kDn2MAigBDwfWD40kAbg0MPpQ4sNf09hZWJ0L2Wg+5hBle9GkCgYEAmXG9AoBQlGWtSUhFGobgONVb4GH855J5mYIPN/X8YmtTAX5/gXoJOCat+lFqzD0bMRStNDhWpSg3vYXcGkmWv/+MMX2jDUgYesgmrEY8q9V0AewVo5D9GY1UbCRO4cvZHERsZVB0dIyXog3+8tiMzSqiUvgDzdQToGlSlv3Dry8CgYEAkBKKCMYWLF5wwhkPFQ5qbWPyJSgmm8qC5ukbaADQdG5a2hIUNk+MRKfMfwHTn+MfEvV2sE8Yhqq/UI5wOvV+hgAG6eizw4e9w2Oq9qh6HDFghNxp0J5jdStevjv1Z2CLgMpwPJvvdtxrYtmjlKfSekNEzjSOgZDCu0cZcz/0uxU=";
    const APP_KEY = "app_10013183371";
    const PUBLIC_KEY  = "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6p0XWjscY+gsyqKRhw9MeLsEmhFdBRhT2emOck/F1Omw38ZWhJxh9kDfs5HzFJMrVozgU+SJFDONxs8UB0wMILKRmqfLcfClG9MyCNuJkkfm0HFQv1hRGdOvZPXj3Bckuwa7FrEXBRYUhK7vJ40afumspthmse6bs6mZxNn/mALZ2X07uznOrrc2rk41Y2HftduxZw6T4EmtWuN2x4CZ8gwSyPAW5ZzZJLQ6tZDojBK4GZTAGhnn3bg5bBsBlw2+FLkCQBuDsJVsFPiGh/b6K/+zGTvWyUcu+LUj2MejYQELDO3i2vQXVDk7lVi2/TcUYefvIcssnzsfCfjaorxsuwIDAQAB";//公钥

    const CFCA_PRIVATE_KEY = "MIIEpAIBAAKCAQEAvlxIM9ZkNvRG2frKwHll6e96xzaq54v7NtkYkS/IuGxcIOH+hr31Mmd7wfTJL8iKrCoic6fUUSUQs5EecmPFctEi3cEAlzGvDygi/EAY6YTDhqwAUU7WkTrbT4+7HjzZEID9OksO9dXHsB4+MlSOa6NlDQ8CKKjUuwMFM65vPOqoyRkuu+ZcQOUlZU5pJJy7QNhA7OfEuAobqLS/hXcoCbqZOSoJf5cTrJCgYN/nVECOkr7Bp/FgPYHUGIBBlGELUEngRDHIPYYi5xXFrlE12orWCA6ofT0zLXbYuL8hHZrTb8zIN3fduz56RGuor8It4iIhIvkv2f3ovogHqO4suQIDAQABAoIBAQC5K384nYXiRwBcra5oSC6wvrlJ642Xqvz1P3y8TiUL8Kw1eGBkpYdMPomOBBVoG8V474uPwWOwg0OZyMI7N34rz/AAkeuHJ1dIgRx3D8qr7O9doxa0AOKLZxtKa1/za/EiMrcg6z5kOE4ErYaG4uWfItP7Pew1rPUx7SA0Q6wFLfXbq0o4ZQMkfmSyTyQIRykHxUbgZLkDF9tY1sR8hyK+qAjDAW3a1UN6YXEr0dgvANGEVD4sAKuvrHnrksG2Hv4LFVM9IUMg09K8jB72Sq0V41VgsDV1gFyd6bU1q+/Sdco52uwVANsSZX0F1zUSLBiW6fUt0bn9RtLLxO/CQblZAoGBAMOP0C4d1Vric9HL9HptcWCRFSZDmTXX7sQT9MRB/Psq+gcq34BNUlD/3aXaK9UPNYTo/YuteK/xWe+ssBUAcs2s9wgT9bOYZ55hJVn2zt7pkNvXH5qJG6YOxp2z5fWxzVQqwhoOFAvfvjfXmQo/+lnnQcGIVTgamapsKLurky37AoGBAPkw9cYT+mQPdsqIZdFKd8y0SFWAIbJ+y5Kf+jISj0bP8oEARIJJz+UK0rhsRKDGZHLs+nC+M1z0XT8OE4k/kwUQj5ORCOlnWqiIzYrgfJVoNWNo+ndsO6Duoqk6WUWkVk0AtMj4f8rv+IHsfxzw9/Lv1tLjJONcJxWRdbK/DNXbAoGAE5oRqt2wqghyxX+lBLR9nclNDmXOMJhxRVQev6FBo0degmNovaqCar4K0Hn61MNOgQD9kQeVRkVx11U+3QwLddQ1eqjNgu/uyvA/1zGm8K8GpxJ8B3hgvhdTDzGeBi+JPjt+8y+gEMSfg2dn3qAlDufgLm7k49e+uVdYCd+bxAsCgYBDOIjkQRlniqBh7D+DINKGXw+wONteOkQSLqOghE8wLAJf+EGC4AR75dqIM3Sj5kDMm87HQRe7+JN64gI1IPg5AomophRAWkgjdJv13a7d4vmb8oK2WnUPabBpdDsGxVkedpVOLXLTvL5N1g1IMlzApSBPTUTzLDXC5LVaXZ1JxwKBgQCweAWAAuWB5kDkz7nLWYln3nfdPXM3PbnpVafDgAzVMoJiGblroUcaYVzXqkPDdG1J2crGal3Gt4A43aiVyey+LGMHfAS1ovm1arJLxiNEk3CVOptHqgrSeaP1wGFAjF7DjSkV6GQI+9XfqckZYImykU6wrb8Mi9wkQoN7zlehQA==";
    public $appKey;
    public $aesSecretKey;
    public $hmacSecretKey;
   

    public static $debug=false;

    public $connectTimeout=30;
    public $readTimeout=60;

    public $maxUploadLimit=4096000;

    //签名算法
    public $ALG_AES = "AES";
    public $ALG_SHA = "SHA";
    public $ALG_SHA1 = "SHA1";

    // 保护参数
    public $ENCODING = "UTF-8";
    public $SUCCESS = "SUCCESS";
    public $CALLBACK = "callback";
    // 方法的默认参数名
    public $METHOD = "method";

    // 会话id默认参数名
    public $SESSION_ID = "sessionId";
    // 应用键的默认参数名 ;
    public $APP_KEY = "appKey";
    // 服务版本号的默认参数名
    public $VERSION = "v";
    // 签名的默认参数名
    public $SIGN = "sign";
    // 加密报文key
    public $ENCRYPT = "encrypt";
    // 商户编号
    public $CUSTOMER_NO = "customerNo";

    // 返回结果是否签名
    public $SIGN_RETURN = "signRet";

    // 时间戳
    public $TIMESTAMP = "ts";
    public $publicED_KEY=array();
    public $publickey="MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEA6p0XWjscY+gsyqKRhw9MeLsEmhFdBRhT2emOck/F1Omw38ZWhJxh9kDfs5HzFJMrVozgU+SJFDONxs8UB0wMILKRmqfLcfClG9MyCNuJkkfm0HFQv1hRGdOvZPXj3Bckuwa7FrEXBRYUhK7vJ40afumspthmse6bs6mZxNn/mALZ2X07uznOrrc2rk41Y2HftduxZw6T4EmtWuN2x4CZ8gwSyPAW5ZzZJLQ6tZDojBK4GZTAGhnn3bg5bBsBlw2+FLkCQBuDsJVsFPiGh/b6K/+zGTvWyUcu+LUj2MejYQELDO3i2vQXVDk7lVi2/TcUYefvIcssnzsfCfjaorxsuwIDAQAB";


    public function __construct()
    {
        array_push($this->publicED_KEY, $this->APP_KEY, $this->VERSION, $this->SIGN, $this->METHOD, $this->SESSION_ID, $this->CUSTOMER_NO, $this->ENCRYPT, "", false);
    }

    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    public function __get($name)
    {
        return $this->$name;
    }

    public function getSecret()
    {
        if (!empty($this->appKey) && strlen($this->appKey) > 0) {
            return $this->aesSecretKey;
        } else {
            return $this->hmacSecretKey;
        }
    }

    public function ispublicedKey($key)
    {
        if (in_array($key, $this->publicED_KEY)) {
            return true;
        }
        return false;
    }
}
