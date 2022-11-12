#!/bin/sh

##
# Get descriptive version for application and save in VERSION.txt
#
# @example Run from root of this repo: scripts/version.sh
##

# Check if in correct directory
if [ ! -d "scripts" ]; then
    echo "Please run this script from root of repo."
    exit 1
fi

# Check for project config file in root of repo
PROJECT_CONFIG_FILE=""
if test -f "composer.json"; then
    PROJECT_CONFIG_FILE=composer.json
elif test -f "package.json"; then
    PROJECT_CONFIG_FILE=package.json
elif test -f "pyproject.toml"; then
    PROJECT_CONFIG_FILE=pyproject.toml
fi

# For project version, take the 1st line in the project config file containing the word "version"
# Branch name may be used as Docker tag hence remove invalid chars such as /
PROJECT_VERSION=$(cat ${PROJECT_CONFIG_FILE} | grep 'version' | awk 'NR==1{ print $0 }' | sed 's/version//i')
PROJECT_VERSION=$(echo "${PROJECT_VERSION}" | sed 's/[^a-z0-9\.\+\-]//g') # allows 1.0.0-alpha+001 as per semver.org
GIT_BRANCH=$(git rev-parse --symbolic-full-name --abbrev-ref HEAD | sed 's/[^a-z0-9\.\+\-]//g')
GIT_COMMIT=$(git rev-parse --short HEAD) # short commit reference is sufficient
TIMESTAMP=$(date --utc --iso-8601=minutes | sed 's/[-: ]//g' | sed 's/+0000/Z/g') # use UTC for easier comparison

# Application version must start with "v" to prevent accidental treatment as a primitive number if starting with a digit
APPLICATION_VERSION="v${PROJECT_VERSION}-${GIT_BRANCH}-${GIT_COMMIT}-${TIMESTAMP}"
echo "${APPLICATION_VERSION}" > VERSION.txt
