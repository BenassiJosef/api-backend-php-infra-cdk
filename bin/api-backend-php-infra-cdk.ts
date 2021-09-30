#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from '@aws-cdk/core';
import { ApiBackendPhpInfraCdkStack } from '../lib/api-backend-php-infra-cdk-stack';
import { PipelineStack } from '../lib/ecs-pipeline-stack';

const app = new cdk.App();

const pipelineStack = new PipelineStack(app, 'aws-cdk-pipeline-ecs-separate-sources',{
  env: { account: '511089130325', region: 'eu-west-1' }
});

new ApiBackendPhpInfraCdkStack(app, 'ApiBackendPhpInfraCdkStack', {
  containerImg: pipelineStack.tagParameterContainerImage,
  env: { account: '511089130325', region: 'eu-west-1' },
});

// // we supply the image to the ECS application Stack from the CodePipeline Stack
// new EcsAppStack(app, 'EcsStackDeployedInPipeline', {
//   image: pipelineStack.tagParameterContainerImage,
// });
