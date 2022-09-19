<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

class RequestVerifyUser extends AbstractRequestBase {

    /**
     * 用户名
     *
     * @required
     * 
     * @var string
     */
    public $username;

    /**
     * 密码
     *
     * @required
     * 
     * @var password
     */
    public $password;
}