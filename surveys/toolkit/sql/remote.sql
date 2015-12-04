CREATE TABLE IF NOT EXISTS `answers` (
  `AnswerID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `LocalID` int(11) unsigned NOT NULL DEFAULT '0' COMMENT 'local database AnswerID',
  `ResponseID` varchar(111) NOT NULL DEFAULT '',
  `Question` varchar(50) NOT NULL DEFAULT '',
  `Answer` text,
  `AnswerCreated` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`AnswerID`),
  KEY `ResponseID` (`ResponseID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;


CREATE TABLE IF NOT EXISTS `responses` (
  `ResponseID` varchar(111) NOT NULL,
  `DeviceID` varchar(20) DEFAULT NULL,
  `SurveyID` varchar(100) NOT NULL DEFAULT '',
  `Status` enum('uploading','uploaded','downloaded','test response') DEFAULT NULL,
  `Alert` enum('not checked','n/a','no alerts','alert checking failed','alert(s) sent') NOT NULL DEFAULT 'not checked',
  `Created` int(12) unsigned DEFAULT NULL COMMENT 'unix timestamp',
  `Received` int(12) unsigned DEFAULT NULL COMMENT 'unix timestamp',
  `Exported` int(12) unsigned DEFAULT NULL COMMENT 'unix timestamp',
  PRIMARY KEY (`ResponseID`),
  KEY `SurveyID` (`SurveyID`),
  KEY `DeviceID` (`DeviceID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;


CREATE TABLE IF NOT EXISTS `surveys` (
  `SurveyID` varchar(100) NOT NULL DEFAULT '',
  `Name` varchar(100) NOT NULL DEFAULT '',
  `CoreIDs` varchar(255) DEFAULT NULL COMMENT 'space-separated Core survey IDs, if applicable',
  PRIMARY KEY (`SurveyID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
