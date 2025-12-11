#!/bin/bash
cd ../../../
bash "nvm use"
npx grunt amd --files="public/local/deepler/amd/src/*.js,public/local/deepler/amd/src/local/*.js" --force;
cd -
