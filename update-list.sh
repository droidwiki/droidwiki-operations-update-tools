#!/bin/bash

while read line
do
    name=$line
    echo "Updating $name to $2 - 3 seconds to abort"
	for i in {3..0}; do echo -ne "...$i"'\r'; sleep 1; done; echo 
	./bin/update.sh $name $2
	rc=$?; if [[ $rc != 0 ]]; then echo "Error in update.sh script"; exit $rc; fi
done < $1