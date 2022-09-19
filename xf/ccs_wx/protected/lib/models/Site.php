<?php

/**
 * This is the model class for table "dw_site".
 *
 * The followings are the available columns in table 'dw_site':
 * @property string $site_id
 * @property string $code
 * @property string $name
 * @property string $nid
 * @property integer $pid
 * @property string $rank
 * @property string $url
 * @property string $aurl
 * @property string $isurl
 * @property integer $order
 * @property integer $status
 * @property string $style
 * @property string $litpic
 * @property string $content
 * @property string $list_name
 * @property string $content_name
 * @property string $sitedir
 * @property string $visit_type
 * @property string $index_tpl
 * @property string $list_tpl
 * @property string $content_tpl
 * @property string $title
 * @property string $keywords
 * @property string $description
 * @property string $user_id
 * @property integer $sitemap
 * @property string $addtime
 * @property string $addip
 */
class Site extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Site the static model class
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
		return 'dw_site';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('pid, order, status, sitemap', 'numerical', 'integerOnly'=>true),
			array('code, nid, rank, litpic, addtime, addip', 'length', 'max'=>50),
			array('name, url, aurl', 'length', 'max'=>255),
			array('isurl, style', 'length', 'max'=>2),
			array('list_name, content_name, sitedir, visit_type', 'length', 'max'=>200),
			array('index_tpl, list_tpl, content_tpl, title, keywords, description', 'length', 'max'=>250),
			array('user_id', 'length', 'max'=>11),
			array('content', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('site_id, code, name, nid, pid, rank, url, aurl, isurl, order, status, style, litpic, content, list_name, content_name, sitedir, visit_type, index_tpl, list_tpl, content_tpl, title, keywords, description, user_id, sitemap, addtime, addip', 'safe', 'on'=>'search'),
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
			'site_id' => 'Site',
			'code' => 'Code',
			'name' => 'Name',
			'nid' => 'Nid',
			'pid' => 'Pid',
			'rank' => 'Rank',
			'url' => 'Url',
			'aurl' => 'Aurl',
			'isurl' => 'Isurl',
			'order' => 'Order',
			'status' => 'Status',
			'style' => 'Style',
			'litpic' => 'Litpic',
			'content' => 'Content',
			'list_name' => 'List Name',
			'content_name' => 'Content Name',
			'sitedir' => 'Sitedir',
			'visit_type' => 'Visit Type',
			'index_tpl' => 'Index Tpl',
			'list_tpl' => 'List Tpl',
			'content_tpl' => 'Content Tpl',
			'title' => 'Title',
			'keywords' => 'Keywords',
			'description' => 'Description',
			'user_id' => 'User',
			'sitemap' => 'Sitemap',
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

		$criteria->compare('site_id',$this->site_id,true);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('nid',$this->nid,true);
		$criteria->compare('pid',$this->pid);
		$criteria->compare('rank',$this->rank,true);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('aurl',$this->aurl,true);
		$criteria->compare('isurl',$this->isurl,true);
		$criteria->compare('order',$this->order);
		$criteria->compare('status',$this->status);
		$criteria->compare('style',$this->style,true);
		$criteria->compare('litpic',$this->litpic,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('list_name',$this->list_name,true);
		$criteria->compare('content_name',$this->content_name,true);
		$criteria->compare('sitedir',$this->sitedir,true);
		$criteria->compare('visit_type',$this->visit_type,true);
		$criteria->compare('index_tpl',$this->index_tpl,true);
		$criteria->compare('list_tpl',$this->list_tpl,true);
		$criteria->compare('content_tpl',$this->content_tpl,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('keywords',$this->keywords,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('sitemap',$this->sitemap);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}