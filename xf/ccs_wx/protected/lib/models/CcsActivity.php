<?php

/**
 * This is the model class for table "ccs_activity".
 *
 * The followings are the available columns in table 'ccs_activity':
 * @property integer $id
 * @property integer $name
 * @property integer $content
 * @property integer $members
 * @property integer $type
 * @property integer $award_type
 * @property integer $award_couponslot
 * @property integer $award_trigger
 * @property integer $status
 * @property integer $addtime
 * @property integer $starttimr
 * @property integer $endtime
 */
class CcsActivity extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CcsActivity the static model class
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
		return Yii::app()->ccsdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ccs_activity';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name', 'required'),
			array('members, type, award_type, status, addtime, starttime, endtime', 'numerical', 'integerOnly'=>true),
			array('content', 'length', 'max'=>100),
			array('name', 'length', 'max'=>20),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, content, members, award_couponslot, award_trigger, type, award_type, status, addtime, starttime, endtime', 'safe', 'on'=>'search'),
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
			'name' => 'name',
			'content' => 'content',
			'members' => 'members',
			'award_couponslot' => 'award_couponslot',
			'award_trigger' => 'award_trigger',
			'type' => 'type',
			'award_type' => 'award_type',
			'status' => 'status',
			'addtime' => 'addtime',
			'starttime' => 'starttime',
			'endtime' => 'endtime',
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
		$criteria->compare('name',$this->name,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('members',$this->members);
		$criteria->compare('award_couponslot',$this->award_couponslot);
		$criteria->compare('award_trigger',$this->award_trigger);
		$criteria->compare('award_type',$this->award_type);
		$criteria->compare('type',$this->type);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('status',$this->status);
		$criteria->compare('starttime',$this->starttime);
		$criteria->compare('endtime',$this->endtime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}