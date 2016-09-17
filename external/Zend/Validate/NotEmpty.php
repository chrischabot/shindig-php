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
 * @version    $Id: NotEmpty.php 8064 2008-02-16 10:58:39Z thomas $
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
class Zend_Validate_NotEmpty extends Zend_Validate_Abstract
{
    const IS_EMPTY = 'isEmpty';

  /**
   * @var array
   */
  protected $_messageTemplates = [self::IS_EMPTY => 'Value is empty, but a non-empty value is required'];

  /**
   * Defined by Zend_Validate_Interface.
   *
   * Returns true if and only if $value is not an empty value.
   *
   * @param  string $value
   *
   * @return bool
   */
  public function isValid($value)
  {
      $valueString = (string) $value;

      $this->_setValue($valueString);

      if (empty($value)) {
          $this->_error();

          return false;
      }

      return true;
  }
}
