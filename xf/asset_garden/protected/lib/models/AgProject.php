<?php

/**
 * This is the model class for table "ag_project".
 *
 * The followings are the available columns in table 'ag_project':
 * @property string $id
 * @property string $name
 * @property string $description
 * @property integer $platform_id
 * @property string $apr
 * @property integer $style
 * @property integer $value_date
 * @property integer $due_date
 * @property string $borrower_ids
 * @property integer $type_id
 */
class AgProject extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgProject the static model class
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
		return 'ag_project';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('platform_id, style, value_date, due_date, type_id', 'numerical', 'integerOnly'=>true),
			array('name, borrower_ids', 'length', 'max'=>127),
			array('apr', 'length', 'max'=>11),
			array('description', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, description, platform_id, apr, style, value_date, due_date, borrower_ids, type_id', 'safe', 'on'=>'search'),
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
			'description' => 'Description',
			'platform_id' => 'Platform',
			'apr' => 'Apr',
			'style' => 'Style',
			'value_date' => 'Value Date',
			'due_date' => 'Due Date',
			'borrower_ids' => 'Borrower Ids',
			'type_id' => 'Type',
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
		$criteria->compare('description',$this->description,true);
		$criteria->compare('platform_id',$this->platform_id);
		$criteria->compare('apr',$this->apr,true);
		$criteria->compare('style',$this->style);
		$criteria->compare('value_date',$this->value_date);
		$criteria->compare('due_date',$this->due_date);
		$criteria->compare('borrower_ids',$this->borrower_ids,true);
		$criteria->compare('type_id',$this->type_id);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}