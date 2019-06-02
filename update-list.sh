#!/bin/bash

while read line
do
  name=$line
  IFS=';' read -ra UPDATE_INFO <<< "$line"
  if (( "${#UPDATE_INFO[@]}" >= 2 )); then
	name=${UPDATE_INFO[0]}
	version=${UPDATE_INFO[1]}
	branchPath=${UPDATE_INFO[2]:-https://gerrit.wikimedia.org/r/mediawiki/extensions/}
	$(dirname $0)/update.sh -p $name -v $version -w "" -u $branchPath
  else
	$(dirname $0)/update.sh -p $name -v $2
  fi
  rc=$?; if [[ $rc != 0 ]]; then echo "Error in update.sh script"; exit $rc; fi
done < $1
