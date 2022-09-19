<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/21
 * Time: 17:35
 */

namespace iauth\helpers;


class Meta
{

    /**
     * 生成表格描述信息
     * @param string $tabId
     * @return string
     */
    public static function doc($tabId = 'meta-info')
    {
        $reflection = new \ReflectionClass(__CLASS__);
        $constants = $reflection->getConstants();
        $html = "<table id='{$tabId}'><tr><th>错误码</th><th>错误标识</th><th>信息</th></tr>";
        $current = 0;
        foreach ($constants as $k => $v) {
            $step = Number::intDiv($v, 100);
            if ($step != $current) {
                $current = $step;
                $html .= '<tr><td colspan="3">　</td></tr>';
            }
            $html .= "<tr><td>{$v}</td><td>$k</td><td>" . self::$codeInfo[$v] . "</td></tr>";
        }
        $html .= '</table>';
        return $html;
    }

    /**
     * 根据 code 返回 meta 信息
     * @param int $code
     * @return array ['code' => 10000, 'info' => '操作成功']
     */
    public static function getMeta($code = self::C_SUCCESS)
    {
        return [
            'code' => $code,
            'info' => self::getCodeInfo($code),
        ];
    }

    public static function getCodeInfo($code)
    {
        return isset(self::$codeInfo[$code]) ? self::$codeInfo[$code] : '未知错误';
    }

    /* ---------------------    以下为各种错误码及其错误信息   --------------------- */
    /* 默认情况下每个模块错误代码范围为 x0y00~x0y99 （共100个可用错误码）  */
    /* 如 10101 ~ 10199， 20300 ~ 20399 */
    /* The prefix C is short for CODE */

    /**
     * 请求 0~30
     */
    const C_SUCCESS = 0;
    const C_FAILURE = 1;
    const C_BAD_REQUEST = 2;
    const C_MISSING_ARGUMENT = 3;
    const C_ARGUMENT_TOO_LONG = 4;
    const C_UNSAFE_ARGUMENT = 5;
    const C_UPLOADED_FAILURE = 6;
    /**
     * 权限控制  60~79
     */
    const C_AUTH_DISABLED = 61;
    const C_AUTH_OFFLINE = 62;
    const C_AUTH_NEED_DUAL_FACTOR = 63;
    const C_AUTH_FORBIDDEN = 64;

    /**
     * 用户
     */
    const C_WRONG_NUMBER = 10101;
    const C_WRONG_SECTOR = 10102;
    const C_EXISTS_USER = 10103;
    const C_EXISTS_NUMBER = 10104;
    const C_PASSWORD_TOO_SHORT = 10105;
    const C_USER_NOT_FOUND = 10106;
    const C_USER_NOT_LOGIN = 10107;
    const C_PHONE_HAS_NOT_VALIDATED = 10108;
    /**
     * 短信
     */
    const C_SMS_SENT_FAILURE = 10201;
    const C_WRONG_VERIFY_CODE = 10202;
    const C_VERIFY_CODE_EXPIRED = 10203;
    const C_SMS_SENT_TOO_OFTEN = 10204;
    const C_DUAL_FACTOR_RECEIVER_PHONE_WRONG = 10205;
    /**
     * 权限
     */
    const C_EXISTS_AUTH_ITEM = 10301;
    const C_AUTH_ITEM_CODE_TOO_SHORT = 10302;
    const C_AUTH_ITEM_NOT_FOUND = 10303;
    const C_SYSTEM_NOT_FOUND = 10304;
    const C_AUTH_ITEM_PARENT_UPDATE_FAILURE = 10305;
    const C_AUT_ITEM_GROUP_NOT_FOUND = 10306;

    /**
     * 返回码对应信息，主要用于调试作用，可不写，默认信息为 '未知错误'。
     *   如果前端需要，可直接改成用户提示信息， e.g.
     *      '缺少参数' 改成 '您还有些信息未填噢！'
     *      这样前端在检测 meta.code != 10000 时，可直接回显给用户 meta.info 信息
     * @var array
     */
    private static $codeInfo = [
        /**
         * 请求类通用
         */
        self::C_SUCCESS => '操作成功',
        self::C_FAILURE => '操作失败',
        self::C_BAD_REQUEST => '非法请求',
        self::C_MISSING_ARGUMENT => '缺少参数',
        self::C_ARGUMENT_TOO_LONG => '参数过长',
        self::C_UNSAFE_ARGUMENT => '非安全的参数',
        self::C_UPLOADED_FAILURE => '上传文件失败',
        /**
         * 权限控制
         */
        self::C_AUTH_DISABLED => '权限被停用',
        self::C_AUTH_OFFLINE => '权限被下线',
        self::C_AUTH_NEED_DUAL_FACTOR => '该权限需要双因子认证',
        self::C_AUTH_FORBIDDEN => '没有执行权限',
        /**
         * 用户
         */
        self::C_WRONG_NUMBER => '电话号码不正确',
        self::C_WRONG_SECTOR => '部门不正确',
        self::C_EXISTS_NUMBER => '电话号码已经存在',
        self::C_EXISTS_USER => '用户名已经存在',
        self::C_PASSWORD_TOO_SHORT => '密码过短',
        self::C_USER_NOT_FOUND => '用户不存在',
        self::C_USER_NOT_LOGIN => '用户未登录',
        self::C_PHONE_HAS_NOT_VALIDATED => '手机号码未被验证',
        /**
         * 短信
         */
        self::C_SMS_SENT_FAILURE => '短信发送失败，请稍候再试',
        self::C_WRONG_VERIFY_CODE => '验证码错误，请重新输入',
        self::C_VERIFY_CODE_EXPIRED => '验证码已失效，请重新获取',
        self::C_SMS_SENT_TOO_OFTEN => '短信发送太频繁，请稍候再试',
        self::C_DUAL_FACTOR_RECEIVER_PHONE_WRONG => '接收双因子认证短信的用户电话号码错误',
        /**
         * 权限模块
         */
        self::C_EXISTS_AUTH_ITEM => '该权限已经存在',
        self::C_AUTH_ITEM_CODE_TOO_SHORT => '权限代码长度过短',
        self::C_AUTH_ITEM_NOT_FOUND => '权限操作不存在',
        self::C_SYSTEM_NOT_FOUND => '归属系统不存在',
        self::C_AUTH_ITEM_PARENT_UPDATE_FAILURE => '权限归属组更新失败',
        self::C_AUT_ITEM_GROUP_NOT_FOUND => '权限组不存在',
    ];
}