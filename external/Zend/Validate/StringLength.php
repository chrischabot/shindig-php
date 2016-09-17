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
 * @version    $Id: StringLength.php 8064 2008-02-16 10:58:39Z thomas $
 */

/**
 * @see Zend_Validate_Abstract
 */
require_once 'external/Zend/Validate/Abstract.php';

/**
 * @category   Zend
 *
 * @copyright  Copyright (c) 2005-2008 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Validate_StringLength extends Zend_Validate_Abstract
{
    const TOO_SHORT = 'stringLengthTooShort';
    const TOO_LONG = 'stringLengthTooLong';

  /**
   * @var array
   */
  protected $_messageTemplates = [self::TOO_SHORT => "'%value%' is less than %min% characters long",
      self::TOO_LONG                              => "'%value%' is greater than %max% characters long", ];

  /**
   * @var array
   */
  protected $_messageVariables = ['min' => '_min', 'max' => '_max'];

  /**
   * Minimum length.
   *
   * @var int
   */
  protected $_min;

  /**
   * Maximum length.
   *
   * If null, there is no maximum length
   *
   * @var int|null
   */
  protected $_max;

  /**
   * Sets validator options.
   *
   * @param  int $min
   * @param  int $max
   *
   * @return void
   */
  public function __construct($min = 0, $max = null)
  {
      $this->setMin($min);
      $this->setMax($max);
  }

  /**
   * Returns the min option.
   *
   * @return int
   */
  public function getMin()
  {
      return $this->_min;
  }

  /**
   * Sets the min option.
   *
   * @param  int $min
   *
   * @throws Zend_Validate_Exception
   *
   * @return Zend_Validate_StringLength Provides a fluent interface
   */
  public function setMin($min)
  {
      if (null !== $this->_max && $min > $this->_max) {
          /**
       * @see Zend_Validate_Exception
       */
      require_once 'external/Zend/Validate/Exception.php';
          throw new Zend_Validate_Exception("The minimum must be less than or equal to the maximum length, but $min >"." $this->_max");
      }
      $this->_min = max(0, (int) $min);

      return $this;
  }

  /**
   * Returns the max option.
   *
   * @return int|null
   */
  public function getMax()
  {
      return $this->_max;
  }

  /**
   * Sets the max option.
   *
   * @param  int|null $max
   *
   * @throws Zend_Validate_Exception
   *
   * @return Zend_Validate_StringLength Provides a fluent interface
   */
  public function setMax($max)
  {
      if (null === $max) {
          $this->_max = null;
      } elseif ($max < $this->_min) {
          /**
       * @see Zend_Validate_Exception
       */
      require_once 'external/Zend/Validate/Exception.php';
          throw new Zend_Validate_Exception('The maximum must be greater than or equal to the minimum length, but '."$max < $this->_min");
      } else {
          $this->_max = (int) $max;
      }

      return $this;
  }

  /**
   * Defined by Zend_Validate_Interface.
   *
   * Returns true if and only if the string length of $value is at least the min option and
   * no greater than the max option (when the max option is not null).
   *
   * @param  string $value
   *
   * @return bool
   */
  public function isValid($value)
  {
      $valueString = (string) $value;
      $this->_setValue($valueString);
      $length = iconv_strlen($valueString);
      if ($length < $this->_min) {
          $this->_error(self::TOO_SHORT);
      }
      if (null !== $this->_max && $this->_max < $length) {
          $this->_error(self::TOO_LONG);
      }
      if (count($this->_messages)) {
          return false;
      } else {
          return true;
      }
  }
}
