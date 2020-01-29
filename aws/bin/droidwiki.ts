#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from '@aws-cdk/core';
import { BackupStack } from '../lib/backup-stack';
import {BillingStack} from "../lib/billing-stack";

const app = new cdk.App();
let droidwikiAccountProps = {
    env: {
        account: '011363899567',
        region: 'eu-west-1'
    }
};
new BackupStack(app, 'BackupStack', droidwikiAccountProps);
new BillingStack(app, 'BillingStack', droidwikiAccountProps);
