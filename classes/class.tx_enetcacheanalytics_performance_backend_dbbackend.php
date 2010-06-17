<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010  Christian Kuhn <lolli@schwarzbu.ch>
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
 * Class test implementation for db backend
 *
 * @package TYPO3
 * @subpackage tx_enetcacheanalytics
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class tx_enetcacheanalytics_performance_backend_DbBackend extends tx_enetcacheanalytics_performance_backend_AbstractBackend {
	/**
	 * Used table names
	 */
	const cacheTable = 'tx_enetcacheanalytics_performance';
	const tagsTable = 'tx_enetcacheanalytics_performance_tags';

	/**
	 * Set up this backend
	 */
	public function setUp() {
		$GLOBALS['TYPO3_DB']->sql_query('CREATE TABLE ' . self::cacheTable . ' (
			id int(11) unsigned NOT NULL auto_increment,
			identifier varchar(128) DEFAULT \'\' NOT NULL,
			crdate int(11) unsigned DEFAULT \'0\' NOT NULL,
			content mediumtext,
			lifetime int(11) unsigned DEFAULT \'0\' NOT NULL,
			PRIMARY KEY (id),
			KEY cache_id (identifier)
		) ENGINE=InnoDB;
		');

		$GLOBALS['TYPO3_DB']->sql_query('CREATE TABLE ' . self::tagsTable. ' (
			id int(11) unsigned NOT NULL auto_increment,
			identifier varchar(128) DEFAULT \'\' NOT NULL,
			tag varchar(128) DEFAULT \'\' NOT NULL,
			PRIMARY KEY (id),
			KEY cache_id (identifier),
			KEY cache_tag (tag)
		) ENGINE=InnoDB;
		');

		$this->backend = t3lib_div::makeInstance(
			't3lib_cache_backend_DbBackend',
			array(
				'cacheTable' => self::cacheTable,
				'tagsTable' => self::tagsTable,
			)
		);

		$this->backend->setCache($this->getMockFrontend());
	}

	public function tearDown() {
		$GLOBALS['TYPO3_DB']->sql_query(
			'DROP TABLE ' . self::cacheTable . ';'
		);
		$GLOBALS['TYPO3_DB']->sql_query(
			'DROP TABLE ' . self::tagsTable . ';'
		);
	}

	public function setCacheEntriesWithSingleTag($numberOfEntries = 100) {
		$message = parent::setCacheEntriesWithSingleTag($numberOfEntries);
		return $message;
	}
}
?>