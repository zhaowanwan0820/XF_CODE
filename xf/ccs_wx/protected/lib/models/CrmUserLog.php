<?php

/**
 * This is the model class for table "crm_user_log".
 *
 * The followings are the available columns in table 'crm_user_log':
 * @property integer $id
 * @property integer $type
 * @property integer $status
 * @property integer $remark
 * @property integer $addtime
 * @property integer $updatetime
 */
class CrmUserLog extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmUserLog the static model class
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
		return 'crm_user_log';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id,admin_id,user_id,addtime', 'numerical', 'integerOnly'=>true),
			array('id,log_type,admin_id,user_id,addtime,remark', 'safe', 'on'=>'search'),
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
			'log_type' => '类型',
			'admin_id' => '客维ID',
			'user_id' => '用户ID',
			'remark' => '备注',
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
		$criteria->compare('log_type',$this->log_type);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}