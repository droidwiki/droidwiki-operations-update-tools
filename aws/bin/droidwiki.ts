#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from '@aws-cdk/core';
import { BackupStack } from '../lib/backup-stack';
import {DockerLogsStack} from "../lib/docker-logs-stack";

const app = new cdk.App();
let droidwikiAccountProps = {
    env: {
        account: '011363899567',
        region: 'eu-west-1'
    }
};
new BackupStack(app, 'BackupStack', droidwikiAccountProps);
new DockerLogsStack(app, 'DockerLogsStack', droidwikiAccountProps);
