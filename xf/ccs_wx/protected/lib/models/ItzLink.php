<?php

/**
 * This is the model class for table "itz_link".
 *
 * The followings are the available columns in table 'itz_link':
 * @property integer $id
 * @property string $hash
 * @property string $domain
 * @property string $url
 * @property string $add_time
 * @property string $domain_pri
 */
class ItzLink extends DwActiveRecord
{
	public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzLink the static model class
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
		return 'itz_link';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('hash, domain, url, add_time, domain_pri', 'required'),
			array('hash', 'length', 'max'=>32),
			array('domain, domain_pri', 'length', 'max'=>60),
			array('url', 'length', 'max'=>2000),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, hash, domain, url, add_time, domain_pri', 'safe', 'on'=>'search'),
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
			'hash' => 'Hash',
			'domain' => 'Domain',
			'url' => 'Url',
			'add_time' => 'Add Time',
			'domain_pri' => 'Domain Pri',
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
		$criteria->compare('hash',$this->hash,true);
		$criteria->compare('domain',$this->domain,true);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('add_time',$this->add_time,true);
		$criteria->compare('domain_pri',$this->domain_pri,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}