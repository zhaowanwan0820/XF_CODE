<?php
/**
 * ModelTask
 * 根据库中表生成model对象
 * php init.php model main DB_NAME
 *
 * Created by guweigang@, Updated by wangjiansong@
 * @package default
 */
class ModelTask extends \Phalcon\CLI\Task
{
    public function mainAction($args = array())
    {
        $db = isset($args[0]) ? $args[0] : 'firstp2p';
        $modelClassOrigin = <<<'EOT'
<?php
namespace NCFGroup\Ptp\models<<<dbModel>>>;

use NCFGroup\Common\Library\Date\XDateTime;
use NCFGroup\Common\Extensions\Base\ModelBaseNoTime;

class <<<className>>> extends ModelBaseNoTime
{

    //BEGIN PROPERTY

    //END PROPERTY

    public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE

        //END DEFAULT_VALUE
    }

    public function initialize()
    {
        parent::initialize();
        $this->setReadConnectionService('<<<db>>>_r');
        $this->setWriteConnectionService('<<<db>>>');
    }

    public function columnMap()
    {
        return <<<columnMap>>>;
    }

    public function getSource()
    {
        return "<<<table>>>";
    }
}
EOT;
        if ($db !== 'firstp2p') {
            $dir = dirname(__DIR__)."/models/".$db."/";
            $dbModel = '\\'.$db;
        } else {
            $dir = dirname(__DIR__)."/models/";
            $dbModel = '';
        }
        if(!is_dir($dir)) mkdir($dir, 0777, true);

        //$db = 'firstp2p';
        $connection = getDI()->get($db);
        $tables = $connection->listTables();
        foreach($tables as $table) {
            $className = preg_replace_callback('/_(\w)/', function ($matches) {return ucfirst($matches[1]);}, $table);
            $className = ucfirst($className);
            $fileName = $dir. $className . ".php";
            if(is_file($fileName)) {
                $modelClass = file_get_contents($fileName);
                $pattern = "#public function columnMap[\(\).\s]+{[\(\),'\s_=>;\w]+}#";
                $replace = <<<METHOD
public function columnMap()
    {
        return <<<columnMap>>>;
    }
METHOD;
                $modelClass = preg_replace($pattern, $replace, $modelClass);

                $defaultReplace = <<<DEFAULTVAL
public function onConstruct()
    {
        //BEGIN DEFAULT_VALUE

        //END DEFAULT_VALUE
DEFAULTVAL;
                $findDefaultPattern = "#public function onConstruct[\(\).\s]+{[$->\s_;\w—]+END DEFAULT_VALUE#";
                preg_match($findDefaultPattern, $modelClass, $matches);
                if (empty($matches)) {
                    $findDefaultPattern = "#public function onConstruct[\(\).\s]+{#";
                }
                $modelClass = preg_replace($findDefaultPattern, $defaultReplace, $modelClass);
            } else {
                $modelClass = $modelClassOrigin;
            }

            $property = array();
            $columns = array();
            $defaults = array();
            $columnsDefaultMap = $this->getDefaultValuesMap($connection->fetchAll("DESC $table"));
            foreach($connection->describeColumns($table) as $columnObj) {
                $columnName = $columnObj->getName();
                $columns[] = $columnName;
                $columnType = $this->getTypeString($columnObj->getType());
                if ($columnObj->isNotNull() && $columnsDefaultMap[$columnName] !== null) {
                    $keyName = preg_replace_callback('/_(\w)/', function($matches) { return ucfirst($matches[1]); }, $columnName);
                    if ($columnsDefaultMap[$columnName] == 'CURRENT_TIMESTAMP') {
                        $defaults[] = "        \$this->".$keyName." = XDateTime::now();";
                    } else {
                        $defaults[] = "        \$this->".$keyName." = "
                            .var_export($columnsDefaultMap[$columnName], true).";";
                    }
                }
                $property[] = <<<EOT

    /**
     *
     * @var {$columnType}
     */
    public \${$columnName};

EOT;
            }

            $columnMap = array();
            $columnMap[] = "array(";
            foreach ($columns as $column) {
                $columnMap[] = "            '$column' => '"
                    .preg_replace_callback('/_(\w)/', function($matches) { return ucfirst($matches[1]); }, $column)
                    ."',";
            }
            $columnMap[] = "        )";

            $tmpClass = str_replace(array('<<<className>>>', '<<<dbModel>>>', '<<<db>>>', '<<<table>>>', '<<<columnMap>>>'), array($className, $dbModel, $db, $table, implode("\n", $columnMap)), $modelClass);

            $tmpClass = preg_replace_callback('#(    //BEGIN PROPERTY)([\s_$;\*/@\w]+)(    //END PROPERTY)#', function($matches) use ($property) {
                    return $matches[1] . PHP_EOL . join(PHP_EOL, $property) . PHP_EOL . $matches[3];
                }, $tmpClass);
            $tmpClass = preg_replace_callback('#(        //BEGIN DEFAULT_VALUE)([\s_$;\*/@\w]+)(        //END DEFAULT_VALUE)#', function($matches) use ($defaults) {
                    return $matches[1] . PHP_EOL . join(PHP_EOL, $defaults) . PHP_EOL . $matches[3];
                }, $tmpClass);
            file_put_contents($fileName, $tmpClass);
        }
    }

    private function getTypeString($type)
    {
        /**
           integer TYPE_INTEGER
           integer TYPE_DATE
           integer TYPE_VARCHAR
           integer TYPE_DECIMAL
           integer TYPE_DATETIME
           integer TYPE_CHAR
           integer TYPE_TEXT
           integer TYPE_FLOAT
           integer TYPE_BOOLEAN
           integer TYPE_DOUBLE
        */
        switch($type) {
            case \Phalcon\Db\Column::TYPE_INTEGER:
                return "integer";
            case \Phalcon\Db\Column::TYPE_DATE:
                return "date";
            case \Phalcon\Db\Column::TYPE_CHAR:
            case \Phalcon\Db\Column::TYPE_TEXT:
            case \Phalcon\Db\Column::TYPE_VARCHAR:
                return "string";
            case \Phalcon\Db\Column::TYPE_DATETIME:
                return "datetime";
            case \Phalcon\Db\Column::TYPE_FLOAT:
            case \Phalcon\Db\Column::TYPE_DOUBLE:
            case \Phalcon\Db\Column::TYPE_DECIMAL:
                return "float";
            case \Phalcon\Db\Column::TYPE_BOOLEAN:
                return "bool";
            default:
                return "unknown";
        }
    }

    private function getDefaultValuesMap($columns)
    {
        $ret = array();
        foreach ($columns as $item) {
            $ret[$item['Field']] = $item['Default'];
        }
        return $ret;
    }
}
