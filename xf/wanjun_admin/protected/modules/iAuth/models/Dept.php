<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/28
 * Time: 21:53
 */

namespace iauth\models;

class Dept extends \CFormModel
{
    public $list = [
        '1' => '客服部',
        '2' => '运营部',
        '3' => '市场部',
        '4' => '业务发展部',
        '5' => '项目支持部',
        '6' => '风控部',
        '7' => '财务部',
        '8' => '产品部',
        '9' => '技术部',
        '10' => '公关部',
        '11' => '行政部',
        '12' => '法务部',
        '13' => '资产管理部',
        '14' => '董事会',
    ];

    public function getList()
    {
        return $this->list;
    }

    public function getName($id)
    {
        return isset($this->list[$id]) ? $this->list[$id] : '未知';
    }

    public function getCount()
    {
        return count($this->list);
    }

}