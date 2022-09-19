<?php

/**
 * This is the model class for table "xf_debt_exchange_notice".
 *
 * The followings are the available columns in table 'xf_debt_exchange_notice':
 * @property string $id
 * @property integer $appid
 * @property integer $user_id
 * @property string $order_id
 * @property string $amount
 * @property string $notify_url
 * @property string $created_at
 * @property integer $status
 * @property integer $notice_time_1
 * @property integer $notice_time_2
 * @property integer $notice_time_3
 * @property string $order_info
 * @property string $order_sn
 */
class XfDebtExchangeNotice extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfDebtExchangeNotice the static model class
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
		return 'xf_debt_exchange_notice';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('notify_url', 'required'),
			array('appid, status,user_id', 'numerical', 'integerOnly'=>true),
			array('order_id', 'length', 'max'=>30),
			array('amount, created_at, notice_time_1, notice_time_2, notice_time_3', 'length', 'max'=>10),
			array('notify_url', 'length', 'max'=>500),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, appid, user_id,order_id, amount, notify_url, created_at, status, notice_time_1, notice_time_2, notice_time_3,order_info,order_sn', 'safe', 'on'=>'search'),
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
			'user_id' => 'UserId',
			'order_id' => 'Order',
			'amount' => 'Amount',
			'notify_url' => 'Notify Url',
			'created_at' => 'Created At',
			'status' => 'Status',
			'notice_time_1' => 'Notice Time 1',
			'notice_time_2' => 'Notice Time 2',
			'notice_time_3' => 'Notice Time 3',
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
		$criteria->compare('appid',$this->appid);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('order_id',$this->order_id,true);
		$criteria->compare('amount',$this->amount,true);
		$criteria->compare('notify_url',$this->notify_url,true);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('notice_time_1',$this->notice_time_1,true);
		$criteria->compare('notice_time_2',$this->notice_time_2,true);
		$criteria->compare('notice_time_3',$this->notice_time_3,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}