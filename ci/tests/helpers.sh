#!/usr/bin/env bash

function expectFileDeleted() {
  if [ -f "$1" ]; then
    printf "FAIL\nExpected file $1 to be deleted"
    exit 1
  fi
}

function expectFourRemainingFiles() {
  if [ $(ls data/ | wc -l) != 4 ]; then
    printf "FAIL\nExpected one file to be deleted"
    exit 1
  fi
}
