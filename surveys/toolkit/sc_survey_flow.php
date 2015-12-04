<?php
require_once('sc_page_generator.php');


// == Stores form responses and loads the next page based on survey logic  == //
class SC_Survey_Flow {
    // --------------- //
    // -- Constants -- //
    // --------------- //
    const DIR_BACK = 'bwd';
    const DIR_NEXT = 'fwd';
    
    const CMD_SUBMIT = 'SUBMIT';

    // -- Operators -- //
    //Logical operators
    const LO_NOT      = '!';
    const LO_AND      = '&&';
    const LO_OR       = '||';
    const OPEN_PAREN  = '(';
    const CLOSE_PAREN = ')';

    //Comparison operators
    const CO_EQUAL     = '.';
    const CO_NOT_EQUAL = '<>';
    const CO_LTET      = '<=';
    const CO_GTET      = '>=';
    const CO_LT        = '<';
    const CO_GT        = '>';

    const LITERAL = '"';
    
    
    // ---------------- //
    // -- Properties -- //
    // ---------------- //
    public $obj_page_generator;
    private $arr_yaml_rules = [];
    
    public $bool_store_bwd = TRUE;
    
    
    // ----------------- //
    // -- Constructor -- //
    // ----------------- //
    /**
    * Parses external Rules YAML file and assigns to object property
    * 
    * The Questions and Structure YAML files are first parsed and stored in the SC Page Generator object
    * If the YAML file could not be found, the array becomes false as logic is not required
    * 
    * @param string $surveyID 'SC Survey ID'
    * @param string $folder directory path where YAML file is stored
    */
    public function __construct($surveyID, $folder, $langCode = NULL) {
        $folder         = Misc::add_slash($folder);
        $surveyFolder   = $folder . $surveyID . '/';
        
        // Load rules
        $this->obj_page_generator = new SC_Page_Generator($surveyID, $folder, $langCode);

        $langCode === !is_null($langCode) ? $langCode : 'en';

        if (!isset($_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['rules'])) { // Default to language-specific questions YAML
            $filepath = $surveyFolder . $langCode . '/' . 'rules.yaml';
            
            ### Fallback to root rules.yaml
            if (!file_exists($filepath)) {
                $filepath = $surveyFolder . 'rules.yaml';
            }

            $arrYAML = Misc::parse_yaml($filepath);
            
            $_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['rules'] = isset($arrYAML) ? $arrYAML : [];
        }

        $this->arr_yaml_rules = $_SESSION[TOOLKIT_TAG]['data'][$surveyID][$langCode]['rules'];
    }
    
    
    // ------------------------- //
    // -- Getters and Setters -- //
    // ------------------------- //
    
    
    // ------------------- //
    // -- Other Methods -- //
    // ------------------- //
    
    /**
    * Resets SC SESSION
    * 
    * Automatically includes start time and unique response 'answers'
    * Allows custom initial 'answers' to be set
    * 
    * @static
    * @param string $first_page_id 'SC Page ID'
    * @param array $initial_answers 'SC Response array'
    */
    public static function reset_survey($first_page_id = NULL, $initial_answers = []) {
        $now = time();
        $urid_str = (isset($initial_answers['h_device']) ? $initial_answers['h_device'] . '-' : '') . $now . '-' . rand();

        $initial_answers['h_start_time']         = $now;
        $initial_answers['h_unique_response_id'] = md5($urid_str);
        
        $_SESSION[TOOLKIT_TAG]['answers']        = [];
        $_SESSION[TOOLKIT_TAG]['initialAnswers'] = $initial_answers;
        $_SESSION[TOOLKIT_TAG]['pages']          = []; //[$first_page_id];
        $_SESSION[TOOLKIT_TAG]['response']       = [];
    }
    
    /**
    * Stores answers and returns the next page id
    * @return string 'SC Page ID'
    */
    public function go() {
        $pageID = $_POST[TOOLKIT_TAG . '_page_id'];

        # Get next page id
        if (isset($_POST[TOOLKIT_TAG . '_dir']) && $_POST[TOOLKIT_TAG . '_dir'] === SC_Survey_Flow::DIR_BACK) { // Moving backward
            # Store answers
            if ($this->bool_store_bwd) {
                $questionIDs = $this->process_answers($_POST);
            }
            
            # Revert to last page
            if (count($_SESSION[TOOLKIT_TAG]['pages'])) {
                $lastPage = array_pop($_SESSION[TOOLKIT_TAG]['pages']);
                $next = $lastPage['pageID'];
            
            } else { // Before the first page
                $next = SC_Page_Generator::START;
            }

            $_SESSION[TOOLKIT_TAG]['response'] = $this->getResponse();
            
        } else {
            # Store answers
            $questionIDs = $this->process_answers($_POST);

            # Store page
            ## Pages array must be numerically indexed, to ensure they are in the correct order.
            $_SESSION[TOOLKIT_TAG]['pages'][] = [
                'pageID'    => $pageID,
                'questions' => $questionIDs
            ];

            $_SESSION[TOOLKIT_TAG]['response'] = $this->getResponse();

            
            # Calculate next page
            $next = $this->calculate_next_page($pageID);
            //$_SESSION[TOOLKIT_TAG]['pages'][] = $next;
        }
        
        return $next;
    }
    
    
    /**
    * Stores page answers in 'SC SESSION' response
    * @param array $data answers (usually $_POST form data)
    * @return bool success
    */
    public function process_answers($data) {
        # Set page id
        if (isset($data[TOOLKIT_TAG . '_page_id'])) {
            $pageID = $data[TOOLKIT_TAG . '_page_id'];
            
            # Loop through page questions
            $pageQuestions = $this->obj_page_generator->get_page_questions($pageID);

            foreach ($pageQuestions as $questionID => $question) {
                switch ($question['type']) {
                    case SC_Survey_Constructor::TYPE_MATRIX:
                    case SC_Survey_Constructor::TYPE_CHECKTRIX:
                        foreach ($question['subquestions'] as $subquestionID => $subquestion) {
                            $this->store_answer($data, $questionID, $subquestionID);
                        }
                        break;

                    case SC_Survey_Constructor::TYPE_RADIO:
                        $this->store_answer($data, $questionID);
                        if (isset($question['other']) && $data[$questionID] === $question['other']['value']) {
                            $this->store_answer($data, $questionID . '-specific-input');
                        }
                        break;

                    default:
                        $this->store_answer($data, $questionID);
                        break;
                }
            }

            return array_keys($pageQuestions);
            
        } else {    //No page id was posted
            return FALSE;
        }
    }


    /**
     * Returns an array of answers based on survey path
     */
    public function getResponse() {
        # Start with the initial answers
        $response = $_SESSION[TOOLKIT_TAG]['initialAnswers'];
        $answers  = $_SESSION[TOOLKIT_TAG]['answers'];

        # Loop through pages path
        for ($i = 0; $i < count($_SESSION[TOOLKIT_TAG]['pages']); $i++) {
            $page = $_SESSION[TOOLKIT_TAG]['pages'][$i];
            //echo $page['pageID'] . ': ' . count($page['questions']) . ' question(s)<br>';

            # Loop through questions in page
            for ($j = 0; $j < count($page['questions']); $j++) {
                $questionID = $page['questions'][$j];
                //echo '    - ' . $questionID . ': ';

                # Add all answer to response if exists
                if (isset($answers[$questionID]) && !empty($answers[$questionID])) {
                    $response[$questionID] = $answers[$questionID];

                    # Also add specified answers from 'other' questions
                    if (isset($answers[$questionID . '-specific-input']) && !empty($answers[$questionID . '-specific-input'])) {
                        $response[$questionID . '-specific-input'] = $answers[$questionID . '-specific-input'];
                    }
                    //echo 'Found';
                } else {
                    //echo 'Not found in answers';
                }
                //echo '<br>';
            }
            //echo '<br>';
        }
        return $response;
    }
    
    
    /** Stores an answer in 'SC SESSION'
    * @param array $data answers (usually $_POST form data)
    * @param string 'SC Question ID'
    * @param string 'SC Subquestion ID', if applicable
    */
    private function store_answer($data, $questionID, $subQuestionID = NULL) {
        $key =
            $subQuestionID !== NULL ?
            $questionID . '-' . $subQuestionID :
            $questionID;
        
        $answer = isset($data[$key]) && !empty($data[$key]) ? $data[$key] : NULL;
        
        # Add/update answer
        if (!is_null($answer)) {
            $_SESSION[TOOLKIT_TAG]['answers'][$key] = $answer;
        } else {
            unset($_SESSION[TOOLKIT_TAG]['answers'][$key]);
        }
    }
    
    
    /**
    * Calculates and returns the next page id, based on logic
    * @return string 'SC Page ID'
    */
    public function calculate_next_page($pageID) {

        # Check if there are rules for this survey
        if (isset($this->arr_yaml_rules)) {
            
            # Loop through Rules YAML pages
            foreach ($this->arr_yaml_rules as $rules_pid => $r) {

                if (is_array($r)) {

                    # Check if page ID matches against this rule
                    if ($rules_pid == $pageID) {

                        foreach ($r as $rule) {

                            # Seperate conditions from results
                            $arr_rule       = explode('->', $rule);
                            $str_conditions = trim($arr_rule[0]);
                            $str_result     = trim($arr_rule[1]);

                            if (strlen($str_conditions) > 0) {  // Condition exists
                                $conditions = $str_conditions;
                                //echo "Conditions: $str_conditions<br />";

                                $rpn = SC_Survey_Flow::infix_2_rpn($conditions);
                                //echo "RPN: $rpn<br />";

                                $responses = [];
                                foreach ($_SESSION[TOOLKIT_TAG]['answers'] as $questionID => $answer) {
                                    $responses[$questionID] = $answer;
                                }
                                $is_true = SC_Survey_Flow::evaluate_rpn_expression($rpn, $responses);
                                //echo "Evaluation: ".($is_true ? "true" : "false").'<br /><br />';
                                                                
                                if ($is_true) {
                                    $next_id = $str_result == SC_Survey_Flow::CMD_SUBMIT ? SC_Page_Generator::SUBMIT_RESPONSE : $str_result;
                                    break;  // Ignore other rules for this page
                                }

                            } elseif (strlen($str_result) > 0) { // Straight 'goto page' (no conditions) (can be used as an 'else' rule)
                                $next_id = $str_result == SC_Survey_Flow::CMD_SUBMIT ? SC_Page_Generator::SUBMIT_RESPONSE : $str_result;
                                break;  // Ignore other rules for this page
                            }

                        } // END FOREACH rule in page

                        if (isset($next_id)) {
                            return $next_id;
                        }

                    } // END IF rules apply to this page

                } else {
                    throw new Exception('error: rules are not an array');
                } // END IF rules are an array
            } // END FOREACH page in YAML
        } // END IF Rules YAML exists for this survey
            
        # Default to next sequential page
        //echo 'no specific rules have been applied, so default to next page<br />';
        $next_id = $this->obj_page_generator->get_neighbouring_page_id($pageID);
        //echo "next page: $next_id<br />";
        
        return $next_id;
    }


    /**
     * Divides an expression up into tokens
     * 
     * @static
     * @param  string $expr Expression
     * @return array        Tokens
     */
    public static function tokenise($expr) {
        $comparison_operators  = array(SC_Survey_Flow::CO_EQUAL, SC_Survey_Flow::CO_NOT_EQUAL, SC_Survey_Flow::CO_LT, SC_Survey_Flow::CO_GT, SC_Survey_Flow::CO_LTET, SC_Survey_Flow::CO_GTET);

        // Get literals
        $arr = explode(SC_Survey_Flow::LITERAL, $expr);
        $is_literal = FALSE;
        $tokens_all = [];
        foreach ($arr as $l) {
            if (strlen($l) > 0) {
                if ($is_literal !== FALSE) {
                    $tokens_all[] = SC_Survey_Flow::LITERAL . $l. SC_Survey_Flow::LITERAL;
                } else { //Not a literal
                    //divide into tokens (including comparison expressions)
                    $l = str_replace(SC_Survey_Flow::LO_NOT, ' ' . SC_Survey_Flow::LO_NOT . ' ', $l);
                    $l = str_replace(SC_Survey_Flow::LO_AND, ' ' . SC_Survey_Flow::LO_AND . ' ', $l);
                    $l = str_replace(SC_Survey_Flow::LO_OR, ' ' . SC_Survey_Flow::LO_OR . ' ', $l);
                    $l = str_replace(SC_Survey_Flow::OPEN_PAREN, ' ' . SC_Survey_Flow::OPEN_PAREN . ' ', $l);
                    $l = str_replace(SC_Survey_Flow::CLOSE_PAREN, ' ' . SC_Survey_Flow::CLOSE_PAREN . ' ', $l);
                    $l = str_replace(SC_Survey_Flow::CO_EQUAL, ' ' . SC_Survey_Flow::CO_EQUAL . ' ', $l);
                    $l = str_replace(SC_Survey_Flow::CO_LT, ' ' . SC_Survey_Flow::CO_LT . ' ', $l);
                    $l = str_replace(SC_Survey_Flow::CO_GT, ' ' . SC_Survey_Flow::CO_GT . ' ', $l);
                    $l = str_replace(' <  > ', ' ' . SC_Survey_Flow::CO_NOT_EQUAL . ' ', $l);
                    $l = str_replace(' <  = ', ' ' . SC_Survey_Flow::CO_LTET . ' ', $l);
                    $l = str_replace(' >  = ', ' ' . SC_Survey_Flow::CO_GTET . ' ', $l);
                    $l = trim($l);
                    $l = preg_replace('/\s+/', ' ', $l);    //remove all whitespace
                    $toks = explode(' ', $l);
                    foreach($toks as $t) {
                        $tokens_all[] = $t;
                    }
                }
                $is_literal = !$is_literal;
            }
        }

        //Combine comparison expressions
        $tokens = [];
        for ($i = 0; $i < count($tokens_all); $i++) {
            if (
                $i < count($tokens_all) - 2 &&
                in_array($tokens_all[$i + 1], $comparison_operators)
            ) {
                $tokens[] = $tokens_all[$i].$tokens_all[$i+1].$tokens_all[$i+2];
                $i+=2;
            } else {
                $tokens[] = $tokens_all[$i];
            }
        }

        return $tokens;
    }



    /**
     * Converts an Infix notation expression to Reverse Polish Notation
     * Uses the Shunting Yard algorithm
     *
     * @static
     * @param  string $infix Infix notation expression
     * @return string        RPN expression
     */
    public static function infix_2_rpn($infix) {
        // Operators
        $unary      = array(SC_Survey_Flow::LO_NOT);
        $binary     = array(SC_Survey_Flow::LO_AND, SC_Survey_Flow::LO_OR);
        $operators  = array(SC_Survey_Flow::LO_NOT, SC_Survey_Flow::LO_AND, SC_Survey_Flow::LO_OR);
        $precedence = array(SC_Survey_Flow::OPEN_PAREN, SC_Survey_Flow::CLOSE_PAREN, SC_Survey_Flow::LO_NOT, SC_Survey_Flow::LO_AND, SC_Survey_Flow::LO_OR);

        // Format expression
        $tokens = SC_Survey_Flow::tokenise($infix);

        $output = $stack = [];
        $loop = 1;
        foreach ($tokens as $t) {

            if (in_array($t, $unary)) { //Token is a unary operator
                $stack[] = $t;

            } elseif (in_array($t, $binary)) {  //Token is a binary operator
                $r = array_pop($stack);
                // check if r is an operator and if it takes precedence over t
                if (in_array($r, $operators) && array_search($t, $precedence) > array_search($r, $precedence)) {
                    if ($r !== NULL) {
                        $output[] = $r;
                    }
                } else {
                    if ($r !== NULL) {
                        $stack[] = $r;
                    }
                }
                $stack[] = $t;

            } elseif ($t === SC_Survey_Flow::OPEN_PAREN) {  //Token is opening parenthesis
                $stack[] = $t;

            } elseif ($t === SC_Survey_Flow::CLOSE_PAREN) { //Token is closing parenthesis
                while (count($stack) > 0) {
                    $r = array_pop($stack);
                    if ($r !== SC_Survey_Flow::OPEN_PAREN) {
                        $output[] = $r;
                        if (count($stack) === 0) {
                            throw new Exception('mismatched parenthesis!');
                        }
                    } else {
                        break;
                    }
                }
                if (count($stack) > 0 && in_array($stack[0], $unary)) {
                    $r = array_pop($stack);
                    $output[] = $r;
                }

            } else {    //Token is a condition
                $output[] = $t;

            }
            $loop++;
        }   // END FOREACH


        //Add the rest of the stack to output
        while (count($stack) > 0) {
            if ($stack[0] === SC_Survey_Flow::OPEN_PAREN || $stack[0] === SC_Survey_Flow::CLOSE_PAREN) {
                throw new Exception('mismatched parenthesis!');
            }
            $r = array_pop($stack);
            $output[] = $r;
        }


        return implode(' ', $output);
        
    }


    /**
     * Takes a RPN logical expression and evaluates it as true or false
     *
     * @static
     * @param  string $expr RPN expression
     * @param  array $vars  variables lookup table
     * @return bool         evaluation
     */
    public static function evaluate_rpn_expression($expr, $vars) {
        // Operators
        $unary  = array(SC_Survey_Flow::LO_NOT);
        $binary = array(SC_Survey_Flow::LO_AND, SC_Survey_Flow::LO_OR);

        //Split expression into tokens
        $tokens = SC_Survey_Flow::tokenise($expr);
        
        $tokens = array_map(function ($val) {
            return str_replace(SC_Survey_Flow::LITERAL, '', $val);
        }, $tokens);
        //print_r($tokens);

        $stack = [];
        $loop  = 1;

        foreach ($tokens as $t) {
            if (in_array($t, $unary)) { //Token is a unary operator
                $r = array_pop($stack);
                $result = SC_Survey_Flow::evaluate_unary_logic($r, $t);
                $stack[] = $result;

            } elseif (in_array($t, $binary)) { //Token is a binary operator
                $r1 = array_pop($stack);
                $r2 = array_pop($stack);
                $result = SC_Survey_Flow::evaluate_binary_logic($r1, $r2, $t);
                $stack[] = $result;

            } else {    //Token is a condition
                $result = SC_Survey_Flow::evaluate_condition($t, $vars);
                //echo 'token: '.$t.' === '.($result ? 'TRUE' : 'FALSE').'<br />';
                $stack[] = $result;

            }
            $loop++;
        }

        return $stack[0];
    }



    /**
     * Evaluates a single condition, based on answers in a lookup table
     *
     * @static
     * @param  string $t    condition expression
     * @param  array  $vars lookup table (key: variable, value: value)
     * @return bool         evaluation of condition
     */
    public static function evaluate_condition($t, $vars) {
        if (strpos($t, SC_Survey_Flow::CO_NOT_EQUAL)) { //Not equal to
            $arr = explode(SC_Survey_Flow::CO_NOT_EQUAL, $t);
            $var = $arr[0];
            $val = $arr[1];
            return $vars[$var] !== $val;

        } elseif (strpos($t, SC_Survey_Flow::CO_GTET)) { //Greater than or equal to
            $arr = explode(SC_Survey_Flow::CO_GTET, $t);
            $var = $arr[0];
            $val = $arr[1];
            return $vars[$var] >= $val;

        } elseif (strpos($t, SC_Survey_Flow::CO_LTET)) { //Less than or equal to
            $arr = explode(SC_Survey_Flow::CO_LTET, $t);
            $var = $arr[0];
            $val = $arr[1];
            return $vars[$var] <= $val;

        } elseif (strpos($t, SC_Survey_Flow::CO_EQUAL)) {   //Equal to
            $arr = explode(SC_Survey_Flow::CO_EQUAL, $t);
            $var = $arr[0];
            $val = $arr[1];
            return $vars[$var] === $val;

        } elseif (strpos($t, SC_Survey_Flow::CO_GT)) { //Greater than
            $arr = explode(SC_Survey_Flow::CO_GT, $t);
            $var = $arr[0];
            $val = $arr[1];
            return $vars[$var] > $val;

        } elseif (strpos($t, SC_Survey_Flow::CO_LT)) { //Less than
            $arr = explode(SC_Survey_Flow::CO_LT, $t);
            $var = $arr[0];
            $val = $arr[1];
            return $vars[$var] < $val;

        } else {    //Exists and is true
            return isset($vars[$t]) && $vars[$t];
        }

    }

    /**
     * Evaluates a unary expression
     *
     * @static
     * @param  bool   $c        value
     * @param  string $operator unary operator (eg. !)
     * @return bool             evaluation
     */
    public static function evaluate_unary_logic($c, $operator) {
        switch($operator) {
            case SC_Survey_Flow::LO_NOT:
                return !$c;
                break;
        }

        return FALSE;
    }


    /**
     * Evaluates a binary expression
     * 
     * @static
     * @param  bool   $c1       value 1
     * @param  bool   $c2       value 2
     * @param  string $operator binary operator (eg. &&)
     * @return bool             evaluation
     */
    public static function evaluate_binary_logic($c1, $c2, $operator) {
        switch ($operator) {
            case SC_Survey_Flow::LO_AND:
                return $c1 && $c2;
                break;

            case SC_Survey_Flow::LO_OR:
                return $c1 || $c2;
                break;
        }

        return FALSE;
    }
}
