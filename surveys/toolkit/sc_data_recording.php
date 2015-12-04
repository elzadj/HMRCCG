<?php
require_once('sc_survey_constructor.php');


// == Provides functions to store a survey response in different ways  == //
class SC_Data_Recording {
    // --------------- //
    // -- Constants -- //
    // --------------- //
    const DB_DELIMITER = ' | ';
    const CSV_DELIMITER = ',';
    
    const DB_STATUS_INCOMPLETE  = 'incomplete';
    const DB_STATUS_READY       = 'ready to upload';
    const DB_STATUS_UPLOADING   = 'uploading';
    const DB_STATUS_UPLOADED    = 'uploaded';
    const DB_STATUS_DOWNLOADED  = 'downloaded';
    
    
    // ---------------- //
    // -- Properties -- //
    // ---------------- //
    private $obj_survey_constructor = [];
    
    
    // ----------------- //
    // -- Constructor -- //
    // ----------------- //
    /**
    * Parses external Questions YAML file and assigns to object property
    * 
    * Both the Survey ID and the YAML array are stored
    * If the YAML file could not be found, the program ends, with an error
    * 
    * @param string $survey_id 'SC Survey ID'
    * @param string $folder directory path where YAML file is stored
    */
    public function __construct($survey_id, $folder) {
        //Load questions
        $this->obj_survey_constructor = new SC_Survey_Constructor($survey_id, $folder);
    }
    
    
    
    // ------------------- //
    // -- Other Methods -- //
    // ------------------- //
    
    
    // -- CSV functions -- //
    
    /**
    * Converts a response to a CSV line
    * @param array SC Response array
    * @return string CSV
    */
    public function to_keysurvey_csv($arr_response, $original_survey_id = NULL) {
        $arr_answers = [];
        $custom_answer_count = 0;
        foreach ($this->obj_survey_constructor->get_questions() as $qid => $q) {
            if ($q['type'] == SC_Survey_Constructor::TYPE_SURVEY_ID) {
                $arr_answers[] = SC_Data_Recording::format_for_csv($original_survey_id, TRUE);

            } elseif ($q['type'] == SC_Survey_Constructor::TYPE_RADIO) {
                $answer = isset($arr_response[$qid]) ? trim($arr_response[$qid]) : '';
                $arr_answers[] = SC_Data_Recording::format_for_csv($answer, TRUE);
                
                //Add an extra column for 'other' answers
                if (isset($q['options']['other'])) {
                    $arr_answers[] = isset($arr_response[$qid.'-specific-input']) ? SC_Data_Recording::format_for_csv(trim($arr_response[$qid.'-specific-input'])) : '';
                }
                
            } elseif ($q['type'] == SC_Survey_Constructor::TYPE_CHECKBOX) {
                //Add a column for each possible answer
                foreach ($q['options'] as $opt) {
                    if (isset($arr_response[$qid])) {
                        if (is_array($arr_response[$qid])) {
                            //Look for this answer in the response answer array
                            //echo 'searching for "'.$opt.'" in array('.implode(', ', $arr_response[$qid]).'): ';
                            $answer = array_search($opt, $arr_response[$qid]) !== FALSE ? '1' : '0';
                            //echo $answer.'<br />';
                        } else {
                            $answer = $opt == $arr_response[$qid] ? '1' : '0';
                        }
                    } else {
                        $answer = '0';
                    }
                    $arr_answers[] = $answer;
                }
                
            } elseif ($q['type'] == SC_Survey_Constructor::TYPE_MATRIX) {
                
                //Add a column for each subquestion
                foreach ($q['subquestions'] as $sub_id => $sub) {
                    $str_answer = isset($arr_response[$qid.'-'.$sub_id]) ? trim($arr_response[$qid.'-'.$sub_id]) : '';
                    $int_answer = array_search($str_answer, $q['options']);
                    if (is_int($int_answer)) {
                        $int_answer++;
                    } else {
                        $int_answer = 0;
                    }
                    $arr_answers[] = $int_answer;
                }
                
            } elseif ($q['type'] == SC_Survey_Constructor::TYPE_CHECKTRIX) {
                
                //Add an array of columns for each subquestion
                foreach ($q['subquestions'] as $sub_id => $sub) {
                    //Add a column for each possible answer
                    foreach ($q['options'] as $opt) {
                        if (isset($arr_response[$qid.'-'.$sub_id])) {
                            if (is_array($arr_response[$qid.'-'.$sub_id])) {
                                //Look for this answer in the response answer array
                                $answer = array_search($opt, $arr_response[$qid.'-'.$sub_id]) !== FALSE ? '1' : '0';
                            } else {
                                $answer = $opt == $arr_response[$qid.'-'.$sub_id] ? '1' : '0';
                            }
                        } else {
                            $answer = '0';
                        }
                        $arr_answers[] = $answer;
                    }
                }
                
            } elseif ($q['type'] == SC_Survey_Constructor::TYPE_DATETIME) {
                $format = isset($q['format']) && strlen($q['format']) > 0 ? $q['format'] : SC_Survey_Constructor::DEFAULT_DATETIME_FORMAT;
                $answer = isset($arr_response[$qid]) ? date($format, (int)$arr_response[$qid]) : '';
                $arr_answers[] = SC_Data_Recording::format_for_csv($answer);
                
            } else {    //All other question types
                $answer = isset($arr_response[$qid]) ? trim($arr_response[$qid]) : '';
                $arr_answers[] = SC_Data_Recording::format_for_csv($answer);
            }
        }
        
        $csv = implode(SC_Data_Recording::CSV_DELIMITER, $arr_answers);
        
        return $csv;
    }
    
    /**
    * Returns escaped valid CSV
    * @static
    * @param $str string
    * @return string
    */
    public static function format_for_csv($str, $entities = FALSE) {
        //Format line breaks
        if (!isset($str)) {
            return '';
        }
        $str = trim($str);
        if ($entities !== FALSE) {
            $str = str_replace('&', '&amp;', $str);
            $str = str_replace('£', '&pound;', $str);
            $str = str_replace('<', '&lt;', $str);
            $str = str_replace('>', '&gt;', $str);
        }
        $str = str_replace("\r\n", ' ', $str);
        $str = str_replace("\n", ' ', $str);
        

        //Enclose in quotes and return
        return (
            substr_count($str, ';') > 0 ||  //contains a delimiter
            substr_count($str, ',') > 0 ||  //contains a delimiter
            substr_count($str, "\t") > 0 || //contains a delimiter
            is_numeric($str)    //is a number
        )
        ? '"'.$str.'"'
        : $str;
    }
    
    /**
    * Writes response to KeySurvey-compliant CSV file
    * 
    * Stamps the filename with the date and short md5 of the file contents
    * 
    * @param array $arr_responses SC Responses array
    * @param string $path directory path
    * @return string resulting file path
    */
    public function write_keysurvey_csv_file($arr_responses, $path) {
        $lines = [];
        foreach ($arr_responses as $response) {
            $lines[] = $this->to_keysurvey_csv($response);
        }
        
        $content = implode("\r\n", $lines);
        $filename = $this->obj_survey_constructor->get_survey_id().'-'.date('Y_m_d').'-'.substr(md5($content), 0, 7);
        $filepath = $path.'/'.$filename.'.csv';
        
        $num_bytes = file_put_contents($filepath, $content);
        
        return $filepath;
    }
    
    
    // -- Text functions -- //
    
    /**
    * Returns serialisation of a survey response
    * @param array SC Response array
    * @return string serialised response
    */
    public function to_txt($arr_response) {
        return serialize($arr_response);
    }
    
    /**
    * Returns array of responses from list of serialised responses
    * @static
    * @param string $content
    * @param string $delim delimiter (defaults to \n)
    * @return array SC Response arrays
    */
    public static function from_txt($content, $delim = "\n")
    {
        $arr_responses = [];
        $lines = explode($delim, $content);
        foreach ($lines as $l)
        {
            $arr_responses[] = unserialize($l);
        }
        return $arr_responses;
    }
    
    
    // -- Database functions -- //

    /**
    * Gets a DSN string from SC Database array
    * @static
    * @param array $db 'SC Database'
    * @param bool include the database name in DSN string?
    * @return string DSN string
    */
    public static function getDSN($db, $includeTable = TRUE) {
        $dsn = array('host=' . $db['host']);

        if (isset($db['port']) && strlen($db['port']) > 0) {
            $dsn[] = 'port=' . $db['port'];
        }

        if ($includeTable !== FALSE) {
            $dsn[] = 'dbname=' . $db['name'];
        }

        return 'mysql:' . implode(';', $dsn);
    }
    
    /**
    * Creates a database on the server
    * @static
    * @param array $db 'SC Database'
    * @param bool $local local database or not?
    */
    public static function create_database($db, $local = TRUE) {
        // -- Create database -- //
        // Connect to the server
        try {
            $dsn = SC_Data_Recording::getDSN($db, FALSE);
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            exit('Could not connect to database server');
        }

        // Run query
        $sql = "CREATE DATABASE IF NOT EXISTS `" . $db['name'] . "`;";
        try {
            $pdo->exec($sql);

        } catch (PDOException $e) {
            exit('Could not create database `' . $db['name'] . '`');
        }


        // -- Create tables -- //
        // Connect to the database
        try {
            $dsn = SC_Data_Recording::getDSN($db);
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            exit('Could not connect to new database');
        }

        // Get SQL from file
        try {
            $filename = $local ? 'local.sql' : 'remote.sql';
            $path = dirname(__FILE__).'/lib/sql/'.$filename;
            $file_text = file_get_contents($path);
        } catch (PDOException $e) {
            exit('Could not open SQL file `' . $path . '`');
        }

        $queries = explode(';', $file_text);
        foreach ($queries as $q) {
            $sql = trim($q);
            if (strlen($sql) > 0) {
                // Run queries
                try {
                    $pdo->exec($sql);

                } catch (PDOException $e) {
                    exit('Could not create table ' . $e->getMessage());
                }
            }
        }

        // Close PDO connection
        $pdo = NULL;
    }
    
    
    /**
    * Adds a response to a database
    * @param array $arr_response 'SC Response array'
    * @param array $db 'SC Database'
    * @param bool $local local database or not?
    * @param string $created_time unix time (normally when the response was submitted)
    * @param stromg $device_id device id
    * @return string ResponseID
    */
    public static function to_database($survey_id, $arr_response, $db, $local = TRUE, $created_time = NULL, $device_id = NULL) {
        date_default_timezone_set('Europe/London');

        // -- Define status messages -- //
        // based on whether the database is local or remote.
        if ($local) {
            //Local databases must record the success of the operation, in case of a power failure part-way through
            $status_before = SC_Data_Recording::DB_STATUS_INCOMPLETE;
            $status_after = SC_Data_Recording::DB_STATUS_READY;
        } else {
            //Remote databases must record the success of the operation, in case of a network or power failure part-way through
            $status_before = SC_Data_Recording::DB_STATUS_UPLOADING;
            $status_after = SC_Data_Recording::DB_STATUS_UPLOADED;
        }

        
        // -- Data -- //
        //Create a response ID that will be unique across all surveys for all customers
        if (!isset($created_time)) { $created_time = time(); }
        if (!isset($device_id)) { $device_id = 'unknown_device'; }

        $response_id = $created_time.'-'.$survey_id.'-'.$device_id.'-'.rand(1000, 9999);
        
        
        // Connect to the database
        try {
            $dsn = SC_Data_Recording::getDSN($db);
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            exit('Could not connect to database');
        }

        
        // -- Add response -- //
        $params = array(
            $response_id,
            $device_id,
            $survey_id,
            $created_time,
            $status_before
        );

        if ($local) {
            $sql =  "INSERT INTO `responses` (`ResponseID`, `DeviceID`, `SurveyID`, `Created`, `Status`) VALUES (?, ?, ?, ?, ?)";
        } else {
            $sql =  "INSERT INTO `responses` (`ResponseID`, `DeviceID`, `SurveyID`, `Created`, `Status`, `Received`) VALUES (?, ?, ?, ?, ?, ?)";
            $params[] = time();
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

        } catch (PDOException $e) {
            exit('Insert response error. ' . $e->getMessage());
        }


        // -- Add survey answers -- //
        $sql    = "INSERT INTO `answers` (`ResponseID`, `Question`, `Answer`) VALUES ";
        $params = [];
        $rows   = [];

        foreach ($arr_response as $q => $a) {
            
            if (is_array($a)) { //Formatting for multi-answer questions
                $answer = implode(SC_Data_Recording::DB_DELIMITER, $a);
            } else {
                $answer = trim($a);
            }

            if (strlen($answer) > 0) {
                $rows[] = "(?, ?, ?)";
                $params[] = $response_id;
                $params[] = $q;
                $params[] = $answer;
            }
        }
        $sql .= implode(', ', $rows);

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

        } catch (PDOException $e) {
            exit('Insert answers error. ' . $e->getMessage());
        }

        
        // -- Update response status to report success -- //
        $sql = "UPDATE `responses` SET `Status` = ? WHERE `ResponseID` = ?";
        $params = array(
            $status_after,
            $response_id
        );

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

        } catch (PDOException $e) {
            exit('Update status error. ' . $e->getMessage());
        }

        $pdo = NULL;

        return $response_id;
    }
    
    
    /**
    * Uploads latest responses from local database to remote database
    * @static
    * @param array $local_db 'SC Database array'
    * @param array $remote_db 'SC Database array'
    * @return array success info
    */
    public static function upload_latest_responses($local_db, $remote_db) {
        date_default_timezone_set('Europe/London');

        // Connect to the local database
        try {
            $dsn = SC_Data_Recording::getDSN($local_db);
            $local_pdo = new PDO($dsn, $local_db['user'], $local_db['pass']);
            $local_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            exit('Could not connect to local database');
        }

        // Connect to the remote database
        try {
            $dsn = SC_Data_Recording::getDSN($remote_db);
            $remote_pdo = new PDO($dsn, $remote_db['user'], $remote_db['pass']);
            $remote_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            exit('Could not connect to remote database');
        }
        
        
        // Loop through local database responses to upload (local db)
        $local_sql = "SELECT `ResponseID`, `DeviceID`, `SurveyID`, `Created` FROM `responses` WHERE `Status` = ? OR `Status` = ? ORDER BY `Created` ASC";
        $params = array(
            SC_Data_Recording::DB_STATUS_READY,
            SC_Data_Recording::DB_STATUS_UPLOADING
        );
        try {
            $stmt = $local_pdo->prepare($local_sql);
            $stmt->execute($params);
            $responses = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            exit('Local responses select error. ' . $e->getMessage());
        }
        
        
        // Only attempt to upload responses if there are responses to upload
        $num_responses = count($responses);
        $num_uploaded = 0;

        if ($num_responses > 0) {
            $params = [];
            foreach ($responses as $r) {
                $params[] = $r['ResponseID'];
            }
            $qmarks = array_fill(0, count($responses), '?');
            $remote_sql = "SELECT `ResponseID` FROM `responses` WHERE `ResponseID` IN (" . implode(', ', $qmarks) . ")";
            
            try {
                $stmt = $remote_pdo->prepare($remote_sql);
                $stmt->execute($params);
                $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);

            } catch (PDOException $e) {
                exit('Duplicate responses select error. ' . $e->getMessage());
            }


            // Upload responses one by one
            foreach ($responses as $r) {
                $response_id = $r['ResponseID'];

                // Double check this response hasn't already been uploaded (remote db)
                foreach ($duplicates as $d) {
                    if ($response_id === $d['ResponseID']) {
                        $num_responses--;
                        continue 2; // move on the next response
                    }
                }

                // Get answers for this response (local db)
                $local_sql = "SELECT * FROM `answers` WHERE `ResponseID` = ? ORDER BY `ResponseID` ASC, `AnswerID` ASC";
                $params = array($response_id);

                try {
                    $stmt = $local_pdo->prepare($local_sql);
                    $stmt->execute($params);
                    $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

                } catch (PDOException $e) {
                    exit('Local answers select error. ' . $e->getMessage());
                }

                
                // Insert response to remote database (remote db)
                $remote_sql = "INSERT INTO `responses` (`ResponseID`, `DeviceID`, `SurveyID`, `Status`, `Created`, `Received`) VALUES (?, ?, ?, ?, ?, ?)";
                $params = array(
                    $response_id,
                    $r['DeviceID'],
                    $r['SurveyID'],
                    SC_Data_Recording::DB_STATUS_UPLOADING,
                    $r['Created'],
                    time()
                );

                try {
                    $stmt = $remote_pdo->prepare($remote_sql);
                    $stmt->execute($params);

                } catch (PDOException $e) {
                    exit('Remote response insert error. ' . $e->getMessage());
                }

            
                // Update response status (local db)
                $local_sql = "UPDATE `responses` SET `Status` = ? WHERE `ResponseID` = ?";
                $params = array(
                    SC_Data_Recording::DB_STATUS_UPLOADING,
                    $response_id
                );

                try {
                    $stmt = $local_pdo->prepare($local_sql);
                    $stmt->execute($params);

                } catch (PDOException $e) {
                    exit('Local response update error. ' . $e->getMessage());
                }
                
                // Insert multiple answer records (remote db)
                $remote_sql  = "INSERT INTO `answers` (`LocalID`, `ResponseID`, `Question`, `Answer`) VALUES ";
                $remote_arr  = array_fill(0, count($answers), '(?, ?, ?, ?)');
                $remote_sql .= implode(", ", $remote_arr);
                $params = [];
            
                foreach ($answers as $a) {
                    $params[] = $a['AnswerID'];
                    $params[] = $response_id;
                    $params[] = $a['Question'];
                    $params[] = $a['Answer'];
                }

                try {
                    $stmt = $remote_pdo->prepare($remote_sql);
                    $stmt->execute($params);

                } catch (PDOException $e) {
                    exit('Remote answers insert error. ' . $e->getMessage());
                }

                
                // Update response status (remote db)
                $remote_sql = "UPDATE `responses` SET `Status` = ? WHERE `ResponseID` = ?";
                $params = array(
                    SC_Data_Recording::DB_STATUS_UPLOADED,
                    $response_id
                );

                try {
                    $stmt = $remote_pdo->prepare($remote_sql);
                    $stmt->execute($params);

                } catch (PDOException $e) {
                    exit('Remote response update error. ' . $e->getMessage());
                }

            
                // Update response status (local db)
                $local_sql = "UPDATE `responses` SET `Status` = ?, `Exported` = ? WHERE `ResponseID` = ?";
                $params = array(
                    SC_Data_Recording::DB_STATUS_UPLOADED,
                    time(),
                    $response_id
                );

                try {
                    $stmt = $local_pdo->prepare($local_sql);
                    $stmt->execute($params);

                } catch (PDOException $e) {
                    exit('Local response update error. ' . $e->getMessage());
                }
                
                $num_uploaded++;
            }
        }

        $local_pdo  = NULL;
        $remote_pdo = NULL;
        
        return array('uploaded' => $num_uploaded, 'total' => $num_responses);
    }
    
    
    /**
    * Returns array of responses to export, from a database
    * @static
    * @param string $survey_id 'SC Survey ID'
    * @param array $db 'SC Database array'
    * @param bool $local local database or not?
    * @return array 'SC Response array's
    */
    public static function from_database($survey_id, $db, $local = FALSE) {
        //Define status messages, based on whether the database is local or remote.
        if ($local) {
            $eligible_status = SC_Data_Recording::DB_STATUS_READY;
        } else {
            $eligible_status = SC_Data_Recording::DB_STATUS_UPLOADED;
        }

        
        // Connect to the database
        try {
            $dsn = SC_Data_Recording::getDSN($db);
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            exit('Could not connect to database');
        }

        
        //Check for responses
        $sql  = "SELECT `r`.`ResponseID`, `Question`, `Answer`\n";
        $sql .= "FROM `responses` `r`\n";
        $sql .= "JOIN `answers` `a`\n";
        $sql .= "ON `r`.`ResponseID` = `a`.`ResponseID`\n";
        $sql .= "WHERE `Status` = ? AND `SurveyID` = ?";

        $params = array(
            $eligible_status,
            $survey_id
        );

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $answers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            exit('Join select error. ' . $e->getMessage());
        }

        
        //Loop through responses
        $arr_responses = [];

        foreach ($answers as $a) {
            //Format answer for response array
            if (strlen($a['Answer']) > 0) {
                $answer = $a['Answer'];
                if (strpos($answer, SC_Data_Recording::DB_DELIMITER)) {
                    $answer = explode(SC_Data_Recording::DB_DELIMITER, $answer);
                } else {
                    $answer = trim($answer);
                }
                $arr_responses[$a['ResponseID']][$a['Question']] = $answer;
            }
        }

        $pdo = NULL;
        
        
        return $arr_responses;
    }
    
    
    /**
    * Marks database responses as downloaded
    * @static
    * @param array $arr_ids response ids to mark
    * @param array $db 'SC Database array'
    * @param bool $local local database or not?
    */
    public static function mark_as_downloaded($arr_ids, $db, $local = FALSE) {
        // Connect to the database
        try {
            $dsn = SC_Data_Recording::getDSN($db);
            $pdo = new PDO($dsn, $db['user'], $db['pass']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $e) {
            exit('Could not connect to database');
        }

        
        //Update response status to flag as downloaded
        $qmarks = array_fill(0, count($arr_ids), '?');
        $sql = "UPDATE `responses` SET `Status` = ?, `Exported` = ? WHERE `ResponseID` IN (" . implode(", ", $qmarks) . ")";
        $params = array(
            SC_Data_Recording::DB_STATUS_DOWNLOADED,
            time()
        );

        foreach ($arr_ids as $id) {
            $params[] = $id;
        }

        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

        } catch (PDOException $e) {
            exit('Responses update error. ' . $e->getMessage());
        }

        $pdo = NULL;
    }
}
