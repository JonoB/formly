# Laravel Form Package

Forms support in Laravel, with Twitter Bootstrap styling. All form inputs use Laravel's form helpers
to create the actual html. Some added goodies like setting form defaults, repopulating forms
after failed validation and showing failed validation errors.

There is a <a href="http://www.screencast.com/t/OiBx2IChh9">5 minute video overview</a> of using Formly. Although this was originally
done for Laravel 3, the concepts remain identical.

# Installation

### Composer

Add `"jonob/formly": "dev-master",` to the `require` section of your `composer.json`:

```composer
"require": {
	"jonob/formly": "dev-master"
},
```

Now run `composer update`.

### Laravel

Add the following code to the `providers` section of the `app/config/app.php` file:
```php
'Jonob\Formly\FormlyServiceProvider',
```

Add the following code to the `aliases` section of the `app/config/app.php` file:
```php
'Formly' => 'Jonob\Formly\Formly',
```

# Usage

### Routes and Controllers
To start off with, you create a new form object in your route/controller. This can be done with a static method
as follows:
```php
$form = Formly::make();
```

Or you can instantiate it like so:
```php
$form = new Formly();
```

You then pass the form object to your view as follows. This means that that formly will be available in your
view with the $form variable.
```php
return View::make('posts.form')->with('form', $form);
```

### Forms
In generaly, Formly follows Laravel's default form helpers in terms of the method names and function parameters.
There are two exceptions. Firstly, all methods are called non-statically and secondly the second parameter in
Formly is the input's label. For example:
```php
// Standard Laravel form input
Form::text($name, $value, $attributes);

// Formly
$form->text($name, $label, $value, $attributes);
```
Because we specify the label name in the method, there is no need to have a separate label field on your form - Formly will
generate it for you automatically.

When it comes to opening your forms, then you just call the open method as follows. Notice that its not necessary to
specify the action - by default Formly will POST to the current URL. You can of course override this if you wish.
```php
$form->open();
```
Using this method has the added benefit that a hidden CSRF token will be automatically inserted for you. You can
override this if you want.

### Setting form values

#### Using formly to set default values
If you are populating your form from existing data (for example, if you are editng a record from your database),
then its not necessary to do this for each field. Let Formly do all the work for you as follows:

 ```php
// Get the single post from the post model
$post::find($post_id);

// Pass the default values to Formly
$form = Formly::make($post);

// Create the view
return View::make('posts.form')->with('form', $form);
```
In order for this to work, the field names for your forms MUST have the same names as your database fields. If they are not
the same, then Formly has no idea how to connect the two together.

You can populate fields manually if you wish:
 ```php
// Pass the default values to Formly
$form = Formly::make(array('start_date' => date('Y-m-d')));

// Create the view
return View::make('posts.form')->with('form', $form);
```

#### Setting default values inline for each input
Alternatively, you can also set default values for individual form fields in the actual form. Values set in this way
will override defaults set via the method above.
```php
$form->text('start_date', 'Start Date', date('Y-m-d'));
```

#### Setting default via $_POST
Well, this is not something that you do - Formly does it for you automatically. If, for example, you try save a
form and validation fails, then Formly will automatically repopulate each input with the posted data.

#### Cascade
Based on the above, its evident that there are 3 methods of populating your forms. The order of precedence is as
follows:
- First check if there is post data for the input
- Secondly check if a value has been set inline
- Lastly check if form defaults have been set


### Validation
Formly automatically hooks up to Laravel's validation library with very little effort. Lets look
at a full example.

```php
// Controller
public function edit($id)
{
	$post = Post::find($id);

	if ( ! $post) {
		// do something. Maybe redirect or 404?
	}

	return View::make('posts.form')
		->with('form', Formly::make($post));
}

public function update()
{
	$rules = array(
	    'name'  => 'required|max:50',
	    'email' => 'required|email|unique:users',
	);

	$validation = Validator::make($input = Input::get(), $rules);

	if ($validation->fails()) {
        return Redirect::to('posts/edit/' Input::get('id'))->withErrors($validation)->withInput(Input::get());
    }
    return Redirect::to('posts');
}

```
Notice that if validation fails, then its necessary to redirect with the errors and the input. By doing
this, we achieve two things:
- The form will be automatically re-populated with the posted data.
- Any errors will be highlighted (if you have enabled the options in Formly; see below)

Note that you do not need to do anything special to your form - simply by returning withErrors() and withInput(), Formly
knows what to do

### Submit buttons
Creating a submit button is easy:
```php
$form->submit('Save');
```
By default, Formly will add in the Twitter Bootstrap 'btn' class. You can override this in the third parameter if you want:
```php
$form->submit('Save', $attributes, 'some-class');
```

There are also some shortcuts for all the Twitter Bootstrap button styles:
```php
// create a button with a class of 'btn btn-primary'
$form->submitPrimary('Save');

// and so on...
$form->submitInfo('Save');
$form->submitSuccess('Save');
$form->submitWarning('Save');
$form->submitDanger('Save');
$form->submitInverse('Save');
```

### Formly Options
There are a couple of options that allow you to customise how Formly works. You can override
these when the class is instantiated or through the `setOption()` method. Note that `setOption()`
can be used to set many options at once, or a single option.

```php
$defaults = Post::find($id);

$options = array(
	'formClass' => 'form_vertical',
	'autoToken' = false
);

// Set multiple options when the class is instantiated
$form = Formly::make($defaults, $options);

// Set multiple options using setOption()
$form = Formly::make()->setOption($options);

// Set a single option using setOption()
$form = Formly::make()->setOption('formClass', 'form_vertical');
```

##### formClass (default: form_horizontal)
By default, forms are styled using form-horizontal, but you can choose
any of Bootstrap's other styles, such as form-vertical, form-inline, form-search

##### autoToken (default: true)
Automatically adds a csrf token to the form_open method

##### nameAsId (default: true)
Automatically creates an id for each field based on the field name

##### idPrefix (default: field_)
If name_as_id is enabled, then this string will be prefixed to the id attribute

##### requiredLabel (default: .req)
Say you want to identify a label as being a required field on your form. Using formly,
you can just append this string to the label parameter, and Formly will automatically
use the required_prefix, required_suffix and required_class
```php
$form->text('start_date', 'Start Date.req');
```

##### requiredPrefix (default:'')
If the required_label has been set, then the text from this variable will
be prefixed to your label

##### requiredSuffix (default:' *')
If the required_label has been set, then the text from this variable will
be added to the end of your label

##### requiredClass (default: 'label-required')
If the required_label has been set, then this class will be added to the
label's attribute. You want the label to be bold, for example, which you can
then style in your css

##### controlGroupError (default: 'error')
Display a class for the control group if an input field fails validation

##### displayInlineErrors (default: false)
If the field has failed validation, then inline errors will be shown
