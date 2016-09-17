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
 * @version    SVN: $Id: Directory.php 1985 2007-12-26 18:11:55Z sb $
 *
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.2.0
 */
require_once 'PHPUnit/Util/Filter.php';
require_once 'PHPUnit/Util/Filesystem.php';
require_once 'PHPUnit/Util/Template.php';
require_once 'PHPUnit/Util/Report/Node.php';
require_once 'PHPUnit/Util/Report/Node/File.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * Represents a directory in the code coverage information tree.
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
 * @since      Class available since Release 3.2.0
 */
class PHPUnit_Util_Report_Node_Directory extends PHPUnit_Util_Report_Node
{
    /**
   * @var    PHPUnit_Util_Report_Node[]
   */
  protected $children = [];

  /**
   * @var    PHPUnit_Util_Report_Node_Directory[]
   */
  protected $directories = [];

  /**
   * @var    PHPUnit_Util_Report_Node_File[]
   */
  protected $files = [];

  /**
   * @var    array
   */
  protected $classes;

  /**
   * @var    int
   */
  protected $numExecutableLines = -1;

  /**
   * @var    int
   */
  protected $numExecutedLines = -1;

  /**
   * @var    int
   */
  protected $numClasses = -1;

  /**
   * @var    int
   */
  protected $numCalledClasses = -1;

  /**
   * @var    int
   */
  protected $numMethods = -1;

  /**
   * @var    int
   */
  protected $numCalledMethods = -1;

  /**
   * Adds a new directory.
   *
   * @return PHPUnit_Util_Report_Node_Directory
   */
  public function addDirectory($name)
  {
      $directory = new self($name, $this);

      $this->children[] = $directory;
      $this->directories[] = &$this->children[count($this->children) - 1];

      return $directory;
  }

  /**
   * Adds a new file.
   *
   * @param  string  $name
   * @param  array   $lines
   * @param  bool $yui
   * @param  bool $highlight
   *
   * @throws RuntimeException
   *
   * @return PHPUnit_Util_Report_Node_File
   */
  public function addFile($name, array $lines, $yui, $highlight)
  {
      $file = new PHPUnit_Util_Report_Node_File($name, $this, $lines, $yui, $highlight);

      $this->children[] = $file;
      $this->files[] = &$this->children[count($this->children) - 1];

      $this->numExecutableLines = -1;
      $this->numExecutedLines = -1;

      return $file;
  }

  /**
   * Returns the directories in this directory.
   *
   * @return
   */
  public function getDirectories()
  {
      return $this->directories;
  }

  /**
   * Returns the files in this directory.
   *
   * @return
   */
  public function getFiles()
  {
      return $this->files;
  }

  /**
   * Returns the classes of this node.
   *
   * @return array
   */
  public function getClasses()
  {
      if ($this->classes === null) {
          $this->classes = [];

          foreach ($this->children as $child) {
              $this->classes = array_merge($this->classes, $child->getClasses());
          }
      }

      return $this->classes;
  }

  /**
   * Returns the number of executable lines.
   *
   * @return int
   */
  public function getNumExecutableLines()
  {
      if ($this->numExecutableLines == -1) {
          $this->numExecutableLines = 0;

          foreach ($this->children as $child) {
              $this->numExecutableLines += $child->getNumExecutableLines();
          }
      }

      return $this->numExecutableLines;
  }

  /**
   * Returns the number of executed lines.
   *
   * @return int
   */
  public function getNumExecutedLines()
  {
      if ($this->numExecutedLines == -1) {
          $this->numExecutedLines = 0;

          foreach ($this->children as $child) {
              $this->numExecutedLines += $child->getNumExecutedLines();
          }
      }

      return $this->numExecutedLines;
  }

  /**
   * Returns the number of classes.
   *
   * @return int
   */
  public function getNumClasses()
  {
      if ($this->numClasses == -1) {
          $this->numClasses = 0;

          foreach ($this->children as $child) {
              $this->numClasses += $child->getNumClasses();
          }
      }

      return $this->numClasses;
  }

  /**
   * Returns the number of classes of which at least one method
   * has been called at least once.
   *
   * @return int
   */
  public function getNumCalledClasses()
  {
      if ($this->numCalledClasses == -1) {
          $this->numCalledClasses = 0;

          foreach ($this->children as $child) {
              $this->numCalledClasses += $child->getNumCalledClasses();
          }
      }

      return $this->numCalledClasses;
  }

  /**
   * Returns the number of methods.
   *
   * @return int
   */
  public function getNumMethods()
  {
      if ($this->numMethods == -1) {
          $this->numMethods = 0;

          foreach ($this->children as $child) {
              $this->numMethods += $child->getNumMethods();
          }
      }

      return $this->numMethods;
  }

  /**
   * Returns the number of methods that has been called at least once.
   *
   * @return int
   */
  public function getNumCalledMethods()
  {
      if ($this->numCalledMethods == -1) {
          $this->numCalledMethods = 0;

          foreach ($this->children as $child) {
              $this->numCalledMethods += $child->getNumCalledMethods();
          }
      }

      return $this->numCalledMethods;
  }

  /**
   * Renders this node.
   *
   * @param string  $target
   * @param string  $title
   * @param string  $charset
   * @param bool $highlight
   * @param int $lowUpperBound
   * @param int $highLowerBound
   */
  public function render($target, $title, $charset = 'ISO-8859-1', $highlight = false, $lowUpperBound = 35, $highLowerBound = 70)
  {
      $this->doRender($target, $title, $charset, $highlight, $lowUpperBound, $highLowerBound);

      foreach ($this->children as $child) {
          $child->render($target, $title, $charset, $highlight, $lowUpperBound, $highLowerBound);
      }
  }

  /**
   * @param string  $target
   * @param string  $title
   * @param string  $charset
   * @param bool $highlight
   * @param int $lowUpperBound
   * @param int $highLowerBound
   */
  protected function doRender($target, $title, $charset, $highlight, $lowUpperBound, $highLowerBound)
  {
      $cleanId = PHPUnit_Util_Filesystem::getSafeFilename($this->getId());
      $file = $target.$cleanId.'.html';

      $template = new PHPUnit_Util_Template(PHPUnit_Util_Report::$templatePath.'directory.html');

      $this->setTemplateVars($template, $title, $charset);

      $totalClassesPercent = $this->getCalledClassesPercent();

      list($totalClassesColor, $totalClassesLevel) = $this->getColorLevel($totalClassesPercent, $lowUpperBound, $highLowerBound);

      $totalMethodsPercent = $this->getCalledMethodsPercent();

      list($totalMethodsColor, $totalMethodsLevel) = $this->getColorLevel($totalMethodsPercent, $lowUpperBound, $highLowerBound);

      $totalLinesPercent = $this->getLineExecutedPercent();

      list($totalLinesColor, $totalLinesLevel) = $this->getColorLevel($totalLinesPercent, $lowUpperBound, $highLowerBound);

      $template->setVar([
        'total_item'      => $this->renderTotalItem($lowUpperBound, $highLowerBound),
        'items'           => $this->renderItems($lowUpperBound, $highLowerBound),
        'low_upper_bound' => $lowUpperBound, 'high_lower_bound' => $highLowerBound, ]);

      $template->renderTo($file);
  }

  /**
   * @return string
   */
  protected function renderItems($lowUpperBound, $highLowerBound)
  {
      $items = $this->doRenderItems($this->directories, $lowUpperBound, $highLowerBound);
      $items .= $this->doRenderItems($this->files, $lowUpperBound, $highLowerBound);

      return $items;
  }

  /**
   * @param  array    $items
   *
   * @return string
   */
  protected function doRenderItems(array $items, $lowUpperBound, $highLowerBound)
  {
      $result = '';

      foreach ($items as $item) {
          $result .= $this->doRenderItemObject($item, $lowUpperBound, $highLowerBound);
      }

      return $result;
  }
}
