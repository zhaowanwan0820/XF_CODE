<?php

/**
 * This is the model class for table "ag_user".
 *
 * The followings are the available columns in table 'ag_user':
 * @property string $id
 * @property string $name
 * @property string $real_name
 * @property string $password
 * @property string $pay_password
 * @property integer $type
 * @property integer $id_type
 * @property string $id_no
 * @property string $phone
 * @property string $mail
 * @property integer $sex
 * @property string $address
 * @property string $zip_code
 */
class AgUser extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgUser the static model class
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
		return 'ag_user';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, id_type, sex, level_id', 'numerical', 'integerOnly'=>true),
			array('name, real_name, password, pay_password', 'length', 'max'=>63),
			array('id_no, mail', 'length', 'max'=>127),
			array('phone', 'length', 'max'=>15),
			array('address', 'length', 'max'=>255),
			array('zip_code', 'length', 'max'=>31),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, agree_status,name, fdd_customer_id, real_name, password, pay_password, type, id_type, id_no, phone, mail, sex, address, zip_code', 'safe', 'on'=>'search'),
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
			'real_name' => 'Real Name',
			'password' => 'Password',
			'pay_password' => 'Pay Password',
			'type' => 'Type',
			'level_id' => 'Level Id',
			'id_type' => 'Id Type',
			'id_no' => 'Id No',
			'phone' => 'Phone',
			'mail' => 'Mail',
			'sex' => 'Sex',
			'address' => 'Address',
			'zip_code' => 'Zip Code',
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
		$criteria->compare('real_name',$this->real_name,true);
		$criteria->compare('password',$this->password,true);
		$criteria->compare('pay_password',$this->pay_password,true);
		$criteria->compare('type',$this->type);
		$criteria->compare('level_id',$this->level_id);
		$criteria->compare('id_type',$this->id_type);
		$criteria->compare('id_no',$this->id_no,true);
		$criteria->compare('phone',$this->phone,true);
		$criteria->compare('mail',$this->mail,true);
		$criteria->compare('sex',$this->sex);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('zip_code',$this->zip_code,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}