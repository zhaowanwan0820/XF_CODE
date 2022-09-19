<?php

/**
 * This is the model class for table "crm_user".
 *
 * The followings are the available columns in table 'crm_user':
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
class CrmUser extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmUser the static model class
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
		return 'crm_user';
	}

	public function primaryKey() {
        return 'user_id';//自定义主键
	}
	
	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, user_type, user_level, is_allot, allot_time, is_call,is_mature,admin_id,addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('user_id, user_type, user_level,user_label, is_allot, allot_time, is_call,is_mature,admin_id,addtime, updatetime', 'safe', 'on'=>'search'),
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
			'user_id' => '用户 ID',
			'user_type' => '用户状态',
			'user_level' => '用户等级',
			'user_label' => '用户标签',
			'is_allot' => '是否分配',
			'allot_time' => '分配时间',
			'is_call' => '是否联系',
			'is_mature' => '是否成熟',
			'admin_id' => '客维ID',
			'addtime' => '添加时间',
			'updatetime' => '更新时间'
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

		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('user_type',$this->user_type);
		$criteria->compare('user_level',$this->user_level);
		$criteria->compare('user_label',$this->user_label);
		$criteria->compare('is_allot',$this->is_allot);
		$criteria->compare('allot_time',$this->allot_time);
		$criteria->compare('is_call',$this->is_call);
		$criteria->compare('is_mature',$this->is_mature);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('updatetime',$this->updatetime);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}