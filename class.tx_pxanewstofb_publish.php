<?php 
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Web Essentials for Pixelant <ext-pxa_newstofb@web-essentials.asia>
 *
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

require_once(t3lib_extMgm::extPath('pxa_newstofb', 'lib/facebook.php'));
require_once(PATH_tslib . 'class.tslib_fe.php');
require_once(PATH_t3lib . 'class.t3lib_userauth.php');
require_once(PATH_tslib . 'class.tslib_feuserauth.php');
require_once(PATH_t3lib . 'class.t3lib_cs.php');
require_once(PATH_tslib . 'class.tslib_content.php');
require_once(PATH_t3lib . 'class.t3lib_tstemplate.php');
require_once(PATH_t3lib . 'class.t3lib_page.php');
require_once(PATH_t3lib . 'class.t3lib_timetrack.php');

/**
 * Task to post news records to Facebook
 * 
 *
 */
class tx_pxanewstofb_publish extends tx_scheduler_Task {
                                                                
	private $cobj;

	/**
	 * Executes the schedule task to post news to facebook
	 * 
	 * @return boolean - True when the task executed successfully
	 */
	public function execute() {
  
		global $GLOBALS, $tsfe, $TYPO3_CONF_VARS;

			// Get pxa_newstofb extConf array
		$fbAppSettings = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['pxa_newstofb']);
		$groupList = array();
		$groupList = t3lib_div::trimExplode(',', $fbAppSettings['groupId'], TRUE);
		$pageList = t3lib_div::trimExplode(',', $fbAppSettings['pageId'], TRUE);

			// Making an instance of frontend classes, tsfe
		$this->cObj = t3lib_div::makeInstance('tslib_cObj');
		$timeTrack = t3lib_div::makeInstance('t3lib_timeTrack');
		$GLOBALS['TT'] = new $timeTrack();
		$GLOBALS['TT']->start();
		$tsfe = new tslib_fe($TYPO3_CONF_VARS, $fbAppSettings['detailNewsPid'], 0, 0);
		$tsfe->connectToDB();
		$tsfe->initFEuser();
    $tsfe->fetch_the_id();      
		$tsfe->getPageAndRootline();
		$this->cObj->start(array(), '');
		$tsfe->initTemplate();
		$tsfe->forceTemplateParsing = 1;
		$tsfe->getConfigArray();
		$tsfe->initUserGroups();
		$tsfe->initTemplate();
		$tsfe->determineId();
		$GLOBALS['TSFE'] = $tsfe;


		Facebook::$CURL_OPTS[CURLOPT_SSL_VERIFYPEER] = FALSE;
  
			// Facebook API instance with appId and secret
		$facebook = new Facebook(
			array(
				'appId'		=> $fbAppSettings['appId'],
				'secret'	=> $fbAppSettings['secret'],
				'cookie'	=> TRUE,
			)
		);

			// Get string responsed by Facebook
		$url = 'https://graph.facebook.com/oauth/access_token?client_id=' . $fbAppSettings['appId'] .
			'&redirect_uri=' . $fbAppSettings['webUrl'] . '&client_secret=' . $fbAppSettings['secret'] .
			'&code=' . $fbAppSettings['authCode'];
		$groupAccessToken = t3lib_div::getURL($url);
      
			// We need only the access token code so cut the word "access_token="
			// This access token is valid for group or non-owner of the page
		$groupAccessToken = explode('&expires=', substr($groupAccessToken, 13));
    $groupAccessToken = $groupAccessToken[0];
    
			// Generate new access token for page
			// This help to post attachment to FB as admin of the page
		$pagesResponse = json_decode(t3lib_div::getURL('https://graph.facebook.com/me/accounts?access_token=' . $groupAccessToken));
    
			// If joson_decode return null, cannot post the page as admin
		if (is_null($pagesResponse)) {
			$pageAccessToken = $groupAccessToken;
		} else {
			$pageAccessTokenArray = array();
			foreach ($pagesResponse->data as $pageData) {

					// Check page authentication if we can post as admin or not
				if (in_array($pageData->id, $pageList)) {
						// Store full access page with id and its access_token
						// because it uses its own access_token
					$pageAccessTokenArray[$pageData->id] = $pageData->access_token;

						// Search and remove full access page from pageList array
					$indexToRemove = array_search($pageData->id, $pageList);
					unset($pageList[$indexToRemove]);
				}
			}
				// Merge group with normal page - because it uses the same access_token
			$groupList = array_merge($groupList, $pageList);
		}

			// Select all news which not yet publish to facebook and post them to
			// the wall of the group page and fan page
		$inCat = '';
		$table = 'tt_news';
		$where = 'tx_pxanewstofb_published = 0  AND tx_pxanewstofb_dont_publish = 0 ' . $this->enableFields('tt_news');
     
			// Get and validate the list of selected news categories
		$selectedNewsCategories = array();
		$selectedNewsCategories = t3lib_div::intExplode(',', $fbAppSettings['categoryId'], TRUE);
		foreach ($selectedNewsCategories as $key => $value) {
			if ($value < 1) {
				unset($selectedNewsCategories[$key]);
			}
		}

			// If the categories set, select the news by the allowed categories
		if (count($selectedNewsCategories) > 0) {
			$table = 'tt_news LEFT JOIN tt_news_cat_mm ON tt_news.uid = tt_news_cat_mm.uid_local';

			$inCat = ' AND uid_foreign IN (' . implode(',', $selectedNewsCategories) .') ';
			$where = 'tx_pxanewstofb_published = 0  AND tx_pxanewstofb_dont_publish = 0 ' . $inCat . $this->enableFields('tt_news');
		}
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tt_news.*', $table, $where, 'tt_news.uid');
    
		$newsUid = '';
		$newsImagePath = $GLOBALS['TCA']['tt_news']['columns']['image']['config']['uploadfolder'];
		if (! $newsImagePath) {
			$newsImagePath = 'uploads/pics';
		}
			// Check if user input a valid webUrl in extension configuration
		$host = '';
		if ($fbAppSettings['webUrl']) {
			preg_match('@^(?:http://)?([^/]+)@i', $fbAppSettings['webUrl'], $matches);
			$host = $matches[1] ? $matches[1] . '/' : '';
		}

		if ($host == '') {
			$host = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		}
    
		$messages = array();
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			try {
					// Make link for each post in FB to the news detail page of the website
				$typolinkConf = array(
					'no_cache' => FALSE,
					'parameter' => $fbAppSettings['detailNewsPid'],
					'additionalParams' => '&tx_ttnews[tt_news]=' . $row['uid'],
					'useCacheHash' => TRUE
				);
				$newslink = $this->cObj->typolink_URL($typolinkConf);     
        
					// only add webUrl if generated $newsLink is not already absolute
				if (substr($newslink, 0, 7) != 'http://' || substr($newslink, 0, 8) != 'https://') {
					$newslink = $fbAppSettings['webUrl'] . $newslink;
				}

					// Create attachment to post to FB by Graph API
				$attachment = array(
						'access_token' => $groupAccessToken,
						'link' => $newslink,
						'name' => $row['title'],
				);


					// If the description length is set , it will show the description text in post
				if (intval($fbAppSettings['descLength']) > 0) {
					$desc = strip_tags($row['short'] ? $row['short'] : $row['bodytext']);
					$desc = preg_replace('/\s+/', ' ', $desc);
          
					$desc = $this->cObj->crop($desc, intval($fbAppSettings['descLength']) . '|...|1');
					$attachment['description'] = $desc;
				}
        
				// Get the image path
				if ($row['image']) {
					if (strpos($row['image'], ',') > 0) {
							// Several Pictures in News, only the first will be taken
						$imagePath = $host . $newsImagePath . '/' . substr($row['image'], 0, strpos($row['image'], ','));
					} else {
							// Only one Picture in News, this will be taken
						$imagePath = $host . $newsImagePath . '/' . $row['image'];
					}
					$attachment['picture'] = $imagePath;
				}
        
				// Get the image path with DAM
				if (t3lib_extMgm::isLoaded('dam_ttnews'))    {
					$attachment['picture'] = '';
					$damFiles = tx_dam_db::getReferencedFiles('tt_news', $row['uid'], 'tx_damnews_dam_images' );
					//only the first image will be taken
					foreach ($damFiles['files'] as $file){
						$attachment['picture'] = $host.$file;
						break;
					}
				}

					// Post feed to all group
				if (count($groupList) > 0) {
					foreach ($groupList as $singleGroupId) {
						$facebook->api('/' . $singleGroupId . '/feed/', 'post', $attachment);
					}
				}

					// Post feed to all page
				if (count($pageAccessTokenArray) > 0) {
					foreach ($pageAccessTokenArray as $pageId => $pageAccessToken) {
						$attachment['access_token'] = $pageAccessToken;
						$facebook->api('/' . $pageId . '/feed/', 'post', $attachment);
					}
				}
				$newsUid .= $row['uid'] . ',';
				array_push($messages, 'Record news uid - ' . $row['uid'] . ' was published.');
			} catch (Exception $e) {
				array_push($messages, $e);
			}
		}

			// Update the news are already post to facebook
		if ($newsUid) {
			$newsUid = substr($newsUid, 0, -1);
			$sqlUpdate = 'UPDATE tt_news SET tx_pxanewstofb_published = 1 WHERE uid in (' . $newsUid .')';
			$GLOBALS['TYPO3_DB']->sql_query($sqlUpdate);
		}

			// Write to log file
		if ($fbAppSettings['logFilePath']) {
			$logFilePath = t3lib_div::getFileAbsFileName($fbAppSettings['logFilePath']);
		}
		$logFile = fopen($logFilePath, 'a');
		$time = date('d-M-Y H:i:s');
		$i = 0;
		while ($i < count($messages)) {
			fprintf($logFile, "%s %s \n", $time, $messages[$i]);
			$i++;
		}
		fclose($logFile);

		return TRUE;
	}

	/**
	 * Implements enableFields call that can be used from regular FE and eID
	 *
	 * @param string $tableName Table name
	 * @return string
	 */
	public function enableFields($tableName) {

		if ($GLOBALS['TSFE']) {
			return $this->cObj->enableFields($tableName);
		}
		$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');

			// @var $sys_page t3lib_pageSelect
		return $sysPage->enableFields($tableName);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pxa_newstofb/class.tx_pxanewstofb_publish.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/pxa_newstofb/class.tx_pxanewstofb_publish.php']);
}

?>