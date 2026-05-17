#!/bin/bash

TARGET_SERVER_ACCOUNT="kurapa@ckii.com"

CURRENT_DIR=$(pwd)
PROJECT_NAME=$(basename "$CURRENT_DIR")
TARGET_DIR="/pub/${PROJECT_NAME}"
TARGET_URL="https://${PROJECT_NAME}"

# compare current directory
if [[ "$CURRENT_DIR" != $TARGET_DIR ]]; then
	echo "Not in $TARGET_DIR, connecting to remote server..."
	password=$(jq -r '.password' /opt/config/kurapa.com.json 2>/dev/null)
	if [ -z "$password" ]; then
		read -sp "Enter password: " password
		echo
	fi
	expect <<EOF
spawn ssh $TARGET_SERVER_ACCOUNT "cd $TARGET_DIR && ./_deploy.sh $@"
expect "*assword:"
send "$password\r"
expect eof
EOF
	exit $?
fi

# set mask
umask 002

# Set branch name based on argument
if [ -z "$1" ]; then
	git fetch --all > /dev/null
	branch_name=$(git symbolic-ref --short HEAD)
else
	branch_name="$1"
fi

# update code
CURRENT_USER=$(whoami)
CURRENT_USER_GROUP=$(id -gn)
CURRENT_GIT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
CURRENT_BRANCH_COMMIT_CODE=$(git rev-parse --short=8 HEAD)

echo -e "\033[0;35mOSTYPE: $OSTYPE | current user: $CURRENT_USER | current user group: $CURRENT_USER_GROUP | current git branch: $CURRENT_GIT_BRANCH ($CURRENT_BRANCH_COMMIT_CODE)\033[0m"
git fetch --all
git reset --hard origin

# Checkout the branch and pull changes
git checkout $branch_name

remote="origin"

# update code
if [ -n "$branch_name" ]; then
	git pull origin $branch_name
else
	git pull
fi

# Pre-populate the data cache so the first visitor doesn't wait ~45s on
# Overpass. Falls through harmlessly if the refresh fails; api/data.php
# will then bootstrap inline on the first request.
if [ -x bin/refresh.sh ]; then
	./bin/refresh.sh || echo "Cache refresh failed; api/data.php will bootstrap inline on first request."
fi

echo