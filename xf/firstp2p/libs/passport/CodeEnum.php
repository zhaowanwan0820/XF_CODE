<?php
namespace libs\passport;

class CodeEnum
{
    const SYS_PARAM_LACK = '100002';
    const SYS_DATA_NOT_IDENTICAL = '100003';
    const SYS_BIZ_PARAM_ILLEGAL = '200000';
    const SYS_BIZ_RULE_UNDEFINED = '200001';
    const SYS_OUTER_BIZ_EXCETION = '200002';
    const SYS_EXCEPTION = '500000';
    const SYS_BUSY = '500001';
    const SYS_UPGRADE = '500002';

    const AUTH_SUCCESS = '000000';
    const AUTH_FAILED = '201001';

    const SESSION_DEL_SUCCESS = '000001';
    const SESSION_DEL_FAILED = '203001';

    const UPDATE_CERT_SUCCESS = '000002';
    const UPDATE_CERT_FAILED = '202001';
    const UPDATE_CERT_CONFILCTED = '202002';

    const UPDATE_IDENTITY_SUCCESS = '000003';
    const UPDATE_IDENTITY_FAILED = '202003';
    const UPDATE_IDENTITY_CONFILCTED = '202004';

    public static $msg = [
        self::SYS_PARAM_LACK => '缺少必选的参数',
        self::SYS_DATA_NOT_IDENTICAL => '业务系统数据和PP数据不一致',
        self::SYS_BIZ_PARAM_ILLEGAL => '业务参数非法',
        self::SYS_BIZ_RULE_UNDEFINED => '业务规则未定义，请联系通行证',
        self::SYS_OUTER_BIZ_EXCETION => '通行证外部业务系统异常',
        self::SYS_EXCEPTION => '系统异常',
        self::SYS_BUSY => '服务器繁忙, 请稍后重试',
        self::SYS_UPGRADE => '通行证维护中',

        self::AUTH_SUCCESS => '鉴权成功',
        self::AUTH_FAILED => '用户鉴权失败',

        self::SESSION_DEL_SUCCESS => '删除session成功',
        self::SESSION_DEL_FAILED => '删除session失败',

        self::UPDATE_CERT_SUCCESS => '更新实名信息成功',
        self::UPDATE_CERT_FAILED => '更新实名信息失败',
        self::UPDATE_CERT_CONFILCTED => '修改的信息与其他用户冲突，请更换',
        self::UPDATE_IDENTITY_SUCCESS => '更新标识成功',
        self::UPDATE_IDENTITY_FAILED => '更新标识失败',
        self::UPDATE_IDENTITY_CONFILCTED => '标识冲突更新失败',
    ];
}
