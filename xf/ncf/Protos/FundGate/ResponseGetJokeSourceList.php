<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;

class ResponseGetJokeSourceList extends ResponseBase {

    /**
     *
     * @var Array<ProtoJokeSource> @required
     */
    public $jokeSourceList;
}