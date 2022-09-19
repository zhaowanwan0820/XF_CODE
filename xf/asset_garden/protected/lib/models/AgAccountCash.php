<?php

/**
 * This is the model class for table "ag_account_cash".
 *
 * The followings are the available columns in table 'ag_account_cash':
 * @property string $id
 * @property integer $user_id
 * @property integer $status
 * @property string $bank_card
 * @property string $bank_branch
 * @property string $bank_code
 * @property string $union_bank_id
 * @property string $money
 * @property string $credited
 * @property string $fee
 * @property string $use_withdraw_free_detail
 * @property string $remark
 * @property integer $verify_userid
 * @property integer $verify_time
 * @property string $verify_remark
 * @property string $transfer_remark
 * @property string $transfer_num
 * @property integer $transfer_userid
 * @property integer $transfer_time
 * @property integer $addtime
 * @property string $addip
 * @property integer $is_send
 * @property string $trade_no
 */
class AgAccountCash extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgAccountCash the static model class
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
		return Yii::app()->agdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ag_account_cash';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, status, verify_userid, verify_time, transfer_userid, transfer_time, addtime, is_send', 'numerical', 'integerOnly'=>true),
			array('bank_card, bank_branch, bank_code, union_bank_id', 'length', 'max'=>63),
			array('money, credited, fee', 'length', 'max'=>11),
			array('remark, verify_remark, transfer_remark', 'length', 'max'=>255),
			array('transfer_num, trade_no', 'length', 'max'=>127),
			array('addip', 'length', 'max'=>15),
			array('use_withdraw_free_detail', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, status, bank_card, bank_branch, bank_code, union_bank_id, money, credited, fee, use_withdraw_free_detail, remark, verify_userid, verify_time, verify_remark, transfer_remark, transfer_num, transfer_userid, transfer_time, addtime, addip, is_send, trade_no', 'safe', 'on'=>'search'),
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
			'status' => 'Status',
			'bank_card' => 'Bank Card',
			'bank_branch' => 'Bank Branch',
			'bank_code' => 'Bank Code',
			'union_bank_id' => 'Union Bank',
			'money' => 'Money',
			'credited' => 'Credited',
			'fee' => 'Fee',
			'use_withdraw_free_detail' => 'Use Withdraw Free Detail',
			'remark' => 'Remark',
			'verify_userid' => 'Verify Userid',
			'verify_time' => 'Verify Time',
			'verify_remark' => 'Verify Remark',
			'transfer_remark' => 'Transfer Remark',
			'transfer_num' => 'Transfer Num',
			'transfer_userid' => 'Transfer Userid',
			'transfer_time' => 'Transfer Time',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
			'is_send' => 'Is Send',
			'trade_no' => 'Trade No',
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
		$criteria->compare('status',$this->status);
		$criteria->compare('bank_card',$this->bank_card,true);
		$criteria->compare('bank_branch',$this->bank_branch,true);
		$criteria->compare('bank_code',$this->bank_code,true);
		$criteria->compare('union_bank_id',$this->union_bank_id,true);
		$criteria->compare('money',$this->money,true);
		$criteria->compare('credited',$this->credited,true);
		$criteria->compare('fee',$this->fee,true);
		$criteria->compare('use_withdraw_free_detail',$this->use_withdraw_free_detail,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('verify_userid',$this->verify_userid);
		$criteria->compare('verify_time',$this->verify_time);
		$criteria->compare('verify_remark',$this->verify_remark,true);
		$criteria->compare('transfer_remark',$this->transfer_remark,true);
		$criteria->compare('transfer_num',$this->transfer_num,true);
		$criteria->compare('transfer_userid',$this->transfer_userid);
		$criteria->compare('transfer_time',$this->transfer_time);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('is_send',$this->is_send);
		$criteria->compare('trade_no',$this->trade_no,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}