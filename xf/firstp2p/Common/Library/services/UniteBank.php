<?php

namespace NCFGroup\Common\Library\services;

use NCFGroup\Common\Library\services\ServiceInterface;

class UniteBank implements ServiceInterface
{
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
            'gatewayUrl'            => 'http://220.174.24.5:9081',
            // 远程日志设置
            'logServerIp'           => '10.20.69.101',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAlY3k/6Wj51vKIkkcf0oerLGhuK6O0++JEf8wKyhANZ5YId0ToLstEiCac7bygirvpSMye51CN/Mx5LwfCzMti4rHCTVCXAp9A5VwAOiw0naZuipce/2L1suSYMWt0cawpiBHU7xfIxHybsVHN6y2zTIzYk4XfSTSBWIbZhvZSLR3UpbdhQU6xBwiBXQVpT5pM3T+tACtkIDY/ZGYEX7slz5zyxjcv0+/i7nknqEAXZsSVR825VXdXHlOFnCvMx9hOU7Slxs+WQo+24TbDOVRy4RVrjHYm3RYC71hZHS9FaztRuds/ztNCJpahpBAIzEt4phbDbGOM/pCD1VgGSq5jwIDAQAB',
            // 商户私钥
            'merchantPrivateKey'    => 'MIIEogIBAAKCAQEAlY3k/6Wj51vKIkkcf0oerLGhuK6O0++JEf8wKyhANZ5YId0ToLstEiCac7bygirvpSMye51CN/Mx5LwfCzMti4rHCTVCXAp9A5VwAOiw0naZuipce/2L1suSYMWt0cawpiBHU7xfIxHybsVHN6y2zTIzYk4XfSTSBWIbZhvZSLR3UpbdhQU6xBwiBXQVpT5pM3T+tACtkIDY/ZGYEX7slz5zyxjcv0+/i7nknqEAXZsSVR825VXdXHlOFnCvMx9hOU7Slxs+WQo+24TbDOVRy4RVrjHYm3RYC71hZHS9FaztRuds/ztNCJpahpBAIzEt4phbDbGOM/pCD1VgGSq5jwIDAQABAoIBAFiZFCXjUiNYvHntSCWcmmmCXiVTvCeQC2sO+9FFaiyZnuqI1vzshjnr+LQ+mJJGr2vsWxbiRf1xZIh2bgmrivrU/y7UT3jJeeAqoozXTRGR02Z3fAy0WyintxL/aQcSp0nza24O9WmIU1AOLS+tcSpE6C3/x2iK7KTlxweJzgKEhTdSgSJzONtQHlmcASHtbzs2U0DhJMpxL/3nQ3p1jb2sVA4py/D8i6rPO00Pi1ey526FxzkOZbAwBQu8v8R+wAsuXWQ2kUJqThFiVacHbYPKmXS/Qsdgvpw+Am57TlasFc/glklRN1A40EtNMtNuaDKAu68YVEHqaPNaowMgYTkCgYEA3rp4ArCB8JdD/tR6CUwXF8Z1me7IBI6sFEDpevZo6UaTDyj7aExbbiHnCH2ud3WiC6BqykujWR2rqI04kzh1oO+zxqLggybha/tpBfei5EmAsod+amyF7UZ1GGEHPbS5SewHWJLEKPmgdn2sMVjx9Y1vTm4cyi1dwJGtECJ/ogsCgYEAq+Ud7A2a8iVY8NmsiNZqsPvdtBJEFtdJ7c1FZ1UyKqvdyluCxCetdup6/CwPDMIwdV8eB8phjDn3KLFUdyYDYCboLrf/D9N4abin8QFCopr714knNJ2/fWeMieRd2Ggn49Cs8COl4R3RVF7jm8q7vNwdJX7y6HlgcC1ojIjk3Q0CgYBIJaTsUhq1QXqQGGmzi0dLt0iu5U87Uq/hG6nF3/3Z2reWSJMvlNRlF2xMLtIN9jfYhk0xXFD8dAT/40b5QWexCxRz+py5PyX0IYtCmJXWVwzuR7+mX6L+Wj1h+UQsM9d1X5R9l4UdNMdKuqjFj2dJQFhW0opW310oHMgvms03QwKBgF9nfOIE8xiubdzPk5knGHQ+dmB5Ot1KhDe+FGUzvfI9DY5AmCVyuC4mGjhX48p7BRY/wpUkWFvR2EH3mh+/M2Rsc7VqXeBUyKI04NE8l9VUG68W6nGjlCJwFGp8GzH/LRSePz3RK0H0oLgpKj7PmL9Lk8m52ev5YfMg4MQPPqcJAoGAL5BDPRiBWytLvm2XsiGj2LUbnwZDW85i9JCDHkk+F5hI9SYjMpLcaQaLzNI9d1mCH+hGhNE2hUre6bOt8T4IM3Ekr/zqgwxzKK59G7aU30DJ4kPnVu3f9BdpBzMW9+nHBa5lF8VYevCesLIY1AgnS7t3mRc6J4I1m56bKLcnW4U=',
        ),
        'test' => array(
            // 网关地址
            'gatewayUrl'            => 'http://60.28.76.14:9091',
            // 远程日志设置
            'logServerIp'           => '10.20.69.101',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyg/3nabRDNb4/q+JmSjsXaHGhh19jPZ0rQKihDFEfdamXCPpGmr4YC+JYzNyIBkDBDN0xgjkcAPRPfvBpZgph8kkpiHMpJnkzJuIdotPPtAw1V2KjUQQUyPBFDYHS8WfA82mq+8BckZHphsnlFb9d4o1HuVKCss+t8+3Zst6EFaYZl0X6P/3Pqqfck86Ke1zbh5ZwvMaRpEOaI5EQO6aTiPwvpD31pYi9Ayk9WeA6RPxFSC0b5mms/JpbAGhH0Srb6NZqDcdZswTP1h6+e4VuP8fC6s6EOPAyJgDpeO5MgdnnTyagLjjTMXqZ1u3t0K9fnh+bchsM/4G7KghfNTuawIDAQAB',
            // 商户私钥
            'merchantPrivateKey'    => 'MIIEpQIBAAKCAQEAvl4pYCZu+7FXEHtePfmmToigHZegjyCSFtpPlXLyMRBaK+msgcMtglNZx2yxoBGe5oHSYxzhnyRkNa2e9Nej1Rsf9PdonS7pd+cNbplDuB0MjyvOrc9InZrtGSNYSdq5I6w49dmM2MH/PjW5ci2W8xW7bONJ7KYcB8axZNaXizPBGhIhSgPbYMvXEucoX3ZVeix/+NQC2GANv6t0yNN99gLVpuJvVzvz7R6SRW5YG0L8DN56a/208Jst4gtE0w+DR5fgH/k9GI2Al2BMX+BdK9pB1Z3sbTooA7oSsb4Q22m1ipnifhlc/GM8wElkY6KDqwwQ0ItAKZFEDgBsJLgdrwIDAQABAoIBAB6UA3NlWQhm2QRVvLKZykPtIEMAmxLCeZTgJk5sM0j8Rm+tTj9duY6oktA8vl9m1S5ThhbTic5FSy9wHwtXJALUI5L2tsAgy/GtlHPCfKUzTVQmBkHW/OQMAa+7BLCASKLZRCEBe+VJbBVzDcGwXwHW6M85xyMTH4eEO/Rln9wFAgZgtY40GWPkLuRWJNrjHePwDgd+9shRkI536U+einGizrZFOL50oVHDJPBYb1ahFHKgOwDRPiBKrmFUXKhIUo7+mVo1zFW/C7o1Rh4QBI8HewJFHI3iImweFLuC6ivg4oHZAKsrv/pdyFfvHugt/wSemmpVOsBYyaS4uK0SWvECgYEA7zY/rIx0PkvxnJdxjDIZn/FNIzkcLh3/ld+xVUF0qga588rClRCZeDN1XIRDO9CsTvqKFtcWk9lSRCsBj3NHIh3wFQnv0vtlqtP+q15BSqJ9fhKo4BSDwRMVHG3dYRQP3hnnQk8S6nsy/4i9ll1fzQ9P9KEJazOd1dJGsDZhxNkCgYEAy7pdm44G4Ym8tg5LDFU+utvr1qTMOjOxZyrXknHaK1rpcsV8T5iHsJu2tsa0M31UuP6p80DzHp8uStF0OTaDzjLkggnG0UJm9ECYzKyNYLgZ9gimoCdtOHYdrqhFz3JCad4w8oWKOaU1t8Ouj0aPlDs/n44jheM2bZaxHZ+FQccCgYEAgCf5LxFEiceYFwPP0oNY1Saq4+8J2O87aekhEYLy5NCbuS/s1X3CKvKusrUtbBNc7Scu6hOrxeQNPfYobNkex/lwEWV0df03t7DB5L+njTvGrc+DaCG1gLAfhE6b5xGfeqc4DX9dq//7D4oLwE4gMDU+6dmIuUU7Dz4LnwZTlOkCgYEAjFAKAoXaJWHo7/Z+J7taXfXzwzxzUC6kI2r1V+5EFZIisKJlUKi7454LRG0sVT4fqN30jQ4Ro+h8SJljk7gBJXYVvZ4gKaWzJMyMsIKzSIbjknk40Zr19WocXVuV4R9PsHyQd6gToEox6iPCyPkPEEeSNUD/JEpuBSJBUCa676cCgYEAwZhjOHhdMmNC69qGNGJDHdB98wehGQU2oySBfzVJIAlIlhkd2usiR9xwLMk7xyPwstGiTlB/o3t2xNtWNHfuDgfyX4kN9Ynhx+jcfUpAak4EdXERNNgUMk89rQ+7njaQicU9DiO3UOozj1Wjs2Pr7eh9Q22hOKVB8jouCafGwEE=',
        ),
        'pdtest' => array(
             // 网关地址
            'gatewayUrl'            => 'http://60.28.76.14:9091',
            // 远程日志设置
            'logServerIp'           => 'pmlog1.wxlc.org',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyg/3nabRDNb4/q+JmSjsXaHGhh19jPZ0rQKihDFEfdamXCPpGmr4YC+JYzNyIBkDBDN0xgjkcAPRPfvBpZgph8kkpiHMpJnkzJuIdotPPtAw1V2KjUQQUyPBFDYHS8WfA82mq+8BckZHphsnlFb9d4o1HuVKCss+t8+3Zst6EFaYZl0X6P/3Pqqfck86Ke1zbh5ZwvMaRpEOaI5EQO6aTiPwvpD31pYi9Ayk9WeA6RPxFSC0b5mms/JpbAGhH0Srb6NZqDcdZswTP1h6+e4VuP8fC6s6EOPAyJgDpeO5MgdnnTyagLjjTMXqZ1u3t0K9fnh+bchsM/4G7KghfNTuawIDAQAB',
            // 商户私钥
            'merchantPrivateKey'    => 'MIIEpQIBAAKCAQEAvl4pYCZu+7FXEHtePfmmToigHZegjyCSFtpPlXLyMRBaK+msgcMtglNZx2yxoBGe5oHSYxzhnyRkNa2e9Nej1Rsf9PdonS7pd+cNbplDuB0MjyvOrc9InZrtGSNYSdq5I6w49dmM2MH/PjW5ci2W8xW7bONJ7KYcB8axZNaXizPBGhIhSgPbYMvXEucoX3ZVeix/+NQC2GANv6t0yNN99gLVpuJvVzvz7R6SRW5YG0L8DN56a/208Jst4gtE0w+DR5fgH/k9GI2Al2BMX+BdK9pB1Z3sbTooA7oSsb4Q22m1ipnifhlc/GM8wElkY6KDqwwQ0ItAKZFEDgBsJLgdrwIDAQABAoIBAB6UA3NlWQhm2QRVvLKZykPtIEMAmxLCeZTgJk5sM0j8Rm+tTj9duY6oktA8vl9m1S5ThhbTic5FSy9wHwtXJALUI5L2tsAgy/GtlHPCfKUzTVQmBkHW/OQMAa+7BLCASKLZRCEBe+VJbBVzDcGwXwHW6M85xyMTH4eEO/Rln9wFAgZgtY40GWPkLuRWJNrjHePwDgd+9shRkI536U+einGizrZFOL50oVHDJPBYb1ahFHKgOwDRPiBKrmFUXKhIUo7+mVo1zFW/C7o1Rh4QBI8HewJFHI3iImweFLuC6ivg4oHZAKsrv/pdyFfvHugt/wSemmpVOsBYyaS4uK0SWvECgYEA7zY/rIx0PkvxnJdxjDIZn/FNIzkcLh3/ld+xVUF0qga588rClRCZeDN1XIRDO9CsTvqKFtcWk9lSRCsBj3NHIh3wFQnv0vtlqtP+q15BSqJ9fhKo4BSDwRMVHG3dYRQP3hnnQk8S6nsy/4i9ll1fzQ9P9KEJazOd1dJGsDZhxNkCgYEAy7pdm44G4Ym8tg5LDFU+utvr1qTMOjOxZyrXknHaK1rpcsV8T5iHsJu2tsa0M31UuP6p80DzHp8uStF0OTaDzjLkggnG0UJm9ECYzKyNYLgZ9gimoCdtOHYdrqhFz3JCad4w8oWKOaU1t8Ouj0aPlDs/n44jheM2bZaxHZ+FQccCgYEAgCf5LxFEiceYFwPP0oNY1Saq4+8J2O87aekhEYLy5NCbuS/s1X3CKvKusrUtbBNc7Scu6hOrxeQNPfYobNkex/lwEWV0df03t7DB5L+njTvGrc+DaCG1gLAfhE6b5xGfeqc4DX9dq//7D4oLwE4gMDU+6dmIuUU7Dz4LnwZTlOkCgYEAjFAKAoXaJWHo7/Z+J7taXfXzwzxzUC6kI2r1V+5EFZIisKJlUKi7454LRG0sVT4fqN30jQ4Ro+h8SJljk7gBJXYVvZ4gKaWzJMyMsIKzSIbjknk40Zr19WocXVuV4R9PsHyQd6gToEox6iPCyPkPEEeSNUD/JEpuBSJBUCa676cCgYEAwZhjOHhdMmNC69qGNGJDHdB98wehGQU2oySBfzVJIAlIlhkd2usiR9xwLMk7xyPwstGiTlB/o3t2xNtWNHfuDgfyX4kN9Ynhx+jcfUpAak4EdXERNNgUMk89rQ+7njaQicU9DiO3UOozj1Wjs2Pr7eh9Q22hOKVB8jouCafGwEE=',
        ),
        'product' => array(
            // 网关地址
            'gatewayUrl'            => 'https://dbank.unitedbank.cn',
            // 远程日志设置
            'logServerIp'           => 'pmlog1.wxlc.org',
            'logPort'               => '55001',
            // 平台公钥
            'platformPublicKey'     => 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAyg/3nabRDNb4/q+JmSjsXaHGhh19jPZ0rQKihDFEfdamXCPpGmr4YC+JYzNyIBkDBDN0xgjkcAPRPfvBpZgph8kkpiHMpJnkzJuIdotPPtAw1V2KjUQQUyPBFDYHS8WfA82mq+8BckZHphsnlFb9d4o1HuVKCss+t8+3Zst6EFaYZl0X6P/3Pqqfck86Ke1zbh5ZwvMaRpEOaI5EQO6aTiPwvpD31pYi9Ayk9WeA6RPxFSC0b5mms/JpbAGhH0Srb6NZqDcdZswTP1h6+e4VuP8fC6s6EOPAyJgDpeO5MgdnnTyagLjjTMXqZ1u3t0K9fnh+bchsM/4G7KghfNTuawIDAQAB',
            // 商户私钥
            'merchantPrivateKey'    => 'MIIEpQIBAAKCAQEAvl4pYCZu+7FXEHtePfmmToigHZegjyCSFtpPlXLyMRBaK+msgcMtglNZx2yxoBGe5oHSYxzhnyRkNa2e9Nej1Rsf9PdonS7pd+cNbplDuB0MjyvOrc9InZrtGSNYSdq5I6w49dmM2MH/PjW5ci2W8xW7bONJ7KYcB8axZNaXizPBGhIhSgPbYMvXEucoX3ZVeix/+NQC2GANv6t0yNN99gLVpuJvVzvz7R6SRW5YG0L8DN56a/208Jst4gtE0w+DR5fgH/k9GI2Al2BMX+BdK9pB1Z3sbTooA7oSsb4Q22m1ipnifhlc/GM8wElkY6KDqwwQ0ItAKZFEDgBsJLgdrwIDAQABAoIBAB6UA3NlWQhm2QRVvLKZykPtIEMAmxLCeZTgJk5sM0j8Rm+tTj9duY6oktA8vl9m1S5ThhbTic5FSy9wHwtXJALUI5L2tsAgy/GtlHPCfKUzTVQmBkHW/OQMAa+7BLCASKLZRCEBe+VJbBVzDcGwXwHW6M85xyMTH4eEO/Rln9wFAgZgtY40GWPkLuRWJNrjHePwDgd+9shRkI536U+einGizrZFOL50oVHDJPBYb1ahFHKgOwDRPiBKrmFUXKhIUo7+mVo1zFW/C7o1Rh4QBI8HewJFHI3iImweFLuC6ivg4oHZAKsrv/pdyFfvHugt/wSemmpVOsBYyaS4uK0SWvECgYEA7zY/rIx0PkvxnJdxjDIZn/FNIzkcLh3/ld+xVUF0qga588rClRCZeDN1XIRDO9CsTvqKFtcWk9lSRCsBj3NHIh3wFQnv0vtlqtP+q15BSqJ9fhKo4BSDwRMVHG3dYRQP3hnnQk8S6nsy/4i9ll1fzQ9P9KEJazOd1dJGsDZhxNkCgYEAy7pdm44G4Ym8tg5LDFU+utvr1qTMOjOxZyrXknHaK1rpcsV8T5iHsJu2tsa0M31UuP6p80DzHp8uStF0OTaDzjLkggnG0UJm9ECYzKyNYLgZ9gimoCdtOHYdrqhFz3JCad4w8oWKOaU1t8Ouj0aPlDs/n44jheM2bZaxHZ+FQccCgYEAgCf5LxFEiceYFwPP0oNY1Saq4+8J2O87aekhEYLy5NCbuS/s1X3CKvKusrUtbBNc7Scu6hOrxeQNPfYobNkex/lwEWV0df03t7DB5L+njTvGrc+DaCG1gLAfhE6b5xGfeqc4DX9dq//7D4oLwE4gMDU+6dmIuUU7Dz4LnwZTlOkCgYEAjFAKAoXaJWHo7/Z+J7taXfXzwzxzUC6kI2r1V+5EFZIisKJlUKi7454LRG0sVT4fqN30jQ4Ro+h8SJljk7gBJXYVvZ4gKaWzJMyMsIKzSIbjknk40Zr19WocXVuV4R9PsHyQd6gToEox6iPCyPkPEEeSNUD/JEpuBSJBUCa676cCgYEAwZhjOHhdMmNC69qGNGJDHdB98wehGQU2oySBfzVJIAlIlhkd2usiR9xwLMk7xyPwstGiTlB/o3t2xNtWNHfuDgfyX4kN9Ynhx+jcfUpAak4EdXERNNgUMk89rQ+7njaQicU9DiO3UOozj1Wjs2Pr7eh9Q22hOKVB8jouCafGwEE=',
        ),
    );

    //  服务配置信息
    private $_services = array(
        // 2.1贷款账户开户
        'CreateLoanAcctPre' => array(
            'desc' => '贷款用户在银行申请开户接口',
            'apiName' => '/portal/CreateLoanAcctPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => '/payment/CreateLoanAccountNotify',
            ),
            'required' => array(
                'WJnlNo' => '网信注册申请流水',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'AcNo' => '绑定卡号',
                'MobilePhone' => '手机号',
                'BankName' => '开户银行',
                'WXUrl' => '回调地址',
                'notifyDomain' => '回调域名',
            ),
            'sign' => array(
                'WJnlNo' => '网信注册申请流水',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'AcNo' => '绑定卡号',
                'MobilePhone' => '手机号',
                'WXUrl' => '回调地址',
            ),
        ), // end CreateLoanAcctPre
        // 2.2贷款账户开户接口
        'CreateNewLoanAcctPre' => array(
            'desc' => '贷款账户开户并发起借款申请',
            'apiName' => '/portal/CreateNewLoanAcctPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'PMethod' => '10',
                'WXUrl' => '/payment/CreateLoanAccountNotify',
                'WXLoanUrl' => '/payment/CreateLoanNotify',
                'WXGrantUrl' => '/payment/LoanLendNotify',
            ),
            'required' => array(
                'WJnlNo' => '网信注册申请流水(用户ID)',
                'LWJnlNo' => '网信贷款申请流水号',
                'LAmount' => '贷款申请金额',
                'LTime' => '借款期限',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'BankId' => '银行Id',
                'AcNo' => '绑定卡号',
                'MobilePhone' => '手机号',
                'RegChannelId' => '渠道号',
                'BankName' => '开户银行',
                'WXUrl' => '回调地址',
                'WXLoanUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
            'sign' => array(
                'WJnlNo' => '网信注册申请流水(用户ID)',
                'LWJnlNo' => '网信贷款申请流水号',
                'LAmount' => '贷款申请金额',
                'LTime' => '借款期限',
                'UserId' => '姓名',
                'IdNo' => '身份证号',
                'BankId' => '银行Id',
                'AcNo' => '绑定卡号',
                'PMethod' => '还款方式',
                'MobilePhone' => '手机号',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXLoanUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
        ), // end CreateNewLoanAcctPre
        // 2.3贷款申请接口
        'LoanApplyPre' => array(
            'desc' => '贷款申请接口',
            'apiName' => '/portal/LoanApplyPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'PMethod' => '10',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => '/payment/CreateLoanNotify',
                'WXGrantUrl' => '/payment/LoanLendNotify',
            ),
            'required' => array(
                'UserId' => '姓名',
                'LTime' => '借款期限',
                'PMethod' => '还款方式',
                'LAmount' => '借款金额',
                'IdNo' => '证件号',
                'BankId' => '银行Id',
                'MobilePhone' => '手机号',
                'WJnlNo' => '网信申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
            'sign' => array(
                'UserId' => '姓名',
                'LTime' => '借款期限',
                'PMethod' => '还款方式',
                'LAmount' => '借款金额',
                'IdNo' => '证件号',
                'BankId' => '银行Id',
                'MobilePhone' => '手机号',
                'WJnlNo' => '网信申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXGrantUrl' => '回调地址',
            ),
        ), // end LoanApplyPre
        // 2.4还款申请（用户发起)
        'LoanRepayEarlyPre' => array(
            'desc' => '还款申请（用户发起）',
            'apiName' => '/portal/LoanRepayEarlyPre.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => '/payment/LoanRepayAcceptNotify',
                'WXRepayUrl' => '/payment/LoanRepayNotify',
            ),
            'required' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
            'sign' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
        ), // end LoanRepayEarlyPre
        // 2.5还款申请（网信POST申请)
        'LoanRepayEarlyWX' => array(
            'desc' => '还款申请（网信POST申请)',
            'apiName' => '/portal/LoanRepayEarlyWX.do',
            'params' => array(
                'BankId' => 9999,
                'LoginType' => 'P',
                '_locale' => 'zh_CN',
                'LoanMobile' => 'PHCH',
                'RegChannelId' => 'wxdk',
                'WXUrl' => '/payment/LoanRepayAcceptNotify',
                'WXRepayUrl' => '/payment/LoanRepayNotify',
            ),
            'required' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
            'sign' => array(
                'UserId' => '姓名',
                'IdNo' => '证件号',
                'MobilePhone' => '手机号',
                'PTime' => '还款日期',
                'Amount' => '还款金额',
                'PState' => '还款状态',
                'PRate' => '还款利率',
                'BankId' => '银行Id',
                'WJnlNo' => '网信申请流水',
                'OWJnlNo' => '网信借款申请流水',
                'RegChannelId' => '渠道号',
                'WXUrl' => '回调地址',
                'WXRepayUrl' => '回调地址',
            ),
        )
    );
}
