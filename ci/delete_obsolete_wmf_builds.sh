#!/usr/bin/env bash

while [[ $(ls $1 | wc -l) -gt 4 ]]
do
  oldestFile=$(find $1 -type f -printf '%T+ %p\n' | sort | head -n 1 | awk '{print $NF}')
  rm $oldestFile
done
