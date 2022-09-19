<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/25
 * Time: 11:53
 */

namespace iauth\models;

use iauth\components\BaseModel;
use iauth\helpers\Meta;

class AuthItem extends BaseModel
{
    const DEFAULT_PAGE_SIZE = 10;
    const MAX_PAGE_SIZE = 500;

    const TYPE_ACTION = 0;
    const TYPE_GROUP = 1;
    const TYPE_ROLE = 2;

    /**
     * 正常状态
     */
    const STATUS_ENABLED = 1;
    /**
     * 停用状态，不可分配，不可执行
     */
    const STATUS_DISABLED = 2;

    private $systemList = [
        1 => '用户系统',
        2 => '数据系统',
        3 => '管理后台',
        4 => '市场系统',
        5 => '消息系统',
        6 => '风控系统',
        7 => '电销CRM'
    ];

    public $countRows;

    public $parent;

    public function rules()
    {
        return [
            ['dual_factor', 'default', 'value' => 0],
            ['created_time', 'default', 'setOnEmpty' => false, 'value' => time(), 'on' => 'insert'],
            ['updated_time', 'default', 'setOnEmpty' => false, 'value' => time()],

            ['code, name, system', 'required', 'message' => Meta::C_MISSING_ARGUMENT],
            ['code', 'length', 'min' => '3', 'tooShort' => Meta::C_AUTH_ITEM_CODE_TOO_SHORT],
            ['dual_factor', 'boolean', 'message' => Meta::C_UNSAFE_ARGUMENT],
            [
                'status', 'in',
                'range' => [
                    self::STATUS_ENABLED,
                    self::STATUS_DISABLED,
                    ],
                'on' => 'updateStatus',
                'message' => Meta::C_UNSAFE_ARGUMENT
            ],
            [
                'parent', 'exist',
                'except' => 'updateStatus',
                'attributeName' => 'id',
                'message' => Meta::C_AUT_ITEM_GROUP_NOT_FOUND
            ],
            [
                'system', 'in',
                'range' => array_keys($this->getSystemList()),
                'message' => Meta::C_SYSTEM_NOT_FOUND
            ],
            [
                'code', 'unique',
                'message' => Meta::C_EXISTS_AUTH_ITEM
            ],
            ['code, name, desc, system, bizrule, data, dual_factor, author', 'safe']
        ];
    }

    /**
     * 获取后台系统列表
     * @return array
     */
    public function getSystemList()
    {
        return $this->systemList;
    }

    /**
     * 获取一条记录
     * @param array $condition
     * @return mixed
     */
    public function getOneAuthItem($condition = [])
    {
       return $this->getAuthItemList(1, $condition, true)[0];
    }

    public function getAuthGroupList($pageSize = null, $condition = [], $withChild = false)
    {
        $criteria = new \CDbCriteria();
        $params = [];

        $criteria->addCondition('type = ' . self::TYPE_GROUP);
        if (isset($condition['system'])) {
            $criteria->addCondition('system = :system');
            $params[':system'] = $condition['system'];
        }

        // 按需分页
        if ($pageSize !== null) {
            $pageSize = $pageSize > self::MAX_PAGE_SIZE ? self::MAX_PAGE_SIZE : $pageSize;
            $count = $this->countByCriteria($criteria);
            $this->countRows = $count;
            $page = new \CPagination($count);
            $page->pageSize = $pageSize;
            $page->applyLimit($criteria);
        }

        /** @var \CDbCommand $command */
        $command = $this->dbConnection->createCommand();
        $command->select('id, code, name, created_time, system');
        $command->where($criteria->condition, $criteria->params);
        $command->from($this->tableName());
        $command->limit($criteria->limit);
        $command->offset($criteria->offset);
        $list = $command->queryAll();

        if ($withChild) {
            foreach ($list as &$item) {
                $item['actionList'] = $this->getAuthGroupChildList($item['id']);
            }
        }

        return $list;
    }
    /**
     * 获取角色中所有itemIds与提交的进行对比
     */
    public function getDiff(){

    }
    /**
     * 获取指定权限组下的子权限列表
     * @param int $groupId
     * @return array
     */
    public function getAuthGroupChildList($groupId)
    {
        $childTab = AuthItemChild::model()->tableName();
        $sql = "SELECT `id`, `code`, `name`, `status`, `dual_factor`, `desc`
                FROM {$childTab}
                INNER JOIN {$this->tableName()} `ai` ON `ai`.`id` = `child`
                WHERE `parent` = :parent
                AND `status` = " . self::STATUS_ENABLED;
        $cmd = $this->dbConnection->createCommand($sql);

        $cmd->bindParam(':parent', $groupId);
        return $cmd->queryAll();
    }

    /**
     * 获取权限项目（action）列表
     *    表名使用了别名 auth_item as ai, auth_item_child as aic
     * @param int|null $pageSize
     * @param array $condition
     * @param bool $withParent
     * @return array
     */
    public function getAuthItemList($pageSize = null, $condition = [], $withParent = false)
    {
        $criteria = new \CDbCriteria();
        $params = [];

        $criteria->addCondition("ai.type = " . self::TYPE_ACTION);

        // 只允许以下 condition
        $keys = ['code', 'type', 'status', 'id'];
        foreach ($condition as $key => $value) {
            if (in_array($key, $keys)) {
                $criteria->addCondition("ai.{$key} = :{$key}");
                $params[":{$key}"] = $value;
            }
        }
        $criteria->params = $params;

        // 按需分页
        if ($pageSize !== null) {
            $pageSize = $pageSize > self::MAX_PAGE_SIZE ? self::MAX_PAGE_SIZE : $pageSize;
            $count = $this->countByCriteria($criteria);
            $this->countRows = $count;
            $page = new \CPagination($count);
            $page->pageSize = $pageSize;
            $page->applyLimit($criteria);
        }


        $cmd = $this->dbConnection->createCommand();
        if ($withParent) {
            $fkTable = AuthItemChild::model()->tableName();
            $cmd->select = "ai.*,
                            aic.*,
                            ai2.name as parent_name,
                            ai2.code as parent_code";
            $cmd->join = "
                LEFT JOIN {$fkTable} as aic ON aic.child = ai.id
                LEFT JOIN {$this->tableName()} as ai2 ON aic.parent= ai2.id
            ";
        }
        $cmd->where = $criteria->condition;
        $cmd->params = $criteria->params;
        $cmd->order('id DESC');
        $cmd->setLimit($criteria->limit);
        $cmd->setOffset($criteria->offset);
        $cmd->from("{$this->tableName()} ai");
        $list = $cmd->queryAll();

        return $list;
    }

    public function countByCriteria($criteria)
    {
        $cmd = $this->dbConnection->createCommand();
        $cmd->select("count(*)");
        $cmd->from("{$this->tableName()} ai");
        $cmd->where = $criteria->condition;
        $cmd->params = $criteria->params;
        return $cmd->queryScalar();
    }

    /**
     * 获取拥有指定权限的用户 ID 列表
     * @param $id
     * @return array
     */
    public function getActionUserList($id)
    {
        /* @var $authAssignmentModel \iauth\models\AuthAssignment */
        $authAssignmentModel = AuthAssignment::model();
        return $authAssignmentModel->getAuthUserList($id);
    }

    /**
     * 按照归属系统分组权限组权限列表
     * @param $list
     * @return array
     */
    public function groupBySystem($list)
    {
        $res = [];
        foreach ($list as $item) {
            $res[$item['system']]['system'] = $item['system'];
            $res[$item['system']]['system_info'] = $this->systemList[$item['system']];
            $res[$item['system']]['groupList'][] = $item;
        }

        return array_values($res);
    }

    /**
     * auth item 通用过滤
     * @param &array $list
     */
    public function filterList(&$list)
    {
        foreach ($list as &$item) {
            $item['system_info'] = $this->systemList[$item['system']];
            $item['created_time'] = $item['created_time'] ? date('Y-m-d H:i', $item['created_time']) : 0;
            $item['updated_time'] = $item['updated_time'] ? date('Y-m-d H:i', $item['updated_time']) : 0;
            unset($item['data']);
            unset($item['bizrule']);
        }
    }

    /**
     * action 列表过滤
     * @param $list
     * @param string $format
     * @return array
     */
    public function filterActionList($list, $format = 'full')
    {
        $res = [];
        if ($format == 'only_id') {
            foreach ($list as $item) {
                $res[] = $item['id'];
            }
        } elseif ($format == 'only_code') {
            foreach ($list as $item) {
                $res[] = $item['code'];
            }
        } else {
            foreach ($list as $item) {
                $item['dual_factor_info'] = $item['dual_factor'] ? '是' : '否';
                $item['status_info'] = $this->getStatusInfo($item['status']);
                $item['parent_info'] = $item['parent_name'];
                $item['parent_id'] = $item['parent'];
                $res[] = $item;
            }
            $this->filterList($res);
        }

        return $res;
    }

    /**
     * group 列表过滤
     * @param $list
     * @param $format
     * @return array
     */
    public function filterGroupList($list, $format = 'full')
    {
        $res = [];
        if ($format == 'small') {
            foreach ($list as $item) {
                $res[$item['id']] = $item['name'];
            }
        } else {
            foreach ($list as $item) {
                // 未分组（ID=1） 如果 actionList 为空则跳过不返回
                if (
                    isset($item['actionList']) &&
                    empty($item['actionList']) &&
                    $item['id'] == 1
                ) {
                    continue;
                }
                unset($item['dual_factor']);
                unset($item['type']);
                unset($item['desc']);
                unset($item['status']);
                unset($item['author']);
                $res[] = $item;
            }
            $this->filterList($res);
        }

        return $res;
    }

    /**
     * 更新权限状态
     * @param int $status
     * @return bool
     */
    public function updateStatus($status = self::STATUS_ENABLED)
    {
        $this->scenario = 'updateStatus';
        $this->status = $status;
        return $this->save();
    }

    public function getStatusInfo($status)
    {
        switch ($status) {
            case self::STATUS_ENABLED:
                $info = '正常';
                break;
            case self::STATUS_DISABLED:
                $info = '停用';
                break;
            default:
                $info = '未知';
                break;
        }

        return $info;
    }

    public function relations()
    {
        return [
            'parent' => [self::HAS_ONE, 'iauth\models\AuthItemChild', 'child']
        ];
    }

    public function tableName()
    {
        return 'itz_auth_item';
    }

    public static function model($className = __CLASS__)
    {
       return parent::model($className);
    }
}
