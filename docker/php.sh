#!/usr/bin/env bash

pushd "$(dirname "$0")" > /dev/null || exit
pushd .. > /dev/null

ENV=""
if [[ -n $XDEBUG_MODE ]]; then
  ENV="--env XDEBUG_MODE=$XDEBUG_MODE";
fi

docker-compose run --interactive --tty --rm --user "$(id -u):$(id -g)" ${ENV} php php "$@"

popd > /dev/null || exit
popd > /dev/null || exit
