<?php

/**
 * This is the model class for table "xf_exchange_auth_user".
 *
 * The followings are the available columns in table 'xf_exchange_auth_user':
 * @property integer $id
 * @property integer $appid
 * @property string $openid
 * @property integer $auth_status
 * @property integer $agreement_status
 * @property string $created_at
 * @property string $auth_at
 * @property string $agreement_at
 */
class XfExchangeAuthUser extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfExchangeAuthUser the static model class
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
		return 'xf_exchange_auth_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('openid', 'required'),
			array('appid, auth_status, agreement_status', 'numerical', 'integerOnly'=>true),
			array('openid', 'length', 'max'=>20),
			array('created_at, auth_at, agreement_at', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, appid, openid, auth_status, agreement_status, created_at, auth_at, agreement_at', 'safe', 'on'=>'search'),
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
			'appid' => 'Appid',
			'openid' => 'Openid',
			'auth_status' => 'Auth Status',
			'agreement_status' => 'Agreement Status',
			'created_at' => 'Created At',
			'auth_at' => 'Auth At',
			'agreement_at' => 'Agreement At',
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
		$criteria->compare('appid',$this->appid);
		$criteria->compare('openid',$this->openid,true);
		$criteria->compare('auth_status',$this->auth_status);
		$criteria->compare('agreement_status',$this->agreement_status);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('auth_at',$this->auth_at,true);
		$criteria->compare('agreement_at',$this->agreement_at,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}