#!/bin/bash

# Options:
#  -p Project name of the extension to update
#  -v Version To which version branch this extension should be upgraded (will be used with -w to form -w/-v)
#  -u Path branch path to use, default ssh://florianschmidtwelzow@gerrit.wikimedia.org:29418/mediawiki/extensions/
#  -w Version-prefix prefix used to build version branch to fecth from (default: wmf)
#  -s Submodules should submodules updated, too?
#  -d Debug Output all the spam!

branchPath="ssh://florianschmidtwelzow@gerrit.wikimedia.org:29418/mediawiki/extensions/"
wmf="wmf"
keepignore="false"
verbose="false"

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
    k)
      keepignore="true"
      ;;
    d)
      debug="true"
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      ;;
  esac
done

function coloredEcho(){
  if [ $verbose = true ] ; then
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
  fi
}
echo "Starting update of $project to $wmf/$newBranch using $branchPath."

shopt -s dotglob

coloredEcho "cd to $project" green
cd $project/

coloredEcho "Prepare git repository" green
git checkout master
git pull origin master
coloredEcho "create working dirs" green
mkdir {new,old}
cd new

coloredEcho "clone the latest revision" green
git clone -b $wmf/$newBranch --single-branch --depth 1 --recursive $branchPath$project .
coloredEcho "fetch new branch" green
git fetch
git checkout $wmf/$newBranch

coloredEcho "Get version information" green
sha1=`cat .git/refs/heads/wmf/$newBranch`

[[ "$sha1" = "" ]] && coloredEcho "$newBranch seems to be not a valid branch in wikimedias git. Exit." && exit 1;

coloredEcho "delete unnecessary files and folders" green
rm -rf .git .gitreview
if [ $keepignore = true ] ; then
  rm -rf .gitignore
fi
cd ..

coloredEcho "Move new revision" green
mv * old/
mv old/.git ./
mv old/.gitreview ./
if [ $keepignore = true ] ; then
  mv old/.gitignore ./
fi
mv old/new/* ./
rm -rf old

coloredEcho "Add to git and commit" green
git add --all
git commit -m "Update $project to $newBranch" -m "Forward to $sha1"

coloredEcho "Commit to review" green
git push origin master

coloredEcho "Finished." green
