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
 * @version    SVN: $Id: TestCase.php 2135 2008-01-17 10:45:15Z sb $
 *
 * @link       http://www.phpunit.de/
 * @since      File available since Release 2.0.0
 */
require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Framework/MockObject/Mock.php';
require_once 'PHPUnit/Framework/MockObject/Matcher/InvokedAtLeastOnce.php';
require_once 'PHPUnit/Framework/MockObject/Matcher/InvokedAtIndex.php';
require_once 'PHPUnit/Framework/MockObject/Matcher/InvokedCount.php';
require_once 'PHPUnit/Framework/MockObject/Stub.php';
require_once 'PHPUnit/Runner/BaseTestRunner.php';
require_once 'PHPUnit/Util/Filter.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

if (!class_exists('PHPUnit_Framework_TestCase', false)) {

  /**
   * A TestCase defines the fixture to run multiple tests.
   *
   * To define a TestCase
   *
   *   1) Implement a subclass of PHPUnit_Framework_TestCase.
   *   2) Define instance variables that store the state of the fixture.
   *   3) Initialize the fixture state by overriding setUp().
   *   4) Clean-up after a test by overriding tearDown().
   *
   * Each test runs in its own fixture so there can be no side effects
   * among test runs.
   *
   * Here is an example:
   *
   * <code>
   * <?php
   * require_once 'PHPUnit/Framework/TestCase.php';
   *
   * class MathTest extends PHPUnit_Framework_TestCase
   * {
   *     public $value1;
   *     public $value2;
   *
   *     protected function setUp()
   *     {
   *         $this->value1 = 2;
   *         $this->value2 = 3;
   *     }
   * }
   * ?>
   * </code>
   *
   * For each test implement a method which interacts with the fixture.
   * Verify the expected results with assertions specified by calling
   * assert with a boolean.
   *
   * <code>
   * <?php
   * public function testPass()
   * {
   *     $this->assertTrue($this->value1 + $this->value2 == 5);
   * }
   * ?>
   * </code>
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
   * @since      Class available since Release 2.0.0
   * @abstract
   */
  abstract class PHPUnit_Framework_TestCase extends PHPUnit_Framework_Assert implements PHPUnit_Framework_Test, PHPUnit_Framework_SelfDescribing
  {
      /**
     * Enable or disable the backup and restoration of the $GLOBALS array.
     * Overwrite this attribute in a child class of TestCase.
     * Setting this attribute in setUp() has no effect!
     *
     * @var bool
     */
    protected $backupGlobals = true;

    /**
     * Enable or disable creating the $GLOBALS reference that is required
     * for the "global" keyword to work correctly.
     * Overwrite this attribute in a child class of TestCase.
     * Setting this attribute in setUp() has no effect!
     *
     * @var bool
     */
    protected $createGlobalsReference = false;

    /**
     * @var array
     */
    protected $data = [];

    /**
     * @var string
     */
    protected $dataName = '';

    /**
     * The name of the expected Exception.
     *
     * @var mixed
     */
    protected $expectedException = null;

    /**
     * The message of the expected Exception.
     *
     * @var string
     */
    protected $expectedExceptionMessage = '';

    /**
     * Fixture that is shared between the tests of a test suite.
     *
     * @var mixed
     */
    protected $sharedFixture;

    /**
     * The name of the test case.
     *
     * @var string
     */
    protected $name = null;

    /**
     * @var Exception
     */
    protected $exception = null;

    /**
     * @var string
     */
    protected $exceptionMessage = null;

    /**
     * @var int
     */
    protected $exceptionCode = 0;

    /**
     * @var array
     */
    protected $iniSettings = [];

    /**
     * @var array
     */
    protected $locale = [];

    /**
     * @var array
     */
    protected $mockObjects = [];

    /**
     * Constructs a test case with the given name.
     *
     * @param string $name
     * @param array  $data
     * @param string $dataName
     */
    public function __construct($name = null, array $data = [], $dataName = '')
    {
        if ($name !== null) {
            $this->setName($name);
        }

        $this->data = $data;
        $this->dataName = $dataName;
    }

    /**
     * Returns a string representation of the test case.
     *
     * @return string
     */
    public function toString()
    {
        $class = new ReflectionClass($this);

        $buffer = sprintf('%s(%s)',

      $this->getName(), $class->name);

        if (!empty($this->data)) {
            if (is_string($this->dataName)) {
                $buffer .= sprintf(' with data set "%s"',

          $this->dataName);
            } else {
                $buffer .= sprintf(' with data set #%d (%s)',

          $this->dataName, $this->dataToString($this->data));
            }
        }

        return $buffer;
    }

    /**
     * Counts the number of test cases executed by run(TestResult result).
     *
     * @return int
     */
    public function count()
    {
        return 1;
    }

    /**
     * Gets the name of a TestCase.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     *
     * @since  Method available since Release 3.2.0
     */
    public function getExpectedException()
    {
        return $this->expectedException;
    }

    /**
     * @param mixed  $exceptionName
     * @param string $exceptionMessage
     * @param int    $exceptionCode
     *
     * @since  Method available since Release 3.2.0
     */
    public function setExpectedException($exceptionName, $exceptionMessage = '', $exceptionCode = 0)
    {
        $this->expectedException = $exceptionName;
        $this->expectedExceptionMessage = $exceptionMessage;
        $this->expectedExceptionCode = $exceptionCode;
    }

    /**
     * Returns the status of this test.
     *
     * @return int
     *
     * @since  Method available since Release 3.1.0
     */
    public function getStatus()
    {
        if ($this->exception === null) {
            return PHPUnit_Runner_BaseTestRunner::STATUS_PASSED;
        }

        if ($this->exception instanceof PHPUnit_Framework_IncompleteTest) {
            return PHPUnit_Runner_BaseTestRunner::STATUS_INCOMPLETE;
        }

        if ($this->exception instanceof PHPUnit_Framework_SkippedTest) {
            return PHPUnit_Runner_BaseTestRunner::STATUS_SKIPPED;
        }

        if ($this->exception instanceof PHPUnit_Framework_AssertionFailedError) {
            return PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE;
        }

        return PHPUnit_Runner_BaseTestRunner::STATUS_ERROR;
    }

    /**
     * Returns whether or not this test has failed.
     *
     * @return bool
     *
     * @since  Method available since Release 3.0.0
     */
    public function hasFailed()
    {
        $status = $this->getStatus();

        return $status == PHPUnit_Runner_BaseTestRunner::STATUS_FAILURE || $status == PHPUnit_Runner_BaseTestRunner::STATUS_ERROR;
    }

    /**
     * Runs the test case and collects the results in a TestResult object.
     * If no TestResult object is passed a new one will be created.
     *
     * @param PHPUnit_Framework_TestResult $result
     *
     * @throws InvalidArgumentException
     *
     * @return PHPUnit_Framework_TestResult
     */
    public function run(PHPUnit_Framework_TestResult $result = null)
    {
        if ($result === null) {
            $result = $this->createResult();
        }

        $result->run($this);

        return $result;
    }

    /**
     * Runs the bare test sequence.
     */
    public function runBare()
    {
        // Backup the $GLOBALS array.
      if ($this->backupGlobals === true) {
          $globalsBackup = serialize($GLOBALS);
      }

      // Set up the fixture.
      $this->setUp();

      // Run the test.
      try {
          // Assert pre-conditions.
        $this->assertPreConditions();

          $this->runTest();

        // Assert post-conditions.
        $this->assertPostConditions();

        // Verify Mock Object conditions.
        foreach ($this->mockObjects as $mockObject) {
            $mockObject->verify();
        }

          $this->mockObjects = [];
      } catch (Exception $e) {
          $this->exception = $e;
      }

      // Tear down the fixture.
      $this->tearDown();

      // Restore the $GLOBALS array.
      if ($this->backupGlobals === true) {
          $GLOBALS = unserialize($globalsBackup);

          if ($this->createGlobalsReference) {
              $GLOBALS['GLOBALS'] = &$GLOBALS;
          }
      }

      // Clean up INI settings.
      foreach ($this->iniSettings as $varName => $oldValue) {
          ini_set($varName, $oldValue);
      }

        $this->iniSettings = [];

      // Clean up locale settings.
      foreach ($this->locale as $category => $locale) {
          setlocale($category, $locale);
      }

      // Clean up stat cache.
      clearstatcache();

      // Workaround for missing "finally".
      if ($this->exception !== null) {
          throw $this->exception;
      }
    }

    /**
     * Override to run the test and assert its state.
     *
     * @throws RuntimeException
     */
    protected function runTest()
    {
        if ($this->name === null) {
            throw new RuntimeException('PHPUnit_Framework_TestCase::$name must not be NULL.');
        }

        try {
            $class = new ReflectionClass($this);
            $method = $class->getMethod($this->name);
        } catch (ReflectionException $e) {
            $this->fail($e->getMessage());
        }

        try {
            if (empty($this->data)) {
                $method->invoke($this);
            } else {
                $method->invokeArgs($this, $this->data);
            }
        } catch (Exception $e) {
            if (is_string($this->expectedException) && $e instanceof $this->expectedException) {
                if (is_string($this->expectedExceptionMessage) && !empty($this->expectedExceptionMessage)) {
                    $this->assertContains($this->expectedExceptionMessage, $e->getMessage());
                }

                if (is_int($this->expectedExceptionCode) && $this->expectedExceptionCode !== 0) {
                    $this->assertEquals($this->expectedExceptionCode, $e->getCode());
                }

                return;
            } else {
                throw $e;
            }
        }

        if ($this->expectedException !== null) {
            $this->fail('Expected exception '.$this->expectedException);
        }
    }

    /**
     * Sets the name of a TestCase.
     *
     * @param  string
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the shared fixture.
     *
     * @param mixed $sharedFixture
     *
     * @since  Method available since Release 3.1.0
     */
    public function setSharedFixture($sharedFixture)
    {
        $this->sharedFixture = $sharedFixture;
    }

    /**
     * This method is a wrapper for the ini_set() function that automatically
     * resets the modified php.ini setting to its original value after the
     * test is run.
     *
     * @param string $varName
     * @param string $newValue
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @since  Method available since Release 3.0.0
     */
    protected function iniSet($varName, $newValue)
    {
        if (!is_string($varName) || !is_string($newValue)) {
            throw new InvalidArgumentException();
        }

        $currentValue = ini_set($varName, $newValue);

        if ($currentValue !== false) {
            $this->iniSettings[$varName] = $currentValue;
        } else {
            throw new RuntimeException();
        }
    }

    /**
     * This method is a wrapper for the setlocale() function that automatically
     * resets the locale to its original value after the test is run.
     *
     * @param int    $category
     * @param string $locale
     *
     * @throws InvalidArgumentException
     * @throws RuntimeException
     *
     * @since  Method available since Release 3.1.0
     */
    protected function setLocale()
    {
        $args = func_get_args();

        if (count($args) < 2) {
            throw new InvalidArgumentException();
        }

        $category = $args[0];
        $locale = $args[1];

        if (!in_array($category, [LC_ALL, LC_COLLATE, LC_CTYPE, LC_MONETARY, LC_NUMERIC,
          LC_TIME, LC_MESSAGES, ])) {
            throw new InvalidArgumentException();
        }

        if (!is_array($locale) && !is_string($locale)) {
            throw new InvalidArgumentException();
        }

        $this->locale[$category] = setlocale($category, null);

        $result = call_user_func_array('setlocale', $args);

        if ($result === false) {
            throw new RuntimeException('The locale functionality is not implemented on your platform, '.'the specified locale does not exist or the category name is '.'invalid.');
        }
    }

    /**
     * Returns a mock object for the specified class.
     *
     * @param string $className
     * @param array  $methods
     * @param array  $arguments
     * @param string $mockClassName
     * @param bool   $callOriginalConstructor
     * @param bool   $callOriginalClone
     * @param bool   $callAutoload
     *
     * @return object
     *
     * @since  Method available since Release 3.0.0
     */
    protected function getMock($className, array $methods = [], array $arguments = [], $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true)
    {
        if (!is_string($className) || !is_string($mockClassName)) {
            throw new InvalidArgumentException();
        }

        $mock = PHPUnit_Framework_MockObject_Mock::generate($className, $methods, $mockClassName, $callOriginalConstructor, $callOriginalClone, $callAutoload);

        $mockClass = new ReflectionClass($mock->mockClassName);
        $mockObject = $mockClass->newInstanceArgs($arguments);

        $this->mockObjects[] = $mockObject;

        return $mockObject;
    }

    /**
     * Returns a matcher that matches when the method it is evaluated for
     * is executed zero or more times.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    protected function any()
    {
        return new PHPUnit_Framework_MockObject_Matcher_AnyInvokedCount();
    }

    /**
     * Returns a matcher that matches when the method it is evaluated for
     * is never executed.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    protected function never()
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedCount(0);
    }

    /**
     * Returns a matcher that matches when the method it is evaluated for
     * is executed at least once.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastOnce
     *
     * @since  Method available since Release 3.0.0
     */
    protected function atLeastOnce()
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedAtLeastOnce();
    }

    /**
     * Returns a matcher that matches when the method it is evaluated for
     * is executed exactly once.
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    protected function once()
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedCount(1);
    }

    /**
     * Returns a matcher that matches when the method it is evaluated for
     * is executed exactly $count times.
     *
     * @param int $count
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedCount
     *
     * @since  Method available since Release 3.0.0
     */
    protected function exactly($count)
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedCount($count);
    }

    /**
     * Returns a matcher that matches when the method it is evaluated for
     * is invoked at the given $index.
     *
     * @param int $index
     *
     * @return PHPUnit_Framework_MockObject_Matcher_InvokedAtIndex
     *
     * @since  Method available since Release 3.0.0
     */
    protected function at($index)
    {
        return new PHPUnit_Framework_MockObject_Matcher_InvokedAtIndex($index);
    }

    /**
     * @param mixed $value
     *
     * @return PHPUnit_Framework_MockObject_Stub_Return
     *
     * @since  Method available since Release 3.0.0
     */
    protected function returnValue($value)
    {
        return new PHPUnit_Framework_MockObject_Stub_Return($value);
    }

    /**
     * @param Exception $exception
     *
     * @return PHPUnit_Framework_MockObject_Stub_Exception
     *
     * @since  Method available since Release 3.1.0
     */
    protected function throwException(Exception $exception)
    {
        return new PHPUnit_Framework_MockObject_Stub_Exception($exception);
    }

    /**
     * @param mixed $value, ...
     *
     * @return PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls
     *
     * @since  Method available since Release 3.0.0
     */
    protected function onConsecutiveCalls()
    {
        $args = func_get_args();

        return new PHPUnit_Framework_MockObject_Stub_ConsecutiveCalls($args);
    }

    /**
     * @param mixed $data
     *
     * @return string
     *
     * @since  Method available since Release 3.2.1
     */
    protected function dataToString($data)
    {
        $result = [];

        foreach ($data as $_data) {
            if (is_array($_data)) {
                $result[] = 'array('.$this->dataToString($_data).')';
            } elseif (is_object($_data)) {
                $object = new ReflectionObject($_data);

                if ($object->hasMethod('__toString')) {
                    $result[] = (string) $_data;
                } else {
                    $result[] = get_class($_data);
                }
            } elseif (is_resource($_data)) {
                $result[] = '<resource>';
            } else {
                $result[] = var_export($_data, true);
            }
        }

        return implode(', ', $result);
    }

    /**
     * Creates a default TestResult object.
     *
     * @return PHPUnit_Framework_TestResult
     */
    protected function createResult()
    {
        return new PHPUnit_Framework_TestResult();
    }

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp()
    {
    }

    /**
     * Performs assertions shared by all tests of a test case.
     *
     * This method is called before the execution of a test starts
     * and after setUp() is called.
     *
     * @since  Method available since Release 3.2.8
     */
    protected function assertPreConditions()
    {
    }

    /**
     * Performs assertions shared by all tests of a test case.
     *
     * This method is called before the execution of a test ends
     * and before tearDown() is called.
     *
     * @since  Method available since Release 3.2.8
     */
    protected function assertPostConditions()
    {
        // assertPostConditions() was named sharedAssertions() in
      // PHPUnit 3.0.0-3.2.7.
      if (method_exists($this, 'sharedAssertions')) {
          $this->sharedAssertions();
      }
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown()
    {
    }
  }
}
