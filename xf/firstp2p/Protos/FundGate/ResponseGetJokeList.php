<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 获得最新文本段子列表的Response Proto
 *
 * @author zhounew
 *        
 */
class ResponseGetJokeList extends ResponseBase {

    /**
     *
     * @var Array<ProtoJoke> @required
     */
    public $textJokeList = array();
}