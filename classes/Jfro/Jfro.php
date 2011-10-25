<?php
/**
 * Base Jfro class based on the Zend Framework's Zend class
 * 
 * @package Jfro
 */

/**
 * Include base exception
 */
require_once 'Jfro/Exception.php';

/**
 * Based on Zend class
 *
 * @package Jfro
 */
class Jfro {
	
    static function loadInterface($interface, $dirs = null)
    {
        if (interface_exists($interface, false)) {
            return;
        }

        $path = str_replace('_', DIRECTORY_SEPARATOR, $interface);
        if ($dirs === null && $path != $interface) {
            // use the autodiscovered path
            $dirs = dirname($path);
            $file = basename($path) . '.php';
        } else {
            $file = $interface . '.php';
        }

        self::loadFile($file, $dirs, true);

        if (!interface_exists($interface, false)) {
            throw new Jfro_Exception("File \"$file\" was loaded but interface \"$interface\" was not found within.");
        }
    }

	static function loadClass($class, $dirs = null) {
		if(class_exists($class, false)) {
            return;
        }

        $path = str_replace('_', DIRECTORY_SEPARATOR, $class);
        if ($dirs === null && $path != $class) {
            // use the autodiscovered path
            $dirs = dirname($path);
            $file = basename($path) . '.php';
        } else {
            $file = $class . '.php';
        }

        self::loadFile($file, $dirs, true);

        if (!class_exists($class, false)) {
            throw new Jfro_Exception("File \"$file\" was loaded but class \"$class\" was not found within.");
        }
	}
	
	static public function loadFile($filename, $dirs=null, $once=false)
    {
        // security check
        if (preg_match('/[^a-z0-9\-_.]/i', $filename)) {
            throw new Jfro_Exception('Security check: Illegal character in filename');
        }

        /**
         * Determine if the file is readable, either within just the include_path
         * or within the $dirs search list.
         */
        $filespec = $filename;
        if ($dirs === null) {
            $found = self::isReadable($filespec);
        } else {
            foreach ((array)$dirs as $dir) {
                $filespec = rtrim($dir, '\\/') . DIRECTORY_SEPARATOR . $filename;
                $found = self::isReadable($filespec);
                if ($found) {
                    break;
                }
            }
        }

        /**
         * Throw an exception if the file could not be located
         */
        if (!$found) {
            throw new Jfro_Exception("File \"$filespec\" was not found.");
        }

        /**
         * Attempt to include() the file.
         *
         * include() is not prefixed with the @ operator because if
         * the file is loaded and contains a parse error, execution
         * will halt silently and this is difficult to debug.
         *
         * Always set display_errors = Off on production servers!
         */
        if ($once) {
            include_once($filespec);
        } else {
            include($filespec);
        }
    }
    
    static public function isReadable($filename)
    {
        $f = @fopen($filename, 'r', true);
        $readable = is_resource($f);
        if ($readable) {
            fclose($f);
        }
        return $readable;
    }
}