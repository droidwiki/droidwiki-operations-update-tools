#!/usr/bin/env bash

set -e

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

scriptPath="$( cd "$(dirname "$0")" ; pwd -P )"

git config --global user.email "nobody@droidwiki.org"
git config --global user.name "DroidWiki build system"

while read line
do
  name=$line
  echo "Updating with config $name"
  IFS=';' read -ra UPDATE_INFO <<< "$line"
  if (( "${#UPDATE_INFO[@]}" >= 2 )); then
	name=${UPDATE_INFO[0]}
	version=${UPDATE_INFO[1]}
	branchPath=${UPDATE_INFO[2]:-$2}
	git clone --recurse-submodules -q --depth 1 $branchPath$name --branch $version --single-branch $name
  else
	git clone --recurse-submodules -q --depth 1 $2$name --branch $GIT_BRANCH --single-branch $name
  fi
  rc=$?; if [[ $rc != 0 ]]; then echo "Error in update.sh script"; exit $rc; fi

  for patch in $scriptPath/patches/$name/*.patch ; do
    if [[ ! -e "$patch" ]]; then
      continue;
    fi
    echo "Applying patch $patch..."
    cd $name
    git am $patch || exit 1
    cd ..
  done || exit 1
done < $1
