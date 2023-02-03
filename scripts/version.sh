#!/bin/sh

##
# Compute application version and save in VERSION.txt to be used by Dockerfile and application
#
# Format: v<project version>-<repository branch>-<git commit>-<UTC timestamp in ISO 8601 format with timezone>,
# e.g. v0.1.0-develop-1234abc-20221121T0230Z.
#
# When adding shell scripts to a repo, run `git update-index --add --chmod=+x <filename>` to set
# the file as executable. Running `git ls-files --stage` should show the file permission as 100755
# and not 100644, e.g. `100755 bb7ddf8783bb33f9c893ac5390d5bfe97db2c759 0 version.sh`.
#
# @example Run from root of this repo: scripts/version.sh
##

# Check if in correct directory
if [ ! -d "scripts" ]; then
    echo "Please run this script from root of repo."
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
TIMESTAMP=$(date --utc --iso-8601=minutes | sed 's/+00:00/Z/' | sed 's/[-: ]//g') # use UTC for easier comparison

# Application version must start with "v" to prevent accidental treatment as a primitive number if starting with a digit
# Application version may be used as Docker tag hence only allow letters, digits, underscores, periods and hyphens
APPLICATION_VERSION="v${PROJECT_VERSION}-${GIT_BRANCH}-${GIT_COMMIT}-${TIMESTAMP}"
APPLICATION_VERSION=$(echo "${APPLICATION_VERSION}" | sed 's/[^a-z0-9_\.\-]//gi')
echo "${APPLICATION_VERSION}" > VERSION.txt
