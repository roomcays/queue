#!/usr/bin/env bash

set -e

pushd "$(dirname "$0")" > /dev/null
pushd .. > /dev/null

source docker/php.sh vendor/bin/phpcs "$@"

popd > /dev/null
popd > /dev/null

