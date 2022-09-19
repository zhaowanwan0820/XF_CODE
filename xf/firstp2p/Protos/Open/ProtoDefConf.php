<?php
namespace NCFGroup\Protos\Open;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;
use Assert\Assertion;

/**
 * open:配置模板信息Proto
 *
 * 由代码生成器生成, 不可人为修改
 * @author yutao
 */
class ProtoDefConf extends ProtoBufferBase
{
    /**
     * 配置ID
     *
     * @var int
     * @optional
     */
    private $id = '';

    /**
     * 配置名称
     *
     * @var string
     * @optional
     */
    private $title = '';

    /**
     * 配置key
     *
     * @var string
     * @optional
     */
    private $name = '';

    /**
     * 默认配置值
     *
     * @var string
     * @optional
     */
    private $valueDefault = '';

    /**
     * 默认是否有效
     *
     * @var int
     * @optional
     */
    private $isEffectDefault = 1;

    /**
     * 是否允许开发者配置
     *
     * @var int
     * @optional
     */
    private $isAllowDevConf = 0;

    /**
     * 显示配置类型
     *
     * @var int
     * @optional
     */
    private $displayType = 1;

    /**
     * 显示配置项
     *
     * @var string
     * @optional
     */
    private $displayContent = '';

    /**
     * 提示
     *
     * @var string
     * @optional
     */
    private $tip = '';

    /**
     * 配置类型
     *
     * @var int
     * @optional
     */
    private $confType = 0;

    /**
     * 创建时间
     *
     * @var int
     * @optional
     */
    private $createTime = 0;

    /**
     * 最后修改时间
     *
     * @var int
     * @optional
     */
    private $updateTime = 0;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     * @return ProtoDefConf
     */
    public function setId($id = '')
    {
        $this->id = $id;

        return $this;
    }
    /**
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * @param string $title
     * @return ProtoDefConf
     */
    public function setTitle($title = '')
    {
        $this->title = $title;

        return $this;
    }
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return ProtoDefConf
     */
    public function setName($name = '')
    {
        $this->name = $name;

        return $this;
    }
    /**
     * @return string
     */
    public function getValueDefault()
    {
        return $this->valueDefault;
    }

    /**
     * @param string $valueDefault
     * @return ProtoDefConf
     */
    public function setValueDefault($valueDefault = '')
    {
        $this->valueDefault = $valueDefault;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsEffectDefault()
    {
        return $this->isEffectDefault;
    }

    /**
     * @param int $isEffectDefault
     * @return ProtoDefConf
     */
    public function setIsEffectDefault($isEffectDefault = 1)
    {
        $this->isEffectDefault = $isEffectDefault;

        return $this;
    }
    /**
     * @return int
     */
    public function getIsAllowDevConf()
    {
        return $this->isAllowDevConf;
    }

    /**
     * @param int $isAllowDevConf
     * @return ProtoDefConf
     */
    public function setIsAllowDevConf($isAllowDevConf = 0)
    {
        $this->isAllowDevConf = $isAllowDevConf;

        return $this;
    }
    /**
     * @return int
     */
    public function getDisplayType()
    {
        return $this->displayType;
    }

    /**
     * @param int $displayType
     * @return ProtoDefConf
     */
    public function setDisplayType($displayType = 1)
    {
        $this->displayType = $displayType;

        return $this;
    }
    /**
     * @return string
     */
    public function getDisplayContent()
    {
        return $this->displayContent;
    }

    /**
     * @param string $displayContent
     * @return ProtoDefConf
     */
    public function setDisplayContent($displayContent = '')
    {
        $this->displayContent = $displayContent;

        return $this;
    }
    /**
     * @return string
     */
    public function getTip()
    {
        return $this->tip;
    }

    /**
     * @param string $tip
     * @return ProtoDefConf
     */
    public function setTip($tip = '')
    {
        $this->tip = $tip;

        return $this;
    }
    /**
     * @return int
     */
    public function getConfType()
    {
        return $this->confType;
    }

    /**
     * @param int $confType
     * @return ProtoDefConf
     */
    public function setConfType($confType = 0)
    {
        $this->confType = $confType;

        return $this;
    }
    /**
     * @return int
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param int $createTime
     * @return ProtoDefConf
     */
    public function setCreateTime($createTime = 0)
    {
        $this->createTime = $createTime;

        return $this;
    }
    /**
     * @return int
     */
    public function getUpdateTime()
    {
        return $this->updateTime;
    }

    /**
     * @param int $updateTime
     * @return ProtoDefConf
     */
    public function setUpdateTime($updateTime = 0)
    {
        $this->updateTime = $updateTime;

        return $this;
    }

}