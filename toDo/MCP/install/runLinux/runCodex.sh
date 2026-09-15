#!/usr/bin/env bash

read -r -s -p "Enter SOLNUH_TOKEN: " SOLNUH_TOKEN
echo

if [ -z "$SOLNUH_TOKEN" ]; then
    echo "SOLNUH_TOKEN is empty"
    exit 1
fi
export SOLNUH_TOKEN

echo
echo "Checking environment..."

node -e "console.log(process.env.SOLNUH_TOKEN ? 'Node sees SOLNUH_TOKEN: OK' : 'Node DOES NOT see SOLNUH_TOKEN')"

echo
echo "Starting Codex..."
echo

exec codex
