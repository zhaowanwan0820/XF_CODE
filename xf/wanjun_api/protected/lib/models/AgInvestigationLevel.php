<?php

/**
 * This is the model class for table "ag_investigation_level".
 *
 * The followings are the available columns in table 'ag_investigation_level':
 * @property string $level_id
 * @property string $level_name
 * @property string $start_score
 * @property string $end_score
 * @property string $add_time
 * @property string $update_time
 */
class AgInvestigationLevel extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgInvestigationLevel the static model class
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
		return 'ag_investigation_level';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('level_name', 'length', 'max'=>255),
			array('start_score, end_score, add_time, update_time', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('level_id, level_name, start_score, end_score, add_time, update_time', 'safe', 'on'=>'search'),
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
			'level_id' => 'Level',
			'level_name' => 'Level Name',
			'start_score' => 'Start Score',
			'end_score' => 'End Score',
			'add_time' => 'Add Time',
			'update_time' => 'Update Time',
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

		$criteria->compare('level_id',$this->level_id,true);
		$criteria->compare('level_name',$this->level_name,true);
		$criteria->compare('start_score',$this->start_score,true);
		$criteria->compare('end_score',$this->end_score,true);
		$criteria->compare('add_time',$this->add_time,true);
		$criteria->compare('update_time',$this->update_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}