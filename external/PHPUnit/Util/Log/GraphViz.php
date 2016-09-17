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
 * @version    SVN: $Id: GraphViz.php 1985 2007-12-26 18:11:55Z sb $
 *
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.0.0
 */
@include_once 'Image/GraphViz.php';

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/Util/Filter.php';
require_once 'PHPUnit/Util/Filesystem.php';
require_once 'PHPUnit/Util/Printer.php';
require_once 'PHPUnit/Util/Test.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * A TestListener that generates maps of the executed tests
 * in GraphViz markup.
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
 * @since      Class available since Release 3.0.0
 */
class PHPUnit_Util_Log_GraphViz extends PHPUnit_Util_Printer implements PHPUnit_Framework_TestListener
{
    /**
   * @var    Image_GraphViz
   */
  protected $graph;

  /**
   * @var    bool
   */
  protected $currentTestSuccess = true;

  /**
   * @var    string[]
   */
  protected $testSuites = [];

  /**
   * @var    int
   */
  protected $testSuiteLevel = 0;

  /**
   * @var    int[]
   */
  protected $testSuiteFailureOrErrorCount = [0];

  /**
   * @var    int[]
   */
  protected $testSuiteIncompleteOrSkippedCount = [0];

  /**
   * Constructor.
   *
   * @param  mixed $out
   */
  public function __construct($out = null)
  {
      $this->graph = new Image_GraphViz(true, ['overlap' => 'scale', 'splines' => 'true',
        'sep'                                          => '.1', 'fontsize' => '8', ]);

      parent::__construct($out);
  }

  /**
   * Flush buffer and close output.
   */
  public function flush()
  {
      $this->write($this->graph->parse());

      parent::flush();
  }

  /**
   * An error occurred.
   *
   * @param  PHPUnit_Framework_Test $test
   * @param  Exception              $e
   * @param  float                  $time
   */
  public function addError(PHPUnit_Framework_Test $test, Exception $e, $time)
  {
      $this->addTestNode($test, 'red');
      $this->testSuiteFailureOrErrorCount[$this->testSuiteLevel]++;

      $this->currentTestSuccess = false;
  }

  /**
   * A failure occurred.
   *
   * @param  PHPUnit_Framework_Test                 $test
   * @param  PHPUnit_Framework_AssertionFailedError $e
   * @param  float                                  $time
   */
  public function addFailure(PHPUnit_Framework_Test $test, PHPUnit_Framework_AssertionFailedError $e, $time)
  {
      $this->addTestNode($test, 'red');
      $this->testSuiteFailureOrErrorCount[$this->testSuiteLevel]++;

      $this->currentTestSuccess = false;
  }

  /**
   * Incomplete test.
   *
   * @param  PHPUnit_Framework_Test $test
   * @param  Exception              $e
   * @param  float                  $time
   */
  public function addIncompleteTest(PHPUnit_Framework_Test $test, Exception $e, $time)
  {
      $this->addTestNode($test, 'yellow');
      $this->testSuiteIncompleteOrSkippedCount[$this->testSuiteLevel]++;

      $this->currentTestSuccess = false;
  }

  /**
   * Skipped test.
   *
   * @param  PHPUnit_Framework_Test $test
   * @param  Exception              $e
   * @param  float                  $time
   */
  public function addSkippedTest(PHPUnit_Framework_Test $test, Exception $e, $time)
  {
      $this->addTestNode($test, 'yellow');
      $this->testSuiteIncompleteOrSkippedCount[$this->testSuiteLevel]++;

      $this->currentTestSuccess = false;
  }

  /**
   * A testsuite started.
   *
   * @param  PHPUnit_Framework_TestSuite $suite
   */
  public function startTestSuite(PHPUnit_Framework_TestSuite $suite)
  {
      $this->graph->addNode($suite->getName());

      if ($this->testSuiteLevel > 0) {
          $this->graph->addEdge([$this->testSuites[$this->testSuiteLevel] => $suite->getName()]);
      }

      $this->testSuiteLevel++;
      $this->testSuites[$this->testSuiteLevel] = $suite->getName();
      $this->testSuiteFailureOrErrorCount[$this->testSuiteLevel] = 0;
      $this->testSuiteIncompleteOrSkippedCount[$this->testSuiteLevel] = 0;
  }

  /**
   * A testsuite ended.
   *
   * @param  PHPUnit_Framework_TestSuite $suite
   */
  public function endTestSuite(PHPUnit_Framework_TestSuite $suite)
  {
      $color = 'red';

      if ($this->testSuiteFailureOrErrorCount[$this->testSuiteLevel] == 0 && $this->testSuiteIncompleteOrSkippedCount[$this->testSuiteLevel] == 0) {
          $color = 'green';
      } elseif ($this->testSuiteFailureOrErrorCount[$this->testSuiteLevel] == 0 && $this->testSuiteIncompleteOrSkippedCount[$this->testSuiteLevel] > 0) {
          $color = 'yellow';
      }

      $this->graph->addNode($this->testSuites[$this->testSuiteLevel], ['color' => $color]);

      if ($this->testSuiteLevel > 1) {
          $this->testSuiteFailureOrErrorCount[$this->testSuiteLevel - 1] += $this->testSuiteFailureOrErrorCount[$this->testSuiteLevel];
          $this->testSuiteIncompleteOrSkippedCount[$this->testSuiteLevel - 1] += $this->testSuiteIncompleteOrSkippedCount[$this->testSuiteLevel];
      }

      $this->testSuiteLevel--;
  }

  /**
   * A test started.
   *
   * @param  PHPUnit_Framework_Test $test
   */
  public function startTest(PHPUnit_Framework_Test $test)
  {
      $this->currentTestSuccess = true;
  }

  /**
   * A test ended.
   *
   * @param  PHPUnit_Framework_Test $test
   * @param  float                  $time
   */
  public function endTest(PHPUnit_Framework_Test $test, $time)
  {
      if ($this->currentTestSuccess) {
          $this->addTestNode($test, 'green');
      }
  }

  /**
   * @param  PHPUnit_Framework_Test $test
   * @param  string                  $color
   */
  protected function addTestNode(PHPUnit_Framework_Test $test, $color)
  {
      $name = PHPUnit_Util_Test::describe($test, false);

      $this->graph->addNode($name[1], ['color' => $color], $this->testSuites[$this->testSuiteLevel]);

      $this->graph->addEdge([$this->testSuites[$this->testSuiteLevel] => $name[1]]);
  }
}
