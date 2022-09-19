<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/28
 * Time: 16:14
 */

namespace iauth\models;

use iauth\components\BaseModel;
use iauth\helpers\Meta;

class AuthItemChild extends BaseModel
{

    public function rules()
    {
        return [
            ['parent, child', 'exist', 'className' => 'iauth\models\AuthItem',
                'attributeName' => 'id', 'message' => Meta::C_AUTH_ITEM_NOT_FOUND],
            ['parent, child', 'length', 'max'=>10],
        ];
    }

    public function relations()
    {
        return array(
            'parent0' => array(self::BELONGS_TO, 'iauth\models\AuthItem', 'parent'),
            'child0' => array(self::BELONGS_TO, 'iauth\models\AuthItem', 'child'),
        );
    }

    public function tableName()
    {
        return 'itz_auth_item_child';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
    /**
     * 批量插入
     * @param  String $table         表名
     * @param  Array  $array_columns 插入数据
     * @return Bool                  失败返回false，成功返回true
     */
    public function insertSeveral($table, $array_columns)
    {
        $sql = '';
        $params = array();
        $i = 0;
        foreach ($array_columns as $columns) {
            $names = array();
            $placeholders = array();
            foreach ($columns as $name => $value) {
                if (!$i) {
                    $names[] = $this->_connection->quoteColumnName($name);
                }
                if ($value instanceof CDbExpression) {
                    $placeholders[] = $value->expression;
                    foreach ($value->params as $n => $v)
                        $params[$n] = $v;
                } else {
                    $placeholders[] = ':' . $name . $i;
                    $params[':' . $name . $i] = $value;
                }
            }
            if (!$i) {
                $sql = 'INSERT INTO ' . $this->_connection->quoteTableName($table)
                    . ' (' . implode(', ', $names) . ') VALUES ('
                    . implode(', ', $placeholders) . ')';
            } else {
                $sql .= ',(' . implode(', ', $placeholders) . ')';
            }
            $i++;
        }
        var_export($sql);die;
        return $this->setText($sql)->execute($params);
    }

}