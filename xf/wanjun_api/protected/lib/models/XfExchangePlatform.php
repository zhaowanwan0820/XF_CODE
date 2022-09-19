<?php

/**
 * This is the model class for table "xf_exchange_platform".
 *
 * The followings are the available columns in table 'xf_exchange_platform':
 * @property integer $appid
 * @property string $name
 * @property integer $status
 * @property string $secret
 * @property string $exchange_async_callback
 * @property string $created_at
 */
class XfExchangePlatform extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfExchangePlatform the static model class
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
        return Yii::app()->db;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_exchange_platform';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('exchange_async_callback', 'required'),
			array('status', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>30),
			array('secret', 'length', 'max'=>100),
			array('exchange_async_callback', 'length', 'max'=>500),
			array('created_at', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('appid, name, status, secret, exchange_async_callback, created_at', 'safe', 'on'=>'search'),
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
			'appid' => 'Appid',
			'name' => 'Name',
			'status' => 'Status',
			'secret' => 'Secret',
			'exchange_async_callback' => 'Exchange Async Callback',
			'created_at' => 'Created At',
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

		$criteria->compare('appid',$this->appid);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('secret',$this->secret,true);
		$criteria->compare('exchange_async_callback',$this->exchange_async_callback,true);
		$criteria->compare('created_at',$this->created_at,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}