<?php

/**
 * This is the model class for table "dw_user_cache".
 *
 * The followings are the available columns in table 'dw_user_cache':
 * @property integer $user_id
 * @property integer $kefu_userid
 * @property integer $kefu_username
 * @property integer $kefu_addtime
 * @property integer $vip_status
 * @property string $vip_remark
 * @property string $vip_money
 * @property string $vip_verify_remark
 * @property string $vip_verify_time
 * @property integer $bbs_topics_num
 * @property integer $bbs_posts_num
 * @property integer $credit
 * @property integer $account
 * @property integer $account_use
 * @property integer $account_nouse
 * @property integer $account_waitin
 * @property integer $account_waitintrest
 * @property integer $account_intrest
 * @property integer $account_award
 * @property integer $account_payment
 * @property integer $account_expired
 * @property integer $account_waitvip
 * @property integer $borrow_amount
 * @property integer $vouch_amount
 * @property integer $borrow_loan
 * @property integer $borrow_success
 * @property integer $borrow_wait
 * @property integer $borrow_paymeng
 * @property integer $friends_apply
 * @property string $card_photo
 */
class UserCache extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserCache the static model class
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
		return 'dw_user_cache';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('card_photo', 'required'),
			array('user_id, kefu_userid, kefu_username, kefu_addtime, vip_status, bbs_topics_num, bbs_posts_num, credit, account, account_use, account_nouse, account_waitin, account_waitintrest, account_intrest, account_award, account_payment, account_expired, account_waitvip, borrow_amount, vouch_amount, borrow_loan, borrow_success, borrow_wait, borrow_paymeng, friends_apply', 'numerical', 'integerOnly'=>true),
			array('vip_remark', 'length', 'max'=>250),
			array('vip_money, vip_verify_remark, vip_verify_time', 'length', 'max'=>100),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('user_id, kefu_userid, kefu_username, kefu_addtime, vip_status, vip_remark, vip_money, vip_verify_remark, vip_verify_time, bbs_topics_num, bbs_posts_num, credit, account, account_use, account_nouse, account_waitin, account_waitintrest, account_intrest, account_award, account_payment, account_expired, account_waitvip, borrow_amount, vouch_amount, borrow_loan, borrow_success, borrow_wait, borrow_paymeng, friends_apply, card_photo', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'kefu_userid' => 'Kefu Userid',
			'kefu_username' => 'Kefu Username',
			'kefu_addtime' => 'Kefu Addtime',
			'vip_status' => 'Vip Status',
			'vip_remark' => 'Vip Remark',
			'vip_money' => 'Vip Money',
			'vip_verify_remark' => 'Vip Verify Remark',
			'vip_verify_time' => 'Vip Verify Time',
			'bbs_topics_num' => 'Bbs Topics Num',
			'bbs_posts_num' => 'Bbs Posts Num',
			'credit' => 'Credit',
			'account' => 'Account',
			'account_use' => 'Account Use',
			'account_nouse' => 'Account Nouse',
			'account_waitin' => 'Account Waitin',
			'account_waitintrest' => 'Account Waitintrest',
			'account_intrest' => 'Account Intrest',
			'account_award' => 'Account Award',
			'account_payment' => 'Account Payment',
			'account_expired' => 'Account Expired',
			'account_waitvip' => 'Account Waitvip',
			'borrow_amount' => 'Borrow Amount',
			'vouch_amount' => 'Vouch Amount',
			'borrow_loan' => 'Borrow Loan',
			'borrow_success' => 'Borrow Success',
			'borrow_wait' => 'Borrow Wait',
			'borrow_paymeng' => 'Borrow Paymeng',
			'friends_apply' => 'Friends Apply',
			'card_photo' => 'Card Photo',
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

		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('kefu_userid',$this->kefu_userid);
		$criteria->compare('kefu_username',$this->kefu_username);
		$criteria->compare('kefu_addtime',$this->kefu_addtime);
		$criteria->compare('vip_status',$this->vip_status);
		$criteria->compare('vip_remark',$this->vip_remark,true);
		$criteria->compare('vip_money',$this->vip_money,true);
		$criteria->compare('vip_verify_remark',$this->vip_verify_remark,true);
		$criteria->compare('vip_verify_time',$this->vip_verify_time,true);
		$criteria->compare('bbs_topics_num',$this->bbs_topics_num);
		$criteria->compare('bbs_posts_num',$this->bbs_posts_num);
		$criteria->compare('credit',$this->credit);
		$criteria->compare('account',$this->account);
		$criteria->compare('account_use',$this->account_use);
		$criteria->compare('account_nouse',$this->account_nouse);
		$criteria->compare('account_waitin',$this->account_waitin);
		$criteria->compare('account_waitintrest',$this->account_waitintrest);
		$criteria->compare('account_intrest',$this->account_intrest);
		$criteria->compare('account_award',$this->account_award);
		$criteria->compare('account_payment',$this->account_payment);
		$criteria->compare('account_expired',$this->account_expired);
		$criteria->compare('account_waitvip',$this->account_waitvip);
		$criteria->compare('borrow_amount',$this->borrow_amount);
		$criteria->compare('vouch_amount',$this->vouch_amount);
		$criteria->compare('borrow_loan',$this->borrow_loan);
		$criteria->compare('borrow_success',$this->borrow_success);
		$criteria->compare('borrow_wait',$this->borrow_wait);
		$criteria->compare('borrow_paymeng',$this->borrow_paymeng);
		$criteria->compare('friends_apply',$this->friends_apply);
		$criteria->compare('card_photo',$this->card_photo,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}