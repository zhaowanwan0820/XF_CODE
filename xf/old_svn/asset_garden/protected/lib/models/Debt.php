<?php

/**
 * This is the model class for table "firstp2p_debt".
 *
 * The followings are the available columns in table 'firstp2p_debt':
 * @property integer $id
 * @property integer $user_id
 * @property integer $tender_id
 * @property integer $borrow_id
 * @property integer $type
 * @property integer $status
 * @property string $money
 * @property string $sold_money
 * @property string $discount
 * @property string $scale
 * @property string $real_apr_s
 * @property string $real_apr_b
 * @property integer $starttime
 * @property integer $endtime
 * @property integer $next_repay_time
 * @property integer $successtime
 * @property integer $addtime
 * @property string $addip
 * @property integer $repayment_time
 * @property string $serial_number
 * @property integer $renew_status
 * @property string $borrow_apr
 * @property integer $debt_type
 * @property string $borrow_repay_scale
 * @property string $arrival_amount
 * @property integer $repay_status
 */
class Debt extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Debt the static model class
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
		return 'firstp2p_debt';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, tender_id, borrow_id, type, status, money, sold_money, discount, starttime, endtime, successtime, addtime', 'required'),
			array('user_id, tender_id, borrow_id, type, status, starttime, endtime, next_repay_time, successtime, addtime, repayment_time, renew_status, debt_type, repay_status', 'numerical', 'integerOnly'=>true),
			array('money, sold_money, discount, scale, real_apr_s, real_apr_b, borrow_apr, borrow_repay_scale, arrival_amount', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>15),
			array('serial_number', 'length', 'max'=>100),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('email_notice_id,is_mail,payee_name,payee_bankzone,payee_bankcard,buy_code,id, user_id, debt_src,tender_id, borrow_id, type, status, money, sold_money, discount, scale, real_apr_s, real_apr_b, starttime, endtime, next_repay_time, successtime, addtime, addip, repayment_time, serial_number, renew_status, borrow_apr, debt_type, borrow_repay_scale, arrival_amount, repay_status', 'safe', 'on'=>'search'),
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
			'tender_id' => 'Tender',
			'borrow_id' => 'Borrow',
			'type' => 'Type',
			'status' => 'Status',
			'money' => 'Money',
			'sold_money' => 'Sold Money',
			'discount' => 'Discount Money',
			'scale' => 'Scale',
			'real_apr_s' => 'Real Apr S',
			'real_apr_b' => 'Real Apr B',
			'starttime' => 'Starttime',
			'endtime' => 'Endtime',
			'next_repay_time' => 'Next Repay Time',
			'successtime' => 'Successtime',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'repayment_time' => 'Repayment Time',
			'serial_number' => 'Request No',
			'renew_status' => 'Renew Status',
			'borrow_apr' => 'Borrow Apr',
			'debt_type' => 'Debt Type',
			'borrow_repay_scale' => 'Borrow Repay Scale',
			'arrival_amount' => 'Arrival Amount',
			'repay_status' => 'Repay Status',
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
		$criteria->compare('tender_id',$this->tender_id);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('type',$this->type);
		$criteria->compare('status',$this->status);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('sold_money',$this->sold_money,true);
		$criteria->compare('discount',$this->discount,true);
		$criteria->compare('scale',$this->scale,true);
		$criteria->compare('real_apr_s',$this->real_apr_s,true);
		$criteria->compare('real_apr_b',$this->real_apr_b,true);
		$criteria->compare('starttime',$this->starttime);
		$criteria->compare('endtime',$this->endtime);
		$criteria->compare('next_repay_time',$this->next_repay_time);
		$criteria->compare('successtime',$this->successtime);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('repayment_time',$this->repayment_time);
		$criteria->compare('serial_number',$this->serial_number,true);
		$criteria->compare('renew_status',$this->renew_status);
		$criteria->compare('borrow_apr',$this->borrow_apr,true);
		$criteria->compare('debt_type',$this->debt_type);
		$criteria->compare('borrow_repay_scale',$this->borrow_repay_scale,true);
		$criteria->compare('arrival_amount',$this->arrival_amount,true);
		$criteria->compare('repay_status',$this->repay_status);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}