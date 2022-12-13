<?php

namespace Web\Form;

use App\Config;

/**
 * Base form class
 */
abstract class AbstractForm
{
    /**
     * @var array
     */
    public $fields = [];

    /**
     * @var string
     */
    public $errorMessage = '';

    /**
     * @var bool
     */
    public $isValid = false;

    /**
     * Application config
     *
     * @var Config
     */
    protected $config = null;

    /**
     * Default properties for a form field in $fields
     *
     * Note that class properties cannot be initialized to anonymous functions,
     * i.e. `protected $prop = function () {};`, as per
     * https://www.php.net/manual/en/language.oop5.properties.php which states
     * "declaration may include an initialization, but this initialization must be a constant value",
     * hence "validateFunction" can only be initialized in the constructor.
     *
     * @var array
     * @property string type="text" <input> type as per
     *     https://developer.mozilla.org/en-US/docs/Web/HTML/Element/input#input_types for field.
     * @property string label="" Label for field.
     * @property string placeholder="" Placeholder text for field.
     * @property string value="" Default value for field. Note that field values in form submissions
     *     are always strings.
     * @property bool required=false Whether field is required.
     * @property string error="" Validation error message for field.
     * @property bool hide_error=false Whether to hide error. This would be true for credential
     *     fields where we do not want to let hackers know which field was wrong,
     *     e.g. hide errors for both username and password fields, and set the overall form error
     *     message as "Invalid credentials" instead. This does not apply if the error is due to an
     *     empty value for a required field.
     * @property callback validateFunction=null Custom validation function for field with
     *     signature `function (string $field, string $value): string`, taking in the field name
     *     and value, returning an error message if validation failed and an empty string if passed.
     */
    protected $fieldDefaults = [
        'type' => 'text',
        'label' => '',
        'placeholder' => '',
        'value' => '',
        'required' => false,
        'error' => '',
        'hide_error' => false,
        'validateFunction' => null,
    ];

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;

        // Ensure defaults are set for each field
        foreach ($this->fields as $field => $info) {
            $this->fields[$field] = array_merge($this->fieldDefaults, $info);
        }
    }

    /**
     * Set form data
     *
     * @param array $formData=[] Field-value pairs. Set to [] to clear form.
     * @return void
     */
    public function setData(array $formData = []): void
    {
        foreach ($this->fields as $field => $info) {
            if ('submit' === $info['type']) { // do not clear value for Submit button
                continue;
            }

            $this->fields[$field]['value'] = trim($formData[$field] ?? '');
            $this->fields[$field]['error'] = '';
        }
    }

    /**
     * Set form error message
     *
     * @param string $errorMessage
     * @return void
     */
    public function setError(string $errorMessage): void
    {
        $this->errorMessage = $errorMessage;
        $this->isValid = false;
    }

    /**
     * Validate form
     *
     * Remaining fields will not be checked if a field has error so as not to
     * waste computational resources.
     *
     * @return bool
     */
    public function validate()
    {
        // Do not proceed with custom validation if any required fields is empty
        foreach ($this->fields as $field => $info) {
            if (true === $info['required'] && '' === $info['value']) {
                $this->isValid = false;
                $this->fields[$field]['error'] = 'Cannot be empty.';

                return false;
            }
        }

        // 2 separate loops to check required fields and custom validation to prevent guessing attacks
        // E.g. if done in same loop, hacker can fill in a wrong value for username and leave the password field empty,
        // which will trigger an error message for the username, letting the hacker know that there is no such account.
        foreach ($this->fields as $field => $info) {
            $validateFn = $info['validateFunction'];

            if (is_callable($validateFn)) {
                $error = $validateFn($field, $info['value']);

                if ($error) {
                    $this->isValid = false;

                    // Do not set error if it is meant to be hidden, else view template may accidentally render it
                    if ($info['hide_error']) {
                        $this->errorMessage = $error; // set as overall form error message
                    } else {
                        $this->fields[$field]['error'] = $error;
                    }

                    return false;
                }
            }
        }

        $this->isValid = true;

        return true;
    }
}
