CREATE TABLE IF NOT EXISTS `answers` (
  `AnswerID` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `ResponseID` varchar(111) NOT NULL DEFAULT '',
  `Question` varchar(50) NOT NULL,
  `Answer` text,
  PRIMARY KEY (`AnswerID`),
  INDEX `ResponseID` (`ResponseID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `responses` (
  `ResponseID` varchar(111) NOT NULL DEFAULT '',
  `DeviceID` varchar(20) DEFAULT NULL,
  `SurveyID` varchar(100) NOT NULL,
  `Status` enum('incomplete','ready to upload','uploading','uploaded','downloaded') DEFAULT NULL,
  `Created` int(12) unsigned NULL DEFAULT NULL COMMENT 'unix timestamp',
  `Exported` int(12) unsigned NULL DEFAULT NULL COMMENT 'unix timestamp',
  PRIMARY KEY (`ResponseID`),
  INDEX `SurveyID` (`SurveyID`),
  INDEX `DeviceID` (`DeviceID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;
