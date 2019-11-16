#!/usr/bin/env bash

#
# Clones a list of repositories into the current directory. Following parameters are allowed:
#
# $1 - The list of repository configurations in the following format:
#      REPOSITORY_NAME[;BRANCH[;BASE_URL]]
#      Where:
#       * REPOSITORY_NAME: The name of the repository, appended to the BASE_URL as well as used as
#         the directory name where the repository is cloned into.
#       * BRANCH: (optional) The branch to clone, if not specified, the environment variable $GIT_BRANCH
#         is used.
#       * BASE_URL: (optional) The base URL used when cloning. The repository name is appended to this URL.
#         If not specified, the second specified default will be used.
# $2 - The default BASE_URL to be used when cloning.
#

while read line
do
  name=$line
  echo "Updating with config $name"
  IFS=';' read -ra UPDATE_INFO <<< "$line"
  if (( "${#UPDATE_INFO[@]}" >= 2 )); then
	name=${UPDATE_INFO[0]}
	version=${UPDATE_INFO[1]}
	branchPath=${UPDATE_INFO[2]:-$2}
	git clone --recurse-submodules -q --depth 1 $branchPath$name.git --branch $version --single-branch $name
  else
	git clone --recurse-submodules -q --depth 1 $2$name.git --branch $GIT_BRANCH --single-branch $name
  fi
  rc=$?; if [[ $rc != 0 ]]; then echo "Error in update.sh script"; exit $rc; fi
done < $1
