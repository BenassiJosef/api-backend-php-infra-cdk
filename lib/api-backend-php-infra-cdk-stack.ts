import * as cdk from '@aws-cdk/core';
import { CodePipeline, CodePipelineSource, ShellStep } from "@aws-cdk/pipelines";
import { pipeline } from 'stream';
import {EcsAppStack} from "./ecs-app-stack"

export interface ApiBackendPhpInfraCdkStackProps extends cdk.StackProps {
  containerImg: any
}


class EcsAppStage extends cdk.Stage {
  constructor(scope: cdk.Construct, id: string, props: ApiBackendPhpInfraCdkStackProps) {
    super(scope, id, props);
    new EcsAppStack(this, 'an-ecs-app',{
      containerImg:props.containerImg
    });
  }
}

export class ApiBackendPhpInfraCdkStack extends cdk.Stack {
  constructor(scope: cdk.Construct, id: string, props: ApiBackendPhpInfraCdkStackProps) {
    super(scope, id, props);

     const pipeline = new CodePipeline(this, 'Pipeline', {
      // The pipeline name
      pipelineName: 'MyServicePipeline',
      
      crossAccountKeys: true,
      
       // How it will be built and synthesized
       synth: new ShellStep('Synth', {
         // Where the source can be found
         input: CodePipelineSource.gitHub('BenassiJosef/api-backend-php-infra-cdk', 'main'),
         
         // Install dependencies, build and run cdk synth
         commands: [
           'npm ci',
           'npm run build',
           'npx cdk synth'
         ],
       }),
    });

    pipeline.addStage(new EcsAppStage(this, "Stage-Deployment",{
      containerImg: props.containerImg,
      env: {
        account: '069793231881',
        region: 'eu-west-1',
      }
    }))
    pipeline.addStage(new EcsAppStage(this, "Prod-Deployment",{
      containerImg: props.containerImg,
      env: {
        account: '414477822279',
        region: 'eu-west-1',
      }
    }))
  }
}
