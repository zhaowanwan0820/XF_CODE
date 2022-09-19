<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 获得最新文本段子列表的Request Protobuffer
 *
 * @author zhounew
 *        
 */
class RequestGetJokeList extends AbstractRequestBase {

    /**
     *
     * @var integer
     * @optional
     */
    public $from = 0;

    /**
     *
     * @var integer
     * @optional
     */
    public $count = 10;

    /**
     *
     * @var string
     * @optional
     */
    public $orderBy = "pub_time DESC";

    /**
     *
     * @var EnumJokeType(int)
     * @optional
     */
    public $category = 0;

    /**
     * @return int
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param int $from
     */
    public function setFrom($from)
    {
        $this->from = $from;
    }
}