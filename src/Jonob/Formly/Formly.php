<?php namespace Jonob\Formly;

use Meido\Form\FormFacade as Form;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Request;

/**
 * Form generation based on Twitter Bootstrap with some added goodness.
 *
 * @author      JonoB
 * @version 	1.0.0
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
	 * Set form defaults
	 *
	 * This would usually be done via the static make() function
	 *
	 * @param array $defaults
	 * @return class
	 */
	public function setDefaults($defaults = array())
	{
		if (count($defaults) > 0)
		{
			$this->defaults = (object)$defaults;
		}
		return $this;
	}

	/**
	 * Set the default options for the class
	 */
	protected function setOptions($options = array())
	{
		$this->options = $options;
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
     * @param null   $https
     * @param bool   $for_files
     * @return string
     */
    public function open($action = null, $method = 'POST', $attributes = array(), $https = null, $for_files = false)
	{
		// If an action has not been specified, use the current url
        $action = $action ?: Request::fullUrl();

		// Add in the form class if necessary
		if (empty($attributes['class']))
		{
			$attributes['class'] =  Config::get('formly::formClass');
		}
		elseif (strpos($attributes['class'], 'form-') === false)
		{
			$attributes['class'] .= ' ' . Config::get('formly::formClass');
		}

		$out = Form::open($action, $method, $attributes, $https);
		if (Config::get('formly::autoToken'))
		{
			$out .= Form::hidden('csrf_token', csrf_token());
		}
		return $out;
	}

	/**
	 * Convenience method to open form for POST
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 * @param  bool $https
	 * @return string
	 */
	public function openPost($action = null, $attributes = array(), $https = null)
	{
		return $this->open($action, 'POST', $attributes, $https);
	}

	/**
	 * Convenience method to open form for PUT
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 * @param  bool $https
	 * @return string
	 */
	public function openPut($action = null, $attributes = array(), $https = null)
	{
		return $this->open($action, 'PUT', $attributes, $https);
	}

	/**
	 * Convenience method to open forms for DELETE
	 *
	 * @param  string $action
	 * @param  array  $attributes
	 * @param  bool $https
	 * @return string
	 */
	public function openDelete($action = null, $attributes = array(), $https = null)
	{
		return $this->open($action, 'DELETE', $attributes, $https);
	}

	/**
	 * Open for files
	 *
	 * @param  string $action
	 * @param  string $method
	 * @param  array  $attributes
	 * @param  bool $https
	 * @return string
	 */
	public function openFiles($action = null, $method = 'POST', $attributes = array(), $https = null)
	{
		$attributes['enctype'] = 'multipart/form-data';
		return $this->open($action, $method, $attributes, $https);
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
		return Form::input('hidden', $name, $value, $attributes);
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
		$attributes = $this->setAttributes($name, $attributes);
		$field = Form::checkbox($name, $value, $checked, $attributes);
		return $this->buildWrapper($field, $name, $label, true);
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
	 * @param  string  $label The label name
	 * @param  boolean $checkbox
	 * @return string
	 */
	private function buildWrapper($field, $name, $label = '', $checkbox = false)
	{
		if ($this->errors and $this->errors instanceof MessageBag)
		{
			$error = $this->errors->first($name);
		}

		$comment = '';
		if ( ! empty($this->comments[$name]))
		{
			$comment = '<div class="'.Config::get('formly::commentClass').'">';
			$comment .= $this->comments[$name];
			$comment .= '</div>';
		}

		$class = 'control-group';
		if (Config::get('formly::controlGroupError') && ! empty($error))
		{
		    $class .= ' ' . Config::get('formly::controlGroupError');
		}

		$id = (Config::get('formly::nameAsId')) ? ' id="control-group-'.$name.'"' : '';
		$out  = '<div class="'.$class.'"'.$id.'>';
		$out .= $this->buildLabel($name, $label);
		$out .= '<div class="controls">'.PHP_EOL;
		$out .= ($checkbox === true) ? '<label class="checkbox">' : '';
		$out .= $field;

		if (Config::get('formly::displayInlineErrors') && ! empty($error))
		{
			// L4 errors already have this class
			//$out .= '<span class="help-inline">'.$error.'</span>';
			$out .= $error;
		}

		if ($checkbox)
		{
			if ( ! empty($this->comments[$name]))
			{
				$out .= $comment;
			}
			$out .= '</label>';
		}
		else
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
		if ( ! empty($label))
		{
			$class = 'control-label';
			if (Config::get('formly::requiredLabel') && substr($label, -strlen(Config::get('formly::requiredLabel'))) == Config::get('formly::requiredLabel'))
			{
				$label = Config::get('formly::requiredPrefix') . str_replace(Config::get('formly::requiredLabel'), '', $label) . Config::get('formly::requiredSuffix');
				$class .= ' ' . Config::get('formly::requiredClass');
			}
			$out .= Form::label($name, $label, array('class' => $class));
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
	 * @param  string $value The default value for the field
	 * @return string
	 */
	private function calculateValue($name, $value = '')
	{
		$result = '';

		// First check if there is post data
		// This assumes that you are redirecting after failed post
		// and that you have flashed the data
		// @see http://laravel.com/docs/input#old-input
		if (Input::old($name) !== null)
		{
			$result = Input::old($name, $value);
		}

		// check if there is a default value set specifically for this field
		elseif ( ! empty($value))
		{
			$result = $value;
		}

		// lastly, check if any defaults have been set for the form as a whole
		elseif ( ! empty($this->defaults->$name))
		{
			$result = $this->defaults->$name;
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
    private function setAttributes($name, $attributes = array())
	{
		// set the comment
		if ( ! empty($attributes['comment']))
		{
			$this->comments[$name] = $attributes['comment'];
			unset($attributes['comment']);
		}

		// set the id attribute
		if (Config::get('formly::nameAsId') && ! isset($attributes['id']))
		{
			$attributes['id'] = Config::get('formly::idPrefix') . $name;
		}

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