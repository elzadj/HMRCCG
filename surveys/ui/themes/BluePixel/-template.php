<?php
class Template {

    const CODE_OTHER    = 'ct-other';
    const CODE_SPECIFY  = 'ct-specify';
    const CODE_REQUIRED = 'ct-required';

    const HEADING_OTHER    = 'Other'; // Default option group heading for 'other' question
    const HEADING_SPECIFY  = 'Please specify'; // Default subquestion title for 'other' question
    const HEADING_REQUIRED = 'An answer to this question is required';

    private $ignoreDefaults;
    private $surveysFolder;
    private $surveyID;
    private $answers;

    private $heading_other;
    private $heading_specify;
    private $heading_required;

    private $dictionary = [];
    private $langCode   = NULL;


    public function __construct($answers = [], $surveyID, $surveysFolder, $ignoreDefaults = TRUE) {
        $this->answers        = $answers;
        $this->surveyID       = $surveyID;
        $this->surveysFolder  = $surveysFolder;
        $this->ignoreDefaults = $ignoreDefaults;

        $this->setSystemHeadings();
    }

    private function setSystemHeadings() {
        $other    = $this->translate(self::CODE_OTHER);
        $specify  = $this->translate(self::CODE_SPECIFY);
        $required = $this->translate(self::CODE_REQUIRED);

        $this->heading_other    = $other ?: self::HEADING_OTHER;
        $this->heading_specify  = $specify ?: self::HEADING_SPECIFY;
        $this->heading_required = $specify ?: self::HEADING_REQUIRED;
    }

    public static function html($str) {
        return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
    }

    public function setLanguage($dictionary, $langCode) {
        $this->dictionary = $dictionary;
        $this->langCode   = $langCode;

        $this->setSystemHeadings();
    }


    public function translate($id) {
        return
            !is_null($this->dictionary) && isset($this->dictionary[$id]) ?
            $this->dictionary[$id] :
            FALSE;
    }

    public function translateMedia($value) {
        if (
            isset($value['id']) &&
            $translation = $this->translate($value['id'])
        ) {
            $value['content'] = $translation;
        }

        return $value;
    }


    public function getMediaHTML($media) {

        # Check media type exists
        if (!isset($media['type'])) {
            exit('media type not set');
        }

        # Display media
        switch ($media['type']) {
            case 'question':
                return $this->get_question_html($media['id'], $media['content']);
                break;

            default:
                $media   = $this->translateMedia($media);
                $trimmed = trim($media['content']); // can't pass function into 'empty()'

                if (!empty($trimmed)) {
                    return $this->encloseFormattedText($media, 'div', 'media-block');
                }
        }
    }



   /**
    * Returns HTML for one question
    * 
    * Generates HTML depending on question type
    * 
    * @param string SC Question ID
    * @param array SC Question Properties
    * @param array SC answers array
    * @return string HTML
    */
    public function get_question_html($id, $question) {
        # Get standard properties
        $type = (string)$question['type'];


        # Get control type
        $control = NULL;
        if ($type === 'radio') {
            //radio by default
            $control = isset($question['control']) ? (string)$question['control'] : 'radio';
        }
        

        # Generate HTML
        $html = '<!-- Q: ' . $this->getFormattedText($question['title'], NULL, TRUE) . '-->' . "\n";
        $html .= '<div class="question">' . "\n";
        
        switch ($type) {
            case 'text':
                $html .= $this->get_question_text($id, $question);
                break;
            
            case 'multiline':
                $html .= $this->get_question_multiline($id, $question);
                break;
            
            case 'number':
                $html .= $this->get_question_number($id, $question);
                break;
            
            case 'radio':
                switch ($control) {
                    case 'selectbox':
                        $html .= $this->get_question_select($id, $question);
                        break;
                    case 'radio':
                    default:
                        $html .= $this->get_question_radio($id, $question);
                }
                break;
            
            case 'checkbox':
                $html .= $this->get_question_checkbox($id, $question);
                break;
            
            case 'matrix':
                $html .= $this->get_question_matrix($id, $question);
                break;
            
            case 'checktrix':
                $html .= $this->get_question_checktrix($id, $question);
                break;
            
            case 'date':
                $html .= $this->get_question_date($id, $question);
                break;
            
            case 'time':
                $html .= $this->get_question_time($id, $question);
                break;
            
            case 'email':
                $html .= $this->get_question_email($id, $question);
                break;
            
            case 'url':
                $html .= $this->get_question_url($id, $question);
                break;
        }
        
        $html .= '</div>    <!--END question-->' . "\n\n";
        
        return $html;
    }


    /**
     * Searches for the location of a file in the expected places
     * @param string $filename filename
     * @return string filepath
     */
    private function getFilepath($filename) {
        $relativeSurveysFolder = 'surveys/';

        if (!is_null($this->langCode)) {
            if (file_exists($this->surveysFolder . $this->surveyID . '/' . $this->langCode . '/assets/' . $filename)) {
                return $relativeSurveysFolder . $this->surveyID . '/' . $this->langCode . '/assets/' . $filename;
            }
        }

        if (file_exists($this->surveysFolder . $this->surveyID . '/assets/' . $filename)) {
            return $relativeSurveysFolder . $this->surveyID . '/assets/' . $filename;

        } elseif (file_exists($this->surveysFolder . 'assets/' . $filename)) {
            return $relativeSurveysFolder . 'assets/' . $filename;

        } else {
            return $filename;
        }
    }


    /**
     * Searches a string for <img> and corrects the filepath
     * @param string $str haystack
     * @return string corrected
     */
    private function formatImages($str) {
        return
            preg_replace_callback('/src="(.*)"/', function ($matches) {
                return 'src="' . self::html($this->getFilepath($matches[1])) . '"';
            }, $str);
    }


    /**
     * Generate and return formatted text
     *
     * @param array | string textual value
     * @param array | string CSS classes
     * @return string HTML
     */
    private function getFormattedText($value, $plain = FALSE) {

        # Return original plain text if specified (HTML encoded)
        if ($plain !== FALSE) {
            return self::html($value['content']);
        }


        # Merge with translation if applicable
        $value = $this->translateMedia($value);

        
        # Format text
        switch ($value['type']) {
            case SC_Survey_Constructor::MEDIA_HTML:
                $html = $value['content'];
                $html = $this->formatImages($html);
                break;

            case SC_Survey_Constructor::MEDIA_MD_INLINE:
                $html = markdown($value['content'], TRUE);
                $html = $this->formatImages($html);
                break;

            case SC_Survey_Constructor::MEDIA_MD_BLOCK:
                $html = markdown($value['content'], FALSE);
                $html = $this->formatImages($html);
                break;

            case SC_Survey_Constructor::MEDIA_PLAIN:
                $html = self::html($value['content']);
                break;

            default:
                exit('Unknown text type: \'' . $value['type'] . '\'');
        }

        return $html;
    }


    private function encloseFormattedText($value, $element = 'div', $class = NULL) {
        $arr_attrs = [];

        # Add class(es) to attributes list
        if (!is_null($class)) {
            $classes = [];

            if (is_array($class)) {
                $classes = array_map($class, function ($c) {
                    return $c;
                });
            } else {
                $classes[] = $class;
            }

            $arr_attrs[] = new Attr('class', $classes);
        }

        # Add textcode to attributes list
        if (isset($value['id'])) {
            $arr_attrs[] = new Attr('data-textcode', $value['id']);
        }

        $html = $this->getFormattedText($value);

        return '<' . $element . (count($arr_attrs) ? ' ' . Attr::displayAll($arr_attrs) : '') . '><div class="label">' . $html . '</div></' . $element . '>';
    }


    /**
     * Generate and return HTML question header
     *
     * @param array SC Question
     * @return string HTML
     */
    private function getQuestionHeader($question) {

        $html = '';

        $html .= '      <div class="question-titles">' . "\n";
        if (isset($question['image'])) {
            $html .= '          <div class="image-block"><img alt="" src="' . self::html($this->getFilepath($question['image'])) . '"></div>' . "\n";
        }
        if (isset($question['intro'])) {
            $html .= '          ' . $this->encloseFormattedText($question['intro'], 'div', 'question-intro') . "\n";
        }
        if (isset($question['title'])) {
            $html .= '          ' . $this->encloseFormattedText($question['title'], 'div', 'question-title') . "\n";
        }
        if (isset($question['subtitle'])) {
            $html .= '          ' . $this->encloseFormattedText($question['subtitle'], 'div', 'question-subtitle') . "\n";
        }
        if (isset($question['instruction'])) {
            $html .= '          ' . $this->encloseFormattedText($question['instruction'], 'div', 'question-instruction') . "\n";
        }
        $html .= '      </div>' . "\n";

        $html .= '      <div class="input-errors" data-textcode="' . self::CODE_REQUIRED. '" style="display: none;"><div class="label">' . self::html($this->heading_required) . '</div></div>' . "\n";

        return $html;
    }

    /**
     * Return an HTML class list based on question type and custom classes
     * @param array SC Question
     * @param string Question type
     * @return string HTML space-seperated list of classes
     */
    private function getFieldClasses($question, $questionType) {
        //Get custom classes
        $custom_classes = [];

        if (isset($question['custom_classes'])) {
            $custom_classes = array_map('trim', explode(SC_Page_Generator::FLAG_DELIMITER, $question['custom_classes']));
        }

        $classes = array_merge(['fieldset', $questionType], $custom_classes);

        return self::html(implode(' ', $classes));
    }


    
    /**
     * Generate HTML for options
     * @param string $questionID Question ID
     * @param array $options SC Options
     * @param array $input_attrs input attributes
     * @param string $value currently selected option value
     * @param int &$loop pointer to loop number
     * @return string HTML
     */
    private function getOptions($questionID, $isSelect = FALSE, $options, $input_attrs, &$loop) {
        $html = '';
        $answers = $this->answers;

        foreach ($options as $opt) {
            $group_attrs  = [];
            $option_attrs = [];
            $label_attrs  = [];

            $opt_id = $questionID . '-' . $loop;

            $group_attrs[] = new Attr('class', 'option');
            if (isset($opt['id'])) {
                $group_attrs[] = new Attr('data-textcode', $opt['id']);
            }
            
            $option_attrs[] = new Attr('id', $opt_id);
            $option_attrs[] = new Attr('value', $opt['value']);

            # Default/Current value
            if (
                (isset($answers[$questionID]) && $opt['value'] === $answers[$questionID]) ||
                (!$this->ignoreDefaults && isset($question['default']) && $opt['value'] === $question['default'])
            ) {
                $option_attrs[] = $isSelect !== FALSE ? new Attr('selected') : new Attr('checked');
            }

            $label_attrs[] = new Attr('class', 'label');
            $label_attrs[] = new Attr('for', $opt_id);


            
            if ($isSelect !== FALSE) {
                $html .= '          <option ' . Attr::displayAll(array_merge($group_attrs, $option_attrs)) . '>' . $this->getFormattedText($opt) . '</option>' . "\n";
            } else {
                $html .= '          <div ' . Attr::displayAll($group_attrs) . '>' . "\n";
                $html .= '              <input ' . Attr::displayAll(array_merge($input_attrs, $option_attrs)) . '>' . "\n";
                $html .= '              <label ' . Attr::displayAll($label_attrs) . '>' . $this->getFormattedText($opt) . '</label>' . "\n";
                $html .= '          </div>' . "\n";
            }
            $loop++;
        }

        return $html;
    }


    /**
     * Generate HTML for 'other option
     * @param string $questionID Question ID
     * @param array $options SC Other Option
     * @param array $input_attrs input attributes
     * @param string $value currently selected option value
     * @return string HTML
     */
    private function getOtherOption($questionID, $isSelect = FALSE, $option, $input_attrs) {
        $html = '';
        $opt = $option;

        $group_attrs  = [];
        $option_attrs = [];
        $label_attrs  = [];

        $opt_id = $questionID . '-other';

        $group_attrs[] = new Attr('class', 'option');
        if (isset($opt['id'])) {
            $group_attrs[] = new Attr('data-textcode', $opt['id']);
        }

        $option_attrs[] = new Attr('id', $opt_id);
        $option_attrs[] = new Attr('value', $opt['value']);
        $option_attrs[] = new Attr('data-special', 'other');
        
        # Current value
        $answers = $this->answers;
        if (isset($answers[$questionID]) && $opt['value'] === $answers[$questionID]) {
            $option_attrs[] = $isSelect !== FALSE ? new Attr('selected') : new Attr('checked');
        }

        $label_attrs[] = new Attr('class', 'label');
        $label_attrs[] = new Attr('for', $opt_id);

        
        if ($isSelect !== FALSE) {
            $html .= '          <option ' . Attr::displayAll($option_attrs) . '>' . $this->getFormattedText($opt) . '</option>' . "\n";            
        } else {
            $html .= '          <div ' . Attr::displayAll($group_attrs) . '>' . "\n";
            $html .= '              <input ' . Attr::displayAll(array_merge($input_attrs, $option_attrs)) . '>' . "\n";
            $html .= '              <label ' . Attr::displayAll($label_attrs) . '>' . $this->getFormattedText($opt) . '</label>' . "\n";
            $html .= '          </div>' . "\n";
        }

        return $html;
    }


    /**
    * Generated and Returns HTML for text questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param bool Question required
    * @param number Maximum length
    * @param string Answer
    * @return string HTML
    */
    private function get_question_text($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'text');
        $input_attrs[] = new Attr('id', $id);
        $input_attrs[] = new Attr('name', $id);
        $input_attrs[] = new Attr('autocomplete', 'off');
        
        # Max length
        if (isset($question['max']) && (int)$question['max']) {
            $input_attrs[] = new Attr('maxlength', (string)$question['max']);
            $input_attrs[] = new Attr('size', (string)min($question['max'], 50));

        } else {
            $input_attrs[] = new Attr('maxlength', '255');
            $input_attrs[] = new Attr('size', '40');
        }

        # Default/Current value
        $answers = $this->answers;
        if (isset($answers[$id])) {
            $input_attrs[] = new Attr('value', $answers[$id]);
        } elseif(!$this->ignoreDefaults && isset($question['default'])) {
            $input_attrs[] = new Attr('value', $question['default']);
        }
        
        # Required
        if (isset($question['required']) && $question['required'] !== FALSE) {
            $input_attrs[] = new Attr('required');
        }
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'text') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <input ' . Attr::displayAll($input_attrs) . '>' . "\n";
        $html .= '  </div>' . "\n";

        return $html;
    }


    /**
    * Generated and Returns HTML for multiline text questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param bool Question required
    * @param number Maximum length
    * @param string Answer
    * @return string HTML
    */
    private function get_question_multiline($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('id', $id);
        $input_attrs[] = new Attr('name', $id);
        
        # Max length
        if (isset($question['max']) && (int)$question['max']) {
            $input_attrs[] = new Attr('maxlength', (string)$question['max']);
            $input_attrs[] = new Attr('size', (string)min($question['max'], 50));
        }

        # Default/Current value
        $answers = $this->answers;
        $value = '';
        if (isset($answers[$id])) {
            $value = $answers[$id];
        } elseif(!$this->ignoreDefaults && isset($question['default'])) {
            $value = $question['default'];
        }
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $input_attrs[] = new Attr('required');
        }

        
        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'multiline') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <textarea '.Attr::displayAll($input_attrs).'>' . self::html($value) . '</textarea>' . "\n";
        $html .= '  </div>' . "\n";

        return $html;
    }


    /**
    * Generated and Returns HTML for number questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param bool Question required
    * @param number Minimum value
    * @param number Maximum value
    * @param number Step value
    * @param string Answer
    * @return string HTML
    */
    private function get_question_number($id, $question) {

        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'number');
        $input_attrs[] = new Attr('id', $id);
        $input_attrs[] = new Attr('name', $id);
        $input_attrs[] = new Attr('autocomplete', 'off');

        if (isset($question['min']) && (is_int($question['min']) || is_float($question['min']))) {
            $input_attrs[] = new Attr('min', $question['min']);
        }
        if (isset($question['max']) && (is_int($question['max']) || is_float($question['max']))) {
            $input_attrs[] = new Attr('max', $question['max']);
        }
        if (isset($question['step']) && (float)$question['step'] != 0) {
            $input_attrs[] = new Attr('step', $question['step']);
        }

        # Default/Current value
        $answers = $this->answers;
        if (isset($answers[$id])) {
            $input_attrs[] = new Attr('value', $answers[$id]);
        } elseif(!$this->ignoreDefaults && isset($question['default'])) {
            $input_attrs[] = new Attr('value', $question['default']);
        }
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $input_attrs[] = new Attr('required');
        }
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'number') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <input ' . Attr::displayAll($input_attrs) . '>' . "\n";
        $html .= '  </div>' . "\n";

        return $html;
    }


    /**
    * Generated and Returns HTML for radio questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param string Question instructions
    * @param bool Question required
    * @param string Answer options
    * @param string Answer
    * @return string HTML
    */
    private function get_question_radio($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'radio');
        $input_attrs[] = new Attr('name', $id);
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $input_attrs[] = new Attr('required');
        }
        
        $is_custom_answer = isset($question['other']);
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'radio') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";

        # Loop through options
        $loop = 0;

        if (isset($question['groups'])) {
            foreach ($question['groups'] as $g) {
                $html .= '    <section class="option-group">' . "\n";

                if (isset($g['content'])) {
                    $html .= '      ' . $this->encloseFormattedText($g, 'div', 'group-title') . "\n";
                }

                $html .= '      <div class="options">' . "\n";
                $html .= $this->getOptions($id, FALSE, $g['options'], $input_attrs, $loop);
                $html .= '      </div>' . "\n";

                $html .= '    </section>' . "\n";
            }

            if ($is_custom_answer) {
                $html .= '    <section class="option-group">' . "\n";
                $html .= '      <div class="group-title" data-textcode="' . self::CODE_OTHER. '"><div class="label">' . self::html($this->heading_other) . '</div></div>' . "\n";
                $html .= '      <div class="options">' . "\n";
                $html .= $this->getOtherOption($id, FALSE, $question['other'], $input_attrs);
                $html .= '      </div>' . "\n";
                $html .= '    </section>' . "\n";
            }

        } else {
            $html .= '      <div class="options">' . "\n";
            $html .= $this->getOptions($id, FALSE, $question['options'], $input_attrs, $loop);

            if ($is_custom_answer) {
                $html .= $this->getOtherOption($id, FALSE, $question['other'], $input_attrs);
            }

            $html .= '      </div>' . "\n";

        }

        $html .= '  </div>' . "\n";
        

        # 'Other' field
        if ($is_custom_answer) {
            $input_attrs = [];
            $input_attrs[] = new Attr('type', 'text');
            $input_attrs[] = new Attr('id', $id . '-specific-input');
            $input_attrs[] = new Attr('name', $id . '-specific-input');
            $input_attrs[] = new Attr('placeholder', self::html($this->heading_specify));
            

            # Current value
            $answers = $this->answers;
            if (isset($answers[$id . '-specific-input'])) {
                $input_attrs[] = new Attr('value', $answers[$id . '-specific-input']);
            }
            
            # Generate HTML
            $html .= '  <div id="' . $id . '-specific" class="text">' . "\n";
            // $html .= '      <div class="title" data-textcode="' . self::CODE_SPECIFY. '"><div class="label">' . self::html($this->heading_specify) . '</div></div>' . "\n";
            $html .= '      <div class="input-errors" data-textcode="' . self::CODE_REQUIRED. '" style="display: none;"><div class="label">' . self::html($this->heading_required) . '</div></div>' . "\n";
            $html .= '      <div class="specify-input" data-textcode="' . self::CODE_SPECIFY. '">' . "\n";
            $html .= '          <input class="label" ' . Attr::displayAll($input_attrs) . '>' . "\n";
            $html .= '      </div>' . "\n";
            $html .= '  </div>' . "\n";
        }
        
        return $html;
    }


    /**
    * Generated and Returns HTML for selectbox radio questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param string Question instructions
    * @param bool Question required
    * @param string Answer options
    * @param string Answer
    * @return string HTML
    */
    private function get_question_select($id, $question) {
        
        # Assign attributes
        $select_attrs = [];
        $select_attrs[] = new Attr('name', $id);
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $select_attrs[] = new Attr('required');
        }
        
        $is_custom_answer = isset($question['other']);
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'select') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";

        # Loop through options
        $loop = 0;
        $html .= '    <select class="options" ' . Attr::displayAll($select_attrs) . '>' . "\n";

        if (isset($question['groups'])) {
            foreach ($question['groups'] as $g) {
                $group_attrs = [];
                if (isset($g['content'])) {
                    $group_attrs[] = new Attr('label', $this->getFormattedText($g));

                    if (isset($g['id'])) {
                        $group_attrs[] = new Attr('data-textcode', self::html($g['id']));
                    }

                } else {
                    $group_attrs[] = new Attr('label', '');
                }

                $html .= '    <optgroup ' . Attr::displayAll($group_attrs) . '>' . "\n";
                $html .= $this->getOptions($id, TRUE, $g['options'], [], $value, $loop);
                $html .= '    </optgroup>' . "\n";
            }

            if ($is_custom_answer) {
                $html .= '    <optgroup data-textcode="' . self::CODE_OTHER. '" label="' . self::html($this->heading_other) . '">' . "\n";
                $html .= $this->getOtherOption($id, TRUE, $question['other'], [], $value);
                $html .= '    </optgroup>' . "\n";
            }

        } else {
            $html .= $this->getOptions($id, TRUE, $question['options'], [], $value, $loop);

            if ($is_custom_answer) {
                $html .= $this->getOtherOption($id, TRUE, $question['other'], [], $value);
            }
        }

        $html .= '    </select>' . "\n";

        $html .= '  </div>' . "\n";
        

        # 'Other' field
        if ($is_custom_answer) {
            $input_attrs = [];
            $input_attrs[] = new Attr('type', 'text');
            $input_attrs[] = new Attr('id', $id.'-specific-input');
            $input_attrs[] = new Attr('name', $id.'-specific-input');

            # Current value
            $answers = $this->answers;
            if (isset($answers[$id . '-specific-input'])) {
                $input_attrs[] = new Attr('value', $answers[$id . '-specific-input']);
            }
            
            # Generate HTML
            $html .= '  <div id="' . $id . '-specific" class="text">' . "\n";
            $html .= '      <div class="title" data-textcode="' . self::CODE_SPECIFY. '"><div class="label">' . self::html($this->heading_specify) . '</div></div>' . "\n";
            $html .= '      <div class="input-errors" data-textcode="' . self::CODE_REQUIRED. '" style="display: none;"><div class="label">' . self::html($this->heading_required) . '</div></div>' . "\n";
            $html .= '      <input ' . Attr::displayAll($input_attrs) . '>' . "\n";
            $html .= '  </div>' . "\n";
        }
        
        return $html;
    }


    /**
    * Generated and Returns HTML for checkbox questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param string Question instructions
    * @param bool Question required
    * @param string Answer options
    * @param string Answer
    * @return string HTML
    */
    private function get_question_checkbox($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'checkbox');
        $input_attrs[] = new Attr('name', $id.'[]');
        
        # Required
        if (isset($question['required']) && $question['required']) {
        //  $input_attrs[] = new Attr('required'); We don't want all the checkboxes to be required!
        }
        
        $is_custom_answer = isset($question['other']);
        
        # Values
        if (isset($value)) {
            $values = is_array($value) ? $value : array($value);
        } else {
            $values = [];
        }

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'checkbox') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";

        # Loop through options
        $loop = 0;

        if (isset($question['groups'])) {
            foreach ($question['groups'] as $g) {
                $html .= '    <section class="option-group">' . "\n";

                if (isset($g['content'])) {
                    $html .= '      ' . $this->encloseFormattedText($g, 'div', 'group-title') . "\n";
                }

                $html .= '      <div class="options">' . "\n";
                $html .= $this->getOptions($id, FALSE, $g['options'], $input_attrs, $value, $loop);
                $html .= '      </div>' . "\n";

                $html .= '    </section>' . "\n";
            }

        } else {
            $html .= '      <div class="options">' . "\n";
            $html .= $this->getOptions($id, FALSE, $question['options'], $input_attrs, $value, $loop);
            $html .= '      </div>' . "\n";

        }

        $html .= '  </div>' . "\n";

        return $html;
    }


    /**
    * Generated and Returns HTML for matrix questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param string Question instructions
    * @param bool Question required
    * @param string Answer options
    * @param string Answer subquestions
    * @param string SC answers array
    * @return string HTML
    */
    private function get_question_matrix($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'radio');
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'matrix') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <table>' . "\n";
        
        # Display options
        $options_len = count($question['options']);
        if (isset($question['scale'])) {
            $scale_len   = count($question['scale']);
            $scale_span  = $options_len;
            $option_span = $scale_len;
            $cols        = $options_len * $scale_len;

        } else {
            $scale_len   = 0;
            $option_span = $options_len;
            $cols        = $options_len;
        }

        $sq_span = $option_span * 3;

        $html .= '          <thead>' . "\n";
        $html .= '              <tr>' . "\n";
        $html .= '                  <th colspan="' . $sq_span . '"></th>' . "\n";

        if ($scale_len) {
            for ($i = 0; $i < $scale_len; $i++) {
                $label = $question['scale'][$i];

                $scale_attrs = [];
                $scale_attrs[] = new Attr('colspan', $scale_span);

                $pos = 'centre';
                if ($scale_len > 1) {
                    if ($i === 0) {
                        $pos = 'left';
                    } elseif ($i === $scale_len - 1) {
                        $pos = 'right';
                    }
                }
                
                $scale_attrs[] = new Attr('class', $pos);

                # Textcode
                if (isset($label['id'])) {
                    $scale_attrs[] = new Attr('data-textcode', $label['id']);
                }

                $html .= '                  <th ' . Attr::displayAll($scale_attrs) . '>' . $this->getFormattedText($label) . '</th>' . "\n";
            }

        } else {
            $option_span = $options_len;
            foreach ($question['options'] as $opt) {
                $option_attr = [];
                $option_attr[] = new Attr('colspan', $option_span);

                # Textcode
                if (isset($opt['id'])) {
                    $option_attr[] = new Attr('data-textcode', $opt['id']);
                }

                $html .= '                  <th ' . Attr::displayAll($option_attr) . '>' . $this->getFormattedText($opt) . '</th>' . "\n";
            }
        }
        $html .= '              </tr>' . "\n";
        $html .= '          </thead>' . "\n";
        $html .= '          <tbody>' . "\n";
        
        # Loop through subquestions
        foreach ($question['subquestions'] as $sq_id => $sq ) {
            $sq_name = $id.'-'.$sq_id;

            $subquestion_attrs = [];
            $subquestion_attrs[] = new Attr('name', $sq_name);
            
            # Required
            if (isset($sq['required'])) {   //Specific instruction for this subquestion
                $sq_required = (bool)$sq['required'];
            } else {
                if (isset($question['required'])) { //Default instruction for all subquestions
                    $sq_required = (bool)$question['required'];
                } else {    //No default specified
                    $sq_required = FALSE;
                }
            }
            if ($sq_required) {
                $subquestion_attrs[] = new Attr('required');
            }
            
            # Values
            if (isset($this->answers[$sq_name])) {
                $value = $this->answers[$sq_name];
            } elseif (!$this->ignoreDefaults) {   //Default answers allowed?
                //Revert back to default answer
                $value = isset($sq['default']) ? $sq['default'] : NULL;
            } else {
                $value = NULL;
            }
            //if ($value) echo $sub_name.': '.$value.'<br>';
            

            # Generate HTML for subquestion
            $question_attrs = [];
            $question_attrs[] = new Attr('colspan', $sq_span);

            # Textcode
            if (isset($sq['id'])) {
                $question_attrs[] = new Attr('data-textcode', $sq['id']);
            }

            $html .= '              <tr>' . "\n";
            $html .= '                  <th ' . Attr::displayAll($question_attrs) . '>' . $this->getFormattedText($sq) . '</th>' . "\n";


            # Loop through options
            $loop = 0;
            foreach ($question['options'] as $k => $opt) {
                $option_attrs = [];
                $option_attrs[] = new Attr('value', $opt['value']);
                
                # Checked value can only be literal option title
                if ($opt['value'] == $value) {
                    $option_attrs[] = new Attr('checked');
                }
                
                $html .= '                  <td colspan="' . $option_span . '"><input ' . Attr::displayAll(array_merge($input_attrs, $subquestion_attrs, $option_attrs)) . '></td>' . "\n";
                $loop++;
            }
            $html .= '              </tr>' . "\n";
        }
        $html .= '          </tbody>' . "\n";
        $html .= '      </table>' . "\n";
        $html .= '  </div>' . "\n";

        return $html;
    }


    /**
    * Generated and Returns HTML for checktrix questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param string Question instructions
    * @param bool Question required
    * @param string Answer options
    * @param string Answer subquestions
    * @param string SC answers array
    * @return string HTML
    */
    private function get_question_checktrix($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'checkbox');
        
        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'checktrix') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <table>' . "\n";
        
        # Display options
        $html .= '          <thead>' . "\n";
        $html .= '              <tr>' . "\n";
        $html .= '                  <th></th>' . "\n";
        foreach ($question['options'] as $opt ) {
            $option_attr = [];
            //$option_attr[] = new Attr('colspan', $option_span);

            # Textcode
            if (isset($opt['id'])) {
                $option_attr[] = new Attr('data-textcode', $opt['id']);
            }

            $html .= '                  <th ' . Attr::displayAll($option_attr) . '>' . $this->getFormattedText($opt) . '</th>' . "\n";
        }
        $html .= '              </tr>' . "\n";
        $html .= '          </thead>' . "\n";
        $html .= '          <tbody>' . "\n";
        
        foreach ($question['subquestions'] as $sq_id => $sq) {
            $sq_name = $id . '-' . $sq_id;

            $subquestion_attrs = [];
            $subquestion_attrs[] = new Attr('name', $sq_name . '[]');
            
            # Required
            if (isset($sq['required'])) {   //Specific instruction for this subquestion
                $sq_required = (bool)$sq['required'];
            } else {
                if (isset($question['required'])) { //Default instruction for all subquestions
                    $sq_required = (bool)$question['required'];
                } else {    //No default specified
                    $sq_required = FALSE;
                }
            }
            if ($sq_required) {
                //$subquestion_attrs[] = new Attr('required'); We don't want all the checkboxes to be required!
            }
            
            # Values
            if (isset($this->answers[$sq_name])) {
                $values = is_array($this->answers[$sq_name]) ? $this->answers[$sq_name] : [$this->answers[$sq_name]];
            } elseif (!$this->ignoreDefaults) {   //Default answers allowed?
                //Revert back to default answer
                $values = isset($sq['default']) ? $sq['default'] : [];
            } else {
                $values = [];
            }


            # Generate HTML for subquestion
            $question_attrs = [];

            # Textcode
            if (isset($sq['id'])) {
                $question_attrs[] = new Attr('data-textcode', $sq['id']);
            }

            $html .= '              <tr>' . "\n";
            $html .= '                  <th ' . Attr::displayAll($question_attrs) . '>' . $this->getFormattedText($sq) . '</th>' . "\n";
            
            $loop = 0;
            foreach ($question['options'] as $k => $opt) {
                $option_attrs = [];
                $option_attrs[] = new Attr('value', $opt['value']);
                
                //Loop through values array
                foreach ($values as $v) {
                    # Checked value can only be literal option title
                    if ($opt['value'] === $v) {
                        $option_attrs[] = new Attr('checked');
                    }
                }
                
                $html .= '                  <td><input '.Attr::displayAll(array_merge($input_attrs, $subquestion_attrs, $option_attrs)).'></td>' . "\n";
                $loop++;
            }
            $html .= '              </tr>' . "\n";
        }
        $html .= '          </tbody>' . "\n";
        $html .= '      </table>' . "\n";
        $html .= '  </div>' . "\n";
        
        return $html;
    }


    /**
    * Generated and Returns HTML for date questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param bool Question required
    * @param number Earliest date
    * @param number Latest date
    * @param string Answer
    * @return string HTML
    */
    private function get_question_date($id, $question) {

        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'text');
        $input_attrs[] = new Attr('id', $id);
        $input_attrs[] = new Attr('name', $id);
        $input_attrs[] = new Attr('autocomplete', 'off');

        # Default/Current value
        $answers = $this->answers;
        if (isset($answers[$id])) {
            $input_attrs[] = new Attr('value', $answers[$id]);
        } elseif(!$this->ignoreDefaults && isset($question['default'])) {
            $input_attrs[] = new Attr('value', $question['default']);
        }
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $input_attrs[] = new Attr('required');
        }
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'date') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <input '.Attr::displayAll($input_attrs).'>' . "\n";
        $html .= '  </div>' . "\n";

        return $html;
    }


    private function get_question_time($id, $question) {
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'hidden');
        $input_attrs[] = new Attr('id', $id);
        $input_attrs[] = new Attr('name', $id);

        $hours_attrs = array(
            new Attr('id', $id . '_hours' ),
            new Attr('data-unit', 'hours' )
        );

        $minutes_attrs = array(
            new Attr('id', $id . '_mins' ),
            new Attr('data-unit', 'mins' )
        );

        # Default/Current value
        $answers = $this->answers;
        if (isset($answers[$id]) && !empty($answers[$id])) {
            $input_attrs[] = new Attr('value', $answers[$id]);
            $str = explode(SC_Survey_Constructor::TIME_DELIMITER, $answers[$id]);
            $val_hours = $str[0];
            $val_mins = $str[1];

        } elseif(!$this->ignoreDefaults && isset($question['default'])) {
            $input_attrs[] = new Attr('value', $question['default']);
            $str = explode(SC_Survey_Constructor::TIME_DELIMITER, $question['default']);
            $val_hours = $str[0];
            $val_mins = $str[1];
        }
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $input_attrs[] = new Attr('required');
        }
        
        # Default step (in minutes)
        $step = isset($question['step']) && is_int($question['step']) ? $question['step'] : 1;
        if ($step === 0) {
            $step = 1;
        }

        # Min and max (hours)
        $min = isset($question['min']) && is_int($question['min']) ? $question['min'] : 0;
        $max = isset($question['max']) && is_int($question['max']) ? $question['max'] : 24;
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'time') . '" data-delimiter="' . self::html(SC_Survey_Constructor::TIME_DELIMITER) . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <input '.Attr::displayAll($input_attrs).'>' . "\n";

        # Hours
        $html .= '      <select ' . Attr::displayAll($hours_attrs) . '>' . "\n";
        $html .= '          <option value="--">--</option>' . "\n";
        for ($i = $min; $i < $max; $i += (floor(($step - 1) / 60) % 24) + 1) {
            $str = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
            $html .= '          <option value="'.$str.'"';
            if (isset($val_hours) && (int)$val_hours === $i) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . $str . '</option>' . "\n";
        }
        $html .= '      </select>' . "\n";

        # Delimiter
        $html .= '      ' . self::html(SC_Survey_Constructor::TIME_DELIMITER) . "\n";

        # Minutes
        if ($step < 60) {
            $html .= '      <select ' . Attr::displayAll($minutes_attrs) . '>' . "\n";
            $html .= '          <option value="--">--</option>' . "\n";
            for ($i = 0; $i < 59; $i += $step) {
                $str = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
                $html .= '          <option value="'.$str.'"';
                if (isset($val_mins) && (int)$val_mins === $i) {
                    $html .= ' selected="selected"';
                }
                $html .= '>' . $str . '</option>' . "\n";
            }
            $html .= '      </select>' . "\n";

        } else {
            $html .= '      00' . "\n";
        }

        $html .= '  </div>' . "\n";

        return $html;
    }


    /**
    * Generated and Returns HTML for e-mail questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param bool Question required
    * @param string Answer
    * @return string HTML
    */
    private function get_question_email($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'email');
        $input_attrs[] = new Attr('id', $id);
        $input_attrs[] = new Attr('name', $id);
        $input_attrs[] = new Attr('autocomplete', 'off');
        $input_attrs[] = new Attr('maxlength', '255');
        $input_attrs[] = new Attr('size', '40');

        # Default/Current value
        $answers = $this->answers;
        if (isset($answers[$id])) {
            $input_attrs[] = new Attr('value', $answers[$id]);
        } elseif(!$this->ignoreDefaults && isset($question['default'])) {
            $input_attrs[] = new Attr('value', $question['default']);
        }
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $input_attrs[] = new Attr('required');
        }
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'email') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <input '.Attr::displayAll($input_attrs).'>' . "\n";
        $html .= '  </div>' . "\n";

        return $html;
    }


    /**
    * Generated and Returns HTML for URL questions
    * 
    * @param string SC Question ID
    * @param string Question title
    * @param string Question subtitle
    * @param bool Question required
    * @param string Answer
    * @return string HTML
    */
    private function get_question_url($id, $question) {
        
        # Assign attributes
        $input_attrs = [];
        $input_attrs[] = new Attr('type', 'url');
        $input_attrs[] = new Attr('id', $id);
        $input_attrs[] = new Attr('name', $id);
        $input_attrs[] = new Attr('autocomplete', 'on');
        $input_attrs[] = new Attr('maxlength', '255');
        $input_attrs[] = new Attr('size', '40');

        # Default/Current value
        $answers = $this->answers;
        if (isset($answers[$id])) {
            $input_attrs[] = new Attr('value', $answers[$id]);
        } elseif(!$this->ignoreDefaults && isset($question['default'])) {
            $input_attrs[] = new Attr('value', $question['default']);
        }
        
        # Required
        if (isset($question['required']) && $question['required']) {
            $input_attrs[] = new Attr('required');
        }
        

        # Generate HTML
        $html  = '  <div class="' . $this->getFieldClasses($question, 'url') . '">' . "\n";
        $html .= $this->getQuestionHeader($question) . "\n";
        $html .= '      <input '.Attr::displayAll($input_attrs).'>' . "\n";
        $html .= '  </div>' . "\n";

        return $html;
    }
}
