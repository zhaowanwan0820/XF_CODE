<?php

/**
 * This is the model class for table "ag_qnr_questionnaire".
 *
 * The followings are the available columns in table 'ag_qnr_questionnaire':
 * @property integer $id
 * @property integer $type
 * @property string $title
 * @property string $description
 * @property integer $reply_num_type
 * @property integer $status
 * @property integer $addtime
 * @property integer $updatetime
 */
class AgQnrQuestionnaire extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return AgQnrQuestionnaire the static model class
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
		return 'ag_qnr_questionnaire';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('type, reply_num_type, status, addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>50),
			array('description', 'length', 'max'=>500),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, type, title, description, reply_num_type, status, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'type' => 'Type',
			'title' => 'Title',
			'description' => 'Description',
			'reply_num_type' => 'Reply Num Type',
			'status' => 'Status',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
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
		$criteria->compare('type',$this->type);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('reply_num_type',$this->reply_num_type);
		$criteria->compare('status',$this->status);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}