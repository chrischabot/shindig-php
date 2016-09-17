<?php
/**
 * PHPUnit.
 *
 * Copyright (c) 2002-2008, Sebastian Bergmann <sb@sebastian-bergmann.de>.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the name of Sebastian Bergmann nor the names of his
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category   Testing
 *
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2008 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version    SVN: $Id: Class.php 1985 2007-12-26 18:11:55Z sb $
 *
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.1.0
 */
require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * Class helpers.
 *
 * @category   Testing
 *
 * @author     Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @copyright  2002-2008 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License
 *
 * @version    Release: 3.2.9
 *
 * @link       http://www.phpunit.de/
 * @since      Class available since Release 3.1.0
 */
class PHPUnit_Util_Class
{
    protected static $buffer = [];
    protected static $fileClassMap = [];
    protected static $fileFunctionMap = [];

  /**
   * Starts the collection of loaded classes.
   *
   * @static
   */
  public static function collectStart()
  {
      self::$buffer = get_declared_classes();
  }

  /**
   * Stops the collection of loaded classes and
   * returns the names of the loaded classes.
   *
   * @return array
   * @static
   */
  public static function collectEnd()
  {
      return array_values(array_diff(get_declared_classes(), self::$buffer));
  }

  /**
   * Stops the collection of loaded classes and
   * returns the names of the files that declare the loaded classes.
   *
   * @return array
   * @static
   */
  public static function collectEndAsFiles()
  {
      $result = self::collectEnd();
      $count = count($result);

      for ($i = 0; $i < $count; $i++) {
          $class = new ReflectionClass($result[$i]);

          if ($class->isUserDefined()) {
              $file = $class->getFileName();

              if (file_exists($file)) {
                  $result[$i] = $file;
              } else {
                  unset($result[$i]);
              }
          }
      }

      return $result;
  }

  /**
   * Returns the names of the classes declared in a sourcefile.
   *
   * @param  string  $filename
   * @param  string  $commonPath
   * @param  bool $clearCache
   *
   * @return array
   * @static
   */
  public static function getClassesInFile($filename, $commonPath = '', $clearCache = false)
  {
      if ($commonPath != '') {
          $filename = str_replace($commonPath, '', $filename);
      }

      if ($clearCache) {
          self::$fileClassMap = [];
      }

      if (empty(self::$fileClassMap)) {
          $classes = array_merge(get_declared_classes(), get_declared_interfaces());

          foreach ($classes as $className) {
              $class = new ReflectionClass($className);

              if ($class->isUserDefined()) {
                  $file = $class->getFileName();

                  if ($commonPath != '') {
                      $file = str_replace($commonPath, '', $file);
                  }

                  if (!isset(self::$fileClassMap[$file])) {
                      self::$fileClassMap[$file] = [$class];
                  } else {
                      self::$fileClassMap[$file][] = $class;
                  }
              }
          }
      }

      return isset(self::$fileClassMap[$filename]) ? self::$fileClassMap[$filename] : [];
  }

  /**
   * Returns the names of the classes declared in a sourcefile.
   *
   * @param  string  $filename
   * @param  string  $commonPath
   * @param  bool $clearCache
   *
   * @return array
   * @static
   *
   * @since  Class available since Release 3.2.0
   *
   * @todo   Find a better place for this method.
   */
  public static function getFunctionsInFile($filename, $commonPath = '', $clearCache = false)
  {
      if ($commonPath != '') {
          $filename = str_replace($commonPath, '', $filename);
      }

      if ($clearCache) {
          self::$fileFunctionMap = [];
      }

      if (empty(self::$fileFunctionMap)) {
          $functions = get_defined_functions();

          foreach ($functions['user'] as $functionName) {
              $function = new ReflectionFunction($functionName);

              $file = $function->getFileName();

              if ($commonPath != '') {
                  $file = str_replace($commonPath, '', $file);
              }

              if (!isset(self::$fileFunctionMap[$file])) {
                  self::$fileFunctionMap[$file] = [$function];
              } else {
                  self::$fileFunctionMap[$file][] = $function;
              }
          }
      }

      return isset(self::$fileFunctionMap[$filename]) ? self::$fileFunctionMap[$filename] : [];
  }

  /**
   * Returns the class hierarchy for a given class.
   *
   * @param  string  $className
   *
   * @return array
   * @static
   */
  public static function getHierarchy($className)
  {
      $classes = [$className];
      $done = false;

      while (!$done) {
          $class = new ReflectionClass($classes[count($classes) - 1]);
          $parent = $class->getParentClass();

          if ($parent !== false) {
              $classes[] = $parent->getName();
          } else {
              $done = true;
          }
      }

      return $classes;
  }

  /**
   * Returns the signature of a method.
   *
   * @param  ReflectionClass $method
   *
   * @return string
   * @static
   *
   * @since  Class available since Release 3.2.0
   */
  public static function getMethodSignature(ReflectionMethod $method)
  {
      if ($method->isPrivate()) {
          $modifier = 'private';
      } elseif ($method->isProtected()) {
          $modifier = 'protected';
      } else {
          $modifier = 'public';
      }

      if ($method->isStatic()) {
          $modifier .= ' static';
      }

      if ($method->returnsReference()) {
          $reference = '&';
      } else {
          $reference = '';
      }

      return sprintf('%s function %s%s(%s)',

    $modifier, $reference, $method->getName(), self::getMethodParameters($method));
  }

  /**
   * Returns the parameters of a method.
   *
   * @param  ReflectionClass $method
   *
   * @return string
   * @static
   *
   * @since  Class available since Release 3.2.0
   */
  public static function getMethodParameters(ReflectionMethod $method)
  {
      $parameters = [];

      foreach ($method->getParameters() as $parameter) {
          $name = '$'.$parameter->getName();
          $typeHint = '';

          if ($parameter->isArray()) {
              $typeHint = 'array ';
          } else {
              try {
                  $class = $parameter->getClass();
              } catch (ReflectionException $e) {
                  $class = false;
              }

              if ($class) {
                  $typeHint = $class->getName().' ';
              }
          }

          $default = '';

          if ($parameter->isDefaultValueAvailable()) {
              $value = $parameter->getDefaultValue();
              $default = ' = '.var_export($value, true);
          }

          $ref = '';

          if ($parameter->isPassedByReference()) {
              $ref = '&';
          }

          $parameters[] = $typeHint.$ref.$name.$default;
      }

      return implode(', ', $parameters);
  }

  /**
   * Returns the sourcecode of a user-defined class.
   *
   * @param  string  $className
   * @param  string  $methodName
   *
   * @return mixed
   * @static
   */
  public static function getMethodSource($className, $methodName)
  {
      if ($className != 'global') {
          $function = new ReflectionMethod($className, $methodName);
      } else {
          $function = new ReflectionFunction($methodName);
      }

      $filename = $function->getFileName();

      if (file_exists($filename)) {
          $file = file($filename);
          $result = '';

          for ($line = $function->getStartLine() - 1; $line <= $function->getEndLine() - 1; $line++) {
              $result .= $file[$line];
          }

          return $result;
      } else {
          return false;
      }
  }

  /**
   * Returns the package information of a user-defined class.
   *
   * @param  string $className
   *
   * @return array
   * @static
   */
  public static function getPackageInformation($className)
  {
      $result = ['fullPackage' => '', 'category' => '', 'package' => '', 'subpackage' => ''];

      $class = new ReflectionClass($className);
      $docComment = $class->getDocComment();

      if (preg_match('/@category[\s]+([\.\w]+)/', $docComment, $matches)) {
          $result['category'] = $matches[1];
      }

      if (preg_match('/@package[\s]+([\.\w]+)/', $docComment, $matches)) {
          $result['package'] = $matches[1];
          $result['fullPackage'] = $matches[1];
      }

      if (preg_match('/@subpackage[\s]+([\.\w]+)/', $docComment, $matches)) {
          $result['subpackage'] = $matches[1];
          $result['fullPackage'] .= '.'.$matches[1];
      }

      if (empty($result['fullPackage'])) {
          $tmp = explode('_', $className);

          if (count($tmp) > 1) {
              unset($tmp[count($tmp) - 1]);

              $result['fullPackage'] = implode('.', $tmp);
          }
      }

      return $result;
  }
}
