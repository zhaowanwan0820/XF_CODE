<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

class ProtoJokeCategory extends ProtoBufferBase {

    /**
     * ID号
     *
     * @var integer
     * @required
     */
    public $id;

    /**
     * 类型名称
     *
     * @var string
     * @required
     */
    public $name;
}