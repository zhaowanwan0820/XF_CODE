<?php

/**
 * This is the model class for table "itzsmsblacklist".
 *
 * The followings are the available columns in table 'itzsmsblacklist':
 * @property string $ids
 * @property string $phone
 * @property string $username
 * @property string $realname
 */
class UserSmsBlackList extends EMongoDocument
{
    //public $dbname = 'dwdb';
	public $mongoaddress=0; //链接的是哪个mongo地址：前台是Yii::app()->mongo2;后台是Yii::app()->mongo;
	public $phone;
	public $username;
	public $addtime;

	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return UserType the static model class
	 */

	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	
	public function collectionName()
	{
		return 'itzsmsblacklist';
	}

	/**
	 * @return array validation rules for model attributes.
	 */

	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('phone', 'length', 'max'=>45),
			array('username', 'length', 'max'=>200),
			array('addtime', 'length', 'max'=>50),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('phone, username, addtime', 'safe', 'on'=>'search'),
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
			'phone' => '电话',
			'username' => '用户名',
			'addtime' => '添加时间'
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
		
		$criteria=new EMongoCriteria;

		if(!empty($this->phone))
			$criteria->compare('phone', (String) $this->phone);

		if(!empty($this->username))
			$criteria->compare('username', (String) $this->username);
		
        if (!empty($this->addtime)) {
            $criteria->compare('addtime', array(strtotime($this->addtime), strtotime($this->addtime) + 86400));
        }

        $criteria->setSort(['addtime' => 'desc']);
		return new EMongoDataProvider($this, array(
			'criteria' =>$criteria,
			'slaveOkay' => true,
		));
	}
}