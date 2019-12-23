#!/usr/bin/env bash

trap "rm -rf data" EXIT

printf "Should not delete any file if less then 4 builds..."
mkdir data
touch data/file1
touch data/file2

./delete_obsolete_wmf_builds.sh "data/"

if [ $(ls data/ | wc -l) != 2 ]; then
  printf "FAIL\nFiles in data should not be deleted"
  exit 1
else
  printf "PASS\n"
fi
