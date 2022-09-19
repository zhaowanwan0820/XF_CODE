<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ResponseBase;
use Assert\Assertion;

/**
 * 合作方表单
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class ResponseGetPartnerForm extends ResponseBase
{
    /**
     * 门店ID
     *
     * @var string
     * @required
     */
    private $storeId;

    /**
     * 表单配置
     *
     * @var string
     * @required
     */
    private $formConf;

    /**
     * 短信配置
     *
     * @var string
     * @optional
     */
    private $msgConf = '';

    /**
     * 表单描述
     *
     * @var string
     * @optional
     */
    private $remark = '';

    /**
     * 门店名称
     *
     * @var string
     * @optional
     */
    private $storeName = '';

    /**
     * 表单提示语
     *
     * @var string
     * @optional
     */
    private $titleName = '';

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     * @return ResponseGetPartnerForm
     */
    public function setStoreId($storeId)
    {
        \Assert\Assertion::string($storeId);

        $this->storeId = $storeId;

        return $this;
    }
    /**
     * @return string
     */
    public function getFormConf()
    {
        return $this->formConf;
    }

    /**
     * @param string $formConf
     * @return ResponseGetPartnerForm
     */
    public function setFormConf($formConf)
    {
        \Assert\Assertion::string($formConf);

        $this->formConf = $formConf;

        return $this;
    }
    /**
     * @return string
     */
    public function getMsgConf()
    {
        return $this->msgConf;
    }

    /**
     * @param string $msgConf
     * @return ResponseGetPartnerForm
     */
    public function setMsgConf($msgConf = '')
    {
        $this->msgConf = $msgConf;

        return $this;
    }
    /**
     * @return string
     */
    public function getRemark()
    {
        return $this->remark;
    }

    /**
     * @param string $remark
     * @return ResponseGetPartnerForm
     */
    public function setRemark($remark = '')
    {
        $this->remark = $remark;

        return $this;
    }
    /**
     * @return string
     */
    public function getStoreName()
    {
        return $this->storeName;
    }

    /**
     * @param string $storeName
     * @return ResponseGetPartnerForm
     */
    public function setStoreName($storeName = '')
    {
        $this->storeName = $storeName;

        return $this;
    }
    /**
     * @return string
     */
    public function getTitleName()
    {
        return $this->titleName;
    }

    /**
     * @param string $titleName
     * @return ResponseGetPartnerForm
     */
    public function setTitleName($titleName = '')
    {
        $this->titleName = $titleName;

        return $this;
    }

}