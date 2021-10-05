#!/usr/bin/env node
import * as cdk from '@aws-cdk/core';
import * as ecs from "@aws-cdk/aws-ecs"
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

interface ApplicationPipelineProps extends cdk.StackProps {
  stageFargateService: ecs.IBaseService
}
class ApplicationPipelineStage extends cdk.Stage {

  constructor(scope: cdk.Construct, id: string, props: ApplicationPipelineProps ) {
    super(scope, id, props);

    new ApplicationPipeline(this,"AppPipeline",{
      env: { account: '511089130325', region: 'eu-west-1' },
      stageFargateService:props.stageFargateService
    })
  }
}


class CdkPipeline extends cdk.Stack {
  constructor(scope: cdk.Construct, id: string, props?: cdk.StackProps) {
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

    const infraFargateStage = new AppStage(app,'Deploy-AppStage-Stage-Fargate',{
      env:{account:'069793231881' ,region:'eu-west-1'},
      serviceName: "Stage-Fargate",
      clusterName:"stage-cluster-stampedeExample"
    })
    phpCdkPipeline.addStage(infraFargateStage)

    phpCdkPipeline.addStage(new ApplicationPipelineStage(app,"Application-Pipeline-Test",{
      stageFargateService: infraFargateStage.fI
    }))

  }
}

new CdkPipeline(app,"CdkPipeline",{
  env: { account: '511089130325', region: 'eu-west-1' },
})

