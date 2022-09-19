<?php

/**
 * This is the model class for table "ag_borrower".
 *
 * The followings are the available columns in table 'ag_borrower':
 * @property string $id
 * @property string $name
 * @property integer $type
 * @property string $legal_person
 * @property integer $id_type
 * @property string $id_no
 * @property string $contact_mobile
 * @property string $business_no
 * @property string $bank_card
 * @property string $bank_detail
 * @property string $province
 * @property string $city
 * @property string $address
 * @property string $desc
 * @property integer $identificated
 * @property integer $identificated_type
 * @property string $identificated_desc
 */
class AgBorrower extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgBorrower the static model class
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
		return 'ag_borrower';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, id_type, identificated, identificated_type', 'numerical', 'integerOnly'=>true),
			array('name, id_no', 'length', 'max'=>127),
			array('legal_person, business_no, bank_card', 'length', 'max'=>63),
			array('contact_mobile', 'length', 'max'=>20),
			array('bank_detail, address, identificated_desc', 'length', 'max'=>255),
			array('province, city', 'length', 'max'=>15),
			array('desc', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, type, legal_person, id_type, id_no, contact_mobile, business_no, bank_card, bank_detail, province, city, address, desc, identificated, identificated_type, identificated_desc', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'legal_person' => 'Legal Person',
			'id_type' => 'Id Type',
			'id_no' => 'Id No',
			'contact_mobile' => 'Contact Mobile',
			'business_no' => 'Business No',
			'bank_card' => 'Bank Card',
			'bank_detail' => 'Bank Detail',
			'province' => 'Province',
			'city' => 'City',
			'address' => 'Address',
			'desc' => 'Desc',
			'identificated' => 'Identificated',
			'identificated_type' => 'Identificated Type',
			'identificated_desc' => 'Identificated Desc',
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
		$criteria->compare('type',$this->type);
		$criteria->compare('legal_person',$this->legal_person,true);
		$criteria->compare('id_type',$this->id_type);
		$criteria->compare('id_no',$this->id_no,true);
		$criteria->compare('contact_mobile',$this->contact_mobile,true);
		$criteria->compare('business_no',$this->business_no,true);
		$criteria->compare('bank_card',$this->bank_card,true);
		$criteria->compare('bank_detail',$this->bank_detail,true);
		$criteria->compare('province',$this->province,true);
		$criteria->compare('city',$this->city,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('desc',$this->desc,true);
		$criteria->compare('identificated',$this->identificated);
		$criteria->compare('identificated_type',$this->identificated_type);
		$criteria->compare('identificated_desc',$this->identificated_desc,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}