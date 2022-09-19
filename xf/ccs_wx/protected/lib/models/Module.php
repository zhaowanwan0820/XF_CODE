<?php

/**
 * This is the model class for table "dw_module".
 *
 * The followings are the available columns in table 'dw_module':
 * @property integer $module_id
 * @property string $code
 * @property string $name
 * @property string $description
 * @property string $default_field
 * @property string $content
 * @property string $version
 * @property string $author
 * @property string $date
 * @property integer $status
 * @property string $type
 * @property integer $order
 * @property integer $fields
 * @property string $purview
 * @property string $remark
 * @property integer $issent
 * @property string $title_name
 * @property integer $onlyone
 * @property string $index_tpl
 * @property string $list_tpl
 * @property string $content_tpl
 * @property string $search_tpl
 * @property integer $article_status
 * @property integer $visit_type
 * @property string $addtime
 * @property string $addip
 */
class Module extends DwActiveRecord
{
    public $dbname = 'dwdb';
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Module the static model class
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
		return 'dw_module';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status, order, fields, issent, onlyone, article_status, visit_type', 'numerical', 'integerOnly'=>true),
			array('code, name, author, type, index_tpl, list_tpl, content_tpl, addtime, addip', 'length', 'max'=>50),
			array('description', 'length', 'max'=>255),
			array('default_field', 'length', 'max'=>200),
			array('version', 'length', 'max'=>10),
			array('date', 'length', 'max'=>20),
			array('title_name, search_tpl', 'length', 'max'=>100),
			array('content, purview, remark', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('module_id, code, name, description, default_field, content, version, author, date, status, type, order, fields, purview, remark, issent, title_name, onlyone, index_tpl, list_tpl, content_tpl, search_tpl, article_status, visit_type, addtime, addip', 'safe', 'on'=>'search'),
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
			'module_id' => 'Module',
			'code' => 'Code',
			'name' => 'Name',
			'description' => 'Description',
			'default_field' => 'Default Field',
			'content' => 'Content',
			'version' => 'Version',
			'author' => 'Author',
			'date' => 'Date',
			'status' => 'Status',
			'type' => 'Type',
			'order' => 'Order',
			'fields' => 'Fields',
			'purview' => 'Purview',
			'remark' => 'Remark',
			'issent' => 'Issent',
			'title_name' => 'Title Name',
			'onlyone' => 'Onlyone',
			'index_tpl' => 'Index Tpl',
			'list_tpl' => 'List Tpl',
			'content_tpl' => 'Content Tpl',
			'search_tpl' => 'Search Tpl',
			'article_status' => 'Article Status',
			'visit_type' => 'Visit Type',
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

		$criteria->compare('module_id',$this->module_id);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('default_field',$this->default_field,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('version',$this->version,true);
		$criteria->compare('author',$this->author,true);
		$criteria->compare('date',$this->date,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('type',$this->type,true);
		$criteria->compare('order',$this->order);
		$criteria->compare('fields',$this->fields);
		$criteria->compare('purview',$this->purview,true);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('issent',$this->issent);
		$criteria->compare('title_name',$this->title_name,true);
		$criteria->compare('onlyone',$this->onlyone);
		$criteria->compare('index_tpl',$this->index_tpl,true);
		$criteria->compare('list_tpl',$this->list_tpl,true);
		$criteria->compare('content_tpl',$this->content_tpl,true);
		$criteria->compare('search_tpl',$this->search_tpl,true);
		$criteria->compare('article_status',$this->article_status);
		$criteria->compare('visit_type',$this->visit_type);
		$criteria->compare('addtime',$this->addtime,true);
		$criteria->compare('addip',$this->addip,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}