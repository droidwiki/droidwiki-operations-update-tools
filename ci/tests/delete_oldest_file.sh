#!/usr/bin/env bash

. ./tests/helpers.sh

trap "rm -rf data" EXIT

function setupTestdata() {
  mkdir data
  touch -t 201805121200 data/file1
  touch data/file2
  touch data/file3
  touch data/file4
  touch data/file5
}

printf "Should delete latest file if more than 4 builds..."
setupTestdata

./delete_obsolete_wmf_builds.sh "data/"

expectFourRemainingFiles
expectFileDeleted "data/file1"
printf "PASS\n"
