<?php
namespace NCFGroup\Protos\O2O;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * o2o:添加合作方表单配置
 *
 * 由代码生成器生成, 不可人为修改
 * @author liguizhi
 */
class RequestAddPartnerForm extends ProtoBufferBase
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
     * 是否删除
     *
     * @var int
     * @optional
     */
    private $isDelete = 0;

    /**
     * @return string
     */
    public function getStoreId()
    {
        return $this->storeId;
    }

    /**
     * @param string $storeId
     * @return RequestAddPartnerForm
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
     * @return RequestAddPartnerForm
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
     * @return RequestAddPartnerForm
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
     * @return RequestAddPartnerForm
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
     * @return RequestAddPartnerForm
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
     * @return RequestAddPartnerForm
     */
    public function setTitleName($titleName = '')
    {
        $this->titleName = $titleName;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsDelete()
    {
        return $this->isDelete;
    }

    /**
     * @param int $isDelete
     * @return RequestAddPartnerForm
     */
    public function setIsDelete($isDelete = 0)
    {
        $this->isDelete = $isDelete;

        return $this;
    }

}