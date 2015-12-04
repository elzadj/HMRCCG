<?php
require_once('sc_survey_constructor.php');


// == Provides functions to render surveys as HTML == //
class SC_Page_Generator {
    // --------------- //
    // -- Constants -- //
    // --------------- //
    const FIRST_PAGE      = 'first_page';
    const LAST_PAGE       = 'last_page';
    const SUBMIT_RESPONSE = 'submit_response';
    const START           = 'start';
    
    const MEDIA_TEXT      = 'text';  // deprecated, use SC_Survey_Constructor::MEDIA_PLAIN
    const MEDIA_IMAGE     = 'image'; // deprecated, use question 'image' attribute or inline HTML/Markdown img tag
    const MEDIA_QUESTION  = 'question';

    const FLAG_DELIMITER  = '|';
    
    
    // ---------------- //
    // -- Properties -- //
    // ---------------- //
    private $arr_yaml_questions;
    private $arr_yaml_structure;
    
    
    // ----------------- //
    // -- Constructor -- //
    // ----------------- //
    /**
    * Parses external Structure YAML file and assigns to object property
    * 
    * The Questions YAML file is first parsed and stored in the SC Survey Constructor object
    * If the YAML file could not be found, a default structure is created, else the program ends and an error is thrown
    * 
    * @param string $surveyID 'SC Survey ID'
    * @param string $folder directory path where YAML file is stored
    */
    public function __construct($surveyID, $folder, $langCode = NULL) {
        $langCode === !is_null($langCode) ? $langCode : 'en';

        $folder          = Misc::add_slash($folder);
        $survey_folder   = $folder . $surveyID . '/';
        $language_folder = $survey_folder . $langCode . '/';
        
        ## Load structure
        $sc_survey_constructor = new SC_Survey_Constructor($surveyID, $folder);

        $this->arr_yaml_questions = $sc_survey_constructor->get_questions();

        if (!isset($_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['structure'])) { // If structure isn't already cached in session### Default to language-specific questions YAML
            $filepath = $language_folder . 'structure.yaml';
            
            ### Fallback to root structure.yaml
            if (!file_exists($filepath)) {
                $filepath = $survey_folder . 'structure.yaml';
            }

            $arr_yaml = Misc::parse_yaml($filepath);
            
            $_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['structure'] =
                isset($arr_yaml) ?
                $arr_yaml :
                $this->create_default_structure($surveyID, $folder, $langCode);
        }

        $this->arr_yaml_structure = $this->standardise($_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['structure']);
    }
    
    
    /**
     * Create a default page structure
     * @param  string $method method of creating structure
     * @return array
     */
    private function create_default_structure($surveyID, $folder, $langCode, $method = NULL) {
        //Create a default structure
        if (isset($_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['questions'])) {
            
            //Discard hidden questions
            $arr_questions = [];
            foreach ($_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['questions'] as $k => $v) {
                if (substr($k, 0, 2) != 'h_') { //Do not add hidden (system) questions to structure
                    $arr_questions[] = $k;
                }
            }
            
            //Filter by method
            switch ($method) {
                case 'one_page':
                    $arr_yaml = array(
                        'cat_solo' => array(
                            'pages' => array(
                                'pg_solo' => array(
                                    'questions' => $arr_questions
                                )
                            )
                        )
                    );
                    break;

                case 'one_per_page':
                default:
                    //one question per page
                    $loop = 0;
                    foreach ($arr_questions as $q) {
                        $pages['p_'.(string)$loop]['questions'] = array($q);
                        $loop++;
                    }
                    $arr_yaml = array(
                        'cat_solo' => array(
                            'pages' => $pages
                        )
                    );
                    break;
            }
            
            return $arr_yaml;
            
        } else {
            return NULL;
        }
    }


    /**
     * Standardise an SC Structure array
     * expanding all values into arrays where applicable
     */
    public static function standardise($arr) {

        function standardisePage(&$p) {
            if (array_key_exists('title', $p)) {
                Misc::standardiseValue($p['title']);
            }

            /*
            if (array_key_exists('intro', $p)) {
                Misc::standardiseValue($p['intro']);
            }
            */

            if (array_key_exists('questions', $p)) {
                for ($i = 0; $i < count($p['questions']); $i++) {
                    Misc::standardiseValue($p['questions'][$i], ['type' => SC_Page_Generator::MEDIA_QUESTION]);
                    
                    # Question IDs should use 'id' attribute, not 'content'
                    if ($p['questions'][$i]['type'] === SC_Page_Generator::MEDIA_QUESTION) {
                        $p['questions'][$i]['id'] = $p['questions'][$i]['content'];
                        unset($p['questions'][$i]['content']);
                    } 
                }
            }
        }

        function standardiseCategory(&$c) {
            if (array_key_exists('title', $c)) {
                Misc::standardiseValue($c['title']);
            }

            if (array_key_exists('pages', $c)) {
                foreach ($c['pages'] as $pid => $v) {
                    standardisePage($c['pages'][$pid]);
                }
            }
        }

        foreach ($arr as $k => $v) {
            standardiseCategory($arr[$k]);
        }

        return $arr;
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
    * Returns the structure array
    * @return array 'SC Structure array'
    */
    public function get_structure() {
        return $this->arr_yaml_structure;
    }


    public function getPageDetails($pageID) {
        $result = [];

        foreach ($this->arr_yaml_structure as $cid => $cat) {
            foreach ($cat['pages'] as $pid => $page) {
                if ($pageID === $pid) {
                    
                    # Get category info
                    $result['categoryID'] = $cid;
                    if (isset($cat['title'])) {
                        $result['categoryTitle'] = $cat['title'];
                    }

                    # Get page info
                    $result['pageID'] = $pid;
                    if (isset($page['title'])) {
                        $result['pageTitle'] = $page['title'];
                    }

                    # Get questions
                    if (isset($page['questions'])) {
                        $result['media'] = $page['questions'];
                    }

                    return $result;
                }
            }
        }

        return FALSE;
    }

    /**
    * Returns SC Question YAML for each question that belongs to this page
    * @return array
    */
    public function get_page_questions($pageID) {
        $pageDetails   = $this->getPageDetails($pageID);
        $pageQuestions = [];

        if (isset($pageDetails['media'])) {
            foreach ($pageDetails['media'] as $media) {
                if ($media['type'] === self::MEDIA_QUESTION) {
                    $pageQuestions[$media['id']] = $this->arr_yaml_questions[$media['id']];
                }
            }
        }
    
        return $pageQuestions;
    }

    
    /**
    * Returns the first page id
    * @return string 'SC Page ID'
    */
    public function get_first_page_id() {       
        $firstCategory = reset($this->arr_yaml_structure);
        reset($firstCategory['pages']);
        return key($firstCategory['pages']);
    }
    
    /**
    * Returns the last page id
    * @return string 'SC Page ID'
    */
    public function get_last_page_id() {
        $lastCategory = end($this->arr_yaml_structure);
        end($lastCategory['pages']);
        $keys = array_keys($lastCategory['pages']);
        return array_pop($keys);
    }

    
    
    // ------------------- //
    // -- Other Methods -- //
    // ------------------- //

    
    /**
    * Returns page as an array
    * Includes current category title and page title, if they exist
    * 
    * @param array SC Response array
    * @return array Page
    */
    public function get_page_data($pageID) {
        $result = $this->getPageDetails($pageID);

        if (isset($result['media'])) {
            $media = $result['media'];
            $result['media'] = [];

            foreach ($media as $m) {

                if ($m['type'] === self::MEDIA_QUESTION) {
                    $m['content'] = $this->arr_yaml_questions[$m['id']];
                }

                $result['media'][] = $m;
            }
        }

        return $result;
    }

    
    
    /**
    * Returns the next sequestion page id, from stored page id
    * 
    * Finds and returns the next consecutive page (ie survey flow is ignored)
    * If no page is found, the survey must be at the end, so return the submit response code
    * 
    * @return string SC Page ID
    */
    public function get_neighbouring_page_id($pageID) {
        $onceMoreLoop = FALSE;

        foreach ($this->arr_yaml_structure as $cat) {
            foreach ($cat['pages'] as $pid => $page) {

                if ($onceMoreLoop) {
                    return $pid;
                } elseif ($pid === $pageID) {
                    $onceMoreLoop = TRUE;
                }
            }
        }

        return SC_Page_Generator::SUBMIT_RESPONSE;
    }
    
    
    /**
    * Returns current page number from stored page id, and total number of pages
    * 
    * 'pos': numerical position of page
    * 'total': total number of pages
    * 
    * @return array
    */
    private function get_page_info($pageID) {
        //Find the position of the current page and the total number of pages
        $pagePos  = 0;
        $numPages = 1;

        foreach ($this->arr_yaml_structure as $cat) {
            foreach ($cat['pages'] as $pid => $page) {
                if ($pageID === $pid) {
                    $pagePos = $numPages;
                }
                $numPages++;
            }
        }
        
        return [
            'pos'   => $pagePos,
            'total' => $numPages
        ];
    }

    
    /**
    * Returns survey progress as a percentage, from stored page id
    * @return int %
    */
    public function calculate_progress($pageID) {
        //Get page info
        $page_info = $this->get_page_info($pageID);
        
        //Turn the ratio into a percent
        return $page_info['pos'] / $page_info['total'] * 100;
    }
    

    /**
    * Returns whether or not the stored page id is the first page
    * @return bool
    */
    public function is_first_page($pageID) {
        return $pageID === $this->get_first_page_id();
    }
    

    /**
    * Returns whether or not the stored page id is the last page
    * @return bool
    */
    public function is_last_page($pageID) {
        return $pageID === $this->get_last_page_id();
    }
}
