<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获取公募基金公告内容
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetAnnouncement extends AbstractRequestBase
{
    /**
     * 公告id
     *
     * @var int
     * @required
     */
    private $id;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return RequestGetAnnouncement
     */
    public function setId($id)
    {
        \Assert\Assertion::integer($id);

        $this->id = $id;

        return $this;
    }

}