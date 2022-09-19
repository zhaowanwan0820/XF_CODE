<?php
namespace NCFGroup\Protos\Stock;

use NCFGroup\Common\Extensions\Base\ResponseBase;

/**
 * 用户开户步骤
 *
 * 由代码生成器生成, 不可人为修改
 * @author zhangzuoyang
 */
class ResponseGetUserStageInfo extends ResponseBase
{
    /**
     * 步骤
     *
     * @var string
     * @required
     */
    private $currentStep;

    /**
     * 身份证号
     *
     * @var string
     * @optional
     */
    private $idCardNumber = '';

    /**
     * 用户姓名
     *
     * @var string
     * @optional
     */
    private $customerName = '';

    /**
     * 是否跳过证书
     *
     * @var int
     * @optional
     */
    private $skipSignDn = 0;

    /**
     * @return string
     */
    public function getCurrentStep()
    {
        return $this->currentStep;
    }

    /**
     * @param string $currentStep
     * @return ResponseGetUserStageInfo
     */
    public function setCurrentStep($currentStep)
    {
        \Assert\Assertion::string($currentStep);

        $this->currentStep = $currentStep;

        return $this;
    }
    /**
     * @return string
     */
    public function getIdCardNumber()
    {
        return $this->idCardNumber;
    }

    /**
     * @param string $idCardNumber
     * @return ResponseGetUserStageInfo
     */
    public function setIdCardNumber($idCardNumber = '')
    {
        $this->idCardNumber = $idCardNumber;

        return $this;
    }
    /**
     * @return string
     */
    public function getCustomerName()
    {
        return $this->customerName;
    }

    /**
     * @param string $customerName
     * @return ResponseGetUserStageInfo
     */
    public function setCustomerName($customerName = '')
    {
        $this->customerName = $customerName;

        return $this;
    }
    /**
     * @return int
     */
    public function getSkipSignDn()
    {
        return $this->skipSignDn;
    }

    /**
     * @param int $skipSignDn
     * @return ResponseGetUserStageInfo
     */
    public function setSkipSignDn($skipSignDn = 0)
    {
        $this->skipSignDn = $skipSignDn;

        return $this;
    }

}