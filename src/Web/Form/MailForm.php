<?php

namespace Web\Form;

use App\Config;
use App\Logger;
use Web\Form\AbstractForm;

/**
 * Form to get mail
 */
class MailForm extends AbstractForm
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
            'validateFunction' => null, // to be init in constructor
        ],
        'api_key' => [
            'type' => 'text',
            'label' => 'API Key',
            'required' => true,
            'hide_error' => true,
            'validateFunction' => null,
        ],
        'api_token' => [
            'type' => 'password',
            'label' => 'API Token',
            'required' => true,
            'hide_error' => true,
            'validateFunction' => null,
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
     * @param Logger $logger
     */
    public function __construct(Config $config, Logger $logger)
    {
        parent::__construct($config, $logger);

        // Additional validation
        $this->fields['subject_pattern']['validateFunction'] = function ($field, $value) {
            return (preg_match('/[^a-z0-9 \-_]/i', $value) ? 'Invalid chars in subject pattern.' : '');
        };
    }
}
