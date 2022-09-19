<?php

/**
 * This is the model class for table "crm_relation".
 *
 * The followings are the available columns in table 'crm_relation':
 * @property integer $id
 * @property integer $admin_id
 * @property string $admin_name
 * @property integer $ag_id
 * @property integer $ag_num
 * @property string $ag_name
 * @property string $ag_password
 * @property integer $ag_role
 * @property integer $user_role
 * @property integer $belong_queues
 * @property string $ag_phone
 * @property integer $role
 * @property integer $status
 * @property integer $addtime
 * @property integer $updatetime
 * @property integer $authtype
 */
class CrmRelation extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmRelation the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->crmdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'crm_relation';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('admin_name', 'required'),
			array('admin_id, ag_id, ag_num, ag_role, user_role, belong_queues, role, status, addtime, updatetime, authtype', 'numerical', 'integerOnly'=>true),
			array('admin_name, ag_name', 'length', 'max'=>100),
			array('ag_password', 'length', 'max'=>50),
			array('ag_phone', 'length', 'max'=>12),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, admin_id, admin_name, ag_id, ag_num, ag_name, ag_password, ag_role, user_role, belong_queues, ag_phone, role, status, addtime, updatetime, authtype', 'safe', 'on'=>'search'),
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
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'admin_id' => 'Admin',
			'admin_name' => 'Admin Name',
			'ag_id' => 'Ag',
			'ag_num' => 'Ag Num',
			'ag_name' => 'Ag Name',
			'ag_password' => 'Ag Password',
			'ag_role' => 'Ag Role',
			'user_role' => 'User Role',
			'belong_queues' => 'Belong Queues',
			'ag_phone' => 'Ag Phone',
			'role' => 'Role',
			'status' => 'Status',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
			'authtype' => 'Authtype',
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
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('admin_name',$this->admin_name,true);
		$criteria->compare('ag_id',$this->ag_id);
		$criteria->compare('ag_num',$this->ag_num);
		$criteria->compare('ag_name',$this->ag_name,true);
		$criteria->compare('ag_password',$this->ag_password,true);
		$criteria->compare('ag_role',$this->ag_role);
		$criteria->compare('user_role',$this->user_role);
		$criteria->compare('belong_queues',$this->belong_queues);
		$criteria->compare('ag_phone',$this->ag_phone,true);
		$criteria->compare('role',$this->role);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);
		$criteria->compare('authtype',$this->authtype);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}