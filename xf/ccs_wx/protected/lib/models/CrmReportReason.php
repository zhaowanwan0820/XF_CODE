<?php

/**
 * This is the model class for table "crm_report_reason".
 *
 * The followings are the available columns in table 'crm_report_reason':
 * @property integer $id
 * @property integer $type
 * @property integer $status
 * @property integer $remark
 * @property integer $addtime
 * @property integer $updatetime
 */
class CrmReportReason extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmReportReason the static model class
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
		return 'crm_report_reason';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, addtime,datetime', 'numerical', 'integerOnly'=>true),
			array('id,user_id,realname,bank,account,addtime,remark,describe,datetime', 'safe', 'on'=>'search'),
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
			'id' => '编号',
			'user_id' => '用户ID',
			'realname' => '用户实名',
			'bank' => '银行',
			'account' => '金额',
			'remark' => '标记',
			'describe' => '描述',
			'datetime' => '发生时间',
			'addtime' => '添加时间',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('realname',$this->realname);
		$criteria->compare('bank',$this->bank);
		$criteria->compare('account',$this->account);
		$criteria->compare('remark',$this->remark);
		$criteria->compare('describe',$this->describe);
		$criteria->compare('datetime',$this->datetime);
		$criteria->compare('addtime',$this->addtime);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}