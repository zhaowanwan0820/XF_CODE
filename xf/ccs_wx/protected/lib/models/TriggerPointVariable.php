<?php

/**
 * This is the model class for table "itz_trigger_point_variable".
 *
 * The followings are the available columns in table 'itz_trigger_point_variable':
 * @property integer $id
 * @property integer $pointid
 * @property string $owner
 * @property integer $createtime
 * @property integer $lasttime
 * @property string $desc
 */
class TriggerPointVariable extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return TriggerPointVariable the static model class
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
		return 'itz_trigger_point_variable';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('pointid', 'required'),
			array('pointid, createtime, lasttime', 'numerical', 'integerOnly'=>true),
			array('owner', 'length', 'max'=>30),
			array('desc', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, varid,pointid, owner, createtime, lasttime, desc', 'safe'),
			array('id, varid,pointid, owner, createtime, lasttime, desc', 'safe', 'on'=>'search'),
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
			'pointid' => 'Pointid',
			'owner' => 'Owner',
			'createtime' => 'Createtime',
			'lasttime' => 'Lasttime',
			'desc' => 'Desc',
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
		$criteria->compare('pointid',$this->pointid);
		$criteria->compare('owner',$this->owner,true);
		$criteria->compare('createtime',$this->createtime);
		$criteria->compare('lasttime',$this->lasttime);
		$criteria->compare('desc',$this->desc,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}