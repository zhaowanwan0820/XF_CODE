<?php

/**
 * This is the model class for table "crm_admin_log".
 *
 * The followings are the available columns in table 'crm_admin_log':
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
class CrmAdminLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmAdminLog the static model class
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
		return 'crm_admin_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		//
		return array(
			array('id,admin_id, to,type, from, operate_id,addtime', 'numerical', 'integerOnly'=>true),
			array('id,admin_id, to,type, from, operate_id,addtime,remark', 'safe', 'on'=>'search'),
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
			'type' => '操作类型',
			'from' => '管理员姓名',
			'to' => '管理员类型',
			'remark' => '备注',
			'addtime' => '添加时间',
			'operate_id' => '操作者',
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
		$criteria->compare('type',$this->type);
		$criteria->compare('from',$this->from);
		$criteria->compare('to',$this->to);
		$criteria->compare('operate_id',$this->operate_id);
		$criteria->compare('remark',$this->remark);
		$criteria->compare('addtime',$this->addtime);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}