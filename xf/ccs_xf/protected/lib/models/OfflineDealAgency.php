<?php

/**
 * This is the model class for table "offline_deal_agency".
 *
 * The followings are the available columns in table 'offline_deal_agency':
 * @property integer $id
 * @property string $header
 * @property integer $type
 * @property string $name
 * @property integer $user_id
 * @property integer $agency_user_id
 * @property string $brief
 * @property string $company_brief
 * @property string $history
 * @property string $content
 * @property integer $is_effect
 * @property integer $create_time
 * @property integer $update_time
 * @property integer $sort
 * @property string $short_name
 * @property string $address
 * @property string $realname
 * @property string $mobile
 * @property string $postcode
 * @property string $fax
 * @property string $email
 * @property double $review
 * @property double $premium
 * @property double $caution_money
 * @property string $agreement
 * @property string $bankzone
 * @property string $bankcard
 * @property string $mechanism
 * @property string $license
 * @property string $repay_inform_email
 * @property integer $is_icp
 * @property integer $site_id
 * @property string $business_place_state
 * @property string $risk_control
 * @property string $agency_brief
 * @property string $man_product
 * @property string $team_brief
 * @property string $future_expectation
 * @property integer $is_credit_display
 * @property string $debt_email
 * @property integer $platform_id
 */
class OfflineDealAgency extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return OfflineDealAgency the static model class
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
		return 'offline_deal_agency';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, user_id, agency_user_id, is_effect, create_time, update_time, sort, is_icp, site_id, is_credit_display, platform_id', 'numerical', 'integerOnly'=>true),
			array('review, premium, caution_money', 'numerical'),
			array('name, short_name', 'length', 'max'=>100),
			array('address, email', 'length', 'max'=>200),
			array('realname, postcode, fax', 'length', 'max'=>50),
			array('mobile, bankzone, bankcard, license, repay_inform_email', 'length', 'max'=>255),
			array('header, brief, company_brief, history, content, agreement, mechanism, business_place_state, risk_control, agency_brief, man_product, team_brief, future_expectation, debt_email', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, header, type, name, user_id, agency_user_id, brief, company_brief, history, content, is_effect, create_time, update_time, sort, short_name, address, realname, mobile, postcode, fax, email, review, premium, caution_money, agreement, bankzone, bankcard, mechanism, license, repay_inform_email, is_icp, site_id, business_place_state, risk_control, agency_brief, man_product, team_brief, future_expectation, is_credit_display, debt_email, platform_id', 'safe', 'on'=>'search'),
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
			'header' => 'Header',
			'type' => 'Type',
			'name' => 'Name',
			'user_id' => 'User',
			'agency_user_id' => 'Agency User',
			'brief' => 'Brief',
			'company_brief' => 'Company Brief',
			'history' => 'History',
			'content' => 'Content',
			'is_effect' => 'Is Effect',
			'create_time' => 'Create Time',
			'update_time' => 'Update Time',
			'sort' => 'Sort',
			'short_name' => 'Short Name',
			'address' => 'Address',
			'realname' => 'Realname',
			'mobile' => 'Mobile',
			'postcode' => 'Postcode',
			'fax' => 'Fax',
			'email' => 'Email',
			'review' => 'Review',
			'premium' => 'Premium',
			'caution_money' => 'Caution Money',
			'agreement' => 'Agreement',
			'bankzone' => 'Bankzone',
			'bankcard' => 'Bankcard',
			'mechanism' => 'Mechanism',
			'license' => 'License',
			'repay_inform_email' => 'Repay Inform Email',
			'is_icp' => 'Is Icp',
			'site_id' => 'Site',
			'business_place_state' => 'Business Place State',
			'risk_control' => 'Risk Control',
			'agency_brief' => 'Agency Brief',
			'man_product' => 'Man Product',
			'team_brief' => 'Team Brief',
			'future_expectation' => 'Future Expectation',
			'is_credit_display' => 'Is Credit Display',
			'debt_email' => 'Debt Email',
			'platform_id' => 'Platform',
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
		$criteria->compare('header',$this->header,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('agency_user_id',$this->agency_user_id);
		$criteria->compare('brief',$this->brief,true);
		$criteria->compare('company_brief',$this->company_brief,true);
		$criteria->compare('history',$this->history,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('is_effect',$this->is_effect);
		$criteria->compare('create_time',$this->create_time);
		$criteria->compare('update_time',$this->update_time);
		$criteria->compare('sort',$this->sort);
		$criteria->compare('short_name',$this->short_name,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('realname',$this->realname,true);
		$criteria->compare('mobile',$this->mobile,true);
		$criteria->compare('postcode',$this->postcode,true);
		$criteria->compare('fax',$this->fax,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('review',$this->review);
		$criteria->compare('premium',$this->premium);
		$criteria->compare('caution_money',$this->caution_money);
		$criteria->compare('agreement',$this->agreement,true);
		$criteria->compare('bankzone',$this->bankzone,true);
		$criteria->compare('bankcard',$this->bankcard,true);
		$criteria->compare('mechanism',$this->mechanism,true);
		$criteria->compare('license',$this->license,true);
		$criteria->compare('repay_inform_email',$this->repay_inform_email,true);
		$criteria->compare('is_icp',$this->is_icp);
		$criteria->compare('site_id',$this->site_id);
		$criteria->compare('business_place_state',$this->business_place_state,true);
		$criteria->compare('risk_control',$this->risk_control,true);
		$criteria->compare('agency_brief',$this->agency_brief,true);
		$criteria->compare('man_product',$this->man_product,true);
		$criteria->compare('team_brief',$this->team_brief,true);
		$criteria->compare('future_expectation',$this->future_expectation,true);
		$criteria->compare('is_credit_display',$this->is_credit_display);
		$criteria->compare('debt_email',$this->debt_email,true);
		$criteria->compare('platform_id',$this->platform_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}