<?php

/**
 * @author YiXiao，<yixiao@ucfgroup.com>
 * @date  2014-2-25 18:07:04
 * @encode UTF-8编码
 */
class P_Conf_Oauth {

    const OAUTH_FUNC_CHECK_USER_INFO= 'check_user_info';
    const OAUTH_FUNC_FORGET_PASSWORD = 'forget_password';
    const OAUTH_FUNC_GET_CODE = 'get_code';
    const OAUTH_FUNC_GET_USER_INFO = 'get_user_info';
    const OAUTH_FUNC_LOGIN = 'login';
    const OAUTH_FUNC_LOGOUT = 'logout';
    const OAUTH_FUNC_MODIFY_PASSWORD = 'modify_password';
    const OAUTH_FUNC_MODIFY_USER_NAME = 'modify_user_name';
    const OAUTH_FUNC_REGISTER = 'register';
    const OAUTH_FUNC_THIRD_LOGIN = 'third_login';
    const OAUTH_METHOD_PREFIX = '_oauth_';
    const OAUTH_URL_PREFIX = 'http://oauth.9888.com/oauthserver_new/rs/';
    const OAUTH_URL_SUFFIX = '?_type=json';
    const OAUTH_XML = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>%s';
    const PREG_EMAIL = P_Conf_Formcheck::EMAIL_DEFAULT_REGEX;
    const PREG_MOBILE = P_Conf_Formcheck::MOBILE_DEFAULT_REGEX;

    public static $url_infix = array(
        self::OAUTH_FUNC_CHECK_USER_INFO => 'user/checkuserinfor',
        self::OAUTH_FUNC_FORGET_PASSWORD => 'user/finduserpwd',
        self::OAUTH_FUNC_GET_CODE => array('verifycode/send', 'sendemail/send'),
        self::OAUTH_FUNC_GET_USER_INFO => 'oauth2/token',
        self::OAUTH_FUNC_LOGIN => 'userlogin/login',
        self::OAUTH_FUNC_LOGOUT => 'userlogin/logout',
        self::OAUTH_FUNC_MODIFY_PASSWORD => 'user/updateuserpwd',
        self::OAUTH_FUNC_MODIFY_USER_NAME => 'user/updateusername',
        self::OAUTH_FUNC_REGISTER => 'user/add',
        self::OAUTH_FUNC_THIRD_LOGIN => 'userlogin/third',
    );

}
