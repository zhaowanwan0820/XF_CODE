<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\AbstractRequestBase;

/**
 * 查询其它基金（不包含当前基金，及货基、私募基金）
 *
 * 由代码生成器生成, 不可人为修改
 * @author fupingzhou
 */
class RequestGetOtherAvailabeFundList extends AbstractRequestBase
{
    /**
     * 当前基金代码
     *
     * @var string
     * @required
     */
    private $currentFundCode;

    /**
     * 分页类
     *
     * @var \NCFGroup\Common\Extensions\Base\Pageable
     * @required
     */
    private $pageable;

    /**
     * @return string
     */
    public function getCurrentFundCode()
    {
        return $this->currentFundCode;
    }

    /**
     * @param string $currentFundCode
     * @return RequestGetOtherAvailabeFundList
     */
    public function setCurrentFundCode($currentFundCode)
    {
        \Assert\Assertion::string($currentFundCode);

        $this->currentFundCode = $currentFundCode;

        return $this;
    }
    /**
     * @return \NCFGroup\Common\Extensions\Base\Pageable
     */
    public function getPageable()
    {
        return $this->pageable;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Pageable $pageable
     * @return RequestGetOtherAvailabeFundList
     */
    public function setPageable(\NCFGroup\Common\Extensions\Base\Pageable $pageable)
    {
        $this->pageable = $pageable;

        return $this;
    }

}