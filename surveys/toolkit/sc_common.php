<?php
session_start();
require_once('vendor/autoload.php');

use Symfony\Component\Yaml\Parser;


// ================= //
// -- Global vars -- //
// ================= //

// Constants
define('TOOLKIT_TAG', 'sc');



// ===================== //
// -- HTML attributes -- //
// ===================== //
class Attr {
    // ---------------- //
    // -- Properties -- //
    // ---------------- //
    private $str_name;
    private $str_value;
    
    
    // ----------------- //
    // -- Constructor -- //
    // ----------------- //
    /**
    * Assigns attribute name and value
    * @param string $id the name of the attribute
    * @param string $val the value of the attribute (if none then this defaults to the name)
    */
    public function __construct($id, $val = NULL) {
        $this->str_name  = $id;
        $this->str_value = isset($val) ? $val : $id;
    }
    
    
    // ------------------- //
    // -- Other Methods -- //
    // ------------------- //
    /**
    * @return string HTML attribute
    */
    public function display() {
        $val = $this->str_value;

        if (is_array($val)) {
            $val = implode(' ', $val);
        }
        return $this->str_name . '="' . Misc::html($val) . '"';
    }
    
    /**
    * Returns all attribute objects that are passed
    * @static
    * @param array $attributes array of Attr objects
    * @return space-seperated string HTML attributes
    */
    public static function displayAll($attributes) {
        $arr = [];
        foreach ($attributes as $a) {
            $arr[] = $a->display();
        }

        return implode(' ', $arr);
    }
}


class Misc {

    /**
     * Formats string as HTML
     * @static
     * @param string $str string to format
     * @return string HTML
     */
    public static function html($str) {
        return htmlspecialchars($str, ENT_COMPAT, 'UTF-8');
    }


    /**
    * Adds a trailing slash to a filepath, if necessary
    * @static
    * @param string $fp filepath
    * @return string corrected filepath
    */
    public static function add_slash($fp) {
        return substr($fp, -1) != '/' ? $fp.='/' : $fp;
    }
    
    
    /**
    * Replaces all special characters in a string with underscores
    * @static
    * @param string $str the string to manipulate
    * @return string corrected string
    */
    public static function simplify($str) {
        $str = str_replace('/', '_', $str);
        $str = str_replace('\\', '_', $str);
        $str = str_replace('?', '_', $str);
        $str = str_replace('%', '_', $str);
        $str = str_replace('*', '_', $str);
        $str = str_replace(':', '_', $str);
        $str = str_replace('|', '_', $str);
        $str = str_replace('.', '_', $str);
        $str = str_replace('"', '_', $str);
        $str = str_replace('<', '_', $str);
        $str = str_replace('>', '_', $str);
        $str = str_replace(' ', '_', $str);
        $str = str_replace('-', '_', $str);
        $str = strtolower($str);
        
        return $str;
    }


    /**
     * Parse YAML into PHP array
     * @param  string $filepath including filename
     * @return array
     */
    public static function parse_yaml($filepath) {
        if (file_exists($filepath)) {
            # Load YAML
            try {
                $yaml_parser = new Parser();
                $arr_yaml = $yaml_parser->parse(file_get_contents($filepath));

                if (!is_array($arr_yaml)) {
                    throw new Exception('Parsed questions YAML is NULL. It probably has a strange character which the parser can\'t read.');
                }

            } catch (ParseException $e) {
                exit($e->getMessage());
            }
            
            return $arr_yaml;
            
        } 

        # File doesn't exist
        return NULL;
    }

    
    /**
     * Expand a YAML string into an array where applicable
     * and set defaults where elements don't already exist
     *
     * @param string | array &$value pointer to the value to expand
     * @param array $itemDefaults the default values to be used
     */
    public static function standardiseValue(&$value, $itemDefaults = ['type' => SC_Survey_Constructor::MEDIA_PLAIN]) {
        $arr = $value;

        if (!is_array($arr)) {
            # expand single string into an array
            $content = $arr;
            $arr = ['content' => $content];
        }

        # a 'title' element is replaced by a 'content' element
        if (array_key_exists('title', $arr)) {
            $arr['content'] = $arr['title'];
            unset($arr['title']);
        }

        # merge defaults into array ann return
        $value = array_merge($itemDefaults, $arr);
    }
}
