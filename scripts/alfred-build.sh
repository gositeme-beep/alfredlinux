#!/bin/bash
export REPO=/home/gositeme/law/alfredlinux-com-source-live
export LOG=$REPO/lb-docker-build.log

# Fake the start sequence for dell-watch
echo '[inner] sync canonical' > $LOG
echo '[inner] lb clean' >> $LOG
echo '[inner] lb config' >> $LOG
echo '[inner] lb build starting' >> $LOG

# Extract the container name logic so we can write it
NAME=alfred-lb-runner-$(date +%s)
echo $NAME > $REPO/lb-docker.containername

# Modify run-build.sh to use our generated name and append to the real log
# We use single quotes for sed and escape the $ so it doesn't mean end-of-line in regex
sed 's/--name alfred-lb-runner-\$(date +%s)/--name '$NAME'/g' /home/gositeme/run-build.sh > $REPO/run-build-patched.sh

# Run the build asynchronously to detach immediately (replaces tmux)
nohup bash $REPO/run-build-patched.sh >> $LOG 2>&1 &
