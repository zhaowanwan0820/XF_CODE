<?php

namespace NCFGroup\Common\Library\services;

use NCFGroup\Common\Library\services\ServiceInterface;

class UcfpayGateway implements ServiceInterface
{
    //   接口响应状态码
    const RESPONSE_SUCCESS      = '99000';
    const RESPONSE_ORDER_EXIST  = '10005';

    // 业务状态代码
    const STATUS_SUCCESS        = 'S';
    const STATUS_FAIL           = 'F';
    const STATUS_PROCESS        = 'I';


    // 业务常量定义
    const REQ_SOURCE_NORMAL     = 1; // 请求来源

    const TRANS_CUR_RMB         = 156; // 默认货币类型为 RMB

    const USER_TYPE_PRIVITE     = 1; // 交易对象为个人对私客户
    const USER_TYPE_ENTERPRISE  = 1; // 交易对象为 企业对公账户

    const TRADE_PRODUCT_FAST    = 'FAST'; // 代发产品 垫资代发
    const TRADE_PRODUCT_NORMAL  = 'NORMAL'; // 代发产品 普通代发

    const UNITED_BANK_ISSUER    = '142834929348'; // 海口联合农商行 联行号
    const SINGLE_PAY_AMOUNT_MAX = '800000000'; // 代发单笔最大金额800万, 单位分

    // 服务别名
    const SERVICE_WITHDRAW      = 'withdraw'; // 代发业务别名
    const SERVICE_ORDER_QUERY   = 'orderQuery'; // 单笔订单查询接口
    const SERVICE_MER_BALANCE   = 'merchantBalance'; // 代发商户可用余额查询

    /**
     * 判断是否存在服务
     * @param string $key 服务名称
     * @return boolean
     */
    public function has($key)
    {
        if (isset($this->_services[$key]))
        {
            return true;
        }
        return false;
    }

    public function getServices()
    {
        return $this->_services;
    }

    /**
     *  读取服务信息配置
     * @param string $key 服务名称
     * @return mixed 返回对应的服务配置信息
     */
    public function get($key)
    {
        return $this->_services[$key];
    }

    /**
     *  读取服务公共配置信息
     * @param string $key
     * @return mixed 返回对应的公共配置数据
     */
    public function getConfig($key)
    {
        // 增加测试环境
        if (empty($this->_config[APP_ENV][$key]))
        {
            return null;
        }
        return $this->_config[APP_ENV][$key];
    }

    // 公用配置信息
    private $_config = array(
        'dev' => array(
            // 网关地址
            'gatewayUrl'            => 'http://sandbox.firstpay.com/security/gateway.do',
            // 默认版本号
            'version'               => '5.0.0',
            // 远程日志设置
            'logServerIp'           => '10.20.69.101',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAj5WSSd5U6CrBslZ4NTMY8ObIh5upVFsIvxsDWFNNjTHAW3AAmdKciGmKQFEvIzatedVsq5+LsfLKJX4RnM4PvY5rDOo0iz30uZkC71oeGvwSxqxLao0bUwXqyfsV/Qggj3S687mINOJd5WE5xgIF/4Fyb6RwalwCDBeCYavRGpBCIKPJFFAfZe01RSDbHeJwI4ebtraT0pPmN3SJvWBKavbIl6o4HYKOg6jpjt/ySTsRvlA86vxlaSKcdsGwxWJsKGi6tf9T805+qJZymhqndEhpy9xWjmCF52yIWVntpXOQuU97KTtjd5qYzRCYM7QE404SX6oN65DzxowBYjrq7QIDAQAB',
            // 默认主商户号
            'defaultMerchantId'     => 'M200000969',
            // 商户公钥
            'merchantKeys'          => array(
                // 支付测试商户1
                'M200000969'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApbCvs4R4TxMXyjqZ3+Zb774aJvT1VxQTd+64d6esvb8+JysMdUSnu/MLtoziwQ0afk+No65JXMjO/1B+/3hH3r5xahdzPMu5Rw/+0GtLQ7Hmic8433VVOPCpmKVit784wn0NnlPtyH41yC8epYq+eS85eHKiaXY52k3mRs3bowViYvMKGNoYWXlHWguIjz1xTxTpVxvpjpXZu5CNBC9WJrT4XIpO8WozRASHeP5BNY7kedJJJc3SXZN+Wuf7EUS+4lwtqPhcpjkgSHWGLIbKciSLFKL699qXl9Plf8XoMv/Kxt0bOwXSpX+iuEOYpz3nSK+qiLTh0A8fPVZ9Pa/PjwIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQClsK+zhHhPExfKOpnf5lvvvhom9PVXFBN37rh3p6y9vz4nKwx1RKe78wu2jOLBDRp+T42jrklcyM7/UH7/eEfevnFqF3M8y7lHD/7Qa0tDseaJzzjfdVU48KmYpWK3vzjCfQ2eU+3IfjXILx6lir55Lzl4cqJpdjnaTeZGzdujBWJi8woY2hhZeUdaC4iPPXFPFOlXG+mOldm7kI0EL1YmtPhcik7xajNEBId4/kE1juR50kklzdJdk35a5/sRRL7iXC2o+FymOSBIdYYshspyJIsUovr32peX0+V/xegy/8rG3Rs7BdKlf6K4Q5inPedIr6qItOHQDx89Vn09r8+PAgMBAAECggEBAKVkUI7m3d05FtdEVdM9NGqFHb/jZ3+Lx79BKRwv4OvrmdQpUZ9BcBnaC8gmrDa+qMKLELzhvdODk7UiGhNTcpJzEe0wCVUXmxPHcLmFULT7QUAw/Pl6Ox7ChNidxoPauoLRp6Vy6/nlmjQAbRwb+fQn4rtL2rlhTXCPsBzfYq4/jho3dq5PMwQAHnh6yiN33AqgCWloSXCQW6VRFAVUGc8fhZ3uK9rlF9Uljk2iLxtXpkcz1QxSRu5nMOOTrKhqxC3Kl49kyfsW8YvPXfdfhXiod9ylFwnW3siLQ3y+k5xmZRyo4d7kIU1PZcs92ykvwFZGwUn0lVbZxqZgA+zgTNECgYEA/vai+DN0orlgQJIhLrd81ZJLCk7I0nxDGKfkUMnY6K76NrmojPL705ohCoVcUhKNSX2yhZDfW9Hl0PlOeSUVPSp5zHMQsdi0gs+A4S8T6qCECZskWwj+i2XJcd7vIVfe6phWx9GjwG/E8fpxdavQcSn2X8rHRqBOcC4VCURQq/cCgYEApl0ikTPOakS1H6au5w4IawwNBK8Wm5UIsgUd29Hej4wUugPyVMhzwWVQwnEz1hQHANTRsKYf/u5UpGW51HXQ++wtgjH+h+Ctcn+PwBoZex5sqkMFxwTLKdsutSrIwhf5rcFseBBcYmryUA7+leTnQUKJkV1+qS+3jWK27l1qoykCgYEAoPbWtnnN1fnQsZNQDa1by33bkDti/7fhqEw+kV6NaYEmiKw3pBy3LcUtvPWq7km2F0KbFUX8LXzbaU4r48GsofwR/yhZzt3wQHF+fSv6l/MUyPfAQRTxltIBFrnXIKbYHiVlDCvnBNPLc7VYMiDxrLAAUkO0AXutaZc+QqZ1g8sCgYBoSRW0I9+O6gcIEjqtiDRqtiErAH6RhLjwrxhqhYKYRV1wxayQzR8S6mnXmZK+7cr+EGpp65k++zN/4my87CXW5dQZOzGtB4Byt9fqufGjJg1EJcNnYG/iiw0ab/ltAg53hzpxgQAIibXfzaZ1XApC9Gy7/Pm7ILhVHr5BabnBEQKBgQCvyUuOVAS2UXoKlRh6Q+jpfPQfCqVep+l/NGozbGaVYS6DNWZ3iV6GxF/iaiMWomIwQX+S/TdvlRh5k7w+q4bUKdKMeOuk8dGjIGJNj+rq7KxLOl4OXCI0/sAO5MGyPFUSHh1BQWkEcqfgtWoh2D765k8EkJ38jUbuXbMPndKLRg==',
                ),
                //  支付测试商户2
                'M200001050'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuqolI+p/baH3LZkwmUQYXNS3p3Mp4RXock84bV4avbrmGaoVZuB/SotBlP30pGm1cJ3a2kISIwh8tQ+2ITuINHWvVbhNThDt4DwUK8AcQsPvOu4FjCatCqEDLNXtDo5WQvQnXPvqeu0+dbv8i04KXUGYBgipFs3BHCtn2c2aEJBRkdr5yv2fWGQalMAC5NCIo76UTbpxNWEBq5KEQAQLzgG5xVavh6QL4XcWSlveJCf2gifQKYWPH3hJt4hfeqxQc4SQd2hPbUe0vgRzLx3SrGg3LrHl6GKCDNAS7a+XaEKabAPOnLEBSDyt0B/zEBYc5ZmkcuCRbRGWNDdFGDT+EwIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC1h8UHLO+lk49vzEfkCmj5CQD1d/SBa4eeVkTpCV3QDGUevnOLLgts3Ve6/NmK74gmrFPP0nWVWrOOQ48m5UPCZhduahEwOj9pZRJD+PreP7q2bzF/w182cDWXKpzK9np5laO7shtUtcdYUOURcE/XU3IW7M+AA/wqV3rbb0tyS0RkTjT6DJRfyXofJHERjHlY0avHgMc+kVo5Ey8cuNK3H3KcNQYW7m4lxHRdYK5R7Xm3TpOS4FsZ8/ol6UDaO/vIIGHGaJEot2bQkFm5R4mLhMNSatTK/Go1oEDBMdDeKQHAr5OHAg1xrLWcQTs/dDCh55uyaGgLzS40RQEC4da3AgMBAAECggEBAKzpyLnPCe80IZsigRAtAlTFSM6JFrP4k1Q1ZKp9q4izZdblHvZiQ6vNIvYQR7/Z7ly4JZV+KVa88PAAVml8VRDlYkhgbEL+GMzx43YvwfbVyaphPEsw9I7MT5/QjU2ffoY2DaKKQxJrnJj5ZVk5HDRFXhWMORL9uMM0VOiUmM8iXBPD7FQz+oxwJAdzx1Y9RxcDbUtLoRyLBBtQgjfI3oQ2jOHZRDa6qsC9ukPp09QI/Qjp+ytJX4WB0KItYIaBMKIvVkyjP7lqq7xcvyCJOTNi0rsZMemhXXs551eAP6zZW1dI1Fb0zwpohuwVRznMA/ulXDvZxvostnRUVmXTZZECgYEA3MDdhu2q2o5rOaCTPCKQD/b+Ndep0OH2970N8mapjjP1apDVvQ0tr7ScOJ/JMfG5dQs/pn7T+uyGQFzMr7/B1RSm+A+YnEuNNuktQqMp9vt3pGN9uhrjD7z4G24pKfYx/sbD5nGiGWlFtqp1T88e5hh5h/sy6Y1+L2/uUfmQECsCgYEA0oOw+xxyl/vsjLkHoMcbU58uT03JWIkWr+pbBVDL/vcyluqlkt6y0czslARfQ6DzzXuyZOAhGR09vKf+N7SMJjYm+b6UybISRj/Q4WBW7Zt+FzlZTAz0HPKmznyxJKJDNGnddLSwNxfdRG1J9U0RpPQxwQEwN46XGh+jBbQFwaUCgYEAwV13Lj90zyi9J6dOEPi9dB6IIiWcrEmiiPLjCpd+of9FU2k2r/ihMi1kQf1EwSjZqHqH8JFboYoZNruS18eCQ+FpOBSBOza6pYSujpZZpewzqp0zfhcbGagPNAfUqtrqhB8bbfnPYa7iz9SUGap1iFub6M7Sk93K0EadXNTbqi0CgYA7kozY4voCzIXqZMol03KGPXurcYXTCihja9yKKo0v/+BPGOP2JhNQj787O+mBh+C2e5TGOy7inoXEB35HYU5v2c85yZbtZPkK7DA+NzciUmhiRhZhESFbt8dAk8TFay29fV/wENn1HUm+fXb6de7SUVBrH9z3O+DCwcUubf1bCQKBgQCrc/1w1sSSKG2i33j3FcaNb0nhyiOuM5IaTjVlp98Nb5vEW4mC7Osx/wwE55lStH73tXDt5vPbSOHfUgWdwB2K/zDCSUnVECUwdWkZSy/+mL7SY/51/r/ZIj6iGjLAeix+zuFYp58kQmh10KVPFCzU9aD5vcFsMcdfzF3jcU/4gg==',
                ),
            ),
        ),
        'test' => array(
            // 网关地址
            'gatewayUrl'            => 'http://sandbox.firstpay.com/security/gateway.do',
            // 默认版本号
            'version'               => '5.0.0',
            // 远程日志设置
            'logServerIp'           => '10.20.69.101',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAj5WSSd5U6CrBslZ4NTMY8ObIh5upVFsIvxsDWFNNjTHAW3AAmdKciGmKQFEvIzatedVsq5+LsfLKJX4RnM4PvY5rDOo0iz30uZkC71oeGvwSxqxLao0bUwXqyfsV/Qggj3S687mINOJd5WE5xgIF/4Fyb6RwalwCDBeCYavRGpBCIKPJFFAfZe01RSDbHeJwI4ebtraT0pPmN3SJvWBKavbIl6o4HYKOg6jpjt/ySTsRvlA86vxlaSKcdsGwxWJsKGi6tf9T805+qJZymhqndEhpy9xWjmCF52yIWVntpXOQuU97KTtjd5qYzRCYM7QE404SX6oN65DzxowBYjrq7QIDAQAB',
            // 默认主商户号
            'defaultMerchantId'     => 'M200000969',
            // 商户公钥
            'merchantKeys'          => array(
                // 支付测试商户1
                'M200000969'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApbCvs4R4TxMXyjqZ3+Zb774aJvT1VxQTd+64d6esvb8+JysMdUSnu/MLtoziwQ0afk+No65JXMjO/1B+/3hH3r5xahdzPMu5Rw/+0GtLQ7Hmic8433VVOPCpmKVit784wn0NnlPtyH41yC8epYq+eS85eHKiaXY52k3mRs3bowViYvMKGNoYWXlHWguIjz1xTxTpVxvpjpXZu5CNBC9WJrT4XIpO8WozRASHeP5BNY7kedJJJc3SXZN+Wuf7EUS+4lwtqPhcpjkgSHWGLIbKciSLFKL699qXl9Plf8XoMv/Kxt0bOwXSpX+iuEOYpz3nSK+qiLTh0A8fPVZ9Pa/PjwIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQClsK+zhHhPExfKOpnf5lvvvhom9PVXFBN37rh3p6y9vz4nKwx1RKe78wu2jOLBDRp+T42jrklcyM7/UH7/eEfevnFqF3M8y7lHD/7Qa0tDseaJzzjfdVU48KmYpWK3vzjCfQ2eU+3IfjXILx6lir55Lzl4cqJpdjnaTeZGzdujBWJi8woY2hhZeUdaC4iPPXFPFOlXG+mOldm7kI0EL1YmtPhcik7xajNEBId4/kE1juR50kklzdJdk35a5/sRRL7iXC2o+FymOSBIdYYshspyJIsUovr32peX0+V/xegy/8rG3Rs7BdKlf6K4Q5inPedIr6qItOHQDx89Vn09r8+PAgMBAAECggEBAKVkUI7m3d05FtdEVdM9NGqFHb/jZ3+Lx79BKRwv4OvrmdQpUZ9BcBnaC8gmrDa+qMKLELzhvdODk7UiGhNTcpJzEe0wCVUXmxPHcLmFULT7QUAw/Pl6Ox7ChNidxoPauoLRp6Vy6/nlmjQAbRwb+fQn4rtL2rlhTXCPsBzfYq4/jho3dq5PMwQAHnh6yiN33AqgCWloSXCQW6VRFAVUGc8fhZ3uK9rlF9Uljk2iLxtXpkcz1QxSRu5nMOOTrKhqxC3Kl49kyfsW8YvPXfdfhXiod9ylFwnW3siLQ3y+k5xmZRyo4d7kIU1PZcs92ykvwFZGwUn0lVbZxqZgA+zgTNECgYEA/vai+DN0orlgQJIhLrd81ZJLCk7I0nxDGKfkUMnY6K76NrmojPL705ohCoVcUhKNSX2yhZDfW9Hl0PlOeSUVPSp5zHMQsdi0gs+A4S8T6qCECZskWwj+i2XJcd7vIVfe6phWx9GjwG/E8fpxdavQcSn2X8rHRqBOcC4VCURQq/cCgYEApl0ikTPOakS1H6au5w4IawwNBK8Wm5UIsgUd29Hej4wUugPyVMhzwWVQwnEz1hQHANTRsKYf/u5UpGW51HXQ++wtgjH+h+Ctcn+PwBoZex5sqkMFxwTLKdsutSrIwhf5rcFseBBcYmryUA7+leTnQUKJkV1+qS+3jWK27l1qoykCgYEAoPbWtnnN1fnQsZNQDa1by33bkDti/7fhqEw+kV6NaYEmiKw3pBy3LcUtvPWq7km2F0KbFUX8LXzbaU4r48GsofwR/yhZzt3wQHF+fSv6l/MUyPfAQRTxltIBFrnXIKbYHiVlDCvnBNPLc7VYMiDxrLAAUkO0AXutaZc+QqZ1g8sCgYBoSRW0I9+O6gcIEjqtiDRqtiErAH6RhLjwrxhqhYKYRV1wxayQzR8S6mnXmZK+7cr+EGpp65k++zN/4my87CXW5dQZOzGtB4Byt9fqufGjJg1EJcNnYG/iiw0ab/ltAg53hzpxgQAIibXfzaZ1XApC9Gy7/Pm7ILhVHr5BabnBEQKBgQCvyUuOVAS2UXoKlRh6Q+jpfPQfCqVep+l/NGozbGaVYS6DNWZ3iV6GxF/iaiMWomIwQX+S/TdvlRh5k7w+q4bUKdKMeOuk8dGjIGJNj+rq7KxLOl4OXCI0/sAO5MGyPFUSHh1BQWkEcqfgtWoh2D765k8EkJ38jUbuXbMPndKLRg==',
                ),
                //  支付测试商户2
                'M200001050'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuqolI+p/baH3LZkwmUQYXNS3p3Mp4RXock84bV4avbrmGaoVZuB/SotBlP30pGm1cJ3a2kISIwh8tQ+2ITuINHWvVbhNThDt4DwUK8AcQsPvOu4FjCatCqEDLNXtDo5WQvQnXPvqeu0+dbv8i04KXUGYBgipFs3BHCtn2c2aEJBRkdr5yv2fWGQalMAC5NCIo76UTbpxNWEBq5KEQAQLzgG5xVavh6QL4XcWSlveJCf2gifQKYWPH3hJt4hfeqxQc4SQd2hPbUe0vgRzLx3SrGg3LrHl6GKCDNAS7a+XaEKabAPOnLEBSDyt0B/zEBYc5ZmkcuCRbRGWNDdFGDT+EwIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC1h8UHLO+lk49vzEfkCmj5CQD1d/SBa4eeVkTpCV3QDGUevnOLLgts3Ve6/NmK74gmrFPP0nWVWrOOQ48m5UPCZhduahEwOj9pZRJD+PreP7q2bzF/w182cDWXKpzK9np5laO7shtUtcdYUOURcE/XU3IW7M+AA/wqV3rbb0tyS0RkTjT6DJRfyXofJHERjHlY0avHgMc+kVo5Ey8cuNK3H3KcNQYW7m4lxHRdYK5R7Xm3TpOS4FsZ8/ol6UDaO/vIIGHGaJEot2bQkFm5R4mLhMNSatTK/Go1oEDBMdDeKQHAr5OHAg1xrLWcQTs/dDCh55uyaGgLzS40RQEC4da3AgMBAAECggEBAKzpyLnPCe80IZsigRAtAlTFSM6JFrP4k1Q1ZKp9q4izZdblHvZiQ6vNIvYQR7/Z7ly4JZV+KVa88PAAVml8VRDlYkhgbEL+GMzx43YvwfbVyaphPEsw9I7MT5/QjU2ffoY2DaKKQxJrnJj5ZVk5HDRFXhWMORL9uMM0VOiUmM8iXBPD7FQz+oxwJAdzx1Y9RxcDbUtLoRyLBBtQgjfI3oQ2jOHZRDa6qsC9ukPp09QI/Qjp+ytJX4WB0KItYIaBMKIvVkyjP7lqq7xcvyCJOTNi0rsZMemhXXs551eAP6zZW1dI1Fb0zwpohuwVRznMA/ulXDvZxvostnRUVmXTZZECgYEA3MDdhu2q2o5rOaCTPCKQD/b+Ndep0OH2970N8mapjjP1apDVvQ0tr7ScOJ/JMfG5dQs/pn7T+uyGQFzMr7/B1RSm+A+YnEuNNuktQqMp9vt3pGN9uhrjD7z4G24pKfYx/sbD5nGiGWlFtqp1T88e5hh5h/sy6Y1+L2/uUfmQECsCgYEA0oOw+xxyl/vsjLkHoMcbU58uT03JWIkWr+pbBVDL/vcyluqlkt6y0czslARfQ6DzzXuyZOAhGR09vKf+N7SMJjYm+b6UybISRj/Q4WBW7Zt+FzlZTAz0HPKmznyxJKJDNGnddLSwNxfdRG1J9U0RpPQxwQEwN46XGh+jBbQFwaUCgYEAwV13Lj90zyi9J6dOEPi9dB6IIiWcrEmiiPLjCpd+of9FU2k2r/ihMi1kQf1EwSjZqHqH8JFboYoZNruS18eCQ+FpOBSBOza6pYSujpZZpewzqp0zfhcbGagPNAfUqtrqhB8bbfnPYa7iz9SUGap1iFub6M7Sk93K0EadXNTbqi0CgYA7kozY4voCzIXqZMol03KGPXurcYXTCihja9yKKo0v/+BPGOP2JhNQj787O+mBh+C2e5TGOy7inoXEB35HYU5v2c85yZbtZPkK7DA+NzciUmhiRhZhESFbt8dAk8TFay29fV/wENn1HUm+fXb6de7SUVBrH9z3O+DCwcUubf1bCQKBgQCrc/1w1sSSKG2i33j3FcaNb0nhyiOuM5IaTjVlp98Nb5vEW4mC7Osx/wwE55lStH73tXDt5vPbSOHfUgWdwB2K/zDCSUnVECUwdWkZSy/+mL7SY/51/r/ZIj6iGjLAeix+zuFYp58kQmh10KVPFCzU9aD5vcFsMcdfzF3jcU/4gg==',
                ),
            ),
        ),
        'pdtest' => array(
             // 网关地址
            'gatewayUrl'            => 'http://sandbox.firstpay.com/security/gateway.do',
            // 默认版本号
            'version'               => '5.0.0',
            // 远程日志设置
            'logServerIp'           => 'pmlog1.wxlc.org',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAj5WSSd5U6CrBslZ4NTMY8ObIh5upVFsIvxsDWFNNjTHAW3AAmdKciGmKQFEvIzatedVsq5+LsfLKJX4RnM4PvY5rDOo0iz30uZkC71oeGvwSxqxLao0bUwXqyfsV/Qggj3S687mINOJd5WE5xgIF/4Fyb6RwalwCDBeCYavRGpBCIKPJFFAfZe01RSDbHeJwI4ebtraT0pPmN3SJvWBKavbIl6o4HYKOg6jpjt/ySTsRvlA86vxlaSKcdsGwxWJsKGi6tf9T805+qJZymhqndEhpy9xWjmCF52yIWVntpXOQuU97KTtjd5qYzRCYM7QE404SX6oN65DzxowBYjrq7QIDAQAB',
            // 默认主商户号
            'defaultMerchantId'     => 'M200000969',
            // 商户公钥
            'merchantKeys'          => array(
                // 支付测试商户1
                'M200000969'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEApbCvs4R4TxMXyjqZ3+Zb774aJvT1VxQTd+64d6esvb8+JysMdUSnu/MLtoziwQ0afk+No65JXMjO/1B+/3hH3r5xahdzPMu5Rw/+0GtLQ7Hmic8433VVOPCpmKVit784wn0NnlPtyH41yC8epYq+eS85eHKiaXY52k3mRs3bowViYvMKGNoYWXlHWguIjz1xTxTpVxvpjpXZu5CNBC9WJrT4XIpO8WozRASHeP5BNY7kedJJJc3SXZN+Wuf7EUS+4lwtqPhcpjkgSHWGLIbKciSLFKL699qXl9Plf8XoMv/Kxt0bOwXSpX+iuEOYpz3nSK+qiLTh0A8fPVZ9Pa/PjwIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQClsK+zhHhPExfKOpnf5lvvvhom9PVXFBN37rh3p6y9vz4nKwx1RKe78wu2jOLBDRp+T42jrklcyM7/UH7/eEfevnFqF3M8y7lHD/7Qa0tDseaJzzjfdVU48KmYpWK3vzjCfQ2eU+3IfjXILx6lir55Lzl4cqJpdjnaTeZGzdujBWJi8woY2hhZeUdaC4iPPXFPFOlXG+mOldm7kI0EL1YmtPhcik7xajNEBId4/kE1juR50kklzdJdk35a5/sRRL7iXC2o+FymOSBIdYYshspyJIsUovr32peX0+V/xegy/8rG3Rs7BdKlf6K4Q5inPedIr6qItOHQDx89Vn09r8+PAgMBAAECggEBAKVkUI7m3d05FtdEVdM9NGqFHb/jZ3+Lx79BKRwv4OvrmdQpUZ9BcBnaC8gmrDa+qMKLELzhvdODk7UiGhNTcpJzEe0wCVUXmxPHcLmFULT7QUAw/Pl6Ox7ChNidxoPauoLRp6Vy6/nlmjQAbRwb+fQn4rtL2rlhTXCPsBzfYq4/jho3dq5PMwQAHnh6yiN33AqgCWloSXCQW6VRFAVUGc8fhZ3uK9rlF9Uljk2iLxtXpkcz1QxSRu5nMOOTrKhqxC3Kl49kyfsW8YvPXfdfhXiod9ylFwnW3siLQ3y+k5xmZRyo4d7kIU1PZcs92ykvwFZGwUn0lVbZxqZgA+zgTNECgYEA/vai+DN0orlgQJIhLrd81ZJLCk7I0nxDGKfkUMnY6K76NrmojPL705ohCoVcUhKNSX2yhZDfW9Hl0PlOeSUVPSp5zHMQsdi0gs+A4S8T6qCECZskWwj+i2XJcd7vIVfe6phWx9GjwG/E8fpxdavQcSn2X8rHRqBOcC4VCURQq/cCgYEApl0ikTPOakS1H6au5w4IawwNBK8Wm5UIsgUd29Hej4wUugPyVMhzwWVQwnEz1hQHANTRsKYf/u5UpGW51HXQ++wtgjH+h+Ctcn+PwBoZex5sqkMFxwTLKdsutSrIwhf5rcFseBBcYmryUA7+leTnQUKJkV1+qS+3jWK27l1qoykCgYEAoPbWtnnN1fnQsZNQDa1by33bkDti/7fhqEw+kV6NaYEmiKw3pBy3LcUtvPWq7km2F0KbFUX8LXzbaU4r48GsofwR/yhZzt3wQHF+fSv6l/MUyPfAQRTxltIBFrnXIKbYHiVlDCvnBNPLc7VYMiDxrLAAUkO0AXutaZc+QqZ1g8sCgYBoSRW0I9+O6gcIEjqtiDRqtiErAH6RhLjwrxhqhYKYRV1wxayQzR8S6mnXmZK+7cr+EGpp65k++zN/4my87CXW5dQZOzGtB4Byt9fqufGjJg1EJcNnYG/iiw0ab/ltAg53hzpxgQAIibXfzaZ1XApC9Gy7/Pm7ILhVHr5BabnBEQKBgQCvyUuOVAS2UXoKlRh6Q+jpfPQfCqVep+l/NGozbGaVYS6DNWZ3iV6GxF/iaiMWomIwQX+S/TdvlRh5k7w+q4bUKdKMeOuk8dGjIGJNj+rq7KxLOl4OXCI0/sAO5MGyPFUSHh1BQWkEcqfgtWoh2D765k8EkJ38jUbuXbMPndKLRg==',
                ),
                //  支付测试商户2
                'M200001050'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAuqolI+p/baH3LZkwmUQYXNS3p3Mp4RXock84bV4avbrmGaoVZuB/SotBlP30pGm1cJ3a2kISIwh8tQ+2ITuINHWvVbhNThDt4DwUK8AcQsPvOu4FjCatCqEDLNXtDo5WQvQnXPvqeu0+dbv8i04KXUGYBgipFs3BHCtn2c2aEJBRkdr5yv2fWGQalMAC5NCIo76UTbpxNWEBq5KEQAQLzgG5xVavh6QL4XcWSlveJCf2gifQKYWPH3hJt4hfeqxQc4SQd2hPbUe0vgRzLx3SrGg3LrHl6GKCDNAS7a+XaEKabAPOnLEBSDyt0B/zEBYc5ZmkcuCRbRGWNDdFGDT+EwIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvwIBADANBgkqhkiG9w0BAQEFAASCBKkwggSlAgEAAoIBAQC1h8UHLO+lk49vzEfkCmj5CQD1d/SBa4eeVkTpCV3QDGUevnOLLgts3Ve6/NmK74gmrFPP0nWVWrOOQ48m5UPCZhduahEwOj9pZRJD+PreP7q2bzF/w182cDWXKpzK9np5laO7shtUtcdYUOURcE/XU3IW7M+AA/wqV3rbb0tyS0RkTjT6DJRfyXofJHERjHlY0avHgMc+kVo5Ey8cuNK3H3KcNQYW7m4lxHRdYK5R7Xm3TpOS4FsZ8/ol6UDaO/vIIGHGaJEot2bQkFm5R4mLhMNSatTK/Go1oEDBMdDeKQHAr5OHAg1xrLWcQTs/dDCh55uyaGgLzS40RQEC4da3AgMBAAECggEBAKzpyLnPCe80IZsigRAtAlTFSM6JFrP4k1Q1ZKp9q4izZdblHvZiQ6vNIvYQR7/Z7ly4JZV+KVa88PAAVml8VRDlYkhgbEL+GMzx43YvwfbVyaphPEsw9I7MT5/QjU2ffoY2DaKKQxJrnJj5ZVk5HDRFXhWMORL9uMM0VOiUmM8iXBPD7FQz+oxwJAdzx1Y9RxcDbUtLoRyLBBtQgjfI3oQ2jOHZRDa6qsC9ukPp09QI/Qjp+ytJX4WB0KItYIaBMKIvVkyjP7lqq7xcvyCJOTNi0rsZMemhXXs551eAP6zZW1dI1Fb0zwpohuwVRznMA/ulXDvZxvostnRUVmXTZZECgYEA3MDdhu2q2o5rOaCTPCKQD/b+Ndep0OH2970N8mapjjP1apDVvQ0tr7ScOJ/JMfG5dQs/pn7T+uyGQFzMr7/B1RSm+A+YnEuNNuktQqMp9vt3pGN9uhrjD7z4G24pKfYx/sbD5nGiGWlFtqp1T88e5hh5h/sy6Y1+L2/uUfmQECsCgYEA0oOw+xxyl/vsjLkHoMcbU58uT03JWIkWr+pbBVDL/vcyluqlkt6y0czslARfQ6DzzXuyZOAhGR09vKf+N7SMJjYm+b6UybISRj/Q4WBW7Zt+FzlZTAz0HPKmznyxJKJDNGnddLSwNxfdRG1J9U0RpPQxwQEwN46XGh+jBbQFwaUCgYEAwV13Lj90zyi9J6dOEPi9dB6IIiWcrEmiiPLjCpd+of9FU2k2r/ihMi1kQf1EwSjZqHqH8JFboYoZNruS18eCQ+FpOBSBOza6pYSujpZZpewzqp0zfhcbGagPNAfUqtrqhB8bbfnPYa7iz9SUGap1iFub6M7Sk93K0EadXNTbqi0CgYA7kozY4voCzIXqZMol03KGPXurcYXTCihja9yKKo0v/+BPGOP2JhNQj787O+mBh+C2e5TGOy7inoXEB35HYU5v2c85yZbtZPkK7DA+NzciUmhiRhZhESFbt8dAk8TFay29fV/wENn1HUm+fXb6de7SUVBrH9z3O+DCwcUubf1bCQKBgQCrc/1w1sSSKG2i33j3FcaNb0nhyiOuM5IaTjVlp98Nb5vEW4mC7Osx/wwE55lStH73tXDt5vPbSOHfUgWdwB2K/zDCSUnVECUwdWkZSy/+mL7SY/51/r/ZIj6iGjLAeix+zuFYp58kQmh10KVPFCzU9aD5vcFsMcdfzF3jcU/4gg==',
                ),
            ),
        ),
        'product' => array(
            // 网关地址
            'gatewayUrl'            => 'https://mapi.ucfpay.com/gateway.do',
            // 默认版本号
            'version'               => '5.0.0',
            // 远程日志设置
            'logServerIp'           => 'pmlog1.wxlc.org',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkC4kZYxADfRFqc+Nn4q5VTtD57dCUTIYMZorLlok1fJjwlamxsuWD9dX5pyUw3KcZdXekGBNsA5wZGrzW4ZMTrrV+LiokdCRD8CBCTLPGQ6DDKveaL3aObAvFFmJzQKFphJTZ2XOh0J/4ImwRMyX04MJk6NRk/HSS4aqo6Enw7RAqU84CzOWLEFR135WHLWn7Fx9ISwW9tHWrecvOma5b/Scmn4QPHAebRxzJX/7E+dVQTiszjChc3IBuS4Ws0tfMN1I3N+90C7Wo24UdK0sjr/gcvU4VtprpmK2xCzaAq3bvxVqdcpnzOIuFt0NRoK4dlhjYcx5SFLuB6v57dVdeQIDAQAB',
            // 默认主商户号
            'defaultMerchantId'     => 'M200006745',
            // 商户公钥
            'merchantKeys'          => array(
                // 平台代发商户
                'M200006745'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAkp5dxswJhtF3oNKArGXWbUPbzSLECYVndsyH78zsKyrRiYf0HsN3PxdXdBLOnV1EMFmX0c23oz7fn50HK8mpE2n89y0HG1riqQ+6OqxG18g8PU5zMH9JmCTPHSMq1uYKY+eUTPwwu8w3H3MX/vMP20fdC+o5fbh/OIuOsgO6HPbHHwQ++DCa5cSPouJYoeh8zG1czfEpC5hzIuGsPGWLy3T906vI2FCyOB9yBLqXDzLnQNgzHvP/DkD1/8k5N7IFyqYRfnR6lgOOEBL3Qy6ldum+6Ve3blUtC/l0kdsyZXFXGnbhcp8oZ4+zouCS8jsBaiL9at8/CEJlWXOyD2NYfQIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvQIBADANBgkqhkiG9w0BAQEFAASCBKcwggSjAgEAAoIBAQCSnl3GzAmG0Xeg0oCsZdZtQ9vNIsQJhWd2zIfvzOwrKtGJh/Qew3c/F1d0Es6dXUQwWZfRzbejPt+fnQcryakTafz3LQcbWuKpD7o6rEbXyDw9TnMwf0mYJM8dIyrW5gpj55RM/DC7zDcfcxf+8w/bR90L6jl9uH84i46yA7oc9scfBD74MJrlxI+i4lih6HzMbVzN8SkLmHMi4aw8ZYvLdP3Tq8jYULI4H3IEupcPMudA2DMe8/8OQPX/yTk3sgXKphF+dHqWA44QEvdDLqV26b7pV7duVS0L+XSR2zJlcVcaduFynyhnj7Oi4JLyOwFqIv1q3z8IQmVZc7IPY1h9AgMBAAECggEAFQsBroyOOXlK0BwmN5gOJHR+0XxR4oPxC43jXLluk+t7U1/d26R2MunotVIVsWQ3azEQpx39Y0Kc2c2xv8kbqRunINqnkHeE3HrTYaRkLoggjTP7OFSsfVebGjV36ovtpdUQ5dO0Mt/mcW8VXJQKDJDN2u3s/mxCZh0xh78dMRbJ1Bn0Y6C7rg5fp5s+cP/MrE3pe3l28A2JxoqPxo3CpgOJJTGP2mWwDmvBzj6pioQxGyQrhNv5jFmkJgUoGGDRhS2K2D2puDHr+LlBjPxP4QIzrH2mOAmsehbzfiX9r4GZNJOVElxx78NmYs2y+XwnLcDGYNeMQDkt6gCVvLa3AQKBgQDhGbO6Ro9UROYaNr4cRLAnmI/HbDxsBv80sSWRa/iYbpSOXf1xqBtT9l/4Eu5ueT3zlhbP+o3kLct8YlIWHwhbskhnX8sMBwnh5u0rrP4R4sOlczX/o9yTZfaYjlNeWx33NNSQCf9v3LgfEHXOl8AMYCH6kNF5sxyAxtEQzwhJ4QKBgQCmvrhrES8z09EvUBV7qdsLAdNNKlJPyIux1z5o11nrsZ/YjPWQubg2rksJiiTIJvl65cfmu+aOe/87diEFOZ/eg7vjVcn77P3ODh2DkJY8A5JZSvwH3vDiRNz8NVnkHKKAVaLtJN4X52WXxykgXjSceoTYWYnnTiRBkTTYTXY6HQKBgQCffZmFm2cEm0i6PB1ZZCW3+HWvI/ZvyElcqUNoFStvvbIOaXQg6q5qQD/hQnCj383QYIDLXcjZasUp3XQx7kz6w4hfjlUMsZQD9p4G+yyNubFL8iTJe+3WlkEx+G4DheXmeQ0+/YZ6WNYwVHZUKdtOKXMJ2UBCzXCdocER+s7xoQKBgFHLNIOwxkrSGMbwrdkflQdtOc2ceQwSABSY1VBwcvefBh4f50W7FJYeIUjorupP1AlpNgoFGi/Cu4zbgY8imT15uWzm5FHkCwxM+EVVY5zE08FhewgRYHBd/1jogtPXCA+T8nwJnbh0Fe3CEHaEP6KSpx/JrE/+kOJo7Fc+iEQxAoGAXraE/VT8HusAQUhfyr+kAQAF5qx0BFdPfoHXMHqc63e9Z9Cdc7DBhN1IhJ7x5Qj8DJnwTS2T3yVA/4mw7DIcLiovxniLPAkW10q+3FcKkdlmEVc+srAHlqvk6zmnq/x5FkXyu+YO4hJZkto0AMpO3SbESzPbJZb3GbjzPtRvdu0=',
                ),
                //TODO 代发商户2
                'M200006814'        => array(
                    // 商户公钥
                    'merchantPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEArkalFIyXEYTAscmLHddJaNrdcpT8/W4e2AlsghUfDQOwWfJWMC1VwVG8yh4jaAjr5ed2NMSEdJHo2hfrSzq4IjO4ZEWg13SpdkpVnUgVcdoyq8rrdJKNc+3CIcEiHs40XUMKTesKeM0kj/9ps7Gn2ZzPsVMRxOiquiFK1waqL1Qh7VLrwpsuuFSGKYxIYdrGg/6Qz1nUCANsnCwxp7OtVJoakqO84tdNUg6Q0HLgOpgFNor52/KuwV2G+i+Qg1mEIkxRroNn7fDLBioX7l5ZlGR3TwO0MC8cpi3JXkXEVesJTjhm4PoJjOSkR4LmSfssaCy8o3DgusI9v8VNHH8CLQIDAQAB',
                    // 商户私钥
                    'merchantPrivateKey'    => 'MIIEvgIBADANBgkqhkiG9w0BAQEFAASCBKgwggSkAgEAAoIBAQCuRqUUjJcRhMCxyYsd10lo2t1ylPz9bh7YCWyCFR8NA7BZ8lYwLVXBUbzKHiNoCOvl53Y0xIR0kejaF+tLOrgiM7hkRaDXdKl2SlWdSBVx2jKryut0ko1z7cIhwSIezjRdQwpN6wp4zSSP/2mzsafZnM+xUxHE6Kq6IUrXBqovVCHtUuvCmy64VIYpjEhh2saD/pDPWdQIA2ycLDGns61UmhqSo7zi101SDpDQcuA6mAU2ivnb8q7BXYb6L5CDWYQiTFGug2ft8MsGKhfuXlmUZHdPA7QwLxymLcleRcRV6wlOOGbg+gmM5KRHguZJ+yxoLLyjcOC6wj2/xU0cfwItAgMBAAECggEBAIgdz0PchwV8zVBPHSQiiUinYTzkVzDN+LM9sQ1s18K/ddba6yxXzFngsHag0YCpLy5y+SU/tECj0d29vt4UL8su4D8ZDwYZLV6hsnrU2UMCbH83T23OJUM9mVnp0e+DmOyatwqioJw3he4eTH24LWRPEPzr574HB4m3BRArOXMalRBwQ9eynF5For/3F3bgh4ghYctSW3A1tDsEQ0aOP8cbVGC4xQ19B7OXXp5FDVafW7Si496yNh9F6i9St/AmASjKP1kNlcj7DUjbT+4wrkKPLf6qVS1EyaJwl1GjmC4mbnS34Wrv8/fjqqBNLQypPDqQmpbVh0mD8ng26OG4J9UCgYEA5VBLmtR9G1miUv2VUd5y2xVR5v9jHYi+xd00U6aOLs0BqUPMCPUz1Aj3hLSlaLEZI1lBT9ovBi85FeTs46CeO69S+d/aAMa1e7A7TzmKvus/zgc11GGo2/VjTHxx+CpcbSVxSN431mi6pRJGCMFThFsBt1Wde42DqXLWd/ialTMCgYEAwo6rFNCoYWUyng0Fy2hS96CK5SdmcURIUKkXF/roQc1uw70YR8ZHNZrveeiFb4RFVL5cpsDNaW/6fmkFrvyjcL7Q87mxFfCWr9DNC+fpV6hSIAGjxjtzx0srRgrEpkv7dGDhX4sXYwoKC9TRuz2sqIR+gtqLwF1bAa//BrWrSx8CgYAB7nM0kqWbHV4opMolLmJ5ReyyrWQAU66HrB1MtxJrgn6JFnEZgjc1if3LuFnMT/GOQqoyKfxLaQpqDMuR/0BV8FwajrAfYY0VxZ46RJnOkdyvt4/Ugh4R/Dch5cpv9Ktin/YcTwLZY3hu+4BrgnVZoAqbqLTy3XZGbxkvWYCz0QKBgCVwFKWtvSj4szPMp7fLfGOgJfoQidrz28KBtyrrIjQ/VrgdtAVjtyIujTR1NPAPkNPMycFgymYmoRogFXLltIGmhkjQC66AHE76q+pFlxZCEoOMLTqhCBdqdIpG2i5x4pIzbkrXVgm9zc8XBcC1Dnti+4EsksXScQezGSzuWmbbAoGBAKoMhQxcUeRsi7fg83+cAkcFAobv8Gx6FFc5UKoVGwqUUOHWIV2YgIxeM9+r0eK8Atx4O/DaEsnZC1CMdbAdB1spcrqQGFXmvuq2ikgzUl4DvhOxBqtVtWzueMtCMOLCrmrsbAO46GK8NVMcnwD6sQbOYqOv2BtIyLwjsraSc0B4',
                ),
            ),
        ),
    );


    //  服务配置信息
    private $_services = array(
        // 代发接口
        self::SERVICE_WITHDRAW => array(
            'service'   => 'REQ_WITHDRAW',
            'noticeUrl' => '/payment/withdrawProxyNotify',
            // 是否进行接口同步重试
            'retry'     => true,
            'defaults'  => array(
                'transCur'      => self::TRANS_CUR_RMB,
                'source'        => self::REQ_SOURCE_NORMAL,
            ),
            'required'  => array(),
        ),
        // 单笔订单查询接口
        self::SERVICE_ORDER_QUERY => array(
            'service'   => 'REQ_WITHDRAW_QUERY_BY_ID',
            'retry'     => true,
        ),
        // 代发商户可用余额查询
        self::SERVICE_MER_BALANCE => array(
            'service'   => 'REQ_QUERY_BALANCE',
            'retry'     => true,
        )

    );

}
