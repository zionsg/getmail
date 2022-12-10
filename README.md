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
- [Docker Compose](https://docs.docker.com/compose/release-notes/) >= 1.29.0
    + Note that Docker Compose v1 uses the `docker-compose` command whereas
      Docker Compose v2 uses the `docker compose` command (without hyphen) via
      the Compose plugin for Docker.
        * [Install Docker Compose v1 on Ubuntu 20.04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-20-04)
        * [Install Docker Compose v2 plugin on Ubuntu 22.04](https://www.digitalocean.com/community/tutorials/how-to-install-and-use-docker-compose-on-ubuntu-22-04)
    + `depends_on` condition in Docker Compose file to wait for successful
      service completion added in Docker Compose v1.29.0 onwards. See
      https://github.com/compose-spec/compose-spec/blob/master/spec.md#depends_on
      for details.
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
                # cos packages may use Linux native libraries and not work on host platform
                - type: bind
                  source: /mnt/c/Users/Zion/localhost/www/getmail/VERSION.txt # application version
                  target: /var/www/html/VERSION.txt
                - type: bind
                  source: /mnt/c/Users/Zion/localhost/www/getmail/public/index.php # application entrypoint
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
        * `docker` is used instead of `docker-compose` to build the image to
          cater for use of SSH agent forwarding in the future (if there is a
          need to install packages from a private GitHub repository),
          i.e. `docker build --ssh default`, which `docker-compose`
          does not support yet.
    + Run `composer start` to start the Docker container.
    + Run `composer stop` to stop the Docker container or just press `Ctrl+C`.
      However, the former should be used as it will properly shut down the
      container, else it may have problems restarting later.
    + The application can be accessed via `http://localhost:8080`.
        * See `GETMAIL_PORT_*` env vars for port settings.
- Additional stuff:
    + Run `composer lint` to do linting checks.

## Application Design
- Basic guiding principles:
    + [3Cs for Coding - Consistency, Context, Continuity](https://blog.intzone.com/3cs-for-coding-consistency-context-continuity/).
    + [Configuration over Convention](https://en.wikipedia.org/wiki/Laminas#Anatomy_of_the_framework),
      [Explicit is better than implicit](https://peps.python.org/pep-0020/#the-zen-of-python).
    + Adherence to [PSR](https://www.php-fig.org/psr/) wherever applicable.
    + Conformance to [The Twelve-Factor App](https://12factor.net/) as much as
      possible, especially with regards to config.
    + No custom static classes/methods except for `App\Constants`
      and `App\Utils`.
    + At most 1 level of inheritance to prevent going down a rabbit hole, e.g.

            class A {}
            class B extends A {} // allowed
            class C extends B {} // not allowed

- Modules:
    + App: Application-wide classes.
    + Api: Classes handling requests to API endpoints.
    + Web: Classes handling requests for web pages.
- Deployment environments: production, staging, feature, testing, local.
