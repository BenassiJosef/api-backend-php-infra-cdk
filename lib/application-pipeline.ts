import codebuild = require('@aws-cdk/aws-codebuild');
import ecs = require('@aws-cdk/aws-ecs')
import codedeploy = require('@aws-cdk/aws-codedeploy');
import codepipeline = require('@aws-cdk/aws-codepipeline');
import actions = require('@aws-cdk/aws-codepipeline-actions');
import ecr = require('@aws-cdk/aws-ecr');
import iam = require('@aws-cdk/aws-iam');
import ecs_patterns = require('@aws-cdk/aws-ecs-patterns')
import * as cdk from '@aws-cdk/core';

interface ApiBackendPhpInfraCdkStackProps extends cdk.StackProps {
  stageFargateService: ecs.IBaseService
}
export class ApplicationPipeline extends cdk.Stack {
  constructor(scope: cdk.Construct, id: string, props: ApiBackendPhpInfraCdkStackProps) {
    super(scope, id, props);

    // // All this pipeline should do is push a new container image to a fargate service in 
    // // stage and prod account 
    
    // const pipeline = new codepipeline.Pipeline(this, 'Pipeline', {
    //     pipelineName: 'php-api-stampede-example-pipeline',
    //     crossAccountKeys: true
    // });

    //   // Source
    //   const githubAccessToken = cdk.SecretValue.secretsManager('github-token');
    //   const sourceOutput = new codepipeline.Artifact('SourceArtifact');
    //   const sourceAction = new actions.GitHubSourceAction({
    //       actionName: 'GitHubSource',
    //       owner: 'BenassiJosef',
    //       repo: 'api-backend-php',
    //       oauthToken: githubAccessToken,
    //       output: sourceOutput
    //   });
    //   // create ecr repo 
    //   const baseImageRepo  = new ecr.Repository(this, 'EcrRepoECRTEST');
    //   baseImageRepo.applyRemovalPolicy(cdk.RemovalPolicy.DESTROY)
      
    //   const baseImageOutput = new codepipeline.Artifact('BaseImage');

    //   pipeline.addStage({
    //     stageName: 'Source',
    //     actions: [sourceAction],
    //   });

    //   pipeline.addStage({
    //     stageName: 'Deploy-Stage',
    //     actions: [
    //       // AWS CodePipeline action to deploy node app to ecs fargate
    //       new actions.EcsDeployAction({
    //         actionName: 'DeployAction',
    //         service: props.stageFargateService,
    //         imageFile: new codepipeline.ArtifactPath(outputBuild , `imagedefinitions.json`)
    //       })
    //     ],
    //   })    

  }
}
