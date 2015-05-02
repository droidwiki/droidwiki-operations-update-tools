#!/bin/bash

newBranch=$2
project=$1

if [ -z "$3" ]
  then
	branchPath="ssh://florianschmidtwelzow@gerrit.wikimedia.org:29418/mediawiki/extensions/"
  else
	branchPath=$3
fi

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

coloredEcho "Starting update of $project to wmf/$newBranch" green

#exec ssh-agent bash
shopt -s dotglob

coloredEcho "cd to $project" green
cd $project/
coloredEcho "Prepare git repository" green
git checkout master
git pull origin master
git checkout -b upgrade/$newBranch origin/master
coloredEcho "create working dirs" green
mkdir {new,old}
cd new

coloredEcho "clone the latest revision" green
git clone $branchPath$project .
coloredEcho "fetch new branch" green
git fetch
git checkout wmf/$newBranch

coloredEcho "Update submodules" green
git submodule update --init

coloredEcho "Get version information" green
sha1=`cat .git/refs/heads/wmf/$newBranch`

coloredEcho "delete unnecessary files and folders" green
rm -rf .git .gitreview
cd ..

coloredEcho "Move new revision" green
mv * old/
mv old/.git ./
mv old/.gitreview ./
mv old/new/* ./
rm -rf old

coloredEcho "Add to git and commit" green
git add --all
git commit -m "Update $project to $newBranch" -m "Forward to $sha1"

coloredEcho "Commit to review" green
git review

coloredEcho "Finished." green
