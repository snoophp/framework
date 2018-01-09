#!/bin/sh
echo "starting ..."
DATE=$(date +%Y-%m-%dT%H:%M:%S)
LOG_ID=$1
LOG_EXT="log"
LOG_DIR=$(dirname "$0")"/log/webhook"
LOG_FILE=$LOG_DIR"/"$1"."$LOG_EXT

echo "/********************************************"	>> $LOG_FILE
echo " * id:   $LOG_ID"									>> $LOG_FILE
echo " * time: $DATE"									>> $LOG_FILE
echo " ********************************************/"	>> $LOG_FILE

echo "\n"												>> $LOG_FILE

echo "/*"												>> $LOG_FILE
echo " * Update repository"								>> $LOG_FILE
echo " */"												>> $LOG_FILE
git fetch origin										>> $LOG_FILE
git reset --hard origin/$2								>> $LOG_FILE
git clean -df											>> $LOG_FILE

echo "\n"												>> $LOG_FILE

echo "/*"												>> $LOG_FILE
echo " * Working directory"								>> $LOG_FILE
echo " */"												>> $LOG_FILE

ls -l													>> $LOG_FILE
echo "completed!"