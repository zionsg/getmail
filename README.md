# Get Mail

**Disclaimer** This is just a personal hobbyist project to experiment with
creating a PHP application with a simple structure without the use of bloated
frameworks and Dockerizing it. It will likely always be Work-In-Progress and
may have breaking changes, so caveat emptor :P

Simple Dockerized PHP application that uses
[IMAP](https://en.wikipedia.org/wiki/Internet_Message_Access_Protocol)
to retrieve body of the most recent email in an inbox matching a subject
pattern.

Paths in all documentation, even those in subfolders, are relative to the root
of the repository. Shell commands are all run from the root of the repository.

## Sections
- [Requirements](#requirements)
- [Installation](#installation)
- [Application Design](#application-design)
- [To-do](#to-do)

## Requirements
- [PHP](https://www.php.net/) >= 8.0
- [Composer](https://getcomposer.org/) >= 2.4.4
- [Docker Engine](https://docs.docker.com/engine/release-notes/) >= 20.10.7
    + Using Ubuntu together with Docker on a Windows machine via
      Windows Subsystem for Linux (WSL), without requiring dual boot.
        * [Install Linux on Windows with WSL](https://docs.microsoft.com/en-us/windows/wsl/install)
        * [Install Docker in WSL 2 without Docker Desktop](https://nickjanetakis.com/blog/install-docker-in-wsl-2-without-docker-desktop)
- [Docker Compose](https://docs.docker.com/compose/release-notes/) >= 2.14.0
    + Note that Docker Compose v2 uses the `docker compose` command (without
      hyphen) via the Compose plugin for Docker whereas Docker Compose v1 uses
      the `docker-compose` command (with hyphen).
        * [Install Docker Compose v2 plugin on Ubuntu 22.04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04)
        * [Install Docker Compose v1 on Ubuntu 20.04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-20-04)
    + Version 3.7 is currently used for the Compose file format.

## Installation
- This section is meant for software developers.
- Clone this repository.
- Copy `.env.example` to `.env` and update the values accordingly. This will be
  read by Docker Compose and the application. The file `.env` will not be
  committed to the repository.
- Copy `config/zenith.local.php.dist` to `config/zenith.local.php` to override
  the application configuration locally during development. The file
  `config/zenith.local.php` will not be committed to the repository.
- Run `composer install`.
- To run the application locally:
    + For consistency with production environment, the application should be run
      using Docker during local development (which settles all dependencies)
      and not directly using `php -S localhost:8080 public/index.php`.
        * May need to run Docker commands as `sudo` depending on machine
          (see https://docs.docker.com/engine/install/linux-postinstall/).
        * If you see a stacktrace error when running a Docker command in
          Windows Subsystem for Linux (WSL),
          e.g. `The provided cwd "" does not exist.`,
          try running `cd .` and run the Docker command again.
    + Create a `docker-compose.override.yml` which will be automatically used by
      Docker Compose to override specified settings in `docker-compose.yml`.
      This is used to temporarily tweak the Docker Compose configuration on the
      local machine and will not be committed to the repository. See
      https://docs.docker.com/compose/extends for details.
        * A common use case during local development would be to use the `dev`
          tag for the Docker image and enabling live reload inside the Docker
          container when changes are made to the source code on a Windows host
          machine.

            ```
            # docker-compose.override.yml in root of repository
            version: "3.7" # this is the version for the compose file config, not the app
            services:
              getmail-app:
                image: getmail:dev
                volumes:
                  # Cannot use the shortform "- ./src/:/var/www/html/src" else Windows permission error
                  # Use the vendor folder inside the container and not the host
                  # as packages may use Linux native libraries and not work on host platform
                  - type: bind
                    source: /mnt/c/Users/Zion/localhost/www/getmail/public/index.php # app entrypoint
                    target: /var/www/html/public/index.php
                  - type: bind
                    source: /mnt/c/Users/Me/localhost/www/getmail/config
                    target: /var/www/html/config
                  - type: bind
                    source: /mnt/c/Users/Me/localhost/www/getmail/src
                    target: /var/www/html/src
                  - type: bind
                    source: /mnt/c/Users/Me/localhost/www/getmail/public/assets/css
                    target: /var/www/html/public/assets/css
                  - type: bind
                    source: /mnt/c/Users/Me/localhost/www/getmail/public/assets/images
                    target: /var/www/html/public/assets/images
                  - type: bind
                    source: /mnt/c/Users/Me/localhost/www/getmail/public/assets/js
                    target: /var/www/html/public/assets/js
                  - type: bind
                    source: /mnt/c/Users/Me/localhost/www/getmail/tmp
                    target: /var/www/html/tmp
            ```

    + Run `composer build` first to build the Docker image with "dev" tag.
    + Run `composer start` to start the Docker container.
    + Run `composer stop` to stop the Docker container or just press `Ctrl+C`.
      However, the former should be used as it will properly shut down the
      container, else it may have problems restarting later.
    + The application can be accessed via `http://localhost:8080`.
        * See `GETMAIL_PORT_*` env vars for port settings.
- Additional stuff:
    + Run `composer lint` to do linting checks.
    + To do linting checks on JavaScript files:
        + Node.js and NPM need to be installed.
        + Run `npm install` to install ESLint.
        + Run `npm run lint` to do linting checks.

## Application Design
- 7 basic guiding principles:
    + [3Cs for Coding - Consistency, Context, Continuity](https://blog.intzone.com/3cs-for-coding-consistency-context-continuity/).
        * This includes [Configuration over Convention](https://en.wikipedia.org/wiki/Laminas#Anatomy_of_the_framework)
          and [Explicit is better than implicit](https://peps.python.org/pep-0020/#the-zen-of-python), which are
          mentioned in the article.
    + [Robustness Principle](https://en.wikipedia.org/wiki/Robustness_principle):
      Be conservative in what you send, be liberal in what you accept,
      i.e. [trust no one](https://en.wikipedia.org/wiki/Trust_no_one_(Internet_security)).
    + Adherence to [PSR (PHP Standards Recommendations)](https://www.php-fig.org/psr/) wherever applicable.
    + Conformance to [The Twelve-Factor App](https://12factor.net/) as much as
      possible, especially with regards to config.
    + Constructor dependency injection. All dependencies should be passed in
      via the constructor, instead of retrieving indirectly from instance
      objects or static classes/methods. In this regard, the application config
      and logger are passed in as the 1st two arguments for all classes as they
      are always required. That said, try to cap arguments to 7. Also see
      https://www.php-fig.org/psr/psr-11/meta/#4-recommended-usage-container-psr-and-the-service-locator on bad example.
    + An instance object should either expose public properties or public
      methods, not both, as it will be hard to remember which to use for each
      scenario. This does not apply to class constants.

        ```
        class Point // allowed
        {
            public $x;
        }

        class Point // allowed
        {
            public const SYSTEM = 'cartesian';
            protected $x;

            public function getX()
            {
                return $this->x;
            }
        }

        class Point // not allowed - use property in some cases, use method in some cases
        {
            public $x;
            protected $y;

            public function getY()
            {
                return $this->y;
            }
        }
        ```

    + At most 1 level of inheritance to prevent going down a rabbit hole. This
      does not apply to vendor classes. It is useful to note that in PHP,
      constructors of extending classes can define completely different
      parameters without conflicting with the parent class, as parent
      constructors are not called implicitly and that `__construct()` is
      exempt from the usual signature compatibility rules when being extended.
      (see https://www.php.net/manual/en/language.oop5.decon.php). This can
      be used by extending classes to simplify instantiation especially if
      internal functionality of the parent class does not need to be changed.

        ```
        use Laminas\Diactoros\Response;
        use Laminas\Diactoros\Response\JsonResponse; // extends Response

        class A {}
        class B extends A {} // allowed
        class C extends B {} // not allowed

        // Allowed even though JsonResponse extends Response as both are vendor classes
        class ApiResponse extends JsonResponse {}
        class ExternalApiResponse extends ApiResponse {} // not allowed, should extend JsonResponse
        ```

- Deployment environments: production, staging, feature, testing, local.
- Modules:
    + App: Application-wide classes.
    + Api: Classes handling requests to API endpoints.
    + Web: Classes handling requests for web pages.
- Directory structure (using `tree --charset unicode --dirsfirst -a -n`):

    ```
    Root of repository
    |-- config  # Configuration files
    |-- public  # Publicly hosted assets used by webpages in <link>, <script>, <img>
    |   |-- assets
    |   |   |-- css     # Stylesheets
    |   |   |-- images  # Images
    |   |   `-- js      # JavaScript files
    |   `-- index.php   # Application entrypoint
    |-- scripts         # Helper shell scripts
    |   `-- version.sh  # Script for generating application version
    |-- src  # Source code
    |   |-- Api             # API module
    |   |   |-- Controller  # Controllers for handling requests to API endpoints
    |   |   |   |-- IndexController.php
    |   |   |   `-- SystemController.php
    |   |   `-- ApiResponse.php  # Standardized JSON response for API endpoints
    |   |-- App             # API module
    |   |   |-- Controller  # Controllers for handling requests application-wide
    |   |   |   |-- AbstractController.php  # Base controller class
    |   |   |   |-- ErrorController.php     # Application-wide error handler
    |   |   |   `-- IndexController.php     # Handles requests to index page
    |   |   |-- Application.php  # Main application class
    |   |   |-- Config.php       # Application configuration
    |   |   |-- Constants.php    # Application constants
    |   |   |-- Logger.php       # Logger
    |   |   |-- Router.php       # Router
    |   |   `-- Utils.php        # Common utility functions
    |   `-- Web             # Web module
    |       |-- Controller  # Controllers for handling requests for web pages
    |       |   `-- IndexController.php  # Handles request to home page
    |       |-- Form                  # Forms
    |       |   |-- AbstractForm.php  # Base form class, handles fields and validation
    |       |   `-- IndexForm.php
    |       |-- view              # View templates, add subfolders if needed
    |       |   |-- error.phtml   # Common view template for error pages
    |       |   |-- index.phtml   # View template for home page
    |       |   `-- layout.phtml  # Layout template in which rendered HTML for views are wrapped
    |       `-- WebResponse.php   # Standardized HTML response for API endpoints
    |-- test         # Tests
    |   `-- ApiTest  # Tests for API module
    |-- .dockerignore
    |-- .env.example        # List of all environment variables, to be copied to .env
    |-- .gitattributes
    |-- .gitignore
    |-- Dockerfile
    |-- LICENSE.md
    |-- README.md
    |-- VERSION.txt         # Generated by scripts/version.sh, not committed to repository
    |-- composer.json       # Backend dependencies
    |-- composer.lock
    |-- docker-compose.yml
    |-- package.json        # Frontend dependencies if any, mainly for JavaScript ESLint linter
    |-- package-lock.json
    `-- phpcs.xml           # Configuration for PHP CodeSniffer linter
    ```

## To-do
- Separate plaintext and HTML parts in output.
- Implement CSRF token for forms.
- Add `debug` query param to trigger debug logs and document in `src/Web/view/layout.phtml`.
- Write tests, especially for API endpoints.
- Generate API documentation. API docblocks are probably best placed at the controller action
  method.
- Add write-up on how this can be used with https://uilicious.com/ in retrieving emails
  for OTP from mailboxes other than https://inboxkitten.com/ while making it easy to retrieve
  mail body (no iframes) and not storing actual mail credentials with them.
    + Sample login test script that uses InboxKitten:

        ```
        // Go to Login Page
        I.goTo(DATA.SITE_DOMAIN + '/web/login')
        I.fill('Email', DATA.LOGIN_USERNAME)
        I.fill('Password', DATA.LOGIN_PASSWORD)
        I.click('Request OTP')
        I.see('OTP Verification Code')

        // Get OTP from mail and fill it in
        let mailBody = getMailBody('One-Time Password', 'Your one-time password')
        let matches = mailBody.match(/password is (\d+)/i)
        let otp = matches[1] || '000000'
        I.fill('otp', otp)
        I.click('Login')

        // See dashboard and then logout
        I.see('Dashboard')
        I.wait(3)
        I.goTo(DATA.SITE_DOMAIN + '/web/logout')

        function getMailBody(mailSubject, mailBodyHintText) {
            let waitForMailSecs = 5
            let url = ''
            let body = ''

            if ('inboxkitten.com' === DATA.MAIL_HOST) {
                // Go to mail inbox page in new tab
                url = 'https://inboxkitten.com/inbox/' + DATA.MAIL_USERNAME + '/list'
                I.goTo(url, {
                    newTab: true
                })

                // Wait a while for mail to arrive
                I.wait(waitForMailSecs)
                I.see('@inboxkitten')
                I.see(mailSubject)
                I.click(mailSubject)

                // Target iframe in mailbox
                UI.context('#message-content', () => {
                    // I.see is critical in ensuring that I.getText is done AFTER the email is loaded
                    // hence use of hint text to check if email body has loaded
                    I.see(mailBodyHintText)

                    // I.getText targets an element and extracts its text
                    // XPath '//body' is used if it is a plaintext email and not an HTML email
                    body = I.getText('//body')
                })

                // Close current tab and switch back to previous tab
                I.closeTab()
            }

            return body
        }
        ```
