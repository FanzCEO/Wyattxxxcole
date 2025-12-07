#!/bin/bash
# wyattxxxcole.com Pull Script
# Syncs server changes to local via FTP

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

FTP_HOST="162.246.20.155"
FTP_USER="wyattxxx"
FTP_PASS='%W@3qBqB'
REMOTE_PATH="/domains/wyattxxxcole.com/public_html"
LOCAL_PATH="$(dirname "$0")"

echo -e "${YELLOW}wyattxxxcole.com Pull Script${NC}"
echo "=============================="

echo -e "Pulling from ${GREEN}$FTP_HOST${NC}..."

lftp -u "$FTP_USER","$FTP_PASS" -e "
    set ssl:verify-certificate no
    mirror --verbose --parallel=4 \
        --exclude .git/ \
        --exclude .idea/ \
        --exclude node_modules/ \
        --exclude *.log \
        '$REMOTE_PATH' '$LOCAL_PATH'
    quit
" "ftp://$FTP_HOST"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}Pull successful!${NC}"
else
    echo -e "${RED}Pull failed!${NC}"
    exit 1
fi
