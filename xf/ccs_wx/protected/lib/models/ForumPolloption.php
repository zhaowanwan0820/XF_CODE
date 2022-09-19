<?php

/**
 * This is the model class for table "qa2_forum_polloption".
 *
 * The followings are the available columns in table 'qa2_forum_polloption':
 * @property string $polloptionid
 * @property integer $tid
 * @property integer $votes
 * @property integer $displayorder
 * @property string $polloption
 * @property string $voterids
 */
class ForumPolloption extends DwActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ForumPolloption the static model class
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
		return Yii::app()->rbbs;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_forum_polloption';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('voterids', 'required'),
			array('tid, votes, displayorder', 'numerical', 'integerOnly'=>true),
			array('polloption', 'length', 'max'=>80),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('polloptionid, tid, votes, displayorder, polloption, voterids', 'safe', 'on'=>'search'),
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
			'polloptionid' => 'Polloptionid',
			'tid' => 'Tid',
			'votes' => 'Votes',
			'displayorder' => 'Displayorder',
			'polloption' => 'Polloption',
			'voterids' => 'Voterids',
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

		$criteria->compare('polloptionid',$this->polloptionid,true);
		$criteria->compare('tid',$this->tid);
		$criteria->compare('votes',$this->votes);
		$criteria->compare('displayorder',$this->displayorder);
		$criteria->compare('polloption',$this->polloption,true);
		$criteria->compare('voterids',$this->voterids,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}