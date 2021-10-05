#!/usr/bin/env node
import 'source-map-support/register';
import * as cdk from '@aws-cdk/core';
import { ApplicationPipeline } from '../lib/application-pipeline';
import {EcsFargateService} from "../lib/ecs-fargate-service"
import { CodePipeline, CodePipelineSource, ShellStep } from '@aws-cdk/pipelines';

const app = new cdk.App();

interface AppStageProps extends cdk.StackProps {
  serviceName:string,
  clusterName: string
}
class AppStage extends cdk.Stage {
  public readonly fI: any;
  public readonly stageFargateInstance: any;
  constructor(scope: cdk.Construct, id: string, props: AppStageProps ) {
    super(scope, id, props);

    const stageFargateInstance = new EcsFargateService(this, props.serviceName,{
      clusterName:props.clusterName
    })
    this.fI = {
      cluster:stageFargateInstance.fargateInstance.service.cluster,
      serviceArn:stageFargateInstance.fargateInstance.service.serviceArn,
      serviceName: stageFargateInstance.fargateInstance.service.serviceName,
      stack: stageFargateInstance.fargateInstance.service.stack,
      env:stageFargateInstance.fargateInstance.service.env,
      node:stageFargateInstance.fargateInstance.service.node
    }
  }
}

const stageFargateInstance = new AppStage(app,'AppStage-Stage-Fargate',{
  env:{account:'069793231881' ,region:'eu-west-1'},
  serviceName: "Stage-Fargate",
  clusterName:"stage-cluster-stampedeExample"
})


interface CdkPipelineProps extends cdk.StackProps {
  fargateInstance:any;
}

class CdkPipeline extends cdk.Stack {
  constructor(scope: cdk.Construct, id: string, props?: CdkPipelineProps) {
    super(scope, id, props);
    const phpCdkPipeline = new CodePipeline(this, 'PhpCdkInfraPipeline', {
      // The pipeline name
      pipelineName: 'MyServicePipeline',
      crossAccountKeys:true,
    
       // How it will be built and synthesized
       synth: new ShellStep('Synth', {
         // Where the source can be found
         input: CodePipelineSource.gitHub('BenassiJosef/api-backend-php-infra-cdk', 'main', {
          authentication: cdk.SecretValue.secretsManager('github-token'),
        }),
         
         // Install dependencies, build and run cdk synth
         commands: [
           'npm ci',
           'npm run build',
           'npx cdk synth'
         ],
       }),
    });

    phpCdkPipeline.addStage(new AppStage(app,'Deploy-AppStage-Stage-Fargate',{
      env:{account:'069793231881' ,region:'eu-west-1'},
      serviceName: "Stage-Fargate",
      clusterName:"stage-cluster-stampedeExample"
    })
    )

  }

}

new CdkPipeline(app,"CdkPipeline",{
  env: { account: '511089130325', region: 'eu-west-1' },
  fargateInstance:stageFargateInstance 
})

new ApplicationPipeline(app, 'PhpApplicationPipeline', {
  env: { account: '511089130325', region: 'eu-west-1' },
  stageFargateService:stageFargateInstance.fI
});
