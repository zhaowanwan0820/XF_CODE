<?php

/**
 * This is the model class for table "dw_luckydraw_event".
 *
 * The followings are the available columns in table 'dw_luckydraw_event':
 * @property string $id
 * @property string $nid
 * @property integer $status
 * @property string $type
 * @property string $name
 * @property string $expirtdate
 * @property string $credit_value
 * @property integer $cycle
 * @property string $interval
 * @property integer $max_chance
 * @property string $template
 * @property string $description
 * @property integer $addtime
 * @property string $addip
 */
class LuckydrawEvent extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return LuckydrawEvent the static model class
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
		return 'dw_luckydraw_event';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, name, template, description', 'required'),
			array('status, cycle, max_chance, addtime', 'numerical', 'integerOnly'=>true),
			array('nid', 'length', 'max'=>50),
			array('type', 'length', 'max'=>25),
			array('name, template', 'length', 'max'=>256),
			array('expirtdate, credit_value, interval', 'length', 'max'=>11),
			array('addip', 'length', 'max'=>15),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, nid, status, type, name, expirtdate, credit_value, cycle, interval, max_chance, template, description, addtime, addip', 'safe', 'on'=>'search'),
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
			'nid' => 'Nid',
			'status' => 'Status',
			'type' => 'Type',
			'name' => 'Name',
			'expirtdate' => 'Expirtdate',
			'credit_value' => 'Credit Value',
			'cycle' => 'Cycle',
			'interval' => 'Interval',
			'max_chance' => 'Max Chance',
			'template' => 'Template',
			'description' => 'Description',
			'addtime' => 'Addtime',
			'addip' => 'Addip',
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
		$criteria->compare('nid',$this->nid,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('expirtdate',$this->expirtdate,true);
		$criteria->compare('credit_value',$this->credit_value,true);
		$criteria->compare('cycle',$this->cycle);
		$criteria->compare('interval',$this->interval,true);
		$criteria->compare('max_chance',$this->max_chance);
		$criteria->compare('template',$this->template,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}