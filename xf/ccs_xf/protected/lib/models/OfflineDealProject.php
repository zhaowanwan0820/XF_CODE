<?php

/**
 * This is the model class for table "offline_deal_project".
 *
 * The followings are the available columns in table 'offline_deal_project':
 * @property integer $id
 * @property string $name
 * @property integer $user_id
 * @property integer $risk_bearing
 * @property string $borrow_amount
 * @property integer $loantype
 * @property integer $repay_time
 * @property string $rate
 * @property string $max_rate
 * @property string $money_borrowed
 * @property string $money_loaned
 * @property string $intro
 * @property string $approve_number
 * @property string $credit
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $deal_type
 * @property string $project_info_url
 * @property string $project_extrainfo_url
 * @property integer $status
 * @property integer $borrow_fee_type
 * @property integer $loan_money_type
 * @property string $card_name
 * @property integer $card_type
 * @property string $bankcard
 * @property string $bankzone
 * @property integer $bank_id
 * @property integer $entrust_sign
 * @property integer $entrust_agency_sign
 * @property integer $entrust_advisory_sign
 * @property string $product_class
 * @property string $product_name
 * @property string $product_mix_1
 * @property string $product_mix_2
 * @property string $product_mix_3
 * @property integer $fixed_value_date
 * @property integer $business_status
 * @property string $assets_desc
 * @property string $post_loan_message
 * @property integer $clearing_type
 * @property integer $platform_id
 * @property integer $limit_type
 */
class OfflineDealProject extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineDealProject the static model class
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
		return 'offline_deal_project';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, risk_bearing, loantype, repay_time, create_time, update_time, deal_type, status, borrow_fee_type, loan_money_type, card_type, bank_id, entrust_sign, entrust_agency_sign, entrust_advisory_sign, fixed_value_date, business_status, clearing_type, platform_id', 'numerical', 'integerOnly'=>true),
			array('name, approve_number, credit, project_info_url, project_extrainfo_url, card_name, bankcard, bankzone', 'length', 'max'=>255),
			array('borrow_amount, money_borrowed, money_loaned', 'length', 'max'=>20),
			array('rate', 'length', 'max'=>8),
			array('product_class, product_name', 'length', 'max'=>25),
			array('product_mix_1, product_mix_2, product_mix_3', 'length', 'max'=>30),
			array('assets_desc', 'length', 'max'=>1000),
			array('intro, post_loan_message', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('limit_type,max_rate, id, name, user_id, risk_bearing, borrow_amount, loantype, repay_time, rate, money_borrowed, money_loaned, intro, approve_number, credit, create_time, update_time, deal_type, project_info_url, project_extrainfo_url, status, borrow_fee_type, loan_money_type, card_name, card_type, bankcard, bankzone, bank_id, entrust_sign, entrust_agency_sign, entrust_advisory_sign, product_class, product_name, product_mix_1, product_mix_2, product_mix_3, fixed_value_date, business_status, assets_desc, post_loan_message, clearing_type, platform_id', 'safe', 'on'=>'search'),
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
			'name' => 'Name',
			'user_id' => 'User',
			'risk_bearing' => 'Risk Bearing',
			'borrow_amount' => 'Borrow Amount',
			'loantype' => 'Loantype',
			'repay_time' => 'Repay Time',
			'rate' => 'Rate',
			'max_rate' => 'Max Rate',
			'money_borrowed' => 'Money Borrowed',
			'money_loaned' => 'Money Loaned',
			'intro' => 'Intro',
			'approve_number' => 'Approve Number',
			'credit' => 'Credit',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'deal_type' => 'Deal Type',
			'project_info_url' => 'Project Info Url',
			'project_extrainfo_url' => 'Project Extrainfo Url',
			'status' => 'Status',
			'borrow_fee_type' => 'Borrow Fee Type',
			'loan_money_type' => 'Loan Money Type',
			'card_name' => 'Card Name',
			'card_type' => 'Card Type',
			'bankcard' => 'Bankcard',
			'bankzone' => 'Bankzone',
			'bank_id' => 'Bank',
			'entrust_sign' => 'Entrust Sign',
			'entrust_agency_sign' => 'Entrust Agency Sign',
			'entrust_advisory_sign' => 'Entrust Advisory Sign',
			'product_class' => 'Product Class',
			'product_name' => 'Product Name',
			'product_mix_1' => 'Product Mix 1',
			'product_mix_2' => 'Product Mix 2',
			'product_mix_3' => 'Product Mix 3',
			'fixed_value_date' => 'Fixed Value Date',
			'business_status' => 'Business Status',
			'assets_desc' => 'Assets Desc',
			'post_loan_message' => 'Post Loan Message',
			'clearing_type' => 'Clearing Type',
			'platform_id' => 'Platform',
			'limit_type' => 'Limit Type',
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('risk_bearing',$this->risk_bearing);
		$criteria->compare('borrow_amount',$this->borrow_amount,true);
		$criteria->compare('loantype',$this->loantype);
		$criteria->compare('repay_time',$this->repay_time);
		$criteria->compare('rate',$this->rate,true);
		$criteria->compare('max_rate',$this->max_rate,true);
		$criteria->compare('money_borrowed',$this->money_borrowed,true);
		$criteria->compare('money_loaned',$this->money_loaned,true);
		$criteria->compare('intro',$this->intro,true);
		$criteria->compare('approve_number',$this->approve_number,true);
		$criteria->compare('credit',$this->credit,true);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('deal_type',$this->deal_type);
		$criteria->compare('project_info_url',$this->project_info_url,true);
		$criteria->compare('project_extrainfo_url',$this->project_extrainfo_url,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('borrow_fee_type',$this->borrow_fee_type);
		$criteria->compare('loan_money_type',$this->loan_money_type);
		$criteria->compare('card_name',$this->card_name,true);
		$criteria->compare('card_type',$this->card_type);
		$criteria->compare('bankcard',$this->bankcard,true);
		$criteria->compare('bankzone',$this->bankzone,true);
		$criteria->compare('bank_id',$this->bank_id);
		$criteria->compare('entrust_sign',$this->entrust_sign);
		$criteria->compare('entrust_agency_sign',$this->entrust_agency_sign);
		$criteria->compare('entrust_advisory_sign',$this->entrust_advisory_sign);
		$criteria->compare('product_class',$this->product_class,true);
		$criteria->compare('product_name',$this->product_name,true);
		$criteria->compare('product_mix_1',$this->product_mix_1,true);
		$criteria->compare('product_mix_2',$this->product_mix_2,true);
		$criteria->compare('product_mix_3',$this->product_mix_3,true);
		$criteria->compare('fixed_value_date',$this->fixed_value_date);
		$criteria->compare('business_status',$this->business_status);
		$criteria->compare('assets_desc',$this->assets_desc,true);
		$criteria->compare('post_loan_message',$this->post_loan_message,true);
		$criteria->compare('clearing_type',$this->clearing_type);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('limit_type',$this->limit_type);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}