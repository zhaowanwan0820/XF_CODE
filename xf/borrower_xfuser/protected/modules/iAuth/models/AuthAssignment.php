<?php
/**
 * Created by PhpStorm.
 * User: Devon
 * Date: 2015/12/29
 * Time: 14:37
 */

namespace iauth\models;

use iauth\components\BaseModel;
use iauth\helpers\Meta;

class AuthAssignment extends BaseModel
{
    public $item_ids;

    public function rules()
    {
        return [
            [
                'user_id',
                'required',
                'on' => 'assignToUser',
                'message' => Meta::C_MISSING_ARGUMENT
            ],
            ['item_ids', 'filter', 'filter' => [$this, 'filterEmptyItem']],
            [
                'item_ids',
                'ruleArrayOfInt',
                'allowEmpty' => true,
                'on' => 'assignToUser',
                'message' => Meta::C_UNSAFE_ARGUMENT
            ],
            ['created_time', 'default', 'value' => time()],
            ['item_id', 'length', 'max' => 10],
            ['user_id', 'length', 'max' => 64]
        ];
    }

    /**
     * 根据用户 ID 获取所有权限数据
     * @param $userId
     * @param string $format 返回数据格式
     * @return array
     */
    public function getAuthList($userId, $format = 'full')
    {
        $user = User::model()->findByPk($userId);
        $list = $this->getAuthItemList($userId);var_dump($list);die;
        /* 合入管理员权限 */
        if ($user->username == \Yii::app()->iDbAuthManager->admin) {
            $adminAuthList = $this->getAdminAuthList();
            $list = $adminAuthList;
        }
        return $list;
    }

    /**
     * 过滤掉数组中空的元素
     * @param $arr
     * @return array
     */
    public function filterEmptyItem($arr)
    {
        $res = [];
        foreach ($arr as $item) {
            if (!empty($item)) {
                $res[] = $item;
            }
        }
        return $res;
    }

    /**
     * 分配权限给指定用户
     * @return bool|int
     * @throws \CDbException
     */
    public function assignToUser()
    {

        $this->deleteAll("user_id = :user_id", [":user_id" => $this->user_id]);

        if (count($this->item_ids)) {
            $insertValueList = [];
            $time = time();
            /** @var AuthItem $model */
            $model = AuthItem::model();
            $groupList = $model->getAuthGroupList(AuthItem::MAX_PAGE_SIZE);
            $groupIds = [];
            foreach ($groupList as $group) {
                $groupIds[] = $group['id'];
            }
            /* 去除权限组 ID */
            $item_ids = array_diff($this->item_ids, $groupIds);
            foreach ($item_ids as $item_id) {
                $insertValueList[] = "({$item_id}, {$this->user_id}, $time)";
            }
            $insertValues = implode(',', $insertValueList);
            $sql = "INSERT INTO `{$this->tableName()}` (`item_id`, `user_id`, `created_time`) VALUES {$insertValues}";
            $command = $this->getDbConnection()->createCommand($sql);
            return $command->execute();
        } else {
            return true;
        }
    }

    /**
     * 权限条件（item_id 或 user_id）获取列表
     * @param string $condition
     * @return array
     */
    public function getList($condition = '')
    {
        $criteria = new \CDbCriteria();
        $params = [];
        if (isset($condition['item_id'])) {
            $criteria->addCondition('item_id = :item_id');
            $params[':item_id'] = $condition['item_id'];
        }

        if (isset($condition['user_id'])) {
            $criteria->addCondition('user_id = :user_id');
            $params[':user_id'] = $condition['user_id'];
        }

        $criteria->params = $params;
        return $this->toArray($this->findAll($criteria));
    }

    /**
     * 获取指定权限的拥有者列表
     * @param int $itemId
     * @return array
     */
    public function getAuthUserList($itemId)
    {
        $fkTable = User::model()->tableName();

        $command = $this->getDbConnection()->createCommand();
        $command->select("realname, username, id");
        $command->from($this->tableName());
        $command->where("item_id = :item_id", [":item_id" => $itemId]);
        $command->join = "LEFT JOIN {$fkTable} ON {$fkTable}.id = {$this->tableName()}.user_id";
        return $command->queryAll();
    }


    /**
     * 获取直接拥有的权限，包括权限action 与 权限组 group
     * @param int $userId
     * @param string|array $fields
     * @return array|\CDbDataReader
     * @throws \CDbException
     */
    public function getDirectAuthList($userId)
    {
        $code = '';
        $sql = "SELECT b.id FROM itz_auth_assignment a LEFT JOIN itz_auth_item b ON a.item_id = b.id WHERE a.user_id = {$userId} AND b.type = 2;";
        $result = \Yii::app()->db->createCommand($sql)->queryRow();
        if(!empty($result)){
            $sql = "SELECT code FROM itz_auth_item WHERE id IN(SELECT child FROM itz_auth_item_child WHERE parent = {$result['id']}) AND type = 0";
            $code = \Yii::app()->db->createCommand($sql)->queryAll();
        }
        return $code;
    }

    /**
     * 获取直接拥有的权限，包括权限action 与 权限组 group
     * @param array $groupIdList
     * @param string|array $fields
     * @return array|\CDbDataReader
     * @throws \CDbException
     */
    public function getIndirectAuthList($groupIdList, $fields)
    {
        $authItemTable = AuthItem::model()->tableName();
        $authItemChildTable = AuthItemChild::model()->tableName();

        $cmd = $this->getDbConnection()->createCommand();
        $cmd->select($fields);
        $cmd->from($authItemChildTable);
        $cmd->where("status != " . AuthItem::STATUS_DISABLED);
        $ids = implode(',', $groupIdList);
        $cmd->andWhere("parent IN ({$ids})");
        $cmd->leftJoin($authItemTable, "{$authItemTable}.id = {$authItemChildTable}.child");
        return $cmd->queryAll();
    }

    /**
     * 获取权限管理员专属权限 (iauth modules 下全部操作)
     * @return array
     */
    public function getAdminAuthList()
    {
        $sql = "SELECT code FROM itz_auth_item WHERE  type = 0 AND status = 1";
        $code = \Yii::app()->db->createCommand($sql)->queryAll();
        return $code;
    }

    public function filterList($list)
    {
        foreach ($list as &$item) {
            $item['created_time'] = date('Y-m-d H:i', $item['created_time']);
        }

        return $list;
    }

    public function relations()
    {
        return [
            'item' => [self::BELONGS_TO, '\iauth\models\AuthItem', 'item_id'],
        ];
    }

    public function tableName()
    {
        return 'itz_auth_assignment';
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }
}
