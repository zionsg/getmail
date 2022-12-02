<?php

namespace Web\Form;

use App\Config;

/**
 * Form for index page
 */
class IndexForm
{
    /**
     * @var array
     */
    public $fields = [
        'subject_pattern' => [
            'type' => 'text',
            'label' => 'Subject Pattern',
            'placeholder' => 'E.g. password',
            'required' => true,
        ],
        'api_key' => [
            'type' => 'text',
            'label' => 'API Key',
            'required' => true,
            'validateFunction' => null, // to be init in constructor
        ],
        'api_token' => [
            'type' => 'password',
            'label' => 'API Token',
            'required' => true,
            'validateFunction' => null, // to be init in constructor
        ],
        'submit' => [
            'type' => 'submit',
            'value' => 'Submit',
        ],
    ];

    /**
     * @var string
     */
    public $errorMessage = '';

    /**
     * @var bool
     */
    public $isValid = false;

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
        'validateFunction' => null,
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        // Ensure defaults are set for each field
        foreach ($this->fields as $field => $info) {
            $this->fields[$field] = array_merge($this->fieldDefaults, $info);
        }

        // Initialize custom validation functions for specific fields
        $this->fields['api_key']['validateFunction'] = function ($field, $value) {
            return ($value === Config::get('api_key') ? '' : 'Invalid credentials.'); // don't let hacker know key/token
        };
        $this->fields['api_token']['validateFunction'] = function ($field, $value) {
            return ($value === Config::get('api_token') ? '' : 'Invalid credentials.');
        };
    }

    /**
     * Set form data
     *
     * @param array $formData=[] Field-value pairs. Set to [] to clear form.
     * @return void
     */
    public function setData(array $formData = [])
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
     * Validate form
     *
     * Remaining fields will not be checked if a field has error.
     *
     * @return bool
     */
    public function validate()
    {
        foreach ($this->fields as $field => $info) {
            $value = $info['value'];
            $label = $info['label'];

            $isRequired = $info['required'] ?? false;
            if ($isRequired && '' === $value) {
                $this->isValid = false;
                $this->errorMessage = "Required field \"{$label}\" cannot be empty.";
                $this->fields[$field]['error'] = 'Cannot be empty.';

                return false;
            }

            $validateFn = $info['validateFunction'];
            if (is_callable($validateFn)) {
                $error = $validateFn($field, $value);

                if ($error) {
                    $this->isValid = false;
                    $this->errorMessage = $error;
                    $this->fields[$field]['error'] = $error;

                    return false;
                }
            }
        }

        $this->isValid = true;

        return true;
    }
}
