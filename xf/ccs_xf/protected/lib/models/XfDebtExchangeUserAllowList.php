<?php

/**
 * This is the model class for table "xf_debt_exchange_user_allow_list".
 *
 * The followings are the available columns in table 'xf_debt_exchange_user_allow_list':
 * @property string $id
 * @property string $user_id
 * @property integer $appid
 * @property integer $status
 * @property string $remark
 * @property string $created_at
 * @property string $update_at
 * @property string $upload_id
 */
class XfDebtExchangeUserAllowList extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return XfDebtExchangeUserAllowList the static model class
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
		return 'xf_debt_exchange_user_allow_list';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('appid, status', 'numerical', 'integerOnly'=>true),
			array('user_id', 'length', 'max'=>30),
			array('remark', 'length', 'max'=>500),
			array('created_at, update_at, upload_id', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, user_id, appid, status, remark, created_at, update_at, upload_id', 'safe', 'on'=>'search'),
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
			'appid' => 'Appid',
			'status' => 'Status',
			'remark' => 'Remark',
			'created_at' => 'Created At',
			'update_at' => 'Update At',
			'upload_id' => 'Upload',
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
		$criteria->compare('user_id',$this->user_id,true);
		$criteria->compare('appid',$this->appid);
		$criteria->compare('status',$this->status);
		$criteria->compare('remark',$this->remark,true);
		$criteria->compare('created_at',$this->created_at,true);
		$criteria->compare('update_at',$this->update_at,true);
		$criteria->compare('upload_id',$this->upload_id,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}


    public $status_cn = [
        0=>'待审核',
        1=>'已生效',
        2=>'已解除',
        3=>'已撤销',
    ];

    public  function getUserList($params)
    {
        $where = ' 1 ';
        if(!empty($params['upload_id'])){
            $where .= " and l.upload_id =".$params['upload_id'];
        }
        if(!empty($params['appid'])){
            $where .= " and l.appid =".$params['appid'];
        }
        if(isset($params['status']) ){
            $where .= " and l.status =".$params['status'];
        }
        if(!empty($params['user_id'])){
            $where .= " and l.user_id =".$params['user_id'];
        }
        if(!empty($params['mobile'])){
            $where .= " and  u.mobile='".GibberishAESUtil::enc($params['mobile'], Yii::app()->c->idno_key)."'";
        }
        $_file=[];
        $countFile = self::model()->countBySql('select count(1) from '.$this->tableName().' as l left join firstp2p_user as u on l.user_id=u.id where '.$where);
        if ($countFile > 0) {
            $page = $params['page'] ?: 1;
            $pageSize = $params['pageSize'] ?: 10;
            $offset = ($page - 1) * $pageSize;
            $sql = "select l.*,u.mobile,u.real_name from ".$this->tableName()." as l left join firstp2p_user as u on l.user_id=u.id  where {$where} order by l.id desc  LIMIT {$offset} , {$pageSize} ";
            $_file = Yii::app()->fdb->createCommand($sql)->queryAll();
            foreach ($_file as &$item){
                $item['created_at']=date('Y-m-d H:i:s',$item['created_at']);
                $item['update_at']=$item['update_at']?date('Y-m-d H:i:s',$item['update_at']):'';
                $item['status_cn'] = $this->status_cn[$item['status']];
                $item['mobile'] = GibberishAESUtil::dec($item['mobile'], Yii::app()->c->idno_key);
            }
        }
        return ['countNum' => $countFile, 'list' => $_file];
    }
}