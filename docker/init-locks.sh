#!/bin/sh
# Pre-create empty lock files so Docker can bind-mount them as files (not directories).
for service in php83-sf6-low php83-sf6-high php83-sf7-low php83-sf7-high \
               php84-sf6-high php84-sf7-high php84-sf8-high php85-sf8-high; do
    touch "docker/composer-locks/${service}.lock"
done
