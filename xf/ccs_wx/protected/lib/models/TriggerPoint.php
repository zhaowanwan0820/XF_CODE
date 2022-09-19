<?php

/**
 * This is the model class for table "itz_trigger_point".
 *
 * The followings are the available columns in table 'itz_trigger_point':
 * @property integer $id
 * @property string $name
 * @property string $code
 * @property string $type
 * @property integer $status
 * @property string $owner
 * @property integer $createtime
 * @property integer $lasttime
 * @property string $desc
 * @property string $nid
 * @property integer $noticeStatus
 */
class TriggerPoint extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return TriggerPoint the static model class
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
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_trigger_point';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status, createtime, lasttime, noticeStatus', 'numerical', 'integerOnly'=>true),
			array('name, code', 'length', 'max'=>100),
			array('type', 'length', 'max'=>10),
			array('owner', 'length', 'max'=>30),
			array('desc', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, code, type, status, owner, createtime, lasttime, desc, nid, noticeStatus', 'safe', 'on'=>'search'),
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
			'name' => 'Name',
			'code' => 'Code',
			'type' => 'Type',
			'status' => 'Status',
			'owner' => 'Owner',
			'createtime' => 'Createtime',
			'lasttime' => 'Lasttime',
			'desc' => 'Desc',
			'nid' => 'Nid',
			'noticeStatus' => 'Notice Status',
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('owner',$this->owner,true);
		$criteria->compare('createtime',$this->createtime);
		$criteria->compare('lasttime',$this->lasttime);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('nid',$this->nid,true);
		$criteria->compare('noticeStatus',$this->noticeStatus);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}