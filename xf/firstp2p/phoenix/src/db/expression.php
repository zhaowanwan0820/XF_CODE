<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-8 17:08:30
 * @encode UTF-8编码
 */
class P_Db_Expression {

    public $expression = '';
    public $params = array();

    public function __construct($expression, $params = array()) {
        $this->expression = $expression;
        $this->params = $params;
    }

    public function __toString() {
        return $this->expression;
    }

}
