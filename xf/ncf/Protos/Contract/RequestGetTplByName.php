<?php
namespace NCFGroup\Protos\Contract;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * 按照合同id取得模板
 *
 * 由代码生成器生成, 不可人为修改
 * @author wangjiantong
 */
class RequestGetTplByName extends ProtoBufferBase
{
    /**
     * 标的ID
     *
     * @var int
     * @required
     */
    private $deal_id;

    /**
     * 模板标记前缀
     *
     * @var string
     * @required
     */
    private $tpl_prefix;

    /**
     * 类型(0:p2p;1:多投宝)
     *
     * @var int
     * @optional
     */
    private $type = 1;

    /**
     * 来源类型(0:P2P,1:通知贷,2:交易所,3:专享)
     *
     * @var int
     * @optional
     */
    private $sourceType = 0;

    /**
     * @return int
     */
    public function getDeal_id()
    {
        return $this->deal_id;
    }

    /**
     * @param int $deal_id
     * @return RequestGetTplByName
     */
    public function setDeal_id($deal_id)
    {
        \Assert\Assertion::integer($deal_id);

        $this->deal_id = $deal_id;

        return $this;
    }
    /**
     * @return string
     */
    public function getTpl_prefix()
    {
        return $this->tpl_prefix;
    }

    /**
     * @param string $tpl_prefix
     * @return RequestGetTplByName
     */
    public function setTpl_prefix($tpl_prefix)
    {
        \Assert\Assertion::string($tpl_prefix);

        $this->tpl_prefix = $tpl_prefix;

        return $this;
    }
    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     * @return RequestGetTplByName
     */
    public function setType($type = 1)
    {
        $this->type = $type;

        return $this;
    }
    /**
     * @return int
     */
    public function getSourceType()
    {
        return $this->sourceType;
    }

    /**
     * @param int $sourceType
     * @return RequestGetTplByName
     */
    public function setSourceType($sourceType = 0)
    {
        $this->sourceType = $sourceType;

        return $this;
    }

}