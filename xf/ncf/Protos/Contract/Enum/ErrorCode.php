<?php

namespace NCFGroup\Protos\Contract\Enum;

use NCFGroup\Common\Extensions\Base\AbstractEnum;

class ErrorCode extends AbstractEnum {

    /** 系统相关 */
    const SUCCESS = '0';
    const FAIL = '1';
    const DB_UNKNOW_ERROR = '1002';

    /** 应用服务相关 */
    const TASK_ID_DUPLICATE = '8000';
    const CATEGORY_IS_DELETED = '8001';
    const CATEGORY_IS_HISTORY = '8002';
    const NO_RECORD_TO_UPDATE = '8003';
    const CATEGORY_IS_NULL = '8004';
    const TPL_IS_NULL = '8005';
    const TPL_SAVE_FAIL = '8006';
    const TPL_UPDATE_FAIL = '8007';
    const TPL_ID_ERROR = '8008';
    const TPL_INSERT_FAIL = '8009';
    const CATEGORY_UPDATE_FAIL = "8010";
    const PROJECT_CONTENT_SAVE_FAIL = "8011";
    const PROJECT_CONTENT_NULL = "8012";
    const CONTRACT_CATEGORY_DT_NULL = '8013';
    const CONTRACT_BEFORE_BORROW_NOT_EXIST = '8014';
    const INSERT_FAIL = '8015';
    const APPROVER_NUMBER_EXIST = '8016';
    const BEFORE_CONTRACT_NOT_SIGN = '8017';

    public static $errMsg = array(
        self::SUCCESS => 'success',
        self::FAIL => 'fail',
        self::DB_UNKNOW_ERROR => 'Database unknow error',
        self::TASK_ID_DUPLICATE => 'task id duplicate',
        self::CATEGORY_IS_DELETED => '分类已被删除',
        self::CATEGORY_IS_HISTORY => '分类为历史使用',
        self::NO_RECORD_TO_UPDATE => '未找到要更新的记录',
        self::CATEGORY_IS_NULL => '分类不存在',
        self::CATEGORY_UPDATE_FAIL => '分类更新失败',
        self::TPL_IS_NULL => '未找到模板信息',
        self::TPL_SAVE_FAIL => '模板保存失败',
        self::TPL_UPDATE_FAIL => '模板更新失败',
        self::TPL_ID_ERROR => '模板ID不正确',
        self::TPL_INSERT_FAIL => '模板插入失败',
        self::PROJECT_CONTENT_SAVE_FAIL => '插入项目描述错误!',
        self::PROJECT_CONTENT_NULL => '未查询到相关记录!',
        self::CONTRACT_CATEGORY_DT_NULL => '未查询到dealId对应的合同分类记录',
        self::CONTRACT_BEFORE_BORROW_NOT_EXIST => '前置合同记录不存在',
        self::INSERT_FAIL => '插入失败',
        self::APPROVER_NUMBER_EXIST => '放款审批单号已存在',
        self::BEFORE_CONTRACT_NOT_SIGN => '合同未签署',
    );

}
