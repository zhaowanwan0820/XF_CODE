<?php

class Enterprise extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Project the static model class
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
		return Yii::app()->reportdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'enterprise';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		return array(
			array('enterpriseid, enterprisename', 'required'),
			array('enterpriseid, enterprisename', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations(){
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'enterprisename' => '企业名称',
			'enterpriseid' => '企业ID',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		$criteria=new CDbCriteria;
		$criteria->compare('enterprisename',$this->enterprisename);
		$criteria->compare('enterpriseid',$this->enterpriseid);
		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}