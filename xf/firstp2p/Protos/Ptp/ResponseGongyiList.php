<?php
namespace NCFGroup\Protos\Ptp;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 捐赠记录列表
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzhuyan@
 */
class ResponseGongyiList extends ResponseBase
{
    /**
     * 捐赠记录列表
     *
     * @var \NCFGroup\Common\Extensions\Base\Page
     * @required
     */
    private $dataPage;

    /**
     * 捐赠总数
     *
     * @var string
     * @required
     */
    private $sum;

    /**
     * @return \NCFGroup\Common\Extensions\Base\Page
     */
    public function getDataPage()
    {
        return $this->dataPage;
    }

    /**
     * @param \NCFGroup\Common\Extensions\Base\Page $dataPage
     * @return ResponseGongyiList
     */
    public function setDataPage(\NCFGroup\Common\Extensions\Base\Page $dataPage)
    {
        $this->dataPage = $dataPage;

        return $this;
    }
    /**
     * @return string
     */
    public function getSum()
    {
        return $this->sum;
    }

    /**
     * @param string $sum
     * @return ResponseGongyiList
     */
    public function setSum($sum)
    {
        \Assert\Assertion::string($sum);

        $this->sum = $sum;

        return $this;
    }

}