<?php
namespace NCFGroup\Protos\Stock\Enum;
use NCFGroup\Common\Extensions\Base\AbstractEnum;

class CertEnum extends AbstractEnum
{
    /**
     *  没有证书, 证书未申请
     */
    const RESULT_NO_APPLY = -65000403;
    /**
     *  证书未签发
     */
    const STATUS_NO_DOWLOAD = 1;
    /**
     *  有证书
     */
    const STATUS_HAS = 2;
    /**
     *  中登
     */
    const ZHONG_DENG = 1;
    /**
     *  国籍 中国
     */
    const CHINA = 156;
    /**
     * 身份证
     */
    const ID_CARD = 0;
    /**
     * 个人普通软证书
     */
    const CERT_SOFT = 1;
    /**
     * 证书申请方式, 网上申请(文档里写的是其他, 但确认后应该是网上申请)
     */
    const APPLY_NET = 3;
    /**
     *  签名类型 detached
     */
    const SIGN_DETACHED = 1;
    /**
     *  签名类型attached
     */
    const SIGN_ATTACHED = 2;
}
