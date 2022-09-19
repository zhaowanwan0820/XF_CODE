<?php

/**
 * This is the model class for table "ag_qnr_answer".
 *
 * The followings are the available columns in table 'ag_qnr_answer':
 * @property integer $id
 * @property integer $user_id
 * @property integer $qstn_id
 * @property integer $qstn_type
 * @property integer $qst_id
 * @property string $batch
 * @property integer $answer
 * @property string $user_write
 * @property integer $addtime
 */
class AgQnrAnswer extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgQnrAnswer the static model class
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
		return 'ag_qnr_answer';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_id, qstn_id, qstn_type, answer_id, qst_id, answer, addtime', 'numerical', 'integerOnly'=>true),
			array('user_write', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, qstn_id, qstn_type, qst_id, batch, answer, user_write, addtime', 'safe', 'on'=>'search'),
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
			'user_id' => 'User',
			'qstn_id' => 'Qstn',
			'qstn_type' => 'Qstn Type',
			'qst_id' => 'Qst',
			'answer_id' => 'Answer Id',
			'answer' => 'Answer',
			'user_write' => 'User Write',
			'addtime' => 'Addtime',
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
		$criteria->compare('user_id',$this->user_id);
		$criteria->compare('qstn_id',$this->qstn_id);
		$criteria->compare('qstn_type',$this->qstn_type);
		$criteria->compare('qst_id',$this->qst_id);
		$criteria->compare('answer_id',$this->answer_id,true);
		$criteria->compare('answer',$this->answer);
		$criteria->compare('user_write',$this->user_write,true);
		$criteria->compare('addtime',$this->addtime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}