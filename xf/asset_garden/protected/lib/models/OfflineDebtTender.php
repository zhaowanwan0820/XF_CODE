<?php

/**
 * This is the model class for table "firstp2p_debt_tender".
 *
 * The followings are the available columns in table 'firstp2p_debt_tender':
 * @property integer $id
 * @property integer $debt_id
 * @property integer $user_id
 * @property integer $new_tender_id
 * @property integer $type
 * @property integer $status
 * @property string $money
 * @property string $account
 * @property string $action_money
 * @property string $money_detail
 * @property integer $addtime
 * @property string $addip
 * @property integer $debt_type
 */
class OfflineDebtTender extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return DebtTender the static model class
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
		return 'offline_debt_tender';
	}

    public function getDbConnection()
    {
        return Yii::app()->offlinedb;
    }

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('debt_id, user_id, type, status, money, account, addtime', 'required'),
			array('debt_id, user_id, new_tender_id, type, status, addtime, debt_type', 'numerical', 'integerOnly'=>true),
			array('money, account, action_money', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('exclusive_purchase_id,platform_id,submit_paytime,cancel_time,payer_name,payer_bankzone,payer_bankcard,payment_voucher,id, debt_id, user_id, new_tender_id, type, status, money, account, action_money, addtime, addip, debt_type', 'safe', 'on'=>'search'),
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
			'debt_id' => 'Debt',
			'user_id' => 'User',
			'new_tender_id' => 'New Tender',
			'type' => 'Type',
			'status' => 'Status',
			'money' => 'Money',
			'account' => 'Account',
			'action_money' => 'Action Money',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'debt_type' => 'Debt Type',
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
		$criteria->compare('debt_id',$this->debt_id);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('new_tender_id',$this->new_tender_id);
		$criteria->compare('type',$this->type);
		$criteria->compare('status',$this->status);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('account',$this->account,true);
		$criteria->compare('action_money',$this->action_money,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('debt_type',$this->debt_type);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}