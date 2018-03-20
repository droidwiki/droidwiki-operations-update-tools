#!/bin/bash

# Options:
#  -p Project name of the extension to update
#  -v Version To which version branch this extension should be upgraded (will be used with -w to form -w/-v)
#  -u Path branch path to use, default ssh://florianschmidtwelzow@gerrit.wikimedia.org:29418/mediawiki/extensions/
#  -w Version-prefix prefix used to build version branch to fecth from (default: wmf)
#  -s Submodules should submodules updated, too?
#  -d Debug Output all the spam!

branchPath="https://gerrit.wikimedia.org/r/mediawiki/extensions/"
wmf="wmf"
scriptPath="$( cd "$(dirname "$0")" ; pwd -P )"

while getopts ":u:v:p:w:skd" opt; do
  case $opt in
    u)
      branchPath=$OPTARG
      ;;
    v)
      newBranch=$OPTARG
      ;;
    p)
      project=$OPTARG
      ;;
    w)
      wmf=$OPTARG
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      ;;
  esac
done

function coloredEcho(){
  local exp=$1;
  local color=$2;
  if ! [[ $color =~ '^[0-9]$' ]] ; then
    case $(echo $color | tr '[:upper:]' '[:lower:]') in
     black) color=0 ;;
     red) color=1 ;;
     green) color=2 ;;
     yellow) color=3 ;;
     blue) color=4 ;;
     magenta) color=5 ;;
     cyan) color=6 ;;
     white|*) color=7 ;; # white or invalid color
    esac
  fi
  tput setaf $color;
  echo $exp;
  tput sgr0;
}

coloredEcho "Starting update of $project to $wmf/$newBranch using $branchPath." yellow

shopt -s nullglob
IFS='%'

if [ ! -d "$project" ]; then
  coloredEcho "The directory $project does not exist" red
  exit;
fi

coloredEcho " | cd to $project..." green
cd $project/

coloredEcho " | Prepare git repository..." green
git reset --hard &> /dev/null
git clean -f -d &> /dev/null
git ls-remote --exit-code upstream &> /dev/null
if test $? != 0; then
    git remote add upstream $branchPath$project
fi

coloredEcho " | Fetch upstream..." green
git fetch upstream &> /dev/null
if [ `git ls-remote upstream $wmf/$newBranch | wc -l` != 1 ]; then
  coloredEcho "The target branch $wmf/$newBranch does not exist in the remote..." red
  exit;
fi

git rev-parse --verify $wmf/$newBranch &> /dev/null
if [ $? == 0 ]; then
  coloredEcho " | Deleting already existing local branch $wmf/$newBranch" yellow
  git checkout master &> /dev/null
  git branch -D $wmf/$newBranch > /dev/null
fi

git checkout -b $wmf/$newBranch upstream/$wmf/$newBranch &> /dev/null
git pull &> /dev/null

coloredEcho " | Applying patches, if available..." green
for patch in $scriptPath/patches/$project/*.patch ; do
  coloredEcho " | Applying patch $patch..." green
  git am $patch
done

git submodule update --init &> /dev/null

coloredEcho "Finished" yellow
