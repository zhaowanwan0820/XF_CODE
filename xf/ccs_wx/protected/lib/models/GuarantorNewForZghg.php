<?php

/**
 * This is the model class for table "dw_guarantor_new".
 *
 * The followings are the available columns in table 'dw_guarantor_new':
 * @property integer $gid
 * @property string $abbr
 * @property integer $type
 * @property integer $status
 * @property string $name
 * @property string $sname
 * @property string $username
 * @property string $password
 * @property string $paypassword
 * @property string $contactperson
 * @property string $card_id
 * @property string $phone
 * @property string $email
 * @property integer $city
 * @property integer $province
 * @property string $regcapital
 * @property string $credited
 * @property string $guaranteeing
 * @property integer $guaranteeing_num
 * @property string $guaranteed
 * @property integer $guaranteed_num
 * @property string $compensated
 * @property integer $compensated_num
 * @property string $risk_insurance
 * @property integer $interestrepaydays
 * @property integer $capitalrepaydays
 * @property string $logo
 * @property string $logotext
 * @property string $desc
 * @property string $summary
 * @property integer $weight
 * @property string $license1
 * @property string $license2
 * @property string $license3
 * @property string $license4
 * @property string $agreement1
 * @property string $agreement2
 * @property string $stamp
 * @property string $crt
 * @property string $bankbranch
 * @property string $bankcardid
 * @property string $addtime
 * @property integer $foundtime
 * @property integer $startcorptime
 * @property string $updatetime
 * @property string $addip
 * @property string $business_license
 * @property string $license1_alt
 * @property string $license2_alt
 * @property string $license3_alt
 * @property string $license4_alt
 * @property string $agreement1_alt
 * @property string $agreement2_alt
 * @property string $logo_full_name
 * @property string $logo_slogan_name
 * @property string $enterprise_character
 * @property string $address
 * @property string $web_url
 * @property string $tel
 * @property string $business_entity
 * @property string $entity_stamp
 * @property string $entity_crt
 * @property string $business_entity_card_id
 * @property integer $cooperation_status
 * @property string $company_phone
 */
class GuarantorNewForZghg extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return GuarantorNewForZghg the static model class
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
		return 'dw_guarantor_new';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type,name,business_license,stamp,crt','required'),
			array('type, status, city, province, guaranteeing_num, guaranteed_num, compensated_num, interestrepaydays, capitalrepaydays, weight, foundtime, startcorptime, cooperation_status', 'numerical', 'integerOnly'=>true),
			array('abbr', 'length', 'max'=>8),
			array('name, logo, logotext, license1, license2, license3, license4, agreement1, agreement2, stamp, crt, license1_alt, license2_alt, license3_alt, license4_alt, agreement1_alt, agreement2_alt, logo_full_name, logo_slogan_name, address, entity_stamp, entity_crt, business_entity_card_id', 'length', 'max'=>255),
			array('sname, phone, company_phone', 'length', 'max'=>20),
			array('username, contactperson, card_id, addtime, updatetime, addip, web_url, tel, business_entity', 'length', 'max'=>50),
			array('password, paypassword', 'length', 'max'=>32),
			array('email, bankbranch', 'length', 'max'=>100),
			array('regcapital, credited, guaranteeing, guaranteed, compensated, risk_insurance', 'length', 'max'=>18),
			array('bankcardid', 'length', 'max'=>30),
			array('business_license, enterprise_character', 'length', 'max'=>120),
			array('item_class,desc, summary', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('item_class,gid, abbr, type, status, name, sname, username, password, paypassword, contactperson, card_id, phone, email, city, province, regcapital, credited, guaranteeing, guaranteeing_num, guaranteed, guaranteed_num, compensated, compensated_num, risk_insurance, interestrepaydays, capitalrepaydays, logo, logotext, desc, summary, weight, license1, license2, license3, license4, agreement1, agreement2, stamp, crt, bankbranch, bankcardid, addtime, foundtime, startcorptime, updatetime, addip, business_license, license1_alt, license2_alt, license3_alt, license4_alt, agreement1_alt, agreement2_alt, logo_full_name, logo_slogan_name, enterprise_character, address, web_url, tel, business_entity, entity_stamp, entity_crt, business_entity_card_id, cooperation_status, company_phone', 'safe', 'on'=>'search'),
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
			'gid' => 'Gid',
			'abbr' => 'Abbr',
			'type' => 'Type',
			'status' => 'Status',
			'name' => 'Name',
			'sname' => 'Sname',
			'username' => 'Username',
			'password' => 'Password',
			'paypassword' => 'Paypassword',
			'contactperson' => 'Contactperson',
			'card_id' => 'Card',
			'phone' => 'Phone',
			'email' => 'Email',
			'city' => 'City',
			'province' => 'Province',
			'regcapital' => 'Regcapital',
			'credited' => 'Credited',
			'guaranteeing' => 'Guaranteeing',
			'guaranteeing_num' => 'Guaranteeing Num',
			'guaranteed' => 'Guaranteed',
			'guaranteed_num' => 'Guaranteed Num',
			'compensated' => 'Compensated',
			'compensated_num' => 'Compensated Num',
			'risk_insurance' => 'Risk Insurance',
			'interestrepaydays' => 'Interestrepaydays',
			'capitalrepaydays' => 'Capitalrepaydays',
			'logo' => 'Logo',
			'logotext' => 'Logotext',
			'desc' => 'Desc',
			'summary' => 'Summary',
			'weight' => 'Weight',
			'license1' => 'License1',
			'license2' => 'License2',
			'license3' => 'License3',
			'license4' => 'License4',
			'agreement1' => 'Agreement1',
			'agreement2' => 'Agreement2',
			'stamp' => 'Stamp',
			'crt' => 'Crt',
			'bankbranch' => 'Bankbranch',
			'bankcardid' => 'Bankcardid',
			'addtime' => 'Addtime',
			'foundtime' => 'Foundtime',
			'startcorptime' => 'Startcorptime',
			'updatetime' => 'Updatetime',
			'addip' => 'Addip',
			'business_license' => 'Business License',
			'license1_alt' => 'License1 Alt',
			'license2_alt' => 'License2 Alt',
			'license3_alt' => 'License3 Alt',
			'license4_alt' => 'License4 Alt',
			'agreement1_alt' => 'Agreement1 Alt',
			'agreement2_alt' => 'Agreement2 Alt',
			'logo_full_name' => 'Logo Full Name',
			'logo_slogan_name' => 'Logo Slogan Name',
			'enterprise_character' => 'Enterprise Character',
			'address' => 'Address',
			'web_url' => 'Web Url',
			'tel' => 'Tel',
			'business_entity' => 'Business Entity',
			'entity_stamp' => 'Entity Stamp',
			'entity_crt' => 'Entity Crt',
			'business_entity_card_id' => 'Business Entity Card',
			'cooperation_status' => 'Cooperation Status',
			'company_phone' => 'Company Phone',
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

		$criteria->compare('gid',$this->gid);
		$criteria->compare('abbr',$this->abbr,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('status',$this->status);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('sname',$this->sname,true);
		$criteria->compare('username',$this->username,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('paypassword',$this->paypassword,true);
		$criteria->compare('contactperson',$this->contactperson,true);
		$criteria->compare('card_id',$this->card_id,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('email',$this->email,true);
		$criteria->compare('city',$this->city);
		$criteria->compare('province',$this->province);
		$criteria->compare('regcapital',$this->regcapital,true);
		$criteria->compare('credited',$this->credited,true);
		$criteria->compare('guaranteeing',$this->guaranteeing,true);
		$criteria->compare('guaranteeing_num',$this->guaranteeing_num);
		$criteria->compare('guaranteed',$this->guaranteed,true);
		$criteria->compare('guaranteed_num',$this->guaranteed_num);
		$criteria->compare('compensated',$this->compensated,true);
		$criteria->compare('compensated_num',$this->compensated_num);
		$criteria->compare('risk_insurance',$this->risk_insurance,true);
		$criteria->compare('interestrepaydays',$this->interestrepaydays);
		$criteria->compare('capitalrepaydays',$this->capitalrepaydays);
		$criteria->compare('logo',$this->logo,true);
		$criteria->compare('logotext',$this->logotext,true);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('summary',$this->summary,true);
		$criteria->compare('weight',$this->weight);
		$criteria->compare('license1',$this->license1,true);
		$criteria->compare('license2',$this->license2,true);
		$criteria->compare('license3',$this->license3,true);
		$criteria->compare('license4',$this->license4,true);
		$criteria->compare('agreement1',$this->agreement1,true);
		$criteria->compare('agreement2',$this->agreement2,true);
		$criteria->compare('stamp',$this->stamp,true);
		$criteria->compare('crt',$this->crt,true);
		$criteria->compare('bankbranch',$this->bankbranch,true);
		$criteria->compare('bankcardid',$this->bankcardid,true);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('foundtime',$this->foundtime);
		$criteria->compare('startcorptime',$this->startcorptime);
		$criteria->compare('updatetime',$this->updatetime,true);
		$criteria->compare('addip',$this->addip,true);
		$criteria->compare('business_license',$this->business_license,true);
		$criteria->compare('license1_alt',$this->license1_alt,true);
		$criteria->compare('license2_alt',$this->license2_alt,true);
		$criteria->compare('license3_alt',$this->license3_alt,true);
		$criteria->compare('license4_alt',$this->license4_alt,true);
		$criteria->compare('agreement1_alt',$this->agreement1_alt,true);
		$criteria->compare('agreement2_alt',$this->agreement2_alt,true);
		$criteria->compare('logo_full_name',$this->logo_full_name,true);
		$criteria->compare('logo_slogan_name',$this->logo_slogan_name,true);
		$criteria->compare('enterprise_character',$this->enterprise_character,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('web_url',$this->web_url,true);
		$criteria->compare('tel',$this->tel,true);
		$criteria->compare('business_entity',$this->business_entity,true);
		$criteria->compare('entity_stamp',$this->entity_stamp,true);
		$criteria->compare('entity_crt',$this->entity_crt,true);
		$criteria->compare('business_entity_card_id',$this->business_entity_card_id,true);
		$criteria->compare('cooperation_status',$this->cooperation_status);
		$criteria->compare('company_phone',$this->company_phone,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}