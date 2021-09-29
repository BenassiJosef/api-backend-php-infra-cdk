#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from '@aws-cdk/core';
import { ApiBackendPhpInfraCdkStack } from '../lib/api-backend-php-infra-cdk-stack';

const app = new cdk.App();
new ApiBackendPhpInfraCdkStack(app, 'ApiBackendPhpInfraCdkStack', {
  env: { account: '511089130325', region: 'eu-west-1' },
});
