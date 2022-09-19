<?php

/**
 * This is the model class for table "dw_user_src_type".
 *
 * The followings are the available columns in table 'dw_user_src_type':
 * @property integer $user_src_type_id
 * @property string $user_src_type_name
 * @property string $user_src_type_cname
 * @property string $user_src_type_comment
 */
class UserSrcType extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserSrcType the static model class
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
		return 'dw_user_src_type';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_src_type_name, user_src_type_cname', 'required'),
			array('user_src_type_name, user_src_type_cname, user_src_type_comment', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('user_src_type_id, user_src_type_name, user_src_type_cname, user_src_type_comment', 'safe', 'on'=>'search'),
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
			'user_src_type_id' => 'User Src Type',
			'user_src_type_name' => 'User Src Type Name',
			'user_src_type_cname' => 'User Src Type Cname',
			'user_src_type_comment' => 'User Src Type Comment',
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

		$criteria->compare('user_src_type_id',$this->user_src_type_id);
		$criteria->compare('user_src_type_name',$this->user_src_type_name,true);
		$criteria->compare('user_src_type_cname',$this->user_src_type_cname,true);
		$criteria->compare('user_src_type_comment',$this->user_src_type_comment,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}