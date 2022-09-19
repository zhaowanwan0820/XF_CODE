<?php

/**
 * This is the model class for table "itz_realname_channel".
 *
 * The followings are the available columns in table 'itz_realname_channel':
 * @property string $id
 * @property string $channel_name
 * @property integer $priority
 * @property integer $op_user
 * @property integer $op_time
 * @property string $op_ip
 */
class RealnameChannel extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return RealnameChannel the static model class
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
		return 'itz_realname_channel';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('priority, op_user, op_time', 'numerical', 'integerOnly'=>true),
			array('channel_name', 'length', 'max'=>50),
			array('op_ip', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, mark,channel_name, priority, op_user, op_time, op_ip', 'safe', 'on'=>'search'),
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
			'channel_name' => 'Channel Name',
			'priority' => 'Priority',
			'op_user' => 'Op User',
			'op_time' => 'Op Time',
			'op_ip' => 'Op Ip',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('channel_name',$this->channel_name,true);
		$criteria->compare('priority',$this->priority);
		$criteria->compare('op_user',$this->op_user);
		$criteria->compare('op_time',$this->op_time);
		$criteria->compare('op_ip',$this->op_ip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}