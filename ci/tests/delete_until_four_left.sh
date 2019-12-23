#!/usr/bin/env bash

. ./tests/helpers.sh

trap "rm -rf data" EXIT

printf "Should delete latest files until only 4 remains..."
mkdir data
touch -t 201805121200 data/file1
touch -t 201805121200 data/file2
touch -t 201805121200 data/file3
touch data/file4
touch data/file5
touch data/file6
touch data/file7

./delete_obsolete_wmf_builds.sh "data/"

expectFourRemainingFiles
expectFileDeleted "data/file1"
expectFileDeleted "data/file2"
expectFileDeleted "data/file3"
printf "PASS\n"
