<?php

########################################################################
# Extension Manager/Repository config file for ext "pxa_newstofb".
#
# Auto generated 01-02-2012 22:37
#
# Manual updates:
# Only the data in the array - everything else is removed by next
# writing. "version" and "dependencies" must not be touched!
########################################################################

$EM_CONF[$_EXTKEY] = array(
	'title' => 'News to Facebook Integration',
	'description' => 'Publish news from tt_news in TYPO3 to Facebook wall.',
	'category' => 'be',
	'author' => 'Web Essentials for Pixelant',
	'author_email' => 'ext-pxa_newstofb@web-essentials.asia',
	'shy' => '',
	'dependencies' => 'tt_news',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => 'www.web-essentials.asia, www.pixelant.se',
	'version' => '1.0.0',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.4.0-0.0.0',
			'tt_news' => '',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:15:{s:9:"ChangeLog";s:4:"33f1";s:10:"README.txt";s:4:"7d69";s:20:"class.ext_update.php";s:4:"9b19";s:32:"class.tx_pxanewstofb_publish.php";s:4:"10fa";s:16:"ext_autoload.php";s:4:"9e25";s:21:"ext_conf_template.txt";s:4:"c338";s:12:"ext_icon.gif";s:4:"694d";s:17:"ext_localconf.php";s:4:"b080";s:14:"ext_tables.php";s:4:"222e";s:14:"ext_tables.sql";s:4:"1bb8";s:16:"locallang_db.xml";s:4:"66ed";s:14:"doc/manual.sxw";s:4:"d147";s:21:"lib/base_facebook.php";s:4:"1f1b";s:16:"lib/facebook.php";s:4:"9ca9";s:26:"lib/fb_ca_chain_bundle.crt";s:4:"c305";}',
	'suggests' => array(
	),
);

?>