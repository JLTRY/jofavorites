#!/bin/bash

# Get the release version number
FILE=$1
echo replace tabs for file $FILE
expand -t4 -i $FILE | sponge $FILE