<?php

/**
 * This is the model class for table "crm_admin".
 *
 * The followings are the available columns in table 'crm_admin':
 * @property integer $user_id
 * @property integer $user_type
 * @property integer $user_level
 * @property integer $user_label
 * @property integer $is_allot
 * @property integer $allot_time
 * @property integer $is_call
 * @property integer $is_mature
 * @property integer $admin_id
 * @property integer $addtime
 * @property integer $updatetime
 */
class CrmAdmin extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmAdmin the static model class
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
		return 'crm_admin';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id,admin_id, relation_id,type, p_id,ag_num1,ag_num2, remark,status,addtime,ucenter_uid', 'numerical', 'integerOnly'=>true),
			array('id,admin_id,admin_name, relation_id, name, type, p_id,ag_num1,ag_num2,  remark,status,addtime,ucenter_uid', 'safe', 'on'=>'search'),
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
			'admin_id' => '管理员ID',
			'admin_name' => '管理员账号',
			'relation_id' => '坐席ID',
			'name' => '管理员姓名',
			'type' => '管理员类型',
			'p_id' => '上级ID',
			'ag_num1'=> '呼出坐席编号',
			'ag_num2'=> '呼入坐席编号', 
			'remark' => '备注',
			'status' => '状态',
			'addtime' => '添加时间',
			'ucenter_uid' => '论坛ID',
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
		$criteria->compare('relation_id',$this->relation_id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('p_id',$this->p_id);
		$criteria->compare('admin_name',$this->admin_name);
		$criteria->compare('ag_num1',$this->ag_num1);
		$criteria->compare('ag_num2',$this->ag_num2);
		$criteria->compare('remark',$this->remark);
		$criteria->compare('is_mature',$this->is_mature);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('ucenter_uid',$this->ucenter_uid);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}