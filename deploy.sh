#!/bin/bash
# wyattxxxcole.com Deployment Script
# Syncs local changes to production server via FTP

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

FTP_HOST="162.246.20.155"
FTP_USER="wyattxxx"
FTP_PASS='%W@3qBqB'
REMOTE_PATH="/domains/wyattxxxcole.com/public_html"
LOCAL_PATH="$(dirname "$0")"

echo -e "${YELLOW}wyattxxxcole.com Deployment Script${NC}"
echo "===================================="

echo -e "Deploying to ${GREEN}$FTP_HOST${NC}..."

lftp -u "$FTP_USER","$FTP_PASS" -e "
    set ssl:verify-certificate no
    mirror --reverse --verbose --parallel=4 \
        --exclude .git/ \
        --exclude .idea/ \
        --exclude node_modules/ \
        --exclude *.log \
        '$LOCAL_PATH' '$REMOTE_PATH'
    quit
" "ftp://$FTP_HOST"

if [ $? -eq 0 ]; then
    echo -e "${GREEN}Deployment successful!${NC}"
else
    echo -e "${RED}Deployment failed!${NC}"
    exit 1
fi
