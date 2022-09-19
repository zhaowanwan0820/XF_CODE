<?php
require(dirname(__FILE__) . '/../app/init.php');

class FormatData {

    public $db;

    public $config = array();

    public $tableName = "";

    public function __construct($tableName, $config) {
        $this->config = $config;
        $this->db = $GLOBALS["db"];
        $this->tableName = DB_PREFIX . $tableName;
    }

    public function process() {

        $total = $this->db->getOne("SELECT COUNT(*) FROM " . $this->tableName);
        $this->_log($this->tableName ." TOTAL:$total");
        $start = 0;
        $limit = 10000;
        $fieldStr = "id," . implode(",", array_keys($this->config));

        while ($start <= $total) {
            $sql = "SELECT $fieldStr FROM " . $this->tableName . " LIMIT $start, $limit";
            $result = $this->db->query($sql);

            while($result && $data = mysql_fetch_assoc($result)) {
                $setFieldStr = "id = " . $data['id'];

                foreach ($data as $key => $value) {
                    if ($key == "id") {
                        continue;
                    }

                    foreach ($this->config[$key] as $process) {
                        $function = $process["process"];
                        $char = $process["char"];

                        if ($function != "trim") {
                            //TODO replace
                            $value = $function($char, "", $value);
                        } else {
                            $value = $function($value, $char);
                        }
                    }
                    $data[$key] = $value;
                    $setFieldStr .= ", $key = '$value'";
                }

                // update process
                $res = $this->db->query("UPDATE " . $this->tableName . " SET $setFieldStr WHERE id = " . $data['id']);

                if ($res === false) {
                    $this->_log("FAILED ID:{$data["id"]}");
                } else if ($this->db->affected_rows() > 0) {
                    $this->_log("SUCCESS ID:{$data["id"]}");
                }
            }
            $this->_log($this->tableName . " DONE");
            unset($result);
            $start+=10000;
        }
    }

    private function _log($msg) {
        file_put_contents(dirname(__FILE__) . "../../log/format_data_" . date("Ymd") . ".log", $msg . PHP_EOL, FILE_APPEND);
    }

}
// update config
$tables = array(
                 'user' => array(
                                    'real_name' => array (
                                                             array ('process' => "trim", "char" => " "),
                                                          ),
                                    'idno' => array (
                                                             array ('process' => "trim", "char" => " "),
                                                          ),
                                    'mobile' => array (
                                                             array ('process' => "trim", "char" => " "),
                                                          ),
                                ),

                 'user_bankcard' => array(
                                    'card_name' => array (
                                                             array ('process' => "trim", "char" => " "),
                                                          ),
                                    'bankcard' => array (
                                                             array ('process' => "str_replace", "char" => " "),
                                                          ),
                                ),

);

// do the real process
$tableName = $argv[1];
if (!$tableName) {
    echo "Table is needed! Use it like 'php format_data.php user  or php format_data.php all'" . PHP_EOL;
    exit;
}

if ($tableName == "all") {
    $processTables = $tables;
} else {
    $processTables = array( $tableName => $tables[$tableName]);;
}

if (!current($processTables)) {
    echo "Table not found in config" . PHP_EOL;
    exit;
}

foreach ($processTables as $table => $tableConfig) {
    $fixData = new FormatData($table, $tableConfig);
    $fixData->process();
}
