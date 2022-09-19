<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 更新作者信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class RequestSetAuthorInfo extends AbstractRequestBase
{
    /**
     * 作者ID
     *
     * @var int
     * @required
     */
    private $authorId;

    /**
     * 作者名
     *
     * @var string
     * @required
     */
    private $authorName;

    /**
     * 头像图片链接
     *
     * @var string
     * @required
     */
    private $headImage;

    /**
     * @return int
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * @param int $authorId
     * @return RequestSetAuthorInfo
     */
    public function setAuthorId($authorId)
    {
        \Assert\Assertion::integer($authorId);

        $this->authorId = $authorId;

        return $this;
    }
    /**
     * @return string
     */
    public function getAuthorName()
    {
        return $this->authorName;
    }

    /**
     * @param string $authorName
     * @return RequestSetAuthorInfo
     */
    public function setAuthorName($authorName)
    {
        \Assert\Assertion::string($authorName);

        $this->authorName = $authorName;

        return $this;
    }
    /**
     * @return string
     */
    public function getHeadImage()
    {
        return $this->headImage;
    }

    /**
     * @param string $headImage
     * @return RequestSetAuthorInfo
     */
    public function setHeadImage($headImage)
    {
        \Assert\Assertion::string($headImage);

        $this->headImage = $headImage;

        return $this;
    }

}