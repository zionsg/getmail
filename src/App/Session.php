<?php

namespace App;

/**
 * Session
 *
 * @link Adapted from https://discourse.laminas.dev/t/rfc-php-session-and-psr-7/294
 * @link http://paul-m-jones.com/post/2016/04/12/psr-7-and-session-cookies/
 */
class Session
{
    /**
     * Session ID
     *
     * @var string
     */
    protected $id;

    /**
     * Session data in key-value pairs
     *
     * @var array
     */
    protected $session;

    /**
     * Constructor
     *
     * @param string $id
     */
    public function __construct(string $id)
    {
        $this->id = $id;
        session_id($id);

        session_start([
            'use_trans_sid' => false, // default is false but set explicitly in case default changes
            // Tell PHP to read only from the cookies, and from nowhere else, to find the session ID value
            'use_cookies' => false,
            'use_only_cookies' => true
        ]);

        $this->session = $_SESSION;
    }

    /**
     * Get value of key in session data
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        return ($this->session[$key] ?? null);
    }

    /**
     * Get session ID
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Set key-value pair in session data
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function set(string $key, $value): void
    {
        $this->session[$key] = $value;
    }

    /**
     * Unset key from session data
     *
     * @param string $key
     * @return void
     */
    public function unset(string $key): void
    {
        unset($this->session[$key]);
    }

    /**
     * Save session data to storage
     *
     * @return void
     */
    public function save(): void
    {
        $_SESSION = $this->session;
        session_write_close();
    }
}
