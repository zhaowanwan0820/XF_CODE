<?php
namespace NCFGroup\Common\Extensions\Base;

use Assert\Assertion;
use NCFGroup\Common\Library\Date\XDateTime;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\ModelMessage;
use Phalcon\Db\RawValue;

class ModelBase extends \Phalcon\Mvc\Model
{
    public function initialize()
    {
        Model::setUp(array(
            'notNullValidations' => false,
        ));

        $this->useDynamicUpdate(true);
        $this->keepSnapshots(true);
    }

    public function getMessage()
    {
        return $this->getFirstMessage();
    }

    /**
     * 获得第一条错误消息
     *
     * @return string
     */
    public function getFirstMessage()
    {
        if (count($this->getMessages())) {
            return (string) current($this->getMessages());
        }

        return false;
    }

    public function getLastMessage()
    {
        if (count($this->getMessages())) {
            return (string) end($this->getMessages());
        }

        return false;
    }

    public function createBuilder()
    {
        return $this->getModelsManager()->createBuilder()->from(get_called_class());
    }

    public static function getInstance()
    {
        static $instance = null;

        if ($instance === null) {
            $class = get_called_class();
            $instance = new $class;
        }

        return $instance;
    }

    public function beforeValidationOnCreate()
    {
        $this->ctime = XDateTime::now();
        $this->mtime = $this->ctime;

        //foreach ($this->toArray() as $key => $value) {
        //    //if (property_exists($this, $key) && $this->{$key} === '') {
        //    //    // 将为空的值，转换为空值
        //    //    $this->{$key} = new RawValue("''");
        //    //}
        //}
    }

    public function afterFetch()
    {
        $this->ctime = XDateTime::valueOf($this->ctime);
        $this->mtime = XDateTime::valueOf($this->mtime);
    }

    public function beforeSave()
    {
        if(is_object($this->ctime)) {
            $this->ctime = $this->ctime->toString();
        }
        $this->mtime = XDateTime::now()->toString();

        return true;
    }

    /**
     * 使用Pageable分页查询
     * @param Pageable $pageable
     * @param array $condition
     * @return Page
     */
    public static function findByPageable(Pageable $pageable, array $condition = [])
    {
        $totalCnt = self::count($condition);
        $condition['limit'] = self::limit($pageable);
        $models = self::find($condition);
        $cleanModels = array();
        foreach($models as $model){
            $cleanModels[] = $model;
        }

        return new Page($pageable, $totalCnt, $cleanModels);
    }

    private static function limit(Pageable $pageable)
    {
        return array("offset" => ($pageable->getPageNo() - 1) * $pageable->getPageSize(),
            "number" => $pageable->getPageSize());
    }
}
