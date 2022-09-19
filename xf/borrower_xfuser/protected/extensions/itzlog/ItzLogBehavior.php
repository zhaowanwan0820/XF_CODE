<?php
/**
 * $RCSfile$ FLogBehavior.php
 * @touch date 2011年08月26日 星期五 16时53分54秒
 * @author work(<kuangjun@jike.com>)
 * @license http://www.zend.com/license/3_0.txt   PHP License 3.0
 * @version $Id: FLogBehavior.php 3142 2011-12-22 09:37:47Z kuangjun@jike.com $ 
 *
 * 自动记录日志工具类, 适用于继承于CActiveRecord的model类的修改日志记录
 * 需要在model类中声明此behavior:
 * <pre>
 *   public function behaviors(){
 *       return array(
 *           'LogBehavior' => array(
 *               'class'               => 'LogBehavior',
 *               'tableName'           => 'data_log',    //日志表名, 如果不指定则默认为'log',
 *               'autoCreateLogTable'  => true,            //是否自动创建日志表,如果为真,第一次保存日志是如果没有这个表,则自动新建, 默认为true
 *               'attributeConvertion' => array(        //表中的某些字段需要记录成可读形式,必须进行转换, 支持以下两种格式
 *                   'type_id'   =>  array('netware_acc_type','name'),    //array('table_name','column'), 将从table_name中根据ID取column的值
 *                   'idc_id'    =>  array('idc_info','name'),
 *                   'status'    =>  array(1=>'在线',2=>'库存'),        //直接根据数组中的对应关系进行转换
 *               ),
 *               'fieldMap'    =>    array(                    //实际的已使用日志表字段可能跟data_log的不一样, 那么可以进行映射一下 :)
 *                   'user_id'        =>    'user',
 *                   'object_type'    =>    'tablename',
 *                   'object_id'        =>    'id',
 *                   'operation'        =>    'operate',
 *                   'content'        =>    'desc',
 *                   'field'            =>    'field_name',    //fieldLevelLog  每个字段的更改记录一条记录, 必须指定field, old, new 的字段名
 *                   'old'            =>    'old_record',
 *                   'new'            =>    'new_record',
 *               ),
 *           ),  
 *       );  
 *   }   
 * </pre>
 * 
 * 使用:
 *<pre>
 *  $device = netware_small_device::model()->findByPk(2);
 *  $device->type_id = 22;
 *  $device->remark  = '修改测试';
 *  $device->log(array('operation'=>'修改测试#22'))->save();
 *</pre> 
 *     生成的日志记录为:
 *                id: 3
 *              time: 2011-08-28 17:11:05
 *           user_id: 13333
 *        object_type:host 
 *          object_id: 2
 *                ip: 1.22.247.45
 *         operation: 修改服务器#22
 *           content: [类型:abc=>ttt],[备注:test=>修改测试]
*/
class ItzLogBehavior extends CActiveRecordBehavior {
    /**
     * @var 
     */
    public $oldAttributes=null;

    public $tableName = 'log';
    public $attributeConvertion = array();
    public $autoCreateLogTable = true;
    public $fieldMap = array();
    
    /**
     * @param params array
     * 可以指定日志的几个特定字段:
     * array(
     *     'user_id' =>$user_id,          //默认为Yii::app()->user->id, 
     *                                 !!NOTICE!! 在console下和remote调用时可能会有问题,必须指定user_id 
     *     'operation'=>$operation,     //标示当前的动作, 默认为空
     *     'content'=>$content,           //默认会自动生成更新内容, 如果指定, 则附加在后面    
     *     )
     * @return owner
     */
    public function log($params){
        $owner = $this->getOwner();
        $logContent = array();
        if($this->oldAttributes === null)
            $this->oldAttributes = array_fill_keys(array_keys($owner->attributes), '');
            
        foreach($owner->attributes as $key=>$value){
            if($value !== $this->oldAttributes[$key]){
                $logContent[$key] = $this->getAttributeLog($key, $this->oldAttributes[$key], $value);
            }
        }
        $this->saveLog($logContent, $params);
        return $owner;
    }

    protected function getDbConnection(){
        return Yii::app()->db;
    }

    protected function getLogTableFields(){
        $fields = array('user_id','object_type','object_id','operation','content','ip','time');
        return array_merge(array_combine($fields, $fields), $this->fieldMap);
    }

    protected function getLogContentString($contentArray){
        $attributeLabels = $this->getOwner()->attributeLabels();
        $contents = array();
        foreach($contentArray as $key => $content){
            list($old, $new) = $content;
            if(isset($attributeLabels[$key])){
                $contents[] = "[{$attributeLabels[$key]}: {$old}=>{$new}]";
            }else{
                $contents[] = "[{$key}: {$old}=>{$new}]";
            }
        }
        return join ("\n", $contents);
    }

    protected function isFieldLevelLog(){
        return isset($this->fieldMap['field'], $this->fieldMap['new'], $this->fieldMap['old']);
    }

    protected function saveLog($content, $params){
        if(empty($content)) return;

        $owner = $this->getOwner();
        $db  = $this->getDbConnection();
        $db->setActive(true);

        $t_sql="DELETE FROM {$this->tableName} WHERE 0";
        try{
            $db->createCommand($t_sql)->execute();
        }catch(Exception $e){
            if($this->autoCreateLogTable !== true){
                Yii::log('auto log failed.' . $e->getMessage(),'error', 'db.autolog');
                return true;
            }
            $this->createLogTable($db, $this->tableName);
        }

        $f = $this->getLogTableFields();
        try{
            if($this->isFieldLevelLog()){
                foreach($content as $key=>$value){
                    list($old, $new) = $value;
                    $sql = "INSERT INTO {$this->tableName} 
                    (`{$f['user_id']}`,`{$f['object_type']}`,`{$f['object_id']}`,`{$f['operation']}`,
                    `{$f['content']}`,`{$f['ip']}`,`{$f['field']}`,`{$f['old']}`,`{$f['new']}`,`{$f['time']}`)
                    VALUES (:user_id, :object_type, :object_id, :operation, :content, :ip, :field, :old, :new, :time)";
                    $db->createCommand($sql)
                        ->bindValue(':user_id',isset($params['user_id']) ? $params['user_id'] : Yii::app()->user->id)
                        ->bindValue(':object_type',$owner->tableName())->bindValue(':object_id',$owner->primaryKey)
                        ->bindValue(':operation',isset($params['operation']) ? $params['operation'] : '')
                        ->bindValue(':content',isset($params['content']) ?  $params['content'] : '')
                        ->bindValue(':ip',Yii::app()->request->userHostAddress)->bindValue(':time', date('Y-m-d H:i:s'))
                        ->bindValue(':field', $key)->bindValue(':old', $old)->bindValue(':new', $new)->execute();
                }
            }else{
                $content = $this->getLogContentString($content);
                $sql = "INSERT INTO {$this->tableName} 
                        (`{$f['user_id']}`,`{$f['object_type']}`,`{$f['object_id']}`,`{$f['operation']}`,`{$f['content']}`,`{$f['ip']}`,`{$f['time']}`)
                    VALUES (:user_id, :object_type, :object_id, :operation, :content, :ip, :time)";
                    $db->createCommand($sql)
                        ->bindValue(':user_id',isset($params['user_id']) ? $params['user_id'] : Yii::app()->user->id)
                        ->bindValue(':object_type',$owner->tableName())->bindValue(':object_id',$owner->primaryKey)
                        ->bindValue(':operation',isset($params['operation']) ? $params['operation'] : '')->bindValue(':time', date('Y-m-d H:i:s'))
                        ->bindValue(':content',isset($params['content']) ? $content . "\n" . $params['content'] : $content)
                        ->bindValue(':ip',Yii::app()->request->userHostAddress)->execute();
            }
        }catch(Exception $e){
            Yii::log('auto log failed.' . $content . $e->getMessage(),'error', 'db.autolog');
        }
    }

    protected function createLogTable($db, $tableName){
        $sql = "
            CREATE TABLE `$tableName` (
              `id` int(11) NOT NULL AUTO_INCREMENT, `time` timestamp,
              `user_id` varchar(32) NOT NULL, `object_type` varchar(64) NOT NULL,
              `object_id` varchar(64) NOT NULL , `ip` varchar(255) NOT NULL,
              `operation` varchar(255) NOT NULL, `content` longtext NOT NULL,
              PRIMARY KEY (`id`), KEY `object_type` (`object_type`),
              KEY `object_id` (`object_id`), KEY `time` (`time`),
              KEY `user_id` (`user_id`)) DEFAULT CHARSET=utf8  
            ";
        $db->createCommand($sql)->execute();
    }

    protected function getAttributeLog($attribute, $old, $new){
        if(is_array($this->attributeConvertion)
            && array_key_exists($attribute, $this->attributeConvertion) ){
                $old = $this->convertAttributeValue($this->attributeConvertion[$attribute], $old);
                $new = $this->convertAttributeValue($this->attributeConvertion[$attribute], $new);
        }
        return array($old, $new);
    }

    protected function convertAttributeValue($convertion,  $value){
        if(array_key_exists($value, $convertion))
            return $convertion[$value];
        
        if(count($convertion) === 2 && array_values($convertion) === $convertion){
            list($convertionTable,$convertionColumn)  = $convertion;
            if(class_exists($convertionTable) && is_subclass_of($convertionTable, 'CActiveRecord')){
                $table = new $convertionTable;
                $record = $table->model()->findByPk($value);
                return $record && isset($record->{$convertionColumn}) ? $record->{$convertionColumn} : $value;
            }
        }
        return $value;
    }

    public function afterFind($event){
        $this->oldAttributes = $this->getOwner()->attributes;
    }
}
