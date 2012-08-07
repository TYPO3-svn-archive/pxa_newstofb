<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

$tempColumns = array (
	'tx_pxanewstofb_dont_publish' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:pxa_newstofb/locallang_db.xml:tx_pxanewstofb_dont_publish',		
		'config' => array (
			'type' => 'check',
		)
	),
	'tx_pxanewstofb_published' => array (		
		'exclude' => 0,		
		'label' => 'LLL:EXT:pxa_newstofb/locallang_db.xml:tx_pxanewstofb_published',		
		'config' => array (
			'type' => 'check',
		)
	),
);

t3lib_div::loadTCA('tt_news');
t3lib_extMgm::addTCAcolumns('tt_news', $tempColumns,1);
t3lib_extMgm::addToAllTCAtypes('tt_news', '--div--;Pxa News to Facebook,tx_pxanewstofb_dont_publish,tx_pxanewstofb_published;;;;1-1-1');
?>
