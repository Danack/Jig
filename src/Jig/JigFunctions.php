<?php


namespace Jig {

    class JigFunctions {
        public static function load(){}
    }
}

namespace {
    
    use Jig\JigException;

    function safeTextObject($string) {

        if (is_object($string) == true) {
            return "";
        }
        if (is_array($string) == true) {
            return "";
        }

        return htmlentities($string, ENT_DISALLOWED | ENT_HTML401 | ENT_NOQUOTES, 'UTF-8');
    }

    function getClassName($namespaceClass) {
        $lastSlashPosition = mb_strrpos($namespaceClass, '\\');

        if ($lastSlashPosition !== false) {
            return mb_substr($namespaceClass, $lastSlashPosition + 1);
        }

        return $namespaceClass;
    }

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

    function convertNamespaceClassToFilepath($namespaceClass) {
        return str_replace('\\', "/", $namespaceClass);
    }

    function ensureDirectoryExists($outputFilename) {

        $directoryName = dirname($outputFilename);
        @mkdir($directoryName, 0755, true);

        if (file_exists($directoryName) == false) {
            throw new JigException("Directory $directoryName does not exist and could not be created");
        }
    }

}




 