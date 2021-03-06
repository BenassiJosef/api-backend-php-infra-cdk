import { Vpc } from '@aws-cdk/aws-ec2';
import { Cluster} from '@aws-cdk/aws-ecs';
import { ApplicationLoadBalancedFargateService } from '@aws-cdk/aws-ecs-patterns';
import ecs = require('@aws-cdk/aws-ecs')
import * as ecr from "@aws-cdk/aws-ecr";
import { DockerImageAsset } from '@aws-cdk/aws-ecr-assets';
import  *  as asassetsecr from '@aws-cdk/aws-ecr-assets';
import * as cdk from '@aws-cdk/core';
import * as path from "path"

interface EcsFargateServiceProps extends cdk.StackProps {
    clusterName: string
  }

export class EcsFargateService extends cdk.Stack {
    public readonly fargateInstance: ApplicationLoadBalancedFargateService;
    public readonly fargateServiceArn: any;
    public readonly fargateServiceCluster: any;
    constructor(scope: cdk.Construct, id: string, props: EcsFargateServiceProps) {
      super(scope, id, props);

      const vpc = new Vpc(this, 'VPC', { 
        maxAzs: 2 
      });
      const cluster = new Cluster(this, 'Cluster', {
        clusterName: props.clusterName,
        vpc

      });

      const assest = new DockerImageAsset(this,'phpApp',{
        directory: path.join(__dirname,"..","php_app")
      })

      this.fargateInstance = new ApplicationLoadBalancedFargateService(this, 'Service', {
        cluster,
        taskImageOptions: {image: ecs.EcrImage.fromDockerImageAsset(assest) },
        desiredCount:1
      });

      this.fargateServiceArn = this.fargateInstance.service.serviceArn;
      this.fargateServiceCluster = this.fargateInstance.cluster;
    }
  }


