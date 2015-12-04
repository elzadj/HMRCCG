<?php
require_once('sc_common.php');


//Survey
class SC_Survey_Constructor {
    // --------------- //
    // -- Constants -- //
    // --------------- //
    const TYPE_UNIX      = 'unix';
    const TYPE_URID      = 'urid'; // Unique response identifier
    const TYPE_SURVEY_ID = 'sid'; // Survey ID (for core survey responses to identify original survey)
    
    const TYPE_TEXT      = 'text';
    const TYPE_MULTILINE = 'multiline';
    const TYPE_NUMBER    = 'number';
    const TYPE_RADIO     = 'radio';
    const TYPE_CHECKBOX  = 'checkbox';
    const TYPE_MATRIX    = 'matrix';
    const TYPE_CHECKTRIX = 'checktrix';
    const TYPE_DATE      = 'date';
    const TYPE_TIME      = 'time';
    const TYPE_DATETIME  = 'datetime';  // Requires PHP date 'format'. Defaults to DEFAULT_DATETIME_FORMAT
    const TYPE_EMAIL     = 'email';
    const TYPE_URL       = 'url';

    const CTRL_RADIO     = 'radio';
    const CTRL_SELECTBOX = 'selectbox';

    const MEDIA_PLAIN     = 'plain';
    const MEDIA_MD_INLINE = 'markdown';
    const MEDIA_MD_BLOCK  = 'markdown-block';
    const MEDIA_HTML      = 'html';
    
    const DEFAULT_DATETIME_FORMAT = 'Y-m-d h:i:s';

    const TIME_DELIMITER = '.';
    
    
    // ---------------- //
    // -- Properties -- //
    // ---------------- //
    private $str_surveys_folder;
    private $str_survey_id;
    private $arr_yaml_questions;
    private $html_intro;
    private $html_outro;
    
    
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
        $folder = Misc::add_slash($folder);
        $this->str_surveys_folder = $folder;
        $this->str_survey_id      = $survey_id;

        ## Load questions
        if (!isset($_SESSION[TOOLKIT_TAG]['data'][$survey_id]['questions'])) { // If questions aren't already cached in session
            $filepath = $folder . $survey_id . '/questions.yaml';

            $arr_yaml = Misc::parse_yaml($filepath);

            if (isset($arr_yaml)) {
                $_SESSION[TOOLKIT_TAG]['data'][$survey_id]['questions'] = $arr_yaml;
            } else {
                throw new Exception('could not find ' . $filepath);
            }
        }
        
        $this->arr_yaml_questions = $this->standardise($_SESSION[TOOLKIT_TAG]['data'][$survey_id]['questions']);
    }
    
    
    // ------------------------- //
    // -- Getters and Setters -- //
    // ------------------------- //
    /**
    * Returns the array of questions
    * @return array 'SC Questions array'
    */
    public function get_questions() {
        return $this->arr_yaml_questions;
    }
    
    /**
    * Returns the path to surveys folder
    * @return string path
    */
    public function get_surveys_folder() {
        return $this->str_surveys_folder;
    }
    
    /**
    * Returns the Survey ID
    * @return string 'SC Survey ID'
    */
    public function get_survey_id() {
        return $this->str_survey_id;
    }

    /**
     * Returns the option number of an answer to a question (0-based)
     * @param string $qid 'SC Question ID'
     * @param string $ans Question Answer
     * @return mixed option number or false if not found or not relevant
     */
    public function get_opt_num($qid, $ans) {

        //Check if Question ID exists
        if (isset($this->arr_yaml_questions[$qid])) {
            $q = $this->arr_yaml_questions[$qid];
            
            //Check for valid question type
            if (
                $q['type'] == SC_Survey_Constructor::TYPE_RADIO ||
                $q['type'] == SC_Survey_Constructor::TYPE_CHECKBOX ||
                $q['type'] == SC_Survey_Constructor::TYPE_MATRIX ||
                $q['type'] == SC_Survey_Constructor::TYPE_CHECKTRIX
            ) {
                $opts = $q['options'];
                
                //Loop through options
                for ($i = 0; $i < count($opts); $i++) {
                    $o = $opts[$i];

                    //Check for complex option
                    if (!is_array($o)) {
                        if ($o === $ans) {
                            return $i;
                        }
                    } else {
                        if ($o['value'] === $ans) {
                            return $i;
                        }
                    }
                }
            }
        }

        return FALSE;
    }


    /**
     * Standardise an SC Questions array
     * expanding all value into arrays where applicable
     */
    public static function standardise($arr) {

        function standardiseOption(&$o) {
            Misc::standardiseValue($o);

            if (!isset($o['value'])) {
                $o['value'] = $o['content'];
            }
        }

        function standardiseGroup(&$g) {
            Misc::standardiseValue($g);
            
            if (array_key_exists('options', $g)) {
                for ($i = 0; $i < count($g['options']); $i++) {
                    standardiseOption($g['options'][$i]);
                }
            }

            return $g;
        }

        function standardiseQuestion(&$q) {
            if (array_key_exists('intro', $q)) {
                Misc::standardiseValue($q['intro']);
            }

            if (array_key_exists('title', $q)) {
                Misc::standardiseValue($q['title']);
            }
            
            if (array_key_exists('subtitle', $q)) {
                Misc::standardiseValue($q['subtitle']);
            }
            
            if (array_key_exists('instruction', $q)) {
                Misc::standardiseValue($q['instruction']);
            }

            if (array_key_exists('subquestions', $q)) {
                for ($i = 0; $i < count($q['subquestions']); $i++) {
                    Misc::standardiseValue($q['subquestions'][$i]);
                }
            }

            if (array_key_exists('scale', $q)) {
                for ($i = 0; $i < count($q['scale']); $i++) {
                    Misc::standardiseValue($q['scale'][$i]);
                }
            }

            if (array_key_exists('groups', $q)) {
                for ($i = 0; $i < count($q['groups']); $i++) {
                    standardiseGroup($q['groups'][$i]);
                }
            }

            if (array_key_exists('options', $q)) {
                for ($i = 0; $i < count($q['options']); $i++) {
                    standardiseOption($q['options'][$i]);
                }
            }

            if (array_key_exists('other', $q)) {
                standardiseOption($q['other']);
            }
        }

        foreach ($arr as $qid => $q) {
            standardiseQuestion($arr[$qid]);
        }

        return $arr;
    }
}
