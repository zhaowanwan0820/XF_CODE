<?php

/**
 * This is the model class for table "offline_user_platform".
 *
 * The followings are the available columns in table 'offline_user_platform':
 * @property integer $id
 * @property integer $user_id
 * @property integer $platform_id
 * @property string $real_name
 * @property string $phone
 * @property string $money
 * @property string $lock_money
 * @property string $wait_join_money
 * @property integer $old_user_id
 * @property integer $id_type
 * @property string $idno
 * @property integer $user_purpose
 */
class OfflineUserPlatform extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineUserPlatform the static model class
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
		return Yii::app()->offlinedb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'offline_user_platform';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, platform_id, old_user_id, id_type, user_purpose', 'numerical', 'integerOnly'=>true),
			array('real_name, money, lock_money, wait_join_money', 'length', 'max'=>20),
			array('phone, idno', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, platform_id, real_name, phone, money, lock_money, wait_join_money, old_user_id, id_type, idno, user_purpose', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'platform_id' => 'Platform',
			'real_name' => 'Real Name',
			'phone' => 'Phone',
			'money' => 'Money',
			'lock_money' => 'Lock Money',
			'wait_join_money' => 'Wait Join Money',
			'old_user_id' => 'Old User',
			'id_type' => 'Id Type',
			'idno' => 'Idno',
			'user_purpose' => 'User Purpose',
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
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('real_name',$this->real_name,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('lock_money',$this->lock_money,true);
		$criteria->compare('wait_join_money',$this->wait_join_money,true);
		$criteria->compare('old_user_id',$this->old_user_id);
		$criteria->compare('id_type',$this->id_type);
		$criteria->compare('idno',$this->idno,true);
		$criteria->compare('user_purpose',$this->user_purpose);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}