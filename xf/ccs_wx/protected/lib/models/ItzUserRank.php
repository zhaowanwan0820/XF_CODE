<?php

/**
 * This is the model class for table "itz_user_rank".
 *
 * The followings are the available columns in table 'itz_user_rank':
 * @property integer $id
 * @property string $name
 * @property integer $rank
 * @property integer $start_point
 * @property integer $end_point
 * @property string $pic_url
 * @property string $remark
 * @property integer $addtime
 * @property integer $updatetime
 * @property integer $admin_id
 * @property integer $admin_name
 */
class ItzUserRank extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return ItzUserRank the static model class
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
		return Yii::app()->dwdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'itz_user_rank';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('updatetime', 'required'),
			array('rank, start_point, end_point, admin_id,addtime, updatetime', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>150),
			array('pic_url, remark', 'length', 'max'=>200),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, rank, start_point, end_point, pic_url, remark, admin_name, admin_id, addtime, updatetime', 'safe', 'on'=>'search'),
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
			'rank' => 'Rank',
			'start_point' => 'Start Point',
			'end_point' => 'End Point',
			'pic_url' => 'Pic Url',
			'remark' => 'Remark',
			'addtime' => 'Addtime',
			'updatetime' => 'Updatetime',
			'admin_name' => 'Admin name',
			'admin_id' => 'Admin ID',
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
		$criteria->compare('rank',$this->rank);
		$criteria->compare('start_point',$this->start_point);
		$criteria->compare('end_point',$this->end_point);
		$criteria->compare('pic_url',$this->pic_url,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('addtime',$this->addtime);
		$criteria->compare('updatetime',$this->updatetime);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}