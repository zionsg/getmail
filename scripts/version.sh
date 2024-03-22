#!/bin/sh

##
# Compute application version and save in VERSION.txt to be used by Dockerfile and application
#
# Format: v<project version>-<repository branch>-<git commit>-<UTC timestamp in ISO 8601 format with timezone>,
# e.g. v0.1.0-develop-1234abc-20221121T0230Z.
#
# The application version should be always indicated in the following places:
#   - API responses: Specify it under the meta key of the JSON response as it should be indicated
#     regardless of a success or error response, e.g.
#
#         {
#             "data": {
#                 message: "OK"
#             },
#             "error": null,
#             "meta": {
#                 "version": "v0.1.0-develop-1234abc-20221121T0230Z"
#             }
#         }
#
#  - Useragent: Specify it especially when calling external APIs, in the format
#    <application name>/<hostname>/<application version> (operating system info), e.g.
#
#        MYAPP/localhost/v0.1.0-develop-1234abc-20221121T0230Z (5.10.60.1-microsoft-standard-WSL2; linux; x64)
#
#  - Website: Specify it using a meta tag in the website layout so that it can be checked easily on
#    all webpages in the website, e.g.
#
#        <!DOCTYPE html>
#        <html>
#            <head>
#                <meta charset="utf-8">
#                <meta name="version" content="v0.1.0-develop-1234abc-20221121T0230Z">
#            </head>
#            <body></body>
#        </html>
#
#  - Docker image: Create a file VERSION.txt that contains the application version and store it in
#    when building the Docker image. This file should NOT be committed to the repository, i.e. it
#    should be listed in .gitignore.
#      + This allows any developer to quickly determine the version by entering a Docker container
#        started from the Docker image, without having to search for project config files or thru
#        the source code in the Docker image.
#      + The file extension .txt is explicitly stated so that the user need not waste time guessing
#        the file type, whether it's text or binary, or how to open the file.
#
# When adding shell scripts to a repository, run `git update-index --add --chmod=+x <filename>` to
# set the file as executable. Running `git ls-files --stage` should show the file permission as
# 100755 and not 100644, e.g. `100755 bb7ddf8783bb33f9c893ac5390d5bfe97db2c759 0 version.sh`.
#
# @example Run from root of this repository: scripts/version.sh
##

# Check if in correct directory
if [ ! -d "scripts" ]; then
    echo "Please run this script from root of repository."
    exit 1
fi

# Check for project config file in root of repo
# package.json may exist in a PHP/Python project due to use of apiDoc/ESLint hence checked last
PROJECT_CONFIG_FILE=""
if test -f "composer.json"; then
    PROJECT_CONFIG_FILE=composer.json
elif test -f "pyproject.toml"; then
    PROJECT_CONFIG_FILE=pyproject.toml
elif test -f "package.json"; then
    PROJECT_CONFIG_FILE=package.json
fi

# For project version, take the 1st line in the project config file containing the word "version"
PROJECT_VERSION=$(cat ${PROJECT_CONFIG_FILE} | grep 'version' | awk 'NR==1{ print $0 }' | sed 's/version//i')
GIT_BRANCH=$(git rev-parse --symbolic-full-name --abbrev-ref HEAD)
GIT_COMMIT=$(git rev-parse --short HEAD) # short commit reference is sufficient
TIMESTAMP=$(date -u +%Y%m%dT%H%MZ) # use UTC for easier comparison, runs on Linux and macOS, no symbols

# Application version must start with "v" to prevent accidental treatment as a primitive number if starting with a digit
# Application version may be used as Docker tag hence only allow letters, digits, underscores, periods and hyphens
APPLICATION_VERSION="v${PROJECT_VERSION}-${GIT_BRANCH}-${GIT_COMMIT}-${TIMESTAMP}"
APPLICATION_VERSION=$(echo "${APPLICATION_VERSION}" | sed 's/[^a-z0-9_\.\-]//gi')
echo "${APPLICATION_VERSION}" > VERSION.txt
