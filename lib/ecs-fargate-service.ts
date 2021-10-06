import { Vpc } from '@aws-cdk/aws-ec2';
import { Cluster} from '@aws-cdk/aws-ecs';
import { ApplicationLoadBalancedFargateService } from '@aws-cdk/aws-ecs-patterns';
import ecs = require('@aws-cdk/aws-ecs')
import * as ecr from "@aws-cdk/aws-ecr";
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
      const ecrRepoName = "application-pipeline-test-apppipeline-phptestecrrepo73b958db-rq2tk4yfdllp"
      const ecrRepo = ecr.Repository.fromRepositoryAttributes(
        this,
        ecrRepoName,
        {
          repositoryArn: `arn:aws:ecr:eu-west-1:511089130325:repository/${ecrRepoName}`,
          repositoryName: ecrRepoName,
        }
      );
      this.fargateInstance = new ApplicationLoadBalancedFargateService(this, 'Service', {
        cluster,
        taskImageOptions: {image: ecs.ContainerImage.fromEcrRepository(ecrRepo)}
      });

      this.fargateServiceArn = this.fargateInstance.service.serviceArn;
      this.fargateServiceCluster = this.fargateInstance.cluster;
    }
  }


