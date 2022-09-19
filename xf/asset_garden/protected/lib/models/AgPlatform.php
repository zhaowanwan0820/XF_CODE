<?php

/**
 * This is the model class for table "ag_platform".
 *
 * The followings are the available columns in table 'ag_platform':
 * @property string $id
 * @property string $name
 * @property string $legal_person
 * @property integer $id_type
 * @property string $id_no
 * @property string $contact_mobile
 * @property string $bank_card
 * @property string $bank_detail
 * @property string $province
 * @property string $city
 * @property integer $buyback_user_id
 * @property string $address
 * @property string $business_no
 * @property string $from_desc
 * @property string $desc
 * @property string $e_debt_template
 * @property integer $type
 * @property string $id_pic_front
 * @property string $id_pic_back
 * @property string $business_pic
 * @property integer $status
 * @property string $status_remark
 * @property string $full_name
 * @property string $contact_name
 */
class AgPlatform extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgPlatform the static model class
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
		return 'ag_platform';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('id_type, buyback_user_id, type, status','import_status', 'numerical', 'integerOnly'=>true),
			array('name, id_no', 'length', 'max'=>127),
			array('legal_person, business_no', 'length', 'max'=>63),
			array('contact_mobile, province, city', 'length', 'max'=>15),
			array('bank_card, bank_detail, address, from_desc, e_debt_template', 'length', 'max'=>255),
			array('id_pic_front, id_pic_back, business_pic, status_remark, full_name', 'length', 'max'=>128),
			array('contact_name', 'length', 'max'=>20),
			array('desc', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, company_name,platform_url,name,import_status, legal_person, id_type, id_no, contact_mobile, bank_card, bank_detail, province, city, buyback_user_id, address, business_no, from_desc, desc, e_debt_template, type, id_pic_front, id_pic_back, business_pic, status, status_remark, full_name, contact_name', 'safe', 'on'=>'search'),
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
			'legal_person' => 'Legal Person',
			'id_type' => 'Id Type',
			'id_no' => 'Id No',
			'contact_mobile' => 'Contact Mobile',
			'bank_card' => 'Bank Card',
			'bank_detail' => 'Bank Detail',
			'province' => 'Province',
			'city' => 'City',
			'buyback_user_id' => 'Buyback User',
			'address' => 'Address',
			'business_no' => 'Business No',
			'from_desc' => 'From Desc',
			'desc' => 'Desc',
			'e_debt_template' => 'E Debt Template',
			'type' => 'Type',
			'id_pic_front' => 'Id Pic Front',
			'id_pic_back' => 'Id Pic Back',
			'business_pic' => 'Business Pic',
			'status' => 'Status',
			'import_status' => 'Import Status',
			'status_remark' => 'Status Remark',
			'full_name' => 'Full Name',
			'contact_name' => 'Contact Name',
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('legal_person',$this->legal_person,true);
		$criteria->compare('id_type',$this->id_type);
		$criteria->compare('id_no',$this->id_no,true);
		$criteria->compare('contact_mobile',$this->contact_mobile,true);
		$criteria->compare('bank_card',$this->bank_card,true);
		$criteria->compare('bank_detail',$this->bank_detail,true);
		$criteria->compare('province',$this->province,true);
		$criteria->compare('city',$this->city,true);
		$criteria->compare('buyback_user_id',$this->buyback_user_id);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('business_no',$this->business_no,true);
		$criteria->compare('from_desc',$this->from_desc,true);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('e_debt_template',$this->e_debt_template,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('id_pic_front',$this->id_pic_front,true);
		$criteria->compare('id_pic_back',$this->id_pic_back,true);
		$criteria->compare('business_pic',$this->business_pic,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('status',$this->import_status);
		$criteria->compare('status_remark',$this->status_remark,true);
		$criteria->compare('full_name',$this->full_name,true);
		$criteria->compare('contact_name',$this->contact_name,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}