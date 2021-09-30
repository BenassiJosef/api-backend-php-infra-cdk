import * as cdk from '@aws-cdk/core';
import * as ecs from '@aws-cdk/aws-ecs';
import { Construct } from '@aws-cdk/core';
import * as ec2 from '@aws-cdk/aws-ec2';
/**
 * These are the construction properties for {@link EcsAppStack}.
 * They extend the standard Stack properties,
 * but also require providing the ContainerImage that the service will use.
 * That Image will be provided from the Stack containing the CodePipeline.
 */
 export interface EcsAppStackProps extends cdk.StackProps {
    readonly containerImg: ecs.ContainerImage;
  }
  
  /**
   * This is the Stack containing a simple ECS Service that uses the provided ContainerImage.
   */
  export class EcsAppStack extends cdk.Stack {
    constructor(scope: Construct, id: string, props: EcsAppStackProps) {
      super(scope, id, props);
  
      const taskDefinition = new ecs.TaskDefinition(this, 'TaskDefinition', {
        compatibility: ecs.Compatibility.FARGATE,
        cpu: '1024',
        memoryMiB: '2048'
      });
      taskDefinition.addContainer('AppContainer', {
        image: props.containerImg,
      });
      new ecs.FargateService(this, 'EcsService', {
        taskDefinition,
        cluster: new ecs.Cluster(this, 'Cluster', {
          vpc: new ec2.Vpc(this, 'Vpc', {
            maxAzs: 1,
          }),
        }),
      });
    }
  }