<?php

namespace Web\Form;

use App\Config;
use Web\Form\AbstractForm;

/**
 * Form for index page
 */
class IndexForm extends AbstractForm
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
            'hide_error' => true,
            'validateFunction' => null, // to be init in constructor
        ],
        'api_token' => [
            'type' => 'password',
            'label' => 'API Token',
            'required' => true,
            'hide_error' => true,
            'validateFunction' => null, // to be init in constructor
        ],
        'submit' => [
            'type' => 'submit',
            'value' => 'Submit',
        ],
    ];

    /**
     * Constructor
     *
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        parent::__construct($config);

        // Additional validation
        // Don't let hacker know key/token
        $this->fields['api_key']['validateFunction'] = function ($field, $value) {
            return ($value === $this->config->get('api_key') ? '' : 'Invalid credentials.');
        };
        $this->fields['api_token']['validateFunction'] = function ($field, $value) {
            return ($value === $this->config->get('api_token') ? '' : 'Invalid credentials.');
        };
    }
}
