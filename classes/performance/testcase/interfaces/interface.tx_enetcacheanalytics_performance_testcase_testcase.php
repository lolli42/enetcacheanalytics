<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Christian Kuhn <lolli@schwarzbu.ch>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/**
 * Interface for performance test cases
 *
 * @package TYPO3
 * @subpackage tx_enetcacheanalytics
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
interface tx_enetcacheanalytics_performance_testcase_Testcase {
	/**
	 * Instantiate and set up testcase
	 *
	 * @var tx_enetcacheanalytics_performance_backend_abstractbackend Backend instance to run test on
	 * @return void
	 */
	public function setUp(tx_enetcacheanalytics_performance_backend_Backend $backend);

	/**
	 * Run test case
	 */
	public function run();

	/**
	 * Cleanup backend
	 */
	public function tearDown();

	/**
	 * Get testcase name
	 */
	public function getName();

	/**
	 * Initialize the scale factor
	 *
	 * @param integer scale factor in percent
	 * @return void
	 */
	public function setScaleFactor($factor = 400);

	/**
	 * Set number of data points to retrieve
	 *
	 * @param integer Number of points
	 * @return void
	 */
	public function setNumberOfDataPoints($points = 3);
}
?>
