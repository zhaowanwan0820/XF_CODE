<?php
/*
 * 封装数据管理的方法
 * @author wangyiming@ucfgroup.com
 */
FP::import("libs.common.dict");
class DictionaryModel extends CommonModel {
    private $err;

    /*
     * 获取全部字典
     * @return array 字典信息
     */
    public function get_dictionary($start=0, $limit=100) {
        if ($limit>0) {
            $result = M("Dictionary")->limit("{$start}, {$limit}")->findAll();
        } else {
            $result = M("Dictionary")->findAll();
        }
        return $result;
    }

    /*
     * 获取字典总数
     * @return int
     */
    public function get_dict_count() {
        return intval(M("Dictionary")->count());
    }

    /*
     * 根据id获取字典内容
     * todo 要不要从缓存读内容
     * @param $id int id
     * @return array 字典信息
     */
    public function get_dictionary_by_id($id) {
        $result = M("Dictionary")->where("`id`='{$id}'")->find();
        $value = M("DictionaryValue")->where("`key_id`='{$id}'")->findAll();
        $result['value'] = $value;
        return $result;
    }

    /*
     * 增加一个字典项
     * todo 是否要加入增加缓存的逻辑
     * @param $key string 字典键
     * @param $value array 字典值 目前是枚举数组，以后可以扩展为关联数组
     * @param $note string 字典描述
     * @return boolean
     */
    public function insert_dictionary($key, $arr_val, $note) {
        $res = M("Dictionary")->where("`key`='{$key}'")->find();
        if ($res) {
            $this->err = "字典键不能重复";
            $this->log("insert", 1, $key, false);
            return false;
        }

        $data = array(
            "key"   => $key,
            "note"  => $note,
        );
        $key_id = M("Dictionary")->add($data);
        if (!$key_id) {
            $this->err = "插入数据库失败，请联系管理员";
            $this->log("insert", 1, $key, false);
            return false;
        }
        foreach ($arr_val as $v) {
            $data = array(
                "key_id" => $key_id,
                "value" => $v['value'],
                "desc" => $v['desc'],
            );
            if (!M("DictionaryValue")->add($data)) {
                $this->err = "插入数据库失败，请联系管理员";
                $this->log("insert", 1, $key, false);
                return false;
            }
        }
        $this->log("insert", 1, $key, true);
        return true;
    }

    /*
     * 修改一个字典项
     * todo 删除缓存后要不要新增
     * @param $id int
     * @param $key string 字典键
     * @param $value array 字典值 目前是枚举数组，以后可以扩展为关联数组
     * @param $note string 字典描述
     * @return boolean
     */
    public function update_dictionary($id, $key, $arr_val, $note) {
        $res = M("Dictionary")->where("`key`='{$key}' AND `id`!='{$id}'")->find();
        if ($res) {
            $this->err = "字典键不能重复";
            $this->log("update", 1, $key, false);
            return false;
        }

        $res = M("Dictionary")->where("`id`='{$id}'")->find();
        if (!$res || !$res['key']) {
            $this->err = "字典项不存在";
            $this->log("update", 1, $key, false);
            return false;
        }

        $data   = array(
//             "key"   => $key,
            "note"  => $note,
        );
        $option = array(
            "where" => "`id`='{$id}'",
        );
        if (M("Dictionary")->save($data, $option) === false) {
            $this->err = "更新数据库失败";
            $this->log("update", 1, $key, false);
            return false;
        }
        if (M("DictionaryValue")->where("`key_id`='{$id}'")->delete() === false) {
            $this->err = "删除数据库失败";
            $this->log("delete", 1, $key, false);
            return false;
        }
        foreach ($arr_val as $v) {
            $data = array(
                "key_id" => $id,
                "value" => $v['value'],
                "desc" => $v['desc'],
            );
            if (!M("DictionaryValue")->add($data)) {
                $this->err = "插入数据库失败，请联系管理员";
                $this->log("insert", 1, $key, false);
                return false;
            }
        }

        $this->clear_cache($res['key']);

        $this->log("insert", 1, $key, true);
        return true;
    }

    /*
     * 将所有key对应的缓存清除
     */
    public function flush() {
        $dict = $this->get_dictionary(0, 0);
        foreach ($dict as $v) {
            $this->clear_cache($v['key']);
        }
        return true;
    }

    /*
     * 根据id数组删除数据字典
     * @param $id_arr array id数组
     * @return boolean
     */
    public function delete_dictionary_by_ids($id_arr) {
        $ids    = implode(",", $id_arr);
        $result = M("Dictionary")->where("id IN ({$ids})")->findAll();
        
        if (M("Dictionary")->where("id IN ({$ids})")->delete() === false) {
            $this->log("delete", 1, $ids, false);
            $this->err = "删除数据库失败，请联系管理员";
            return false;
        }

        foreach ($result as $val) {
            $this->clear_cache($val['key']);
        }

        if (M("DictionaryValue")->where("`key_id` IN ({$ids})")->delete() === false) {
            $this->log("delete", 1, $ids, false);
            $this->err = "删除数据库失败，请联系管理员";
            return false;
        } else {
            $this->log("delete", 1, $ids, true);
            return true;
        }
    }

    /*
     * 获取错误信息
     * @return string 错误信息
     */
    public function get_err() {
        return $this->err;
    }

    /*
     * 根据key从缓存中读取数据
     * @param $key string
     * @return string
     */
    private function get_cache_by_key($key) {
        return false;
    }

    /*
     * 根据key清除缓存
     * @param $key string
     * @return boolean
     */
    private function clear_cache($key) {
        dict::del($key);
    }


    /*
     * 私有记录日志方法
     * @param $act string 操作 insert/update/delete/get
     * @param $type int 1-数据库 2-mc
     * @param $key string
     * @param $is_succ boolean 默认true-操作成功 false-操作失败
     */
    private function log($act, $type, $key, $is_succ = true) {
        $db = $type == 1 ? "数据库" : "缓存";
        $act .= $db . ($is_succ ? "成功" : "失败");
        $data = array(
            "act" => $act,
            "key" => $key,
        );
        $msg  = implode(" | ", $data);
        save_log($msg, $is_succ ? 1 : 0);
    }

}

?>
