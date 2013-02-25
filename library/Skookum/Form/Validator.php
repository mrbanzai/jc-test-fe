<?php
/**
 * Form validation library.
 *
 * @author  Tasos Bekos <tbekos@gmail.com>
 * @author  Corey Ballou <ballouc@gmail.com>
 * @author  Chris Gutierrez <cdotgutierrez@gmail.com>
 * @see     Based on http://brettic.us/2010/06/18/form-validation-class-using-php-5-3/
 */
class Skookum_Form_Validator {

    private $messages = array();
    private $errors = array();
    private $rules = array();
    private $fields = array();
    private $functions = array();
    private $arguments = array();
    private $data = null;
    private $filters = array();
    private $validData = array();

    /**
     * Constructor.
     * Define values to validate.
     *
     * @param array $data
     */
    function __construct($data = null) {
        $this->data = (is_null($data)) ? $_POST : $data;
    }

    /**
     * allow for setting the data later
     *
     * @access public
     * @return $this
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    public function getValidData()
    {
        return $this->validData;
    }

    /**
     * add a filter callback for the data
     */
    public function filter($callback)
    {
        // initialize an array of callbacks
        if(empty($this->filters)) {
            $this->filters = array();
        }

        if(is_callable($callback)) {
            $this->filters[] = $callback;
        }

        return $this;
    }

    /**
     * applies filters based on a data key
     *
     * @access protected
     * @param string $key
     * @return boolean
     */
    protected function _applyFilters($key)
    {
        if (empty($this->filters)) {
            $this->filters = array();
        }

        return $this->_applyFilter($this->data[$key]);
    }

    /**
     * recursively apply filters to a value
     *
     * @access protected
     * @param mixed $val reference
     * @return boolean
     */
    protected function _applyFilter(&$val)
    {
        if (is_array($val)) {
            foreach($val as $key => &$item) {
                $this->_applyFilter($item);
            }
        } else {
            foreach($this->filters as $filter) {
                $val = $filter($val);
            }
        }

        return true;
    }

    /**
     * Field, if completed, has to be a valid email address.
     * http://www.linuxjournal.com/article/9585?page=0,3
     *
     * @param string $message
     * @return FormValidator
     */
    public function email($message = null) {
        $this->_set_rule(__FUNCTION__, function($email) {
            if (strlen($email) == 0) return true;
            $isValid = true;
            $atIndex = strrpos($email, '@');
            if (is_bool($atIndex) && !$atIndex) {
               $isValid = false;
            } else {
                $domain = substr($email, $atIndex+1);
                $local = substr($email, 0, $atIndex);
                $localLen = strlen($local);
                $domainLen = strlen($domain);
                if ($localLen < 1 || $localLen > 64) {
                    $isValid = false;
                } else if ($domainLen < 1 || $domainLen > 255) {
                    // domain part length exceeded
                    $isValid = false;
                } else if ($local[0] == '.' || $local[$localLen-1] == '.') {
                    // local part starts or ends with '.'
                    $isValid = false;
                } else if (preg_match('/\\.\\./', $local)) {
                    // local part has two consecutive dots
                    $isValid = false;
                } else if (!preg_match('/^[A-Za-z0-9\\-\\.]+$/', $domain)) {
                    // character not valid in domain part
                    $isValid = false;
                } else if (preg_match('/\\.\\./', $domain)) {
                    // domain part has two consecutive dots
                    $isValid = false;
                } else if (!preg_match('/^(\\\\.|[A-Za-z0-9!#%&`_=\\/$\'*+?^{}|~.-])+$/', str_replace("\\\\","",$local))) {
                    // character not valid in local part unless
                    // local part is quoted
                    if (!preg_match('/^"(\\\\"|[^"])+"$/', str_replace("\\\\","",$local))) {
                        $isValid = false;
                    }
                }
                // check DNS
                if ($isValid && !(checkdnsrr($domain, "MX") || checkdnsrr($domain, "A"))) {
                    $isValid = false;
                }
            }
            return $isValid;
        }, $message);
        return $this;
    }

    /**
     * Field must be filled in.
     *
     * @param string $message
     * @return FormValidator
     */
    public function required($message = null) {
        $this->_set_rule(__FUNCTION__, function($string) {
                    return (strlen(trim($string)) === 0) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /**
     * Field must contain a valid float value.
     *
     * @param string $message
     * @return FormValidator
     */
    public function float($message = null) {
        $this->_set_rule(__FUNCTION__, function($string) {
                    return (filter_var($string, FILTER_VALIDATE_FLOAT) === FALSE) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /**
     * Field must contain a valid integer value.
     *
     * @param string $message
     * @return FormValidator
     */
    public function integer($message = null) {
        $this->_set_rule(__FUNCTION__, function($string) {
                    return (filter_var($string, FILTER_VALIDATE_INT) === FALSE) ? FALSE : TRUE;
        }, $message);
        return $this;
    }

    /**
     * Every character in field, if completed, must be a digit.
     * This is just like integer(), except there is no upper limit.
     *
     * @param string $message
     * @return FormValidator
     */
    public function digits($message = null) {
        $this->_set_rule(__FUNCTION__, function($value) {
                    return (strlen($value) === 0 || ctype_digit((string) $value));
        }, $message);
        return $this;
    }

    /**
     * Every character in field, if completed, must be a digit.
     * This is just like integer(), except there is no upper limit.
     *
     * @param string $message
     * @return FormValidator
     */
    public function uristub($message = null) {
        $this->_set_rule(__FUNCTION__, function($value) {
            return (!preg_match('/[^a-zA-Z0-9]/i', (string) $value));
        }, $message);
        return $this;
    }

    /**
     * Field must be a number greater than [or equal to] X.
     *
     * @param   numeric     $limit
     * @param   bool        $include Whether to include limit value.
     * @param   string      $message
     * @return  FormValidator
     */
    public function min($limit, $include = TRUE, $message = null) {
        $this->_set_rule(__FUNCTION__, function($value, $args) {
            if (strlen($value) === 0) {
                return TRUE;
            }

            $value = (float) $value;
            $limit = (float) $args[0];
            $inc = (bool) $args[1];

            return ($value > $limit || ($inc === TRUE && $value === $limit));
        }, $message, array($limit, $include));
        return $this;
    }

    /**
     * Field must be a number greater than [or equal to] X.
     *
     * @param numeric $limit
     * @param bool $include Whether to include limit value.
     * @param string $message
     * @return FormValidator
     */
    public function max($limit, $include = TRUE, $message = null) {
        $this->_set_rule(__FUNCTION__, function($value, $args) {
            if (strlen($value) === 0) {
                return TRUE;
            }

            $value = (float) $value;
            $limit = (float) $args[0];
            $inc = (bool) $args[1];

            return ($value < $limit || ($inc === TRUE && $value === $limit));
        }, $message, array($limit, $include));
        return $this;
    }

    /**
     * Field must be a number between X and Y.
     *
     * @param   numeric     $min
     * @param   numeric     $max
     * @param   bool        $include Whether to include limit value.
     * @param   string      $message
     * @return  FormValidator
     */
    public function between($min, $max, $include = TRUE, $message = null) {
        $message = !empty($message) ? $message : self::getDefaultMessage(__FUNCTION__, array($min, $max, $include));

        $this->min($min, $include, $message)->max($max, $include, $message);
        return $this;
    }

    /**
     * Field has to be greater than or equal to X characters long.
     *
     * @param int $len
     * @param string $message
     * @return FormValidator
     */
    public function minlength($len, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            return (strlen(trim($string)) < $args[0]) ? FALSE : TRUE;
        }, $message, array($len));
        return $this;
    }

    /**
     * Field has to be less than or equal to X characters long.
     *
     * @param int $len
     * @param string $message
     * @return FormValidator
     */
    public function maxlength($len, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            return (strlen(trim($string)) > $args[0]) ? FALSE : TRUE;
        }, $message, array($len));
        return $this;
    }

    /**
     * Field has to be between minlength and maxlength characters long.
     *
     * @param   int $minlength
     * @param   int $maxlength
     * @
     */
    public function betweenlength($minlength, $maxlength, $message = null) {
        $message = empty($message) ? self::getDefaultMessage(__FUNCTION__, array($minlength, $maxlength)) : NULL;

        $this->minlength($minlength, $message)->max($maxlength, $message);
        return $this;
    }

    /**
     * Field has to be X characters long.
     *
     * @param int $len
     * @param string $message
     * @return FormValidator
     */
    public function length($len, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            return (strlen(trim($string)) == $args[0]) ? TRUE : FALSE;
        }, $message, array($len));
        return $this;
    }

    /**
     * Field is the same as another one (password comparison etc).
     *
     * @param string $field
     * @param string $label
     * @param string $message
     * @return FormValidator
     */
    public function matches($field, $label, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            return ((string) $args[0] == (string) $string) ? TRUE : FALSE;
        }, $message, array($this->_getval($field), $label));
        return $this;
    }

    /**
     * Field is different from another one.
     *
     * @param string $field
     * @param string $label
     * @param string $message
     * @return FormValidator
     */
    public function notmatches($field, $label, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            return ((string) $args[0] == (string) $string) ? FALSE : TRUE;
        }, $message, array($this->_getval($field), $label));
        return $this;
    }

    /**
     * Field must start with a specific substring.
     *
     * @param string $sub
     * @param string $message
     * @return FormValidator
     */
    public function startsWith($sub, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            $sub = $args[0];
            return (strlen($string) === 0 || substr($string, 0, strlen($sub)) === $sub);
        }, $message, array($sub));
        return $this;
    }

    /**
     * Field must NOT start with a specific substring.
     *
     * @param string $sub
     * @param string $message
     * @return FormValidator
     */
    public function notstartsWith($sub, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            $sub = $args[0];
            return (strlen($string) === 0 || substr($string, 0, strlen($sub)) !== $sub);
        }, $message, array($sub));
        return $this;
    }

    /**
     * Field must end with a specific substring.
     *
     * @param string $sub
     * @param string $message
     * @return FormValidator
     */
    public function endsWith($sub, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            $sub = $args[0];
            return (strlen($string) === 0 || substr($string, -strlen($sub)) === $sub);
        }, $message, array($sub));
        return $this;
    }

    /**
     * Field must not end with a specific substring.
     *
     * @param string $sub
     * @param string $message
     * @return FormValidator
     */
    public function notendsWith($sub, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            $sub = $args[0];
            return (strlen($string) === 0 || substr($string, -strlen($sub)) !== $sub);
        }, $message, array($sub));
        return $this;
    }

    /**
     * Field has to be valid IP address.
     *
     * @param string $message
     * @return FormValidator
     */
    public function ip($message = null) {
        $this->_set_rule(__FUNCTION__, function($string) {
            return (strlen(trim($string)) === 0 || filter_var($string, FILTER_VALIDATE_IP)) ? TRUE : FALSE;
        }, $message);
        return $this;
    }

    /**
     * Field has to be valid internet address.
     *
     * @param string $message
     * @return FormValidator
     */
    public function url($message = null) {
        $this->_set_rule(__FUNCTION__, function($string) {
            return (strlen(trim($string)) === 0 || filter_var($string, FILTER_VALIDATE_URL)) ? TRUE : FALSE;
        }, $message);
        return $this;
    }

    /**
     * Field has to be a valid date.
     *
     * @param string $message
     * @return FormValidator
     */
    public function date($format = null, $separator = null, $message = null) {
        if (empty($format)) {
            $format = self::_getDefaultDateFormat();
        }

        $this->_set_rule(__FUNCTION__, function($string, $args) {
            if (strlen(trim($string)) === 0) {
                return TRUE;
            }

            $separator = $args[1];
            $dt = (is_null($separator)) ? preg_split('/[-\.\/ ]/', $string) : explode($separator, $string);

            if ((count($dt) != 3) || !is_numeric($dt[2]) || !is_numeric($dt[1]) || !is_numeric($dt[0])) {
                return FALSE;
            }

            $dateToCheck = array();
            $format = explode('/', $args[0]);
            foreach ($format as $i => $f) {
                switch ($f) {
                    case 'Y':
                        $dateToCheck[2] = $dt[$i];
                        break;

                    case 'm':
                        $dateToCheck[1] = $dt[$i];
                        break;

                    case 'd':
                        $dateToCheck[0] = $dt[$i];
                        break;
                }
            }

            return (checkdate($dateToCheck[1], $dateToCheck[0], $dateToCheck[2]) === FALSE) ? FALSE : TRUE;
        }, $message, array($format, $separator));
        return $this;
    }

    /**
     * Field has to be a date later than or equal to X.
     *
     * @param string $message
     * @return FormValidator
     */
    public function mindate($date = 0, $format = null, $message = null) {
        if (empty($format)) {
            $format = self::_getDefaultDateFormat();
        }
        if (is_numeric($date)) {
            $date = new DateTime($date . ' days'); // Days difference from today
        } else {
            $fieldValue = $this->_getval($date);
            $date = ($fieldValue == FALSE) ? $date : $fieldValue;

            $date = DateTime::createFromFormat($format, $date);
        }

        $this->_set_rule(__FUNCTION__, function($string, $args) {
                    $format = $args[1];
                    $limitDate = $args[0];

                    return ($limitDate > DateTime::createFromFormat($format, $string)) ? FALSE : TRUE;
                }, $message, array($date, $format));
        return $this;
    }

    /**
     * Field has to be a date later than or equal to X.
     *
     * @param string|integer $date Limit date.
     * @param string $format Date format.
     * @param string $message
     * @return FormValidator
     */
    public function maxdate($date = 0, $format = null, $message = null) {
        if (empty($format)) {
            $format = self::_getDefaultDateFormat();
        }
        if (is_numeric($date)) {
            $date = new DateTime($date . ' days'); // Days difference from today
        } else {
            $fieldValue = $this->_getval($date);
            $date = ($fieldValue == FALSE) ? $date : $fieldValue;

            $date = DateTime::createFromFormat($format, $date);
        }

        $this->_set_rule(__FUNCTION__, function($string, $args) {
                    $format = $args[1];
                    $limitDate = $args[0];

                    return ($limitDate < DateTime::createFromFormat($format, $string)) ? FALSE : TRUE;
                }, $message, array($date, $format));
        return $this;
    }

    /**
     * Field has to be a valid credit card number format.
     *
     * @see https://github.com/funkatron/inspekt/blob/master/Inspekt.php
     * @param string $message
     * @return FormValidator
     */
    public function ccnum($message = null) {
        $this->_set_rule(__FUNCTION__, function($value) {
                    $value = str_replace(' ', '', $value);
                    $length = strlen($value);

                    if ($length < 13 || $length > 19) {
                        return FALSE;
                    }

                    $sum = 0;
                    $weight = 2;

                    for ($i = $length - 2; $i >= 0; $i--) {
                        $digit = $weight * $value[$i];
                        $sum += floor($digit / 10) + $digit % 10;
                        $weight = $weight % 2 + 1;
                    }

                    $mod = (10 - $sum % 10) % 10;

                    return ($mod == $value[$length - 1]);
                }, $message);
        return $this;
    }

    /**
     * Ensure an uploaded image's max dimensions don't exceed.
     *
     * @access  public
     * @param   int     $width
     * @param   int     $height
     * @param   string  $message
     * @return  FormValidator
     */
    public function dimensions($width = null, $height = null, $message = null) {
        $this->_set_rule(__FUNCTION__, function($string, $args) {
            return in_array($string, $args[0]);
        }, $message, array($width, $height));
        return $this;
    }

    // --------------- END [ADD NEW RULE FUNCTIONS ABOVE THIS LINE] ------------

    /**
     * callback
     * @param   string  $name
     * @param   mixed   $function
     * @param   string  $message
     * @param   mixed   $params
     * @return  FormValidator
     */
    public function callback($name, $function, $message = '', $params = NULL) {

        if (is_array($function)) {
            $this->_set_rule($name, function($value) use($function, $params) {
                return call_user_func($function, $value, $params);
            }, $message);
        } elseif (is_callable($function)) {
            // set rule and function
            $this->_set_rule($name, $function, $message);
        } elseif (is_string($function) && preg_match($function, 'callback') !== FALSE) {
            // we can parse this as a regexp. set rule function accordingly.
            $this->_set_rule($name, function($value) use ($function) {
                        return ( preg_match($function, $value) ) ? TRUE : FALSE;
                    }, $message);
        } else {
            // just set a rule function to check equality.
            $this->_set_rule($name, function($value) use ( $function) {
                        return ( (string) $value === (string) $function ) ? TRUE : FALSE;
                    }, $message);
        }
        return $this;
    }

    /**
     * validate
     * @param string $key
     * @param string $label
     * @return bool
     */
    public function validate($key, $label = '') {
        // map multi-dimensional keys to underscore notation
        $fieldkey = str_replace('.', '_', $key);
        // set up field name for error message
        $this->fields[$fieldkey] = (empty($label)) ? 'Field with the name of "' . $fieldkey . '"' : $label;

        $this->_applyFilters($key);

        // Keep value for use in each rule
        $string = $this->_getval($key);

        // try each rule function
        foreach ($this->rules as $rule => $is_true) {
            if ($is_true) {
                $function = $this->functions[$rule];
                $args = $this->arguments[$rule]; // Arguments of rule

                $valid = (empty($args)) ? $function($string) : $function($string, $args);
                if ($valid === FALSE) {
                    $this->_register_error($rule, $key);

                    $this->rules = array();  // reset rules
                    $this->filters = array(); // reset filters
                    return FALSE;
                }
            }
        }

        // reset rules
        $this->rules = array();
        $this->filters = array(); // reset filters

        $this->validData[$key] = $string;
        return $string;
    }

    /**
     * Whether errors have been found.
     *
     * @return bool
     */
    public function hasErrors() {
        return (count($this->errors) > 0) ? TRUE : FALSE;
    }

    /**
     * Get specific error.
     *
     * @param string $field
     * @return string
     */
    public function getError($field) {
        return $this->errors[$field];
    }

    /**
     * Get all errors.
     *
     * @return array
     */
    public function getAllErrors($keys = true) {
        return ($keys == true) ? $this->errors : array_values($this->errors);
    }

    /**
     * _getval with added support for retrieving values from numeric and
     * associative multi-dimensional arrays. When doing so, use DOT notation
     * to indicate a break in keys, i.e.:
     *
     * key = "one.two.three"
     *
     * would search the array:
     *
     * array('one' => array(
     *      'two' => array(
     *          'three' => 'RETURN THIS'
     *      )
     * );
     *
     * @param string $key
     * @return mixed
     */
    private function _getval($key) {
        // handle multi-dimensional arrays
        if (strpos($key, '.') !== FALSE) {
            $arrData = NULL;
            $keys = explode('.', $key);
            $keyLen = count($keys);
            for ($i = 0; $i < $keyLen; ++$i) {
                if (trim($keys[$i]) == '') {
                    return false;
                } else {
                    if (is_null($arrData)) {
                        if (!isset($this->data[$keys[$i]])) {
                            return false;
                        }
                        $arrData = $this->data[$keys[$i]];
                    } else {
                        if (!isset($arrData[$keys[$i]])) {
                            return false;
                        }
                        $arrData = $arrData[$keys[$i]];
                    }
                }
            }
            return $arrData;
        } else {
            return (isset($this->data[$key])) ? $this->data[$key] : FALSE;
        }
    }

    /**
     * Register error.
     *
     * @param string $rule
     * @param string $key
     * @param string $message
     */
    private function _register_error($rule, $key, $message = null) {
        if (empty($message)) {
            $message = $this->messages[$rule];
        }
        // map multi-dimensional keys to underscore notation
        $key = str_replace('.', '_', $key);
        // set the errors
        $this->errors[$key] = sprintf($message, $this->fields[$key]);
    }

    /**
     * Set rule.
     *
     * @param string $rule
     * @param closure $function
     * @param string $message
     * @param array $args
     */
    private function _set_rule($rule, $function, $message = '', $args = array()) {
        if (!array_key_exists($rule, $this->rules)) {
            $this->rules[$rule] = TRUE;
            if (!array_key_exists($rule, $this->functions)) {
                if (!is_callable($function)) {
                    die('Invalid function for rule: ' . $rule);
                }
                $this->functions[$rule] = $function;
            }
            $this->arguments[$rule] = $args; // Specific arguments for rule
            $this->messages[$rule] = (empty($message)) ? self::getDefaultMessage($rule, $args) : $message;
        }
    }

    /**
     * Get default error message.
     *
     * @param string $key
     * @param array $args
     * @return string
     */
    private static function getDefaultMessage($rule, $args = null) {
        switch ($rule) {
            case 'email':
                $message = '%s is an invalid email address.';
                break;

            case 'ip':
                $message = '%s is an invalid IP address.';
                break;

            case 'url':
                $message = '%s is an invalid url.';
                break;

            case 'required':
                $message = '%s is required.';
                break;

            case 'float':
                $message = '%s must consist of numbers only.';
                break;

            case 'integer':
                $message = '%s must consist of integer value.';
                break;

            case 'digits':
                $message = '%s must consist only of digits.';
                break;

            case 'uristub':
                $message = '%s must consist of only letters, numbers, and dashes.';
                break;

            case 'min':
                $message = '%s must be greater than ';
                if ($args[1] == TRUE) {
                    $message .= 'or equal to ';
                }
                $message .= $args[0] . '.';
                break;

            case 'max':
                $message = '%s must be less than ';
                if ($args[1] == TRUE) {
                    $message .= 'or equal to ';
                }
                $message .= $args[0] . '.';
                break;

            case 'between':
                $message = '%s must be between ' . $args[0] . ' and ' . $args[1] . '.';
                if ($args[2] == FALSE) {
                    $message .= '(Without limits)';
                }
                break;

            case 'minlength':
                $message = '%s must be at least ' . $args[0] . ' characters or longer.';
                break;

            case 'maxlength':
                $message = '%s must be no longer than ' . $args[0] . ' characters.';
                break;

            case 'length':
                $message = '%s must be exactly ' . $args[0] . ' characters in length.';
                break;

            case 'matches':
                $message = '%s must match ' . $args[1] . '.';
                break;

            case 'notmatches':
                $message = '%s must not match ' . $args[1] . '.';
                break;

            case 'startsWith':
                $message = '%s must start with "' . $args[0] . '".';
                break;

            case 'notstartsWith':
                $message = '%s must not start with "' . $args[0] . '".';
                break;

            case 'endsWith':
                $message = '%s must end with "' . $args[0] . '".';
                break;

            case 'notendsWith':
                $message = '%s must not end with "' . $args[0] . '".';
                break;

            case 'date':
                $message = '%s is not valid date.';
                break;

            case 'mindate':
                $message = '%s must be later than ' . $args[0]->format($args[1]) . '.';
                break;

            case 'maxdate':
                $message = '%s must be before ' . $args[0]->format($args[1]) . '.';
                break;

            case 'oneof':
                $message = '%s must be one of ' . implode(', ', $args[0]) . '.';
                break;

            case 'ccnum':
                $message = '%s must be a valid credit card number.';
                break;

            default:
                $message = '%s has an error.';
                break;
        }

        return $message;
    }

    /**
     * Date format.
     *
     * @return string
     */
    private static function _getDefaultDateFormat() {
        return 'd/m/Y';
    }

}