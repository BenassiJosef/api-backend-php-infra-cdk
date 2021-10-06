import { Vpc } from '@aws-cdk/aws-ec2';
import { Cluster} from '@aws-cdk/aws-ecs';
import { ApplicationLoadBalancedFargateService } from '@aws-cdk/aws-ecs-patterns';
import ecs = require('@aws-cdk/aws-ecs')
import * as cdk from '@aws-cdk/core';

interface EcsFargateServiceProps extends cdk.StackProps {
    clusterName: string
  }

export class EcsFargateService extends cdk.Stack {
    public readonly fargateInstance: ApplicationLoadBalancedFargateService;
    public readonly fargateServiceArn: any;
    public readonly fargateServiceCluster: any;
    constructor(scope: cdk.Construct, id: string, props: EcsFargateServiceProps) {
      super(scope, id, props);

      const vpc = new Vpc(this, 'VPC', { maxAzs: 2 });
      const cluster = new Cluster(this, 'Cluster', {
        clusterName: props.clusterName,
        vpc
      });
      this.fargateInstance = new ApplicationLoadBalancedFargateService(this, 'Service', {
        cluster,
        taskImageOptions: {image: ecs.ContainerImage.fromRegistry("amazon/amazon-ecs-sample")}
      });

      this.fargateServiceArn = this.fargateInstance.service.serviceArn;
      this.fargateServiceCluster = this.fargateInstance.cluster;
    }
  }


