<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获得最新文本段子列表的Response Proto
 *
 * @author zhounew
 *
 */
class ResponseGetJokeCategoryList extends ResponseBase {
    /**
     * @var Array<ProtoJokeCategory>
     * @required
     */
    public $jokeCategoryList = array();
}