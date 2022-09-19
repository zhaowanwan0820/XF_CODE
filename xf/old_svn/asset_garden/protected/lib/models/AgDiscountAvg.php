<?php

/**
 * This is the model class for table "ag_discount_avg".
 *
 * The followings are the available columns in table 'ag_discount_avg':
 * @property string $id
 * @property string $date_time
 * @property string $discount_avg
 * @property string $total_wait_capital
 * @property string $total_order
 */
class AgDiscountAvg extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgDiscountAvg the static model class
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
		return 'ag_discount_avg';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('date_time', 'required'),
			array('discount_avg, total_wait_capital', 'length', 'max'=>11),
			array('total_order', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, date_time, discount_avg, total_wait_capital, total_order', 'safe', 'on'=>'search'),
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
			'date_time' => 'Date Time',
			'discount_avg' => 'Discount Avg',
			'total_wait_capital' => 'Total Wait Capital',
			'total_order' => 'Total Order',
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
		$criteria->compare('date_time',$this->date_time,true);
		$criteria->compare('discount_avg',$this->discount_avg,true);
		$criteria->compare('total_wait_capital',$this->total_wait_capital,true);
		$criteria->compare('total_order',$this->total_order,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}