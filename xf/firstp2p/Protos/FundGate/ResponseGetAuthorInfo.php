<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 获得作者信息
 *
 * 由代码生成器生成, 不可人为修改
 * @author LiBing <libing10@ucfgroup.com>
 */
class ResponseGetAuthorInfo extends ResponseBase
{
    /**
     * 作者信息
     *
     * @var array
     * @required
     */
    private $authorInfo;

    /**
     * @return array
     */
    public function getAuthorInfo()
    {
        return $this->authorInfo;
    }

    /**
     * @param array $authorInfo
     * @return ResponseGetAuthorInfo
     */
    public function setAuthorInfo(array $authorInfo)
    {
        $this->authorInfo = $authorInfo;

        return $this;
    }

}