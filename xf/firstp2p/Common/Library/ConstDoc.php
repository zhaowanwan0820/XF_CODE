<?php
namespace NCFGroup\Common\Library;

class ConstDoc
{
    /**
     * @var array Constant names to DocComment strings.
     */
    private $docComments = [];

    /** Constructor. */
    public function __construct(\ReflectionClass $clazz)
    {
        $this->parse($clazz);
    }

    /** Parses the class for constant DocComments. */
    private function parse(\ReflectionClass $clazz)
    {
        if($clazz->isInternal()) {
            return ;
        }
        $content = file_get_contents($clazz->getFileName());
        $tokens = token_get_all($content);

        $doc = null;
        $isConst = false;
        foreach($tokens as $token) {

            if(!is_array($token)) {
                continue;
            }
            list($tokenType, $tokenValue) = $token;

            switch ($tokenType) {

                // ignored tokens
                case T_WHITESPACE:
                case T_COMMENT:
                    break;

                case T_DOC_COMMENT:
                    $doc = $tokenValue;
                    break;

                case T_CONST:
                    $isConst = true;
                    break;

                case T_STRING:
                    if ($isConst) {
                        $this->docComments[$tokenValue] = self::clean($doc);
                    }
                    $doc = null;
                    $isConst = false;
                    break;

                    // all other tokens reset the parser
                default:
                    $doc = null;
                    $isConst = false;
                    break;
            }
        }

        if(!$isConst && ($parent=$clazz->getParentClass())) {
            $this->parse($parent);
        }
    }

    /**
     * Returns an array of all constants to their DocComment. If no comment is present the comment is null.
     */
    public function getDocComments()
    {
        return $this->docComments;
    }

    /**
     * Returns the DocComment of a class constant. Null if the constant has no DocComment or the constant does not exist.
     */
    public function getDocComment($constantName)
    {
        if (!isset($this->docComments)) {
            return null;
        }

        if(isset($this->docComments[$constantName])) {
            return $this->docComments[$constantName];
        }

        return null;
    }

    /**
     * Cleans the doc comment. Returns null if the doc comment is null.
     */
    public static function clean($doc)
    {
        if ($doc === null) {
            return null;
        }

        $result = null;
        $lines = preg_split('/\R/', $doc);
        foreach($lines as $line) {
            $line = trim($line, "/* \t\x0B\0");
            if ($line === '') {
                continue;
            }

            if ($result != null) {
                $result .= ' ';
            }
            $result .= $line;
        }
        return $result;
    }
}
