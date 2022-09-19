<?php

/**
 * This is the model class for table "dw_cache".
 *
 * The followings are the available columns in table 'dw_cache':
 * @property integer $id
 * @property string $date
 * @property integer $user_num
 * @property integer $user_online_num
 * @property string $user_online_time
 * @property string $last_user
 * @property integer $bbs_first_visit
 * @property integer $bbs_topics_num
 * @property integer $bbs_posts_num
 * @property integer $bbs_today_topics
 * @property integer $bbs_today_posts
 * @property integer $bbs_yesterday_topics
 * @property integer $bbs_yesterday_posts
 * @property integer $bbs_most_topics
 * @property integer $bbs_most_posts
 * @property string $borrow_account
 * @property string $borrow_success
 * @property integer $borrow_num
 * @property string $borrow_successnum
 */
class Cache extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Cache the static model class
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
		return 'dw_cache';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('user_num, user_online_num, bbs_first_visit, bbs_topics_num, bbs_posts_num, bbs_today_topics, bbs_today_posts, bbs_yesterday_topics, bbs_yesterday_posts, bbs_most_topics, bbs_most_posts, borrow_num', 'numerical', 'integerOnly'=>true),
			array('date, last_user, borrow_success', 'length', 'max'=>20),
			array('user_online_time', 'length', 'max'=>30),
			array('borrow_account, borrow_successnum', 'length', 'max'=>11),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, date, user_num, user_online_num, user_online_time, last_user, bbs_first_visit, bbs_topics_num, bbs_posts_num, bbs_today_topics, bbs_today_posts, bbs_yesterday_topics, bbs_yesterday_posts, bbs_most_topics, bbs_most_posts, borrow_account, borrow_success, borrow_num, borrow_successnum', 'safe', 'on'=>'search'),
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
			'date' => 'Date',
			'user_num' => 'User Num',
			'user_online_num' => 'User Online Num',
			'user_online_time' => 'User Online Time',
			'last_user' => 'Last User',
			'bbs_first_visit' => 'Bbs First Visit',
			'bbs_topics_num' => 'Bbs Topics Num',
			'bbs_posts_num' => 'Bbs Posts Num',
			'bbs_today_topics' => 'Bbs Today Topics',
			'bbs_today_posts' => 'Bbs Today Posts',
			'bbs_yesterday_topics' => 'Bbs Yesterday Topics',
			'bbs_yesterday_posts' => 'Bbs Yesterday Posts',
			'bbs_most_topics' => 'Bbs Most Topics',
			'bbs_most_posts' => 'Bbs Most Posts',
			'borrow_account' => 'Borrow Account',
			'borrow_success' => 'Borrow Success',
			'borrow_num' => 'Borrow Num',
			'borrow_successnum' => 'Borrow Successnum',
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
		$criteria->compare('date',$this->date,true);
		$criteria->compare('user_num',$this->user_num);
		$criteria->compare('user_online_num',$this->user_online_num);
		$criteria->compare('user_online_time',$this->user_online_time,true);
		$criteria->compare('last_user',$this->last_user,true);
		$criteria->compare('bbs_first_visit',$this->bbs_first_visit);
		$criteria->compare('bbs_topics_num',$this->bbs_topics_num);
		$criteria->compare('bbs_posts_num',$this->bbs_posts_num);
		$criteria->compare('bbs_today_topics',$this->bbs_today_topics);
		$criteria->compare('bbs_today_posts',$this->bbs_today_posts);
		$criteria->compare('bbs_yesterday_topics',$this->bbs_yesterday_topics);
		$criteria->compare('bbs_yesterday_posts',$this->bbs_yesterday_posts);
		$criteria->compare('bbs_most_topics',$this->bbs_most_topics);
		$criteria->compare('bbs_most_posts',$this->bbs_most_posts);
		$criteria->compare('borrow_account',$this->borrow_account,true);
		$criteria->compare('borrow_success',$this->borrow_success,true);
		$criteria->compare('borrow_num',$this->borrow_num);
		$criteria->compare('borrow_successnum',$this->borrow_successnum,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}