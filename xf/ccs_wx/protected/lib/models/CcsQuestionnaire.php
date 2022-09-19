<?php

/**
 * This is the model class for table "ccs_questionnaire".
 *
 * The followings are the available columns in table 'ccs_questionnaire':
 * @property integer $id
 * @property integer $admin_id
 * @property integer $order_id
 * @property integer $order_status
 * @property integer $type
 * @property integer $score
 * @property integer $answer1
 * @property integer $answer2
 * @property integer $answer3
 * @property integer $answer4
 * @property integer $answer5
 * @property integer $answer6
 * @property integer $answer7
 * @property integer $answer8
 * @property integer $answer9
 * @property integer $answer10
 * @property string $answers
 * @property integer $addtime
 * @property string $addip
 */
class CcsQuestionnaire extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return CcsQuestionnaire the static model class
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
		return 'ccs_questionnaire';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('addip', 'required'),
			array('admin_id, order_id, order_status, type, score, answer1, answer2, answer3, answer4, answer5, answer6, answer7, answer8, answer9, answer10, addtime', 'numerical', 'integerOnly'=>true),
			array('answers', 'length', 'max'=>400),
			array('addip', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, admin_id, order_id, order_status, type, score, answer1, answer2, answer3, answer4, answer5, answer6, answer7, answer8, answer9, answer10, answers, addtime, addip', 'safe', 'on'=>'search'),
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
			'admin_id' => 'Admin',
			'order_id' => 'Order',
			'order_status' => 'Order Status',
			'type' => 'Type',
			'score' => 'Score',
			'answer1' => 'Answer1',
			'answer2' => 'Answer2',
			'answer3' => 'Answer3',
			'answer4' => 'Answer4',
			'answer5' => 'Answer5',
			'answer6' => 'Answer6',
			'answer7' => 'Answer7',
			'answer8' => 'Answer8',
			'answer9' => 'Answer9',
			'answer10' => 'Answer10',
			'answers' => 'Answers',
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

		$criteria->compare('id',$this->id);
		$criteria->compare('admin_id',$this->admin_id);
		$criteria->compare('order_id',$this->order_id);
		$criteria->compare('order_status',$this->order_status);
		$criteria->compare('type',$this->type);
		$criteria->compare('score',$this->score);
		$criteria->compare('answer1',$this->answer1);
		$criteria->compare('answer2',$this->answer2);
		$criteria->compare('answer3',$this->answer3);
		$criteria->compare('answer4',$this->answer4);
		$criteria->compare('answer5',$this->answer5);
		$criteria->compare('answer6',$this->answer6);
		$criteria->compare('answer7',$this->answer7);
		$criteria->compare('answer8',$this->answer8);
		$criteria->compare('answer9',$this->answer9);
		$criteria->compare('answer10',$this->answer10);
		$criteria->compare('answers',$this->answers,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}