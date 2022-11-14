# Get Mail

**Disclaimer** This is just a personal hobbyist project to experiment with
creating a PHP application with a simple structure without the use of bloated
frameworks and Dockerizing it. It will likely always be Work-In-Progress, so
don't expect too much from it :P

Simple Dockerized PHP application that uses
[IMAP](https://en.wikipedia.org/wiki/Internet_Message_Access_Protocol)
to retrieve body of the most recent email in inbox matching a subject pattern.

Paths in all documentation, even those in subfolders, are relative to the root
of the repository. Shell commands are all run from the root of the repository.

## Sections
- [Requirements](#requirements)
- [Installation](#installation)

## Requirements
- [PHP](https://www.php.net/) >= 8.1
- [Composer](https://getcomposer.org/) >= 2.4.4
- [Docker Engine](https://docs.docker.com/engine/release-notes/) >= 20.10.7
- [Docker Compose](https://docs.docker.com/compose/release-notes/) >= 1.29.0
    + `depends_on` condition in Docker Compose file to wait for successful
      service completion added in Docker Compose v1.29.0 onwards. See
      https://github.com/compose-spec/compose-spec/blob/master/spec.md#depends_on
      for more info.
    + Version 3.7 is currently used for the Compose file format.

## Installation
- This section is meant for software developers.
- Clone this repository.
- Copy `.env.example` to `.env` and update the values accordingly. This will be
  read by Docker Compose and the application. The file `.env` will not be
  committed to the repository.
- Run `composer install`.
- To run the application locally:
    + For consistency with production environment, the application should be run
      using Docker during local development (which settles all dependencies)
      and not directly using `php -S localhost:8080 public/index.php`.
    + Create a `docker-compose.override.yml` which will be automatically used by
      Docker Compose to override specified settings in `docker-compose.yml`.
      This is used to temporarily tweak the Docker Compose configuration on the
      local machine and will not be committed to the repository. See
      https://docs.docker.com/compose/extends for more info.
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
                  source: /mnt/c/Users/Me/localhost/www/getmail/public/index.php # entrypoint for application
                  target: /var/www/html/public/index.php
                - type: bind
                  source: /mnt/c/Users/Me/localhost/www/getmail/src
                  target: /var/www/html/src
                - type: bind
                  source: /mnt/c/Users/Me/localhost/www/getmail/public/assets/css
                  target: /var/www/html/public/css
                - type: bind
                  source: /mnt/c/Users/Me/localhost/www/getmail/public/assets/images
                  target: /var/www/html/public/images
                - type: bind
                  source: /mnt/c/Users/Me/localhost/www/getmail/public/assets/js
                  target: /var/www/html/public/js
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
