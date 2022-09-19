<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2014-1-13 10:05:15
 * @encode UTF-8编码
 */
class P_Formcheck_File extends P_Formcheck_Abstract {

    private $_max_height = PHP_INT_MAX;
    private $_max_width = PHP_INT_MAX;
    private $_min_height = 0;
    private $_min_width = 0;
    private $_size = PHP_INT_MAX;
    private $_type = array(IMAGETYPE_GIF, IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_SWF, IMAGETYPE_PSD, IMAGETYPE_BMP, IMAGETYPE_TIFF_II, IMAGETYPE_TIFF_MM, IMAGETYPE_JPC, IMAGETYPE_JP2, IMAGETYPE_JPX, IMAGETYPE_JB2, IMAGETYPE_SWC, IMAGETYPE_IFF, IMAGETYPE_WBMP, IMAGETYPE_XBM,);

    private function _process_file($value) {
        $value['errno'] = P_Conf_Globalerrno::OK;
        $value['size'] = intval($value['size']);
        $value['width'] = null;
        $value['height'] = null;
        $value['image_type'] = null;
        $value['bits'] = null;
        $value['channels'] = null;
        $value['ext'] = null;
        if (!is_uploaded_file($value['tmp_name'])) {
            $value['errno'] = P_Conf_Formcheck::FILE_ERROR_INVALID_UPLOADED;
        }

        $info = @getimagesize($value['tmp_name'], $ext);
        if ($info) {
            $value['width'] = intval($info[0]);
            $value['height'] = intval($info[1]);
            $value['image_type'] = intval($info[2]);
            $value['bits'] = isset($info['bits']) ? $info['bits'] : null;
            $value['channels'] = isset($info['channels']) ? $info['channels'] : null;
            $value['ext'] = $ext;
            if ($value['size'] > $this->_size) {
                $value['errno'] = P_Conf_Formcheck::FILE_ERROR_INVALID_SIZE;
            }
            if ($this->_min_width > $value['width'] || $this->_max_width < $value['width']) {
                $value['errno'] = P_Conf_Formcheck::FILE_ERROR_INVALID_WIDTH;
            }
            if ($this->_min_height > $value['height'] || $this->_max_height < $value['height']) {
                $value['errno'] = P_Conf_Formcheck::FILE_ERROR_INVALID_HEIGHT;
            }
            if (!in_array($value['image_type'], $this->_type)) {
                $value['errno'] = P_Conf_Formcheck::FILE_ERROR_INVALID_TYPE;
            }
        } else {
            $value['errno'] = P_Conf_Formcheck::FILE_ERROR_INVALID_IMAGE;
        }
        if ($value['errno'] != P_Conf_Globalerrno::OK) {
            $value['message'] = P_Conf_Formcheck::$file_upload_error[$value['errno']];
        }
        return $value;
    }

    public function valid($method, $key, $args, $values, $optional, $default) {
        $value = $this->get_value($method, $key, $values);
        if ($value === false) {
            if ($optional) {
                $value = $default;
                return array($key => $value);
            }
            new P_Exception_Formcheck('invalid optional value', P_Conf_Globalerrno::FORM_CHECK_ERROR);
            return false;
        }
        if (isset($args[P_Conf_Formcheck::FILE_INDEX_SIZE])) {
            $this->_size = intval($args[P_Conf_Formcheck::FILE_INDEX_SIZE]);
        }
        if (isset($args[P_Conf_Formcheck::FILE_INDEX_TYPE]) && is_array($args[P_Conf_Formcheck::FILE_INDEX_TYPE])) {
            $this->_type = $args[P_Conf_Formcheck::FILE_INDEX_TYPE];
        }
        if (isset($args[P_Conf_Formcheck::FILE_INDEX_DIMENSION]) && is_array($args[P_Conf_Formcheck::FILE_INDEX_DIMENSION])) {
            $dimension = $args[P_Conf_Formcheck::FILE_INDEX_DIMENSION];
            if (isset($dimension[P_Conf_Formcheck::FILE_INDEX_WIDTH]) && is_array($dimension[P_Conf_Formcheck::FILE_INDEX_WIDTH]) && isset($dimension[P_Conf_Formcheck::FILE_INDEX_WIDTH][P_Conf_Formcheck::FILE_INDEX_MIN]) && isset($dimension[P_Conf_Formcheck::FILE_INDEX_WIDTH][P_Conf_Formcheck::FILE_INDEX_MAX])) {
                $this->_min_width = intval($dimension[P_Conf_Formcheck::FILE_INDEX_WIDTH][P_Conf_Formcheck::FILE_INDEX_MIN]);
                $this->_max_width = intval($dimension[P_Conf_Formcheck::FILE_INDEX_WIDTH][P_Conf_Formcheck::FILE_INDEX_MAX]);
            }
            if (isset($dimension[P_Conf_Formcheck::FILE_INDEX_HEIGHT]) && is_array($dimension[P_Conf_Formcheck::FILE_INDEX_HEIGHT]) && isset($dimension[P_Conf_Formcheck::FILE_INDEX_HEIGHT][P_Conf_Formcheck::FILE_INDEX_MIN]) && isset($dimension[P_Conf_Formcheck::FILE_INDEX_HEIGHT][P_Conf_Formcheck::FILE_INDEX_MAX])) {
                $this->_min_height = intval($dimension[P_Conf_Formcheck::FILE_INDEX_HEIGHT][P_Conf_Formcheck::FILE_INDEX_MIN]);
                $this->_max_height = intval($dimension[P_Conf_Formcheck::FILE_INDEX_HEIGHT][P_Conf_Formcheck::FILE_INDEX_MAX]);
            }
        }
        $file = array();
        if (!is_array($value['name'])) {
            if ($value['error'] != UPLOAD_ERR_NO_FILE) {
                $file[] = $this->_process_file($value);
            }
        } else {
            foreach ($value['name'] as $k => $v) {
                if ($value['error'][$k] == UPLOAD_ERR_NO_FILE) {
                    continue;
                }
                $item = array(
                    'name' => $v,
                    'tmp_name' => $value['tmp_name'][$k],
                    'size' => $value['size'][$k],
                    'type' => $value['type'][$k],
                    'error' => $value['error'][$k]
                );
                $file[] = $this->_process_file($item);
            }
        }
        return array($key => $file);
    }

}
