#!/bin/bash
# Google Jules Environment Setup Script for OurAzerothServer
# Installs compiling dependencies to let Jules test builds in the cloud sandbox VM.

echo "=== 1. Updating APT Package Lists ==="
sudo apt-get update

echo "=== 2. Installing Compilers and Build Tools ==="
sudo apt-get install -y cmake make gcc g++ clang

echo "=== 3. Installing AzerothCore Dependency Libraries ==="
sudo apt-get install -y \
  libssl-dev \
  libbz2-dev \
  libreadline-dev \
  libncurses-dev \
  libboost-all-dev \
  mariadb-client \
  libmariadb-dev-compat \
  libmariadb-dev

echo "=== 4. Creating Build Directory and Running Test Configuration ==="
# Jules mounts the repository automatically under /app
mkdir -p /app/build
cd /app/build

# Run a quick CMake config check to ensure compiler tools are validated
cmake .. -DTOOLS=0 -DSERVERS=0 -DSCRIPTS=0

echo "=== Setup Completed Successfully! Environment is ready for Snapshot. ==="
