<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE == 'BE') {
	t3lib_extMgm::addModulePath('tools_txenetcacheanalyticsM1', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
	t3lib_extMgm::addModule('tools', 'txenetcacheanalyticsM1', '', t3lib_extMgm::extPath($_EXTKEY) . 'mod1/');
}

t3lib_extMgm::registerExtDirectComponent(
	'TYPO3.EnetcacheAnalytics.Analyzer',
	t3lib_extMgm::extPath($_EXTKEY) . 'classes/extdirect/class.tx_enetcacheanalytics_extdirectserver.php:tx_enetcacheanalytics_ExtDirectServer'
);
?>