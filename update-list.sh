#!/bin/bash

while read line
do
  name=$line
  $(dirname $0)/update.sh -p $name -v $2
  rc=$?; if [[ $rc != 0 ]]; then echo "Error in update.sh script"; exit $rc; fi
done < $1
