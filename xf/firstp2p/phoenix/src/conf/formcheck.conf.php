<?php

/**
 * @author YiXiao, <yixiao@ucfgroup.com>
 * @date  2013-12-16 18:30:16
 * @encode UTF-8编码
 */
class P_Conf_Formcheck {

    const ARRAYINARRAY = 'arrayinarray';
    const BATCHCHECK = 'batchcheck';
    const BC_INDEX_CLASS = 0;
    const BC_INDEX_ARGS = 1;
    const CLASS_INFFIX = 'Formcheck';
    const DEFAULT_DEFAULT = false;
    const DEFAULT_ERROR = 'form check error';
    const DEFAULT_MIXSTRING_WIDTH = 2;
    const DEFAULT_OPTIONAL = true;
    const DEFAULT_VALUES = false;
    const EMAIL = 'email';
    const EMAIL_DEFAULT_REGEX = "/^[0-9a-z][[a-z0-9]*[-_\.]?[a-z0-9]+]*@[a-z0-9][a-z0-9\-]*[a-z0-9](\.[a-z0-9][a-z0-9\-]*[a-z0-9]){1,2}$/i";
    const FILE = 'file';
    const FILE_ERROR_INVALID_IMAGE = 1;
    const FILE_ERROR_INVALID_UPLOADED = 2;
    const FILE_ERROR_INVALID_SIZE = 3;
    const FILE_ERROR_INVALID_TYPE = 4;
    const FILE_ERROR_INVALID_WIDTH = 5;
    const FILE_ERROR_INVALID_HEIGHT = 6;
    const FILE_INDEX_SIZE = 0;
    const FILE_INDEX_TYPE = 1;
    const FILE_INDEX_DIMENSION = 2;
    const FILE_INDEX_WIDTH = 0;
    const FILE_INDEX_HEIGHT = 1;
    const FILE_INDEX_MIN = 0;
    const FILE_INDEX_MAX = 1;
    const FLOAT = 'float';
    const INARRAY = 'inarray';
    const INT = 'int';
    const INDEX_CLASS = 0;
    const INDEX_METHOD = 1;
    const INDEX_KEY = 2;
    const INDEX_ARGS = 3;
    const INDEX_VALUES = 4;
    const INDEX_OPTIONAL = 5;
    const INDEX_DEFAULT = 6;
    const INDEX_ERROR = 7;
    const METHOD_CUSTOM = 'custom';
    const METHOD_FILE = 'file';
    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const MIXSTRING = 'mixstring';
    const MIXSTRING_WIDTH = 2;
    const MOBILE_DEFAULT_REGEX = '/^1[3456789][0-9]{9}$/';
    const NUM_INDEX_LOWER = 0;
    const NUM_INDEX_UPPER = 1;
    const NUM_INDEX_ROUND = 2;
    const REGEX = 'regex';
    const RULE_ARGS_COUNT = 4;
    const STRING = 'string';
    const STRING_INDEX_STRLEN = 2;
    const STRING_INDEX_ENCODING = 3;
    const XID = 'xid';
    const XID_INDEX_KEY = 0;
    const XID_INDEX_TOKEN = 1;
    const XID_INDEX_CLASS = 1;
    const XID_INDEX_ARGS = 2;

    public static $file_upload_error = array(
        self::FILE_ERROR_INVALID_IMAGE => '无效的图片文件',
        self::FILE_ERROR_INVALID_UPLOADED => '无效的上传文件',
        self::FILE_ERROR_INVALID_SIZE => '文件大小超出限制',
        self::FILE_ERROR_INVALID_TYPE => '无效的文件类型',
        self::FILE_ERROR_INVALID_WIDTH => '图片宽度超出限制',
        self::FILE_ERROR_INVALID_HEIGHT => '图片高度超出限制',
    );
    
    public static $method = array(
        self::METHOD_FILE,
        self::METHOD_GET,
        self::METHOD_POST,
        self::METHOD_CUSTOM,
    );
    public static $types = array(
        self::ARRAYINARRAY,
        self::BATCHCHECK,
        self::EMAIL,
        self::FILE,
        self::FLOAT,
        self::INARRAY,
        self::INT,
        self::MIXSTRING,
        self::REGEX,
        self::STRING,
        self::XID,
    );

}
