<?php

/**
 * This is the model class for table "xf_debt_exchange_special_area".
 *
 * The followings are the available columns in table 'xf_debt_exchange_special_area':
 * @property string $id
 * @property string $appid
 * @property string $name
 * @property string $code
 * @property integer $status
 * @property string $created_at
 */
class XfDebtExchangeSpecialArea extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfDebtExchangeSpecialArea the static model class
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
		return Yii::app()->fdb;
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'xf_debt_exchange_special_area';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('status', 'numerical', 'integerOnly'=>true),
			array('appid, created_at', 'length', 'max'=>10),
			array('name, code', 'length', 'max'=>30),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, appid, name, code, status, created_at', 'safe', 'on'=>'search'),
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
			'appid' => 'AppId',
			'name' => 'Name',
			'code' => 'Code',
			'status' => 'Status',
			'created_at' => 'Created At',
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

		$criteria->compare('id',$this->id,true);
		$criteria->compare('appid',$this->appid,true);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('code',$this->code,true);
		$criteria->compare('status',$this->status);
		$criteria->compare('created_at',$this->created_at,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}

    public function getList($params)
    {
        $where = ' 1 ';
        if(!empty($params['name'])){
            $where .= " and a.name ='".$params['name']."'";
        }
        if(!empty($params['appid'])){
            $where .= " and a.appid =".$params['appid'];
        }

        $fileList = [];
        $countFile = self::model()->countBySql('select count(1) from '.$this->tableName().' as a where '.$where);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select a.*,p.name as p_name from ".$this->tableName()." as a left join xf_debt_exchange_platform as p on a.appid = p.id  where {$where} order by a.id desc  LIMIT {$offset} , {$pageSize} ";

            $_file = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($_file as $item){
                $list['id']=$item['id'];
                $list['name']=$item['name'];
                $list['p_name']=$item['p_name'];
                $list['code']=$item['code'];
                //$list['buyer_uid']=$item->buyer_uid;
                $list['status_cn']=$item['status']==1?"启用":'禁用';
                $list['created_at']=date('Y-m-d H:i:s',$item['created_at']);
                $fileList[] = $list;
            }
        }
        return ['countNum' => $countFile, 'list' => $fileList];
    }
}