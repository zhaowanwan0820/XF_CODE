<?php
namespace libs\common;

/**
 * WXException
 */
use \libs\common\ErrCode;

class WXException extends \Exception {

    public function __construct($key) {
        parent::__construct(ErrCode::getMsg($key), ErrCode::getCode($key));
    }
}
