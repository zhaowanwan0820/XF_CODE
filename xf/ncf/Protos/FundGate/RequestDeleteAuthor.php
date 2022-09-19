<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;
use Assert\Assertion;

/**
 * 删除作者
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class RequestDeleteAuthor extends AbstractRequestBase
{
    /**
     * 作者ID
     *
     * @var int
     * @required
     */
    private $authorId;

    /**
     * @return int
     */
    public function getAuthorId()
    {
        return $this->authorId;
    }

    /**
     * @param int $authorId
     * @return RequestDeleteAuthor
     */
    public function setAuthorId($authorId)
    {
        \Assert\Assertion::integer($authorId);

        $this->authorId = $authorId;

        return $this;
    }

}