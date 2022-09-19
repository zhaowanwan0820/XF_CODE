<?php

/**
 * This is the model class for table "itz_mobile_devices".
 *
 * The followings are the available columns in table 'itz_mobile_devices':
 * @property integer $id
 * @property string $token
 * @property string $uuid
 * @property string $device_name
 * @property string $device_model
 * @property string $login_time
 * @property integer $is_invalid
 * @property integer $user_id
 * @property string $expire_time
 */
class MobileDevices extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return MobileDevices the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_mobile_devices';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('is_invalid, user_id', 'numerical', 'integerOnly'=>true),
			array('token, uuid, device_name, device_model', 'length', 'max'=>255),
			array('login_time, expire_time', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, token, uuid, device_name, device_model, login_time, is_invalid, user_id, expire_time', 'safe', 'on'=>'search'),
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
			'token' => 'Token',
			'uuid' => 'Uuid',
			'device_name' => 'Device Name',
			'device_model' => 'Device Model',
			'login_time' => 'Login Time',
			'is_invalid' => 'Is Invalid',
			'user_id' => 'User',
			'expire_time' => 'Expire Time',
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
		$criteria->compare('token',$this->token,true);
		$criteria->compare('uuid',$this->uuid,true);
		$criteria->compare('device_name',$this->device_name,true);
		$criteria->compare('device_model',$this->device_model,true);
		$criteria->compare('login_time',$this->login_time,true);
		$criteria->compare('is_invalid',$this->is_invalid);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('expire_time',$this->expire_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}