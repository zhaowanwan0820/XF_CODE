<?php

namespace iauth\models;

use iauth\components\BaseModel;
use CJSON;
use iauth\helpers\Meta;
use iauth\helpers\Number;

class User extends BaseModel
{
    const DEFAULT_PAGE_SIZE = 10;
    const MAX_PAGE_SIZE = 500;

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 2;

    const MIN_PASS_LEN = 10;

    public function beforeValidate()
    {
        /*
         * 因未知原因，在 Insert 时 rules()中使用如下代码，无法获取 $this->username 的值，故额外添加自动完成代码，
         */
        if (empty($this->email)) {
            $this->email = $this->username . self::EMAIL_DOMAIN;
        }
        return parent::beforeValidate();
    }

    public function rules()
    {
        return array(
            array('realname,phone,username,email,password', 'required','message'=>\Yii::t('luben','{attribute}不能为空')),
            array('phone', 'numerical','message'=>\Yii::t('luben','{attribute}号格式错误'),'integerOnly'=>true),
            array('phone', 'match','pattern'=>'/^1[0-9]{10}$/','message'=>\Yii::t('luben','{attribute}号格式错误')),
            array('username, email, password', 'length', 'max'=>50),
            array('phone', 'length', 'max'=>11,'min'=>11,'tooLong'=>'手机号格式错误','tooShort'=>'手机号格式错误'),
//            array('sector', 'numerical','integerOnly'=>true,'min'=>1,'tooSmall'=>'请选择所属部门'),
            array('email', 'email', 'message'=>'邮箱格式错误', 'pattern'=>'/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/'),
            array('email', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'邮箱【{value}】已存在'),
            array('username', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'用户名【{value}】已存在'),
            array('phone', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'手机号【{value}】已存在'),
            array('phone', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'手机号【{value}】已存在','on'=>'update', 'criteria' => array('condition' => "`id` != '{$this->id}'")),


            array('company_id,last_login_time,id, username, email, password, is_claim, last_reset_pwd, phone, addtime, updatetime, operator_id, operator_ip, realname, sector', 'safe', 'on'=>'search'),
        );
    }
    /**
     * 获取后台用户列表
     * @param int $pageSize//显示行数
     * @param int $page//当前页
     * @return mixed
     */
    public function getList($page,$pageSize = 10,$where = '')
    {
        $userData = array();//返回数据
        $count = User::model()->count($where);//获取总数
        if($count > 0){
            $userInfo = \Yii::app()->db->createCommand()
                ->select("id,username,company_id,email,phone,addtime,realname,status,user_type")
                ->from('itz_user')
                ->where($where)
                ->limit($pageSize)
                ->offset(($page-1) * $pageSize)
                ->order('id desc')
                ->queryAll();
            if(!empty($userInfo)){
                $userData = $this->filterList($userInfo);
            }
        }
        return array("countNum" => $count,"userData" => $userData);
    }

    /**
     * 列表过滤
     * @param $list
     * @return mixed
     */
    public function filterList($list)
    {
        //当前用户所属角色
        foreach($list as $key => $item){
            $roleName = $this->getRoleName($item['id']);
            $company_name = $this->getCompanyName($item['company_id']);
            $listArr[] = array(
                "id" => $item['id'],
                "addtime" => $item['addtime'] ? date('Y-m-d H:i', $item['addtime']) : '',
                "status_info" =>  $this->getStatusInfo($item['status']),
                "username" =>  $item['username'],
                "realname" =>  $item['realname'],
                "user_type" =>  $item['user_type'],
                "phone" =>  $item['phone'],
                "email" =>  $item['email'],
                "rolename" =>  !empty($roleName) ? $roleName : "暂无角色",
                "company_name" =>  !empty($company_name) ? $company_name : "-",
            );
        }
        return $listArr;
    }

    public function getCompanyName($company_id)
    {
        $itemData = \Yii::app()->cmsdb->createCommand()
            ->select('name')
            ->from('firstp2p_cs_company')
            ->where("id = $company_id")
            ->queryRow();
        return $itemData['name'];
    }


    /**
     * 获取用户角色名
     */
    public function getRoleName($user_id = '')
    {
        //权限分配表查询当前用户所有权限
        $assignment = \Yii::app()->db->createCommand()
            ->select('item_id,user_id')
            ->from('itz_auth_assignment')
            ->where("user_id = {$user_id}")
            ->queryAll();
        if(!empty($assignment)){
            $itemIds = \ArrayUtil::array_column($assignment,"item_id");
            //获取权限表中有效角色类型信息
            return $this->getItemDate($itemIds,2);
        }
        return array();
    }
    /**
     * 获取权限表中相关信息
     * @param $item_ids  授权IDS 数组 [1,2,3,4]
     * @param $type 0：授权类型 1：角色类型
     * @param $status 1：正常； 2：停用
     */
    public function getItemDate($item_ids,$type = 1,$status = 1)
    {
        $itemData = \Yii::app()->db->createCommand()
            ->select('name')
            ->from('itz_auth_item')
            ->where(['in','id',$item_ids])
            ->andWhere("type = {$type} and status = {$status}")
            ->queryRow();
        return $itemData['name'];
    }
    public function getStatusInfo($status)
    {
        switch ($status) {
            case self::STATUS_DISABLED:
                $info = '已停用';
                break;
            case self::STATUS_ENABLED:
                $info = '已启用';
                break;
            default:
                $info = '未知';
        }
        return $info;
    }

    /**
     * 更新用户状态
     *
     * @param $pkId
     * @param int $status
     * @return mixed
     */
    /**
     * 更新用户状态
     * @param $id
     * @param $type
     */
    public function updateStatus($pkId, $status)
    {
        $saveModel = User::model();
        $saveModel->id = $pkId;
        $saveModel->status = $status;
        return $saveModel->save(false);
    }

    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'itz_user';
    }
}
