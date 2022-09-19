<?php

/**
 * This is the model class for table "itz_stat_cash_check".
 *
 * The followings are the available columns in table 'itz_stat_cash_check':
 * @property integer $id
 * @property string $user_id
 * @property string $realname
 * @property string $phone
 * @property string $frost_account
 * @property string $cash_account
 * @property string $verify_account
 * @property string $transfer_account
 * @property string $bank_account
 * @property string $addtime
 */
class ItzStatCashCheck extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzStatCashCheck the static model class
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
		return 'itz_stat_cash_check';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id', 'required'),
			array('user_id', 'length', 'max'=>10),
			array('realname', 'length', 'max'=>20),
			array('phone, addtime', 'length', 'max'=>50),
			array('frost_account, cash_account, verify_account, transfer_account, bank_account', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, realname, phone, frost_account, cash_account, verify_account, transfer_account, bank_account, addtime', 'safe', 'on'=>'search'),
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
			'realname' => 'Realname',
			'phone' => 'Phone',
			'frost_account' => 'Frost Account',
			'cash_account' => 'Cash Account',
			'verify_account' => 'Verify Account',
			'transfer_account' => 'Transfer Account',
			'bank_account' => 'Bank Account',
			'addtime' => 'Addtime',
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
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('realname',$this->realname,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('frost_account',$this->frost_account,true);
		$criteria->compare('cash_account',$this->cash_account,true);
		$criteria->compare('verify_account',$this->verify_account,true);
		$criteria->compare('transfer_account',$this->transfer_account,true);
		$criteria->compare('bank_account',$this->bank_account,true);
		$criteria->compare('addtime',$this->addtime,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}