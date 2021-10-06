#!/usr/bin/env node
import { ApplicationPipeline} from '../lib/application-pipeline';
import {EcsFargateService} from "../lib/ecs-fargate-service"
import * as cdk from '@aws-cdk/core';
import ecs = require('@aws-cdk/aws-ecs')
import { CodePipeline, CodePipelineSource, ShellStep } from '@aws-cdk/pipelines';
const app = new cdk.App();
interface AppStageProps extends cdk.StackProps {
  serviceName:string,
  clusterName: string
}
class AppStage extends cdk.Stage {
  public readonly iBaseServiceInstance: any;

  constructor(scope: cdk.Construct, id: string, props: AppStageProps ) {
    super(scope, id, props);

    const fargateResources = new EcsFargateService(this, props.serviceName,{
      clusterName:props.clusterName
    })
    this.iBaseServiceInstance = {
      cluster : fargateResources.fargateServiceCluster,
      serviceArn: fargateResources.fargateServiceArn
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
   
    // console.log("ibaseserviceinstance: ", infraFargateStage.iBaseServiceInstance)
    
    // phpCdkPipeline.addStage(new ApplicationPipelineStage(app,"Application-Pipeline-Test",{
    //   stageFargateService: infraFargateStage.iBaseServiceInstance
    // }))
  }
} 

new CdkPipeline(app,"CdkPipeline",{
  env: { account: '511089130325', region: 'eu-west-1' },
})

