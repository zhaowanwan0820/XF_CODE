<?php

/**
 * This is the model class for table "itz_user".
 *
 * The followings are the available columns in table 'itz_user':
 * @property integer $id
 * @property string $username
 * @property string $email
 * @property string $password
 * @property integer $is_claim
 * @property integer $last_reset_pwd
 * @property string $phone
 * @property integer $addtime
 * @property integer $updatetime
 * @property integer $operator_id
 * @property string $operator_ip
 * @property string $realname
 * @property integer $sector
 */
class ItzUser extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzUser the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
		    array('realname,phone,username,email,password', 'required','message'=>Yii::t('luben','{attribute}不能为空')),
            array('phone', 'numerical','message'=>Yii::t('luben','{attribute}号格式错误'),'integerOnly'=>true),
            array('phone', 'match','pattern'=>'/^1[0-9]{10}$/','message'=>Yii::t('luben','{attribute}号格式错误')),
            array('username, email, password', 'length', 'max'=>50),
            array('phone', 'length', 'max'=>11,'min'=>11,'tooLong'=>'手机号格式错误','tooShort'=>'手机号格式错误'),
//            array('sector', 'numerical','integerOnly'=>true,'min'=>1,'tooSmall'=>'请选择所属部门'),
            array('email', 'email', 'message'=>'邮箱格式错误', 'pattern'=>'/^([a-zA-Z0-9_\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})+$/'),
            array('email', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'邮箱【{value}】已存在'),
            array('username', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'用户名【{value}】已存在'),
            array('phone', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'手机号【{value}】已存在'),
            array('phone', 'unique','caseSensitive'=>true,'className'=>'ItzUser','message'=>'手机号【{value}】已存在','on'=>'update', 'criteria' => array('condition' => "`id` != '{$this->id}'")),
          
            
			array('last_login_time,id, username, email, password, is_claim, last_reset_pwd, phone, addtime, updatetime, operator_id, operator_ip, realname, sector', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		      'ItzUserInfo'=>array(self::BELONGS_TO, 'ItzUser', 'operator_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'is_claim' => 'Is Claim',
			'last_reset_pwd' => 'Last Reset Pwd',
			'addtime' => '添加时间',
			'updatetime' => '修改时间',
			'operator_id' => '操作者用户ID',
			'operator_ip' => '操作者IP',
			'id' => 'ID',
            'username' => '用户名',
            'realname' => '姓名',
            'email' => '邮箱',
            'password' => '密码',
            'phone' => '手机',
            'sector' => '部门',
            'last_login_time'=>'最后登录时间'
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('username',$this->username);
		$criteria->compare('email',$this->email);
		$criteria->compare('password',$this->password);
		$criteria->compare('is_claim',$this->is_claim);
		$criteria->compare('last_reset_pwd',$this->last_reset_pwd);
		$criteria->compare('phone',$this->phone);
		$criteria->compare('updatetime',$this->updatetime);
		$criteria->compare('operator_id',$this->operator_id);
		$criteria->compare('operator_ip',$this->operator_ip);
		$criteria->compare('realname',$this->realname);
		$criteria->compare('sector',$this->sector);
        if(!empty($this->addtime))
             $criteria->addBetweenCondition('addtime',strtotime($this->addtime),(strtotime($this->addtime)+86399));
        else
            $criteria->compare('addtime',$this->addtime);
        
        if(!empty($this->last_login_time))
             $criteria->addBetweenCondition('last_login_time',strtotime($this->last_login_time),(strtotime($this->last_login_time)+86399));
        else
            $criteria->compare('last_login_time',$this->last_login_time);

		return new CActiveDataProvider($this, array(
		    'sort'=>array(
                'defaultOrder'=>'id DESC', 
            ),
			'criteria'=>$criteria,
		));
	}
    /**
     * 获取用户列表
     * @param int $pageSize//显示行数
     * @param int $page//当前页
     * @return mixed
     */
    public function getList($page,$pageSize = 10)
    {
        $userData = array();//返回数据
        $userInfo = \Yii::app()->db->createCommand()
            ->select("id,username,email,phone,addtime,realname,status")
            ->from('itz_user')
            ->limit($pageSize)
            ->offset(($page-1) * $pageSize)
            ->order('id desc')
            ->queryAll();
        if(!empty($userInfo)){
            $userData = $this->filterList($userInfo);
        }
        return $userData;
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
            $listArr[] = array(
                "id" => $item['id'],
                "addtime" => $item['addtime'] ? date('Y-m-d H:i:s', $item['addtime']) : '',
                "status_info" =>  $this->getStatusInfo($item['status']),
                "username" =>  $item['username'],
                "phone" =>  $item['phone'],
                "email" =>  $item['email'],
                "rolename" =>  !empty($roleName) ? $roleName : "暂无角色",
            );
        }
        return $listArr;
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
        if(empty($assignment)) return array();
        $itemIds = array_column($assignment,"item_id");
        //获取权限表中有效角色类型信息
        return $this->getItemDate($itemIds);
    }
    /**
     * 获取权限表中角色名称 只能一个用户对应一个角色
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
    /**
     * 更新用户状态
     *
     * @param $pkId
     * @param int $status
     * @return mixed
     */
    public function updateStatus($pkId, $status)
    {
        $saveModel = ItzUser::model();
        $saveModel->id = $pkId;
        $saveModel->status = $status;
        if ($saveModel->save(false)) {
            $this->echoJson("", 0, "状态更新成功");
        } else {
            $this->echoJson("", 1, "状态更新失败");
        }
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
}