<?php


namespace Jig;

use Jig\Converter\JigConverter;

class JigFunctions
{
    public static function load()
    {
    }
}

/**
 * @param $templateName
 * @return string
 */
function getCompileFilename($templateName, JigConverter $jigConverter, JigConfig $jigConfig)
{
    $className = $jigConverter->getClassNameFromFilename($templateName);
    $compileFilename = $jigConfig->getCompiledFilename($className);

    return $compileFilename;
}


/**
 * Convert a PHP variable to an escaped output. Objects and arrays return empty string.
 * @param $string
 * @return string
 */
function safeTextObject($string)
{
    //TODO - add __toString calling
    if (is_object($string) == true) {
        if (method_exists($string, '__toString') == false) {
            $message = sprintf(
                "Object of type %s does not have a __toString method. Cannot use it as a string.",
                get_class($string)
            );
            throw new JigException($message);
        }

        return call_user_func([$string, '__toString()']);
    }
    if (is_array($string) == true) {
        throw new JigException(JigException::IMPLICIT_ARRAY_TO_STRING);
    }

    return htmlentities($string, ENT_DISALLOWED | ENT_HTML401 | ENT_NOQUOTES, 'UTF-8');
}

/**
 * Get the class part of a fully namespaced class name
 * @param $namespaceClass
 * @return string
 */
function getClassName($namespaceClass)
{
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
function getNamespace($namespaceClass)
{
    if (is_object($namespaceClass)) {
        $namespaceClass = get_class($namespaceClass);
    }

    $lastSlashPosition = mb_strrpos($namespaceClass, '\\');

    if ($lastSlashPosition !== false) {
        return mb_substr($namespaceClass, 0, $lastSlashPosition);
    }

    return "";
}

function getFQCN($namespace, $classname)
{
    if (strlen($namespace)) {
        return $namespace."\\".$classname;
    }

    return $classname;
}

/**
 * @param $namespaceClass
 * @return mixed
 */
function convertNamespaceClassToFilepath($namespaceClass)
{
    return str_replace('\\', "/", $namespaceClass);
}

/**
 * ensureDirectoryExists by creating it with 0755 permissions and throwing
 * an exception if it does not exst after that mkdir call.
 * @param $outputFilename
 * @throws JigException
 */
function ensureDirectoryExists($outputFilename)
{
    $directoryName = dirname($outputFilename);
    @mkdir($directoryName, 0755, true);

    if (file_exists($directoryName) == false) {
        throw new JigException("Directory $directoryName does not exist and could not be created");
    }
}
