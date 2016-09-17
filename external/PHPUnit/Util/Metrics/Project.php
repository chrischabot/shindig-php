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
 * @version    SVN: $Id: Project.php 1985 2007-12-26 18:11:55Z sb $
 *
 * @link       http://www.phpunit.de/
 * @since      File available since Release 3.2.0
 */
@include_once 'Image/GraphViz.php';

require_once 'PHPUnit/Util/Filter.php';
require_once 'PHPUnit/Util/Metrics.php';

PHPUnit_Util_Filter::addFileToFilter(__FILE__, 'PHPUNIT');

/**
 * Project-Level Metrics.
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
class PHPUnit_Util_Metrics_Project extends PHPUnit_Util_Metrics
{
    protected static $CPD_IGNORE_LIST = [T_INLINE_HTML, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG,
      T_OPEN_TAG_WITH_ECHO, T_CLOSE_TAG, T_WHITESPACE, ];

    protected $classes = [];
    protected $files = [];
    protected $functions = [];

    protected $cls = 0;
    protected $clsa = 0;
    protected $clsc = 0;
    protected $interfs = 0;
    protected $roots = 0;
    protected $leafs = 0;
    protected $maxDit = 0;

    protected $cpdDuplicates = [];
    protected $cpdHashes = [];
    protected $dependencies = [];

  /**
   * Constructor.
   *
   * @param  array   $files
   * @param  array   $codeCoverage
   * @param  bool $cpd
   * @param  int $cpdMinLines
   * @param  int $cpdMinMatches
   */
  public function __construct(array $files, &$codeCoverage = [], $cpd = false, $cpdMinLines = 5, $cpdMinMatches = 70)
  {
      foreach ($files as $file) {
          if (file_exists($file)) {
              $this->files[$file] = PHPUnit_Util_Metrics_File::factory($file, $codeCoverage);

              foreach ($this->files[$file]->getFunctions() as $function) {
                  $this->functions[$function->getFunction()->getName()] = $function;
              }

              foreach ($this->files[$file]->getClasses() as $class) {
                  $className = $class->getClass()->getName();
                  $package = $class->getPackage();

                  $this->classes[$className] = $class;

                  if ($class->getClass()->isInterface()) {
                      $this->interfs++;
                  } else {
                      if ($class->getClass()->isAbstract()) {
                          $this->clsa++;
                      } else {
                          $this->clsc++;
                      }

                      $this->cls++;
                  }
              }
          }
      }

      $numClasses = count($this->classes);

      foreach ($this->classes as $a => $b) {
          foreach ($this->classes as $c => $d) {
              $this->dependencies[$a][$c] = 0;
          }
      }

      foreach ($this->classes as $className => $class) {
          foreach ($class->getDependencies() as $dependency) {
              $this->dependencies[$className][$dependency] = 1;
          }

          $class->setProject($this);

          if ($class->getNOC() == 0) {
              $this->leafs++;
          } elseif ($class->getClass()->getParentClass() === false) {
              $this->roots++;
          }

          $this->maxDit = max($this->maxDit, $class->getDit());
      }

      if ($cpd) {
          $this->copyPasteDetection($cpdMinLines, $cpdMinMatches);
      }
  }

  /**
   * Returns the classes of this project.
   *
   * @return array
   */
  public function getClasses()
  {
      return $this->classes;
  }

  /**
   * A class.
   *
   * @param  string $className
   *
   * @return ReflectionClass
   */
  public function getClass($className)
  {
      return $this->classes[$className];
  }

  /**
   * Returns the dependencies between the classes of this project.
   *
   * @return array
   */
  public function getDependencies()
  {
      return $this->dependencies;
  }

  /**
   * Returns the dependencies between the classes of this project
   * as GraphViz/DOT markup.
   *
   * @return Image_GraphViz
   *
   * @since  Method available since Release 3.2.2
   */
  public function getDependenciesAsDOT()
  {
      if (class_exists('Image_GraphViz', false)) {
          $graph = new Image_GraphViz(true, ['overlap' => 'scale', 'splines' => 'true',
          'sep'                                    => '.1', 'fontsize' => '8', ]);

          foreach (array_keys($this->dependencies) as $className) {
              $graph->addNode($className);
          }

          foreach ($this->dependencies as $from => $dependencies) {
              foreach ($dependencies as $to => $flag) {
                  if ($flag === 1) {
                      $graph->addEdge([$from => $to]);
                  }
              }
          }

          return $graph;
      } else {
          throw new RuntimeException();
      }
  }

  /**
   * Returns the duplicates found by the Copy & Paste Detection (CPD).
   *
   * @return array
   */
  public function getDuplicates()
  {
      return $this->cpdDuplicates;
  }

  /**
   * Returns the files of this project.
   *
   * @return array
   */
  public function getFiles()
  {
      return $this->files;
  }

  /**
   * A file.
   *
   * @param  string $className
   *
   * @return ReflectionClass
   */
  public function getFile($filename)
  {
      return $this->files[$filename];
  }

  /**
   * Functions.
   *
   * @return array
   */
  public function getFunctions()
  {
      return $this->functions;
  }

  /**
   * A function.
   *
   * @param  string $functionName
   *
   * @return ReflectionClass
   */
  public function getFunction($functionName)
  {
      return $this->functions[$functionName];
  }

  /**
   * Returns the Number of Classes (CLS) for the project.
   *
   * @return int
   *
   * @see    http://www.aivosto.com/project/help/pm-oo-misc.html
   */
  public function getCLS()
  {
      return $this->cls;
  }

  /**
   * Returns the Number of Abstract Classes (CLSa) for the project.
   *
   * @return int
   *
   * @see    http://www.aivosto.com/project/help/pm-oo-misc.html
   */
  public function getCLSa()
  {
      return $this->clsa;
  }

  /**
   * Returns the Number of Concrete Classes (CLSc) for the project.
   *
   * @return int
   *
   * @see    http://www.aivosto.com/project/help/pm-oo-misc.html
   */
  public function getCLSc()
  {
      return $this->clsc;
  }

  /**
   * Returns the Number of Root Classes (ROOTS) for the project.
   *
   * @return int
   *
   * @see    http://www.aivosto.com/project/help/pm-oo-misc.html
   */
  public function getRoots()
  {
      return $this->roots;
  }

  /**
   * Returns the Number of Leaf Classes (LEAFS) for the project.
   *
   * @return int
   *
   * @see    http://www.aivosto.com/project/help/pm-oo-misc.html
   */
  public function getLeafs()
  {
      return $this->leafs;
  }

  /**
   * Returns the Number of Interfaces (INTERFS) for the project.
   *
   * @return int
   *
   * @see    http://www.aivosto.com/project/help/pm-oo-misc.html
   */
  public function getInterfs()
  {
      return $this->interfs;
  }

  /**
   * Returns the Maximum Depth of Intheritance Tree (maxDIT) for the project.
   *
   * @return int
   *
   * @see    http://www.aivosto.com/project/help/pm-oo-misc.html
   */
  public function getMaxDit()
  {
      return $this->maxDit;
  }

  /**
   * Copy & Paste Detection (CPD).
   *
   * @param  int $minLines
   * @param  int $minMatches
   *
   * @author Johann-Peter Hartmann <johann-peter.hartmann@mayflower.de>
   */
  protected function copyPasteDetection($minLines, $minMatches)
  {
      foreach ($this->files as $file) {
          $currentTokenPositions = [];
          $currentSignature = '';
          $lines = $file->getLines();
          $tokens = $file->getTokens();
          $tokenNr = 0;
          $line = 1;

          foreach (array_keys($tokens) as $key) {
              $token = $tokens[$key];

              if (is_string($token)) {
                  $line += substr_count($token, "\n");
              } else {
                  if (!in_array($token[0], self::$CPD_IGNORE_LIST)) {
                      $currentTokenPositions[$tokenNr++] = $line;
                      $currentSignature .= chr($token[0] & 255).pack('N*', crc32($token[1]));
                  }

                  $line += substr_count($token[1], "\n");
              }
          }

          $tokenNr = 0;
          $firstLine = 0;
          $found = false;

          if (count($currentTokenPositions) > 0) {
              do {
                  $line = $currentTokenPositions[$tokenNr];

                  $hash = substr(md5(substr($currentSignature, $tokenNr * 5, $minMatches * 5), true), 0, 8);

                  if (isset($this->cpdHashes[$hash])) {
                      $found = true;

                      if ($firstLine === 0) {
                          $firstLine = $line;
                          $firstHash = $hash;
                          $firstToken = $tokenNr;
                      }
                  } else {
                      if ($found) {
                          if ($line + 1 - $firstLine > $minLines) {
                              $this->cpdDuplicates[] = [
                    'fileA'      => $this->cpdHashes[$firstHash][0],
                    'firstLineA' => $this->cpdHashes[$firstHash][1],
                    'fileB'      => $file,
                    'firstLineB' => $firstLine,
                    'numLines'   => $line + 1 - $firstLine,
                    'numTokens'  => $tokenNr + 1 - $firstToken, ];
                          }

                          $found = false;
                          $firstLine = 0;
                      }

                      $this->cpdHashes[$hash] = [$file, $line];
                  }

                  $tokenNr++;
              } while ($tokenNr <= (count($currentTokenPositions) - $minMatches) + 1);
          }

          if ($found) {
              if ($line + 1 - $firstLine > $minLines) {
                  $this->cpdDuplicates[] = [
              'fileA'      => $this->cpdHashes[$firstHash][0],
              'firstLineA' => $this->cpdHashes[$firstHash][1],
              'fileB'      => $file, 'firstLineB' => $firstLine,
              'numLines'   => $line + 1 - $firstLine,
              'numTokens'  => $tokenNr + 1 - $firstToken, ];
              }

              $found = false;
          }
      }
  }
}
