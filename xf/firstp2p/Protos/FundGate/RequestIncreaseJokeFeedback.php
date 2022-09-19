<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

class RequestIncreaseJokeFeedback extends AbstractRequestBase {

    /**
     * 段子的ID号
     * 
     * @var integer
     * @required
     */
    public $joke_id = 0;

    /**
     * 支持数增量
     * 
     * @var integer
     * @optional
     */
    public $support_inc = 0;

    /**
     * 反对数增量
     * 
     * @var integer
     * @optional
     */
    public $deny_inc = 0;

    /**
     * 喜欢数增量
     * 
     * @var integer
     * @optional
     */
    public $favorite_inc = 0;

    /**
     * 用户IP
     * 
     * @var string
     * @optional
     */
    public $user_ip = "";

    /**
     * 用户ID
     * 
     * @var integer
     * @optional
     */
    public $user_id = "";
}