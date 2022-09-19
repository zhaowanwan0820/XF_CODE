<?php

/**
 * This is the model class for table "itz_borrow_reserve".
 *
 * The followings are the available columns in table 'itz_borrow_reserve':
 * @property string $id
 * @property integer $user_id
 * @property integer $borrow_id
 * @property integer $borrow_pre_id
 * @property integer $tender_id
 * @property string $account_init
 * @property string $coupon_value
 * @property integer $coupon_type
 * @property string $coupon_detail
 * @property string $money_detail
 * @property string $device
 * @property string $extra_reward
 * @property string $request_no
 * @property integer $status
 * @property integer $reserve_time
 * @property integer $tender_time
 * @property integer $addtime
 */
class ItzBorrowReserve extends CActiveRecord
{
	
	/**
	 * @return CDbConnection database connection
	 */
	public function getDbConnection()
	{
		return Yii::app()->dwdb;
	}
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzBorrowReserve the static model class
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
		return 'itz_borrow_reserve';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, borrow_id, borrow_pre_id, tender_id, coupon_type, extra_reward_type,status, reserve_time, tender_time, addtime', 'numerical', 'integerOnly'=>true),
			array('account_init, coupon_value', 'length', 'max'=>11),
			array('device', 'length', 'max'=>20),
			array('request_no', 'length', 'max'=>100),
			array('coupon_detail, money_detail, extra_reward', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, borrow_id, borrow_pre_id, tender_id, account_init, coupon_value, coupon_type, coupon_detail, money_detail, device, extra_reward,extra_reward_type, request_no, status, reserve_time, tender_time, addtime', 'safe', 'on'=>'search'),
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
			'borrow_id' => 'Borrow',
			'borrow_pre_id' => 'Borrow Pre',
			'tender_id' => 'Tender',
			'account_init' => 'Account Init',
			'coupon_value' => 'Coupon Value',
			'coupon_type' => 'Coupon Type',
			'coupon_detail' => 'Coupon Detail',
			'money_detail' => 'Money Detail',
			'device' => 'Device',
			'extra_reward' => 'Extra Reward',
			'extra_reward_type' => 'Extra Reward Type',
			'request_no' => 'Request No',
			'status' => 'Status',
			'reserve_time' => 'Reserve Time',
			'tender_time' => 'Tender Time',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('borrow_id',$this->borrow_id);
		$criteria->compare('borrow_pre_id',$this->borrow_pre_id);
		$criteria->compare('tender_id',$this->tender_id);
		$criteria->compare('account_init',$this->account_init,true);
		$criteria->compare('coupon_value',$this->coupon_value,true);
		$criteria->compare('coupon_type',$this->coupon_type);
		$criteria->compare('coupon_detail',$this->coupon_detail,true);
		$criteria->compare('money_detail',$this->money_detail,true);
		$criteria->compare('device',$this->device,true);
		$criteria->compare('extra_reward',$this->extra_reward,true);
		$criteria->compare('extra_reward_type',$this->extra_reward_type,true);
		$criteria->compare('request_no',$this->request_no,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('reserve_time',$this->reserve_time);
		$criteria->compare('tender_time',$this->tender_time);
		$criteria->compare('addtime',$this->addtime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}