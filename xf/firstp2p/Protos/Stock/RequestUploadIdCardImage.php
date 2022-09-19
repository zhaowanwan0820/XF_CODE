<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 上传身份证
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class RequestUploadIdCardImage extends AbstractRequestBase
{
    /**
     * 正面还是反而
     *
     * @var string
     * @required
     */
    private $idCardType;

    /**
     * 图片内容
     *
     * @var string
     * @required
     */
    private $idCardContent;

    /**
     * 用户id
     *
     * @var int
     * @required
     */
    private $userId;

    /**
     * @return string
     */
    public function getIdCardType()
    {
        return $this->idCardType;
    }

    /**
     * @param string $idCardType
     * @return RequestUploadIdCardImage
     */
    public function setIdCardType($idCardType)
    {
        \Assert\Assertion::string($idCardType);

        $this->idCardType = $idCardType;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdCardContent()
    {
        return $this->idCardContent;
    }

    /**
     * @param string $idCardContent
     * @return RequestUploadIdCardImage
     */
    public function setIdCardContent($idCardContent)
    {
        \Assert\Assertion::string($idCardContent);

        $this->idCardContent = $idCardContent;

        return $this;
    }
    /**
     * @return int
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     * @return RequestUploadIdCardImage
     */
    public function setUserId($userId)
    {
        \Assert\Assertion::integer($userId);

        $this->userId = $userId;

        return $this;
    }

}