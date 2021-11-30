#!/usr/bin/env bash

dependencies="${COMPOSER_INSTALL_DEPENDENCIES}"
working_directory="${COMPOSER_WORKING_DIRECTORY}"

if [[ ${dependencies} == "lowest" ]]; then
  composer update --no-interaction --no-progress --prefer-lowest --working-dir="${working_directory}"

  exit $?
fi

if [[ ${dependencies} == "locked" ]]; then
  composer install --no-interaction --no-progress --working-dir="${working_directory}"

  exit $?
fi

if [[ ${dependencies} == "highest" ]]; then
  composer update --no-interaction --no-progress --working-dir="${working_directory}"

  exit $?
fi

echo "::error::The value for the \"dependencies\" input needs to be one of \"lowest\", \"locked\", \"highest\" - got \"${dependencies}\" instead."

exit 1
