<?php

/**
 * This is the model class for table "market_tinyUrl".
 *
 * The followings are the available columns in table 'market_tinyUrl':
 * @property string $id
 * @property string $longUrl
 * @property string $shortCode
 */
class MarketTinyUrl extends DwActiveRecord
{
	/**
	 * 	指定库
	 */
	public $dbname = 'grocerydb';
	
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return MarketTinyUrl the static model class
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
		return 'itz_market_tinyurl';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('longUrl', 'length', 'max'=>1023),
			array('shortCode', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, longUrl, shortCode', 'safe', 'on'=>'search'),
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
			'longUrl' => 'Long Url',
			'shortCode' => 'Short Code',
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
		$criteria->compare('longUrl',$this->longUrl,true);
		$criteria->compare('shortCode',$this->shortCode,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}