import { Vpc } from '@aws-cdk/aws-ec2';
import { Cluster} from '@aws-cdk/aws-ecs';
import { ApplicationLoadBalancedFargateService } from '@aws-cdk/aws-ecs-patterns';
import ecs = require('@aws-cdk/aws-ecs')
import * as iam from "@aws-cdk/aws-iam";
import * as cdk from '@aws-cdk/core';

  
/**
 * This is the Stack containing a simple ECS Service that uses the provided ContainerImage.
 */
export interface EcsAppStackProps extends cdk.StackProps {
  readonly image: ecs.ContainerImage;
}
export class EcsAppStack extends cdk.Stack {

    constructor(scope: cdk.Construct, id: string, props: EcsAppStackProps) {
      super(scope, id, props);

      const vpc = new Vpc(this, 'VPC', { maxAzs: 2 });
      const cluster = new Cluster(this, 'Cluster', {
        vpc
      });

      const taskDefinition = new ecs.TaskDefinition(this, 'TaskDefinition', {
        compatibility: ecs.Compatibility.FARGATE,
        cpu: '1024',
        memoryMiB: '2048'
      });
      const container = taskDefinition.addContainer('AppContainer', {
        image: props.image,
      });

      container.addPortMappings({
        containerPort: 8080,
        protocol: ecs.Protocol.TCP
      });
      new ApplicationLoadBalancedFargateService(this, 'Service', {
        cluster,
        taskDefinition
      });
    }
}



