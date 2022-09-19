<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 异步发送邮件接口
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestSendAsync extends AbstractRequestBase
{
    /**
     * 邮件发送proto
     *
     * @var ProtoSendEmail
     * @required
     */
    private $protoSendEmail;

    /**
     * @return ProtoSendEmail
     */
    public function getProtoSendEmail()
    {
        return $this->protoSendEmail;
    }

    /**
     * @param ProtoSendEmail $protoSendEmail
     * @return RequestSendAsync
     */
    public function setProtoSendEmail(ProtoSendEmail $protoSendEmail)
    {
        $this->protoSendEmail = $protoSendEmail;

        return $this;
    }

}