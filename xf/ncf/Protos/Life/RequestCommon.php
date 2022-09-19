<?php
namespace NCFGroup\Protos\Life;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * life 通用request proto
 * 觉得写protos麻烦的话可以调用此类
 */
class RequestCommon extends ProtoBufferBase {

    /**
     * 变量容器
     *
     * @var array
     * @required
     */
    private $var;

    /**
     * @return mixed
     */
    public function getVar($var) {
        return isset($this->var[$var]) ? $this->var[$var] : '';
    }

    /**
     * get all vars
     * @return array
     */
    public function getVars() {
        return is_array($this->var) ? $this->var : array();
    }

    /**
     * @param array $var
     * @return RequestCommon
     */
    public function setVars($vars=array()) {
        if(!is_array($vars) || empty($vars)) {
            return;
        }
        foreach($vars as $k=>$v) {
            $this->var[$k] = $v;
        }
    }
}