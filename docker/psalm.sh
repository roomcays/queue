#!/usr/bin/env bash

pushd "$(dirname "$0")" > /dev/null || exit
pushd .. > /dev/null

source docker/php.sh vendor/bin/psalm "$@"

popd > /dev/null || exit
popd > /dev/null || exit

