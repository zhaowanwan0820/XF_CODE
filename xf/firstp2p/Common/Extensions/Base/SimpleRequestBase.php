<?php
namespace NCFGroup\Common\Extensions\Base;

/* SimpleRequestBase.php ---
 *
 * Filename: SimpleRequestBase.php
 * Description: <put the file description here>
 * Author: zhounew
 * Created: 14-10-3 下午3:34
 * Version: v1.0
 *
 * Copyright (c) 2014-2020 NCFGroup
 */

final class SimpleRequestBase extends AbstractRequestBase {
    private $paramArray = array();

    public function __construct()
    {
        $args = func_get_args();
        $this->paramArray = $args;
        parent::__construct();
    }

    /**
     * @return array
     */
    public function getParamArray()
    {
        return $this->paramArray;
    }

    public function setParamArray(Array $paramArray)
    {
        $this->paramArray = $paramArray;
        return $this;
    }

    /**
     * @return array | null
     */
    public function getParam($idx)
    {
        if(isset($this->paramArray[$idx])) {
            return $this->paramArray[$idx];
        }
        return null;
    }

    public function setParam($val)
    {
        $this->paramArray[] = $val;
        return $this;
    }

    // 下面这是一些alias 为了兼容RequestCommon, 以避免各个项目都初始化RequestCommon的问题。
    public function getVars()
    {
        return $this->getParamArray();
    }

    public function getVar($var)
    {
        return $this->getParam($var);
    }

    public function setVars(Array $vars)
    {
        return $this->setParamArray($vars);
    }
}
