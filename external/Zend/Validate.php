<?php

/**
 * Zend Framework.
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 *
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 *
 * @version    $Id: Validate.php 8911 2008-03-19 20:22:15Z thomas $
 */

/**
 * @see Zend_Validate_Interface
 */
require_once 'external/Zend/Validate/Interface.php';

/**
 * @category   Zend
 *
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Validate implements Zend_Validate_Interface
{
    /**
   * Validator chain.
   *
   * @var array
   */
  protected $_validators = [];

  /**
   * Array of validation failure messages.
   *
   * @var array
   */
  protected $_messages = [];

  /**
   * Array of validation failure message codes.
   *
   * @var array
   *
   * @deprecated Since 1.5.0
   */
  protected $_errors = [];

  /**
   * Adds a validator to the end of the chain.
   *
   * If $breakChainOnFailure is true, then if the validator fails, the next validator in the chain,
   * if one exists, will not be executed.
   *
   * @param  Zend_Validate_Interface $validator
   * @param  bool                 $breakChainOnFailure
   *
   * @return Zend_Validate Provides a fluent interface
   */
  public function addValidator(Zend_Validate_Interface $validator, $breakChainOnFailure = false)
  {
      $this->_validators[] = ['instance' => $validator,
        'breakChainOnFailure'          => (bool) $breakChainOnFailure, ];

      return $this;
  }

  /**
   * Returns true if and only if $value passes all validations in the chain.
   *
   * Validators are run in the order in which they were added to the chain (FIFO).
   *
   * @param  mixed $value
   *
   * @return bool
   */
  public function isValid($value)
  {
      $this->_messages = [];
      $this->_errors = [];
      $result = true;
      foreach ($this->_validators as $element) {
          $validator = $element['instance'];
          if ($validator->isValid($value)) {
              continue;
          }
          $result = false;
          $messages = $validator->getMessages();
          $this->_messages = array_merge($this->_messages, $messages);
          $this->_errors = array_merge($this->_errors, array_keys($messages));
          if ($element['breakChainOnFailure']) {
              break;
          }
      }

      return $result;
  }

  /**
   * Defined by Zend_Validate_Interface.
   *
   * Returns array of validation failure messages
   *
   * @return array
   */
  public function getMessages()
  {
      return $this->_messages;
  }

  /**
   * Defined by Zend_Validate_Interface.
   *
   * Returns array of validation failure message codes
   *
   * @return array
   *
   * @deprecated Since 1.5.0
   */
  public function getErrors()
  {
      return $this->_errors;
  }

  /**
   * @param  mixed    $value
   * @param  string   $classBaseName
   * @param  array    $args          OPTIONAL
   * @param  mixed    $namespaces    OPTIONAL
   *
   * @throws Zend_Validate_Exception
   *
   * @return bool
   */
  public static function is($value, $classBaseName, array $args = [], $namespaces = [])
  {
      $namespaces = array_merge(['Zend_Validate'], (array) $namespaces);
      foreach ($namespaces as $namespace) {
          $className = $namespace.'_'.ucfirst($classBaseName);
          try {
              require_once 'external/Zend/Loader.php';
              @Zend_Loader::loadClass($className);
              if (class_exists($className, false)) {
                  $class = new ReflectionClass($className);
                  if ($class->implementsInterface('Zend_Validate_Interface')) {
                      if ($class->hasMethod('__construct')) {
                          $object = $class->newInstanceArgs($args);
                      } else {
                          $object = $class->newInstance();
                      }

                      return $object->isValid($value);
                  }
              }
          } catch (Zend_Validate_Exception $ze) {
              // if there is an exception while validating throw it
        throw $ze;
          } catch (Zend_Exception $ze) {  // fallthrough and continue for missing validation classes
          }
      }
      require_once 'external/Zend/Validate/Exception.php';
      throw new Zend_Validate_Exception("Validate class not found from basename '$classBaseName'");
  }
}
