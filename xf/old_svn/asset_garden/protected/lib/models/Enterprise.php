<?php

/**
 * This is the model class for table "firstp2p_enterprise".
 *
 * The followings are the available columns in table 'firstp2p_enterprise':
 * @property string $id
 * @property integer $user_id
 * @property string $company_purpose
 * @property string $privilege_note
 * @property string $company_name
 * @property string $company_shortname
 * @property integer $credentials_type
 * @property string $credentials_no
 * @property string $credentials_expire_date
 * @property string $credentials_expire_at
 * @property integer $is_permanent
 * @property string $legalbody_name
 * @property integer $legalbody_credentials_type
 * @property string $legalbody_credentials_no
 * @property string $legalbody_mobile_code
 * @property string $legalbody_mobile
 * @property string $legalbody_email
 * @property string $registration_address
 * @property string $registration_region
 * @property string $contract_address
 * @property string $contract_region
 * @property string $memo
 * @property string $create_time
 * @property string $update_time
 * @property string $identifier
 * @property string $supervision_user_id
 * @property integer $indu_cate
 * @property string $reg_amt
 * @property string $app_no
 * @property string $xian_account
 * @property integer $xianbank_state
 * @property integer $reg_amt_unit
 * @property string $business_scope
 */
class Enterprise extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Enterprise the static model class
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
		return 'firstp2p_enterprise';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, credentials_type, is_permanent, legalbody_credentials_type, indu_cate, xianbank_state, reg_amt_unit', 'numerical', 'integerOnly'=>true),
			array('company_purpose, legalbody_name, legalbody_mobile, registration_region, reg_amt, app_no', 'length', 'max'=>20),
			array('privilege_note, company_name, legalbody_email, registration_address, contract_address, memo', 'length', 'max'=>200),
			array('company_shortname', 'length', 'max'=>150),
			array('credentials_no, contract_region', 'length', 'max'=>50),
			array('legalbody_credentials_no, identifier', 'length', 'max'=>30),
			array('legalbody_mobile_code', 'length', 'max'=>8),
			array('create_time, update_time, supervision_user_id', 'length', 'max'=>11),
			array('xian_account', 'length', 'max'=>255),
			array('business_scope', 'length', 'max'=>5000),
			array('credentials_expire_date, credentials_expire_at', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, company_purpose, privilege_note, company_name, company_shortname, credentials_type, credentials_no, credentials_expire_date, credentials_expire_at, is_permanent, legalbody_name, legalbody_credentials_type, legalbody_credentials_no, legalbody_mobile_code, legalbody_mobile, legalbody_email, registration_address, registration_region, contract_address, contract_region, memo, create_time, update_time, identifier, supervision_user_id, indu_cate, reg_amt, app_no, xian_account, xianbank_state, reg_amt_unit, business_scope', 'safe', 'on'=>'search'),
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
			'company_purpose' => 'Company Purpose',
			'privilege_note' => 'Privilege Note',
			'company_name' => 'Company Name',
			'company_shortname' => 'Company Shortname',
			'credentials_type' => 'Credentials Type',
			'credentials_no' => 'Credentials No',
			'credentials_expire_date' => 'Credentials Expire Date',
			'credentials_expire_at' => 'Credentials Expire At',
			'is_permanent' => 'Is Permanent',
			'legalbody_name' => 'Legalbody Name',
			'legalbody_credentials_type' => 'Legalbody Credentials Type',
			'legalbody_credentials_no' => 'Legalbody Credentials No',
			'legalbody_mobile_code' => 'Legalbody Mobile Code',
			'legalbody_mobile' => 'Legalbody Mobile',
			'legalbody_email' => 'Legalbody Email',
			'registration_address' => 'Registration Address',
			'registration_region' => 'Registration Region',
			'contract_address' => 'Contract Address',
			'contract_region' => 'Contract Region',
			'memo' => 'Memo',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'identifier' => 'Identifier',
			'supervision_user_id' => 'Supervision User',
			'indu_cate' => 'Indu Cate',
			'reg_amt' => 'Reg Amt',
			'app_no' => 'App No',
			'xian_account' => 'Xian Account',
			'xianbank_state' => 'Xianbank State',
			'reg_amt_unit' => 'Reg Amt Unit',
			'business_scope' => 'Business Scope',
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
		$criteria->compare('company_purpose',$this->company_purpose,true);
		$criteria->compare('privilege_note',$this->privilege_note,true);
		$criteria->compare('company_name',$this->company_name,true);
		$criteria->compare('company_shortname',$this->company_shortname,true);
		$criteria->compare('credentials_type',$this->credentials_type);
		$criteria->compare('credentials_no',$this->credentials_no,true);
		$criteria->compare('credentials_expire_date',$this->credentials_expire_date,true);
		$criteria->compare('credentials_expire_at',$this->credentials_expire_at,true);
		$criteria->compare('is_permanent',$this->is_permanent);
		$criteria->compare('legalbody_name',$this->legalbody_name,true);
		$criteria->compare('legalbody_credentials_type',$this->legalbody_credentials_type);
		$criteria->compare('legalbody_credentials_no',$this->legalbody_credentials_no,true);
		$criteria->compare('legalbody_mobile_code',$this->legalbody_mobile_code,true);
		$criteria->compare('legalbody_mobile',$this->legalbody_mobile,true);
		$criteria->compare('legalbody_email',$this->legalbody_email,true);
		$criteria->compare('registration_address',$this->registration_address,true);
		$criteria->compare('registration_region',$this->registration_region,true);
		$criteria->compare('contract_address',$this->contract_address,true);
		$criteria->compare('contract_region',$this->contract_region,true);
		$criteria->compare('memo',$this->memo,true);
		$criteria->compare('create_time',$this->create_time,true);
		$criteria->compare('update_time',$this->update_time,true);
		$criteria->compare('identifier',$this->identifier,true);
		$criteria->compare('supervision_user_id',$this->supervision_user_id,true);
		$criteria->compare('indu_cate',$this->indu_cate);
		$criteria->compare('reg_amt',$this->reg_amt,true);
		$criteria->compare('app_no',$this->app_no,true);
		$criteria->compare('xian_account',$this->xian_account,true);
		$criteria->compare('xianbank_state',$this->xianbank_state);
		$criteria->compare('reg_amt_unit',$this->reg_amt_unit);
		$criteria->compare('business_scope',$this->business_scope,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}