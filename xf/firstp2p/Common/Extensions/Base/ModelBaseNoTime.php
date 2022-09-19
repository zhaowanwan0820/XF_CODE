<?php
namespace NCFGroup\Common\Extensions\Base;

use Assert\Assertion;
use NCFGroup\Common\Library\Date\XDateTime;
use Phalcon\Mvc\Model;
use Phalcon\Mvc\ModelMessage;
use Phalcon\Db\RawValue;

class ModelBaseNoTime extends \Phalcon\Mvc\Model
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

    /**
     * 使用Pageable分页查询
     * @param Pageable $pageable
     * @param array $condition
     * @return Page
     */
    public static function findByPageable(Pageable $pageable, array $condition = [])
    {
        Assertion::notNull($pageable);
        $rowCount = self::count($condition);
        $condition['limit'] = self::limit($pageable);
        $userFunds = self::find($condition);
        $_tempArr = array();
        foreach($userFunds as $fund){
            $_tempArr[] = $fund;
        }
        return new Page($pageable, $rowCount, $_tempArr);
    }

    private static function limit(Pageable $pageable)
    {
        return array("offset" => ($pageable->getPageNo() - 1) * $pageable->getPageSize(),
            "number" => $pageable->getPageSize());
    }
}
