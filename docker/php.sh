#!/usr/bin/env bash

pushd "$(dirname "$0")" > /dev/null || exit
pushd .. > /dev/null

docker-compose run --interactive --tty --rm --user "$(id -u):$(id -g)" php php "$@"

popd > /dev/null || exit
popd > /dev/null || exit
