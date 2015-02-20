<?php namespace Jonob\Formly;

use Illuminate\Support\Facades\Form;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;

/**
 * Form generation based on Twitter Bootstrap with some added goodness.
 *
 * @author      JonoB
 * @version 	1.1.0
 */
class Formly
{
	/**
	 * The default values for the form
	 */
	protected $defaults = array();

	/**
	 * The options for Formly
	 *
	 * @var array
	 */
	protected $options = array();

	/**
	 * Stores the comments
	 * field_name => comment
	 * @var array
	 */
	protected $comments = array();

	/**
	 * Form errors
	 */
	protected $errors;

    /**
     * Class constructor
     *
     * @param array $defaults
     */
    public function __construct($defaults = array())
	{
		$this->loadConfig();
		$this->setDefaults($defaults);
		$this->errors = Session::get('errors');
	}

	/**
	 * Static function to instantiate the class
	 *
	 * @param  array $defaults
	 * @return class
	 */
	public static function make($defaults = array())
	{
	    return new static($defaults);
	}

    /**
     * Load all the default config options
     */
    private function loadConfig()
    {
        // L4 does not currently have a method for loading an entire config file
        // so we have so spin through them individually for now
        $options = array('formClass', 'autocomplete', 'nameAsId', 'idPrefix', 'requiredLabel', 'requiredPrefix',
            'requiredSuffix', 'requiredClass', 'controlGroupError', 'displayInlineErrors', 'commentClass'
        );
        foreach($options as $option)
        {
            $this->options[$option] = Config::get('formly::' . $option);
        }
    }

	/**
	 * Set form defaults
	 *
	 * This would usually be done via the static make() function
	 *
	 * @param array $defaults
	 * @return class
	 */
	public function setDefaults($defaults = array())
	{
		$defaults = json_decode(json_encode($defaults), true);
		
		if (count($defaults) > 0)
		{
			$this->defaults = $defaults;
		}

		return $this;
	}

	/**
	 * Set option(s) for the class
	 *
	 * Call with option key and value, or an array of options
	 *
	 * @param string|array $key
	 * @param string $value
	 * @return class
	 */
	public function setOption($key, $value = '')
	{
		if (is_array($key))
		{
			$this->options = array_merge($this->options, $key);
		}
		else
		{
			$this->options[$key] = $value;
		}

		return $this;
	}

	/**
	 * Retrieve a single option
	 *
	 * @param  string $key
	 * @return string
	 */
	private function getOption($key)
	{
		return (isset($this->options[$key])) ? $this->options[$key] : '';
	}

	/**
	 * Set comments
	 *
	 * Call with the field name and the comment, or an array
	 * of comments
	 *
	 * @param mixed $name
	 * @param string $comment
	 * @return class
	 */
	public function setComments($name, $comment = '')
	{
		if (is_array($name) && count($name) > 0)
		{
			$this->comments = array_merge($this->comments, $name);
		}
		else
		{
			$this->comments[$name] = $comment;
		}

		return $this;
	}

	/**
	 * Overrides the base form open method to allow for automatic insertion of csrf tokens
	 * and form class
	 *
     * @param null   $action    Defaults to the current url
     * @param string $method    Defaults to POST
     * @param array  $attributes
     * @return string
     */
    public function open($action = null, $method = 'POST', $attributes = array())
	{
		// If an action has not been specified, use the current url
        $action = $action ?: Request::fullUrl();

		// Add in the form class if necessary
		if (empty($attributes['class']))
		{
			$attributes['class'] =  $this->getOption('formClass');
		}
		elseif (strpos($attributes['class'], 'form-') === false)
		{
			$attributes['class'] .= ' ' . $this->getOption('formClass');
		}

		// Auto-complete attribute
		if (empty($attributes['autocomplete']))
		{
			$attributes['autocomplete'] = $this->getOption('autocomplete');
		}

        // Laravel's form builder uses a single array as a parameter
        $attributes['url'] = $action;
        $attributes['method'] = $method;

		return Form::open($attributes);
	}

	/**
	 * Convenience method to open form for POST
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 * @return string
	 */
	public function openPost($action = null, $attributes = array())
	{
		return $this->open($action, 'POST', $attributes);
	}

	/**
	 * Convenience method to open form for PUT
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 * @return string
	 */
	public function openPut($action = null, $attributes = array())
	{
		return $this->open($action, 'PUT', $attributes);
	}

	/**
	 * Convenience method to open forms for DELETE
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 * @return string
	 */
	public function openDelete($action = null, $attributes = array())
	{
		return $this->open($action, 'DELETE', $attributes);
	}

	/**
	 * Open for files
	 *
	 * @param  string $action
	 * @param  string $method
	 * @param  array  $attributes
	 * @return string
	 */
	public function openFiles($action = null, $method = 'POST', $attributes = array())
	{
		$attributes['enctype'] = 'multipart/form-data';

		return $this->open($action, $method, $attributes);
	}

	/**
	 * Create a HTML hidden input element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function hidden($name, $value = null, $attributes = array())
	{
		$value = $this->calculateValue($name, $value);

        return Form::hidden($name, $value, $attributes);
	}

	/**
	 * Create a HTML text input element.
	 *
     * @param string $name
     * @param string $label
     * @param null   $value
     * @param array  $attributes
     * @return string
     */
    public function text($name, $label = '', $value = null, $attributes = array())
	{
		$value = $this->calculateValue($name, $value);
		$attributes = $this->setAttributes($name, $attributes);
		$field = Form::text($name, $value, $attributes);

		return $this->buildWrapper($field, $name, $label);
	}

    /**
     * Create a HTML textarea input element.
     *
     * @param string $name
     * @param string $label
     * @param null $value
     * @param array $attributes
     * @return string
     */
    public function textarea($name, $label = '', $value = null, $attributes = array())
	{
		$value = $this->calculateValue($name, $value);
		$attributes = $this->setAttributes($name, $attributes);
		if ( ! isset($attributes['rows']))
		{
			$attributes['rows'] = 4;
		}
		$field = Form::textarea($name, $value, $attributes);

		return $this->buildWrapper($field, $name, $label);
	}

	/**
	 * Create a HTML password input element.
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  array   $attributes
	 * @return string
	 */
	public function password($name, $label = '', $attributes = array())
	{
		$attributes = $this->setAttributes($name, $attributes);
		$field = Form::password($name, $attributes);

		return $this->buildWrapper($field, $name, $label);
	}

	/**
	 * Create a HTML select element.
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  array   $options
	 * @param  string  $selected
	 * @param  array   $attributes
	 * @return string
	 */
	public function select($name, $label = '', $options = array(), $selected = null, $attributes = array())
	{
		$selected = $this->calculateValue($name, $selected);
		$attributes = $this->setAttributes($name, $attributes);
		$field = Form::select($name, $options, $selected, $attributes);

		return $this->buildWrapper($field, $name, $label);
	}

	/**
	 * Create a HTML checkbox input element.
	 *
	 * @param  string  $name
	 * @param  string  $label
	 * @param  string  $value
	 * @param  bool    $checked
	 * @param  array   $attributes
	 * @return string
	 */
	public function checkbox($name, $label = '', $value = '1', $checked = false, $attributes = array())
	{
		$checked = $this->calculateValue($name, $checked);
		$attributes = $this->setAttributes($name, $attributes, true);
		$field = Form::checkbox($name, $value, $checked, $attributes);

		return $this->buildWrapper($field, $name, $label, true);
	}

	/**
	 * Create a HTML radio input element.
	 *
	 * @param  string  $name
	 * @param  string  $value
	 * @param  bool    $checked
	 * @param  array   $attributes
	 * @return string
	 */
	public function radio($name, $value = '1', $checked = false, $attributes = array())
	{
		$checked = $this->calculateValue($name, $checked, $value);
		$attributes = $this->setAttributes($name, $attributes);

		return Form::radio($name, $value, $checked, $attributes);
	}

	/**
	 * Create a HTML file input element.
	 *
	 * @param  string  $name
     * @param  string  $label
	 * @param  array   $attributes
	 * @return string
	 */
	public function file($name, $label, $attributes = array())
	{
		$attributes = $this->setAttributes($name, $attributes);
		$field = Form::file($name, $attributes);

		return $this->buildWrapper($field, $name, $label);
	}

	/**
	 * Builds the Twitter Bootstrap control wrapper
	 *
	 * @param  string  $field The html for the field
	 * @param  string  $name The name of the field
	 * @param  string/null  $label The label name / null means no label
	 * @param  boolean $checkbox
     *
	 * @return string
	 */
	private function buildWrapper($field, $name, $label = '', $checkbox = false)
	{
		if ($this->errors and $this->errors instanceof MessageBag)
		{
			$error = $this->errors->first($name);
		}

		$comment = '';
		if ( ! empty($this->comments[$name]) && ! $checkbox)
		{
            // normal comments
			$comment = '<div class="'.$this->getOption('commentClass').'">';
			$comment .= $this->comments[$name];
			$comment .= '</div>';
		}
        elseif ( ! empty($this->comments[$name]))
        {
            // checkbox comments shouldn't be more readable
            $comment .= $this->comments[$name];
        }

		$class = 'form-group';
		if ($this->getOption('controlGroupError') && ! empty($error))
		{
		    $class .= ' ' . $this->getOption('controlGroupError');
		}

        $id = ($this->getOption('nameAsId')) ? ' id="form-group-'.$name.'"' : '';
        $out  = '<div class="'.$class.'"'.$id.'>';

        if ($label === null)
        {
            $out .= '<div class="col-sm-12">'.PHP_EOL;
        }
        else
        {
            $out .= $this->buildLabel($name, $label);
            $out .= '<div class="col-sm-10">'.PHP_EOL;
        }

        if ( ! $checkbox)
        {
            $out .= $field;
        }
        else
        {
            $out .= '<div class="checkbox">';
            $out .= '<label>';
            $out .= $field;
            $out .= $comment;
            $out .= '</label>';
            $out .= '</div>';
        }

		if ($this->getOption('displayInlineErrors') && ! empty($error))
		{
			// L4 errors already have this class
			//$out .= '<span class="help-inline">'.$error.'</span>';
			$out .= $error;
		}

        if ( ! $checkbox)
		{
			$out .= $comment;
		}

		$out .= '</div>';
		$out .= '</div>'.PHP_EOL;

		return $out;
	}

	/**
	 * Builds the label html
	 *
	 * @param  string  $name The name of the html field
	 * @param  string  $label The label name
	 * @return string
	 */
	private function buildLabel($name, $label = '')
	{
		$out = '';
        $class = 'col-sm-2 control-label';
		if ( ! empty($label))
		{
			if ($this->getOption('requiredLabel') && substr($label, -strlen($this->getOption('requiredLabel'))) == $this->getOption('requiredLabel'))
			{
				$label = $this->getOption('requiredPrefix') . str_replace($this->getOption('requiredLabel'), '', $label) . $this->getOption('requiredSuffix');
				$class .= ' ' . $this->getOption('requiredClass');
			}
			$name = $this->getOption('idPrefix') . $name;
			$out .= Form::label($name, $label, array('class' => $class));
		}
        else
        {
            // blank label that still has a for
            $out .= Form::label($name, '&nbsp;', array('class' => $class));
        }

		return $out;
	}

	/**
	 * Automatically populate the form field value
	 *
	 * @todo Note that there is s small error with checkboxes that are selected by default
	 * and then unselected by the user. If validation fails, then the checkbox will be
	 * selected again, because unselected checkboxes are not posted and there is no way
	 * to get this value after the redirect.
	 *
	 * @param  string $name Html form field to populate
	 * @param  string $default The default value for the field
	 * @param  string $radioValue Set to true for radio buttons
	 * @return string
	 */
	private function calculateValue($name, $default = '', $radioValue = '')
	{
		$result = '';
		
		//make array named fields to dot notation
		$field_name = str_replace(array('[', ']'), array('.', ''), $name);
		
		// First check if there is post data
		// This assumes that you are redirecting after failed post
		// and that you have flashed the data
		// @see http://laravel.com/docs/input#old-input
		if (Input::old($name) !== null)
		{
			$result = ($radioValue)
				? Input::old($name) == $radioValue
				: Input::old($name, $default);

		}

		// check if there is a default value set specifically for this field
		elseif ( ! empty($default))
		{
			$result = $default;
		}

		// lastly, check if any defaults have been set for the form as a whole
		elseif ($value = array_get($this->defaults, $field_name))
		{
			$result = ($radioValue)
				? $value == $radioValue
				: $value;
		}

		return $result;
	}

    /**
     * Create an id attribute for each field and also
     * determines if there is an comment field
     *
     * @param $name
     * @param array $attributes
     * @return array
     */
    private function setAttributes($name, $attributes = array(), $checkbox = false)
	{
		// set the comment
		if ( ! empty($attributes['comment']))
		{
			$this->comments[$name] = $attributes['comment'];
			unset($attributes['comment']);
		}

        if ( ! $checkbox)
        {
            $attributes['class'] = 'form-control';
        }

		// set the id attribute
		if ($this->getOption('nameAsId') && ! isset($attributes['id']))
		{
			$attributes['id'] = $this->getOption('idPrefix') . $name;
		}

		// if the disabled attribute is set to false, then we will actually unsert it
		// as some browsers will set the field to disabled
		if (isset($attributes['disabled']) && ! $attributes['disabled']) unset($attributes['disabled']);

		return $attributes;
	}

	/**
	 * Create a group of form actions (buttons).
	 *
	 * @param  mixed  $buttons  String or array of HTML buttons.
	 * @return string
	 */
	public function actions($buttons)
	{
		$out  = '<div class="form-actions">';
		$out .= is_array($buttons) ? implode('', $buttons) : $buttons;
		$out .= '</div>';

		return $out;
	}

    /**
     * Create a HTML submit input element.
     *
     * @param $value
     * @param array $attributes
     * @param string $btn_class
     * @return mixed
     */
    public function submit($value = 'Submit', $attributes = array(), $btn_class = 'btn')
	{
		$attributes['type'] = 'submit';
		if ($btn_class != 'btn')
		{
			$btn_class = 'btn btn-' . $btn_class;
		}
		if ( ! isset($attributes['class']))
		{
			$attributes['class'] = $btn_class;
		}
		elseif (strpos($attributes['class'], $btn_class) === false)
		{
			$attributes['class'] .= ' ' . $btn_class;
		}

		return Form::button($value, $attributes);
	}

	/**
	 * Shortcut method for creating a default submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return string
	 */
	public function submitDefault($value = 'Submit', $attributes = array())
	{
		return $this->submit($value, $attributes);
	}

	/**
	 * Shortcut method for creating a primary submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return string
	 */
	public function submitPrimary($value = 'Submit', $attributes = array())
	{
		return $this->submit($value, $attributes, 'primary');
	}

	/**
	 * Shortcut method for creating an info submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return string
	 */
	public function submitInfo($value = 'Submit', $attributes = array())
	{
		return $this->submit($value, $attributes, 'info');
	}

	/**
	 * Shortcut method for creating a success submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return string
	 */
	public function submitSuccess($value = 'Submit', $attributes = array())
	{
		return $this->submit($value, $attributes, 'success');
	}

	/**
	 * Shortcut method for creating a warning submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return string
	 */
	public function submitWarning($value = 'Submit', $attributes = array())
	{
		return $this->submit($value, $attributes, 'warning');
	}

	/**
	 * Shortcut method for creating a danger submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return string
	 */
	public function submitDanger($value = 'Submit', $attributes = array())
	{
		return $this->submit($value, $attributes, 'danger');
	}

	/**
	 * Shortcut method for creating an inverse submit button
	 *
	 * @param  string $value
	 * @param  array  $attributes
	 * @return string
	 */
	public function submitInverse($value = 'Submit', $attributes = array())
	{
		return $this->submit($value, $attributes, 'inverse');
	}

	/**
	 * Create a HTML reset input element.
	 *
	 * @param  string  $value
	 * @param  array   $attributes
	 * @return string
	 */
	public function reset($value = 'Submit', $attributes = array())
	{
		$attributes['type'] = 'reset';
		$attributes['class'] .= ' btn';
		return Form::button($value, $attributes);
	}

	/**
	 * Create a Form close element
	 */
	public function close()
	{
		return Form::close();
	}
}
