<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Class ExtDirectServer of backend module to fetch data.
 *
 * @package TYPO3
 * @subpackage tx_enetcacheanalytics
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class tx_enetcacheanalytics_ExtDirectServer {

	/**
	 * @var array All available backends
	 */
	protected static $backends = array(
		'ApcBackend',
		'DbBackend',
		'DbBackendCompressed',
		'DbBackendCompressedEnetcache',
		'FileBackend',
		'MemcachedBackend',
		'MemcachedBackendCompressed',
		'PdoBackendSqlite',
		'PhpredisRedisBackend',
		'RedisBackendRediscache',
	);

	/**
	 * @var array All available test cases
	 */
	protected static $testcases = array(
		'SetMultipleTimes',
		'GetMultipleTimes',
		'SetSingleTag',
		'GetByIdentifier',
		'DropBySingleTag',
		'SetKiloBytesOfData',
		'GetKiloBytesOfData',
		'SetMultipleTags',
		'DropMultipleTags',
		'FlushSingleTag',
		'FlushMultipleTags',
	);

	/**
	 * Method concerning cache log analyzer tab to get log entries
	 *
	 * @param  $parameters array
	 * @return array
	 */
	public function getLogEntries($parameters) {
		$where = '';
		if (strlen($parameters->unique_id) > 0) {
			$where = 'unique_id=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($parameters->unique_id, 'tx_enetcache_log');
		}
		$logRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_enetcache_log',
			$where
		);

		$data = array();
		$time = 0;
		foreach ($logRows as $row) {
			if ($time === 0) {
				$time = $row['microtime'];
			}
			$elapsedTime = $row['microtime'] - $time;
			$row['time'] = $elapsedTime;

			$row['caller'] = self::unserializeCallerField($row['caller']);

			if ($row['fe_user'] > 0) {
				$userName = 'FE: ' . $row['fe_user'];
			} elseif ($row['be_user'] > 0) {
				$userName = 'BE: ' . $row['be_user'];
			} else {
				$userName = '';
			}
			$row['user'] = $userName;

			$tags = @unserialize($row['tags']);
			if ($tags) {
				$row['tags'] = implode('<br />', $tags);
			} else {
				$row['tags'] = '';
			}

			$identifierSource = @unserialize($row['identifier_source']);
			if ($identifierSource) {
				$row['identifier_source'] = t3lib_utility_Debug::viewArray($identifierSource);
			} else {
				$row['identifier_source'] = '';
			}

			$htmlData = @unserialize($row['data']);
			if (is_array($htmlData)) {
				$row['data'] = t3lib_utility_Debug::viewArray($htmlData);
			} elseif (strlen($htmlData) > 0) {
				$row['data'] = htmlspecialchars($htmlData);
			} else {
				$row['data'] = '';
			}

			$data[] = $row;
		}

		return array(
			'length' => count($data),
			'data' => $data,
		);
	}

	/**
	 * Method concerning cache log analyzer tab to get log group for group drop down
	 *
	 * @return array
	 */
	public function getLogGroups() {
		$groupRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'DISTINCT unique_id, tstamp, page_uid',
			'tx_enetcache_log',
			'',
			'',
			'tstamp DESC'
		);

		$data = array();
		foreach ($groupRows as $row) {
			$formatDate = date('d/m/Y H:i:s', $row['tstamp']);
			$formatPageID = ($row['page_uid'] > 0) ? ' PID:' . $row['page_uid'] : '';
			$title = $formatDate . $formatPageID;

			$data[] = array(
				'unique_id' => $row['unique_id'],
				'title' => $title,
			);
		}

		return array(
			'length' => count($data),
			'data' => $data,
		);
	}

	/**
	 * Method concerning cache log analyzer tab to get stats of a specific log group
	 *
	 * @param  $parameters array
	 * @return array
	 */
	public function getLogStats($parameters) {
		$where = '';
		if (strlen($parameters->unique_id) > 0) {
			$where = 'unique_id=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($parameters->unique_id, 'tx_enetcache_log');
		}
		$logRows = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
			'*',
			'tx_enetcache_log',
			$where
		);

		$numberOfPlugins = 0;
		$numberOfEnetcachePlugins = 0;
		$numberOfSuccessfulGets = 0;

		foreach ($logRows as $row) {
			switch ($row['request_type']) {
				case 'COBJ START':
					$numberOfPlugins ++;
					break;
				case 'GET':
					if (strlen($row['data']) > 0) {
						$numberOfSuccessfulGets ++;
					}
					$numberOfEnetcachePlugins ++;
					break;
			}
		}

		$data = array(
			'unique_id' => $parameters->unique_id,
			'numberOfPlugins' => $numberOfPlugins,
			'numberOfEnetcachePlugins' => $numberOfEnetcachePlugins,
			'numberOfSuccessfulGets' => $numberOfSuccessfulGets,
		);

		return array(
			'length' => count($data),
			'data' => $data
		);
	}

	/**
	 * Helper method of cache log tab to unserialize a log entry backtrace
	 *
	 * @static
	 * @param  $callerField String serialized backtrace
	 * @return string
	 */
	protected static function unserializeCallerField($callerField) {
		$callerField = unserialize($callerField);

		$result = array();
		foreach ($callerField as $k => $v) {
			$result[] = $k . ': ' . $v . '<br />';
		}

		return implode(chr(10), $result);
	}

	/**
	 * Method concerning performance tab to get all available test entries
	 *
	 * @return array
	 */
	public function getNotEnabledTestEntries() {
		$settings = $GLOBALS['BE_USER']->getModuleData('enetcacheanalytics');

		$data = array();
		foreach (self::$testcases as $testcase) {
			$enabled = FALSE;
			if (isset($settings['performance']['enabledTests'][$testcase])
				&& $settings['performance']['enabledTests'][$testcase]
			) {
				$enabled = TRUE;
			}
			if (!$enabled) {
				$data[] = array(
					'name' => $testcase,
					'table' => '',
					'graph' => '',
				);
			}
		}

		return array(
			'length' => count($data),
			'data' => $data,
		);
	}

	/**
	 * Method concerning performance tab to get all enabled test entries
	 *
	 * @return array
	 */
	public function getEnabledTestEntries() {
		$settings = $GLOBALS['BE_USER']->getModuleData('enetcacheanalytics');

		$data = array();
		foreach (self::$testcases as $testcase) {
			$enabled = FALSE;
			if (isset($settings['performance']['enabledTests'][$testcase])
				&& $settings['performance']['enabledTests'][$testcase]
			) {
				$enabled = TRUE;
			}
			if ($enabled) {
				$data[] = array(
					'name' => $testcase,
					'table' => '',
					'graph' => '',
				);
			}
		}

		return array(
			'length' => count($data),
			'data' => $data,
		);
	}

	/**
	 * Method concerning performance tab to get all available backends
	 *
	 * @return array
	 */
	public function getBackends() {
		$settings = $GLOBALS['BE_USER']->getModuleData('enetcacheanalytics');

		$data = array();
		foreach (self::$backends as $backend) {
			$selected = FALSE;
			if (
				isset($settings['performance']['enabledBackends'][$backend])
				&& $settings['performance']['enabledBackends'][$backend]) {
				$selected = TRUE;
			}
			$data[] = array(
				'name' => $backend,
				'selected' => $selected
			);
		}

		return array(
			'length' => count($data),
			'data' => $data,
		);
	}

	/**
	 * Method concerning performance tab to run a specific test
	 *
	 * @return array
	 */
	public function runPerformanceTest($test) {
		/** @var $testSuite tx_enetcacheanalytics_performance_TestSuite */
		$testSuite = t3lib_div::makeInstance('tx_enetcacheanalytics_performance_TestSuite');

		$testSuite->setSelectedTestcases(array($test));

		$settings = $GLOBALS['BE_USER']->getModuleData('enetcacheanalytics');
		$selectedBackends = array();
		foreach (self::$backends as $backend) {
			if (isset($settings['performance']['enabledBackends'][$backend])
				&& $settings['performance']['enabledBackends'][$backend]) {
				$selectedBackends[] = $backend;
			}
		}
		$testSuite->setSelectedBackends($selectedBackends);
		
		if (isset($settings['performance']['settings']['dataPoints'])) {
			$testSuite->setNumberOfDataPoints($settings['performance']['settings']['dataPoints']);
		}
		if (isset($settings['performance']['settings']['scaleFactor'])) {
			$testSuite->setScaleFactor($settings['performance']['settings']['scaleFactor']);
		}

		$testSuite->run();
		$result = $testSuite->getTestResults();
		/** @var $table tx_enetcacheanalytics_performance_view_ResultTable */
		$table = t3lib_div::makeInstance('tx_enetcacheanalytics_performance_view_ResultTable', $result);
		/** @var $grapher tx_enetcacheanalytics_performance_view_ResultGraph */
		$grapher = t3lib_div::makeInstance('tx_enetcacheanalytics_performance_view_ResultGraph', $result);

		$result = array();
		$result['name'] = $test;
		$result['graph'] = $grapher->render();
		$result['table'] = $table->render();

		return $result;
	}
}
?>