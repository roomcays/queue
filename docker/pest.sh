#!/usr/bin/env bash

pushd "$(dirname "$0")" > /dev/null || exit
pushd .. > /dev/null

# Following will let Pest use `--coverage` argument
XDEBUG_MODE=coverage
source docker/php.sh vendor/bin/pest "$@"

popd > /dev/null || exit
popd > /dev/null || exit

