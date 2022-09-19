<?php

/**
 * This is the model class for table "ag_wx_user_account".
 *
 * The followings are the available columns in table 'ag_wx_user_account':
 * @property string $id
 * @property integer $user_id
 * @property string $use_money
 * @property string $lock_money
 * @property string $withdraw_free
 * @property string $recharge_amount
 * @property integer $account_type
 * @property string $bank_card
 * @property string $bank_branch
 */
class WxUserAccount extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return WxUserAccount the static model class
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
		return 'ag_wx_user_account';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, account_type', 'numerical', 'integerOnly'=>true),
			array('use_money, lock_money', 'length', 'max'=>20),
			array('withdraw_free, recharge_amount', 'length', 'max'=>11),
			array('bank_card', 'length', 'max'=>63),
			array('bank_branch', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, use_money, lock_money, withdraw_free, recharge_amount, account_type, bank_card, bank_branch', 'safe', 'on'=>'search'),
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
			'use_money' => 'Use Money',
			'lock_money' => 'Lock Money',
			'withdraw_free' => 'Withdraw Free',
			'recharge_amount' => 'Recharge Amount',
			'account_type' => 'Account Type',
			'bank_card' => 'Bank Card',
			'bank_branch' => 'Bank Branch',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('use_money',$this->use_money,true);
		$criteria->compare('lock_money',$this->lock_money,true);
		$criteria->compare('withdraw_free',$this->withdraw_free,true);
		$criteria->compare('recharge_amount',$this->recharge_amount,true);
		$criteria->compare('account_type',$this->account_type);
		$criteria->compare('bank_card',$this->bank_card,true);
		$criteria->compare('bank_branch',$this->bank_branch,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}