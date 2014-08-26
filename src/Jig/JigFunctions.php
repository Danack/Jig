<?php


namespace Jig {

    class JigFunctions {
        public static function load(){}
    }
}

namespace {
    
    use Jig\JigException;

    /**
     * Convert a PHP variable to an escaped output. Objects and arrays return empty string.
     * @param $string
     * @return string
     */
    function safeTextObject($string) {
        //TODO - add __toString calling
        if (is_object($string) == true) {
            return "";
        }
        if (is_array($string) == true) {
            return "";
        }

        return htmlentities($string, ENT_DISALLOWED | ENT_HTML401 | ENT_NOQUOTES, 'UTF-8');
    }

    /**
     * Get the class part of a fully namespaced class name
     * @param $namespaceClass
     * @return string
     */
    function getClassName($namespaceClass) {
        $lastSlashPosition = mb_strrpos($namespaceClass, '\\');

        if ($lastSlashPosition !== false) {
            return mb_substr($namespaceClass, $lastSlashPosition + 1);
        }

        return $namespaceClass;
    }

    /**
     * Get the name space part of a fully namespaced class. Returns empty string 
     * if the class had no namespace part.
     * @param $namespaceClass
     * @return string
     */
    function getNamespace($namespaceClass) {
        if (is_object($namespaceClass)) {
            $namespaceClass = get_class($namespaceClass);
        }

        $lastSlashPosition = mb_strrpos($namespaceClass, '\\');

        if ($lastSlashPosition !== false) {
            return mb_substr($namespaceClass, 0, $lastSlashPosition);
        }

        return "";
    }

    /**
     * @param $namespaceClass
     * @return mixed
     */
    function convertNamespaceClassToFilepath($namespaceClass) {
        return str_replace('\\', "/", $namespaceClass);
    }

    /**
     * ensureDirectoryExists by creating it with 0755 permissions and throwing
     * an exception if it does not exst after that mkdir call.
     * @param $outputFilename
     * @throws JigException
     */
    function ensureDirectoryExists($outputFilename) {
        $directoryName = dirname($outputFilename);
        @mkdir($directoryName, 0755, true);

        if (file_exists($directoryName) == false) {
            throw new JigException("Directory $directoryName does not exist and could not be created");
        }
    }

}




 