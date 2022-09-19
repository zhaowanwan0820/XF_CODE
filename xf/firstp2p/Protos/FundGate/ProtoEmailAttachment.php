<?php
namespace NCFGroup\Protos\FundGate;

use NCFGroup\Common\Extensions\Base\ProtoBufferBase;

/**
 * 邮件附件
 *
 * 由代码生成器生成, 不可人为修改
 * @author jingxu
 */
class ProtoEmailAttachment extends ProtoBufferBase
{
    /**
     * 文件名
     *
     * @var string
     * @required
     */
    private $fileName;

    /**
     * 文件内容
     *
     * @var string
     * @required
     */
    private $content;

    /**
     * 文件类型
     *
     * @var string
     * @optional
     */
    private $type = 'text/plain';

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $fileName
     * @return ProtoEmailAttachment
     */
    public function setFileName($fileName)
    {
        \Assert\Assertion::string($fileName);

        $this->fileName = $fileName;

        return $this;
    }
    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param string $content
     * @return ProtoEmailAttachment
     */
    public function setContent($content)
    {
        \Assert\Assertion::string($content);

        $this->content = $content;

        return $this;
    }
    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return ProtoEmailAttachment
     */
    public function setType($type = 'text/plain')
    {
        $this->type = $type;

        return $this;
    }

}