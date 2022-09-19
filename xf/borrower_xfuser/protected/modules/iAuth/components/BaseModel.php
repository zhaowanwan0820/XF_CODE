<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/22
 * Time: 14:29
 */

namespace iauth\components;

class BaseModel extends \CActiveRecord
{
    /**
     * 获取错误码
     *  Yii ActiveRecord 的 rules 判断会将所有错误都记录到 errors 中，
     *  在这里只返回第一个错误。
     * @return mixed
     */
    public function getErrCode()
    {
        $firstError = current($this->getErrors());
        return $firstError[0];
    }

    /**
     * 自定义 电话 rules validator
     * @param $attr
     * @param $params
     */
    public function rulePhone($attr, $params)
    {
        if (!\FunctionUtil::IsMobile($this->$attr)) {
            $errMsg = isset($params['message']) ? $params['message']  : '电话号码不正确';
            $this->addError($attr, $errMsg);
        }
    }

    /**
     * 自定义 整型数组 rules validator
     * @param $attr
     * @param $params
     */
    public function ruleArrayOfInt($attr, $params)
    {
        $errMsg = isset($params['message']) ? $params['message'] : '数组参数错误';
        $allowEmpty = false;
        if (isset($params['allowEmpty']) && is_bool($params['allowEmpty'])) {
            $allowEmpty = $params['allowEmpty'];
        }

        if (!is_array($this->$attr)) {
            $this->addError($attr, $errMsg);
        } else {
            foreach ($this->$attr as $v) {
                if (!($allowEmpty && empty($v))) {
                    if (!ctype_digit(strval($v))) {
                        $this->addError($attr, $errMsg);
                    }
                }
            }
        }
    }

    /**
     * 转换数组中的 AR 对象为数组
     * @param array $activeRecords
     * @return array
     */
    public function toArray($activeRecords)
    {
        $res = [];
        foreach ($activeRecords as $activeRecord) {
            $res[] = $activeRecord->attributes;
        }

        return $res;
    }

}