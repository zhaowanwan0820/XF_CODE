<?php

/**
 * This is the model class for table "crm_report".
 *
 * The followings are the available columns in table 'crm_report':
 * @property integer $id
 * @property integer $type
 * @property integer $status
 * @property integer $remark
 * @property integer $addtime
 * @property integer $updatetime
 */
class CrmReport extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CrmReport the static model class
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
		return 'crm_report';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id, type,status,addtime', 'numerical', 'integerOnly'=>true),
			array('id, type,remark,status,addtime,updatetime', 'safe', 'on'=>'search'),
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
			'type' => '类型',
			'remark' => '备注',
			'status' => '状态',
			'updatetime' => '最后分配时间',
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
		$criteria->compare('type',$this->type);
		$criteria->compare('remark',$this->remark);
		$criteria->compare('updatetime',$this->updatetime);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}