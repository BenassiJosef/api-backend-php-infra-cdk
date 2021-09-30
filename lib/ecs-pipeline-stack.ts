import * as cdk from '@aws-cdk/core';
import * as ecs from '@aws-cdk/aws-ecs';
import { Construct, PhysicalName } from '@aws-cdk/core';
import * as codebuild from '@aws-cdk/aws-codebuild';
import * as codepipeline_actions from '@aws-cdk/aws-codepipeline-actions'
import * as ecr from "@aws-cdk/aws-ecr"
import * as codepipeline from '@aws-cdk/aws-codepipeline';
import * as s3 from "@aws-cdk/aws-s3"

/**
 * This is the Stack containing the CodePipeline definition that deploys an ECS Service.
 */
 export class PipelineStack extends cdk.Stack {
    public readonly tagParameterContainerImage: ecs.TagParameterContainerImage;
  
    constructor(scope: Construct, id: string, props?: cdk.StackProps) {
      super(scope, id, props);
  
      /* ********** ECS part **************** */
  
      // this is the ECR repository where the built Docker image will be pushed
      const appEcrRepo = new ecr.Repository(this, 'EcsDeployRepository',{
        repositoryName: PhysicalName.GENERATE_IF_NEEDED
      });
      // the build that creates the Docker image, and pushes it to the ECR repo
      const appCodeDockerBuild = new codebuild.PipelineProject(this, 'AppCodeDockerImageBuildAndPushProject', {
        environment: {
          // we need to run Docker
          privileged: true,
        },
        buildSpec: codebuild.BuildSpec.fromObject({
          version: '0.2',
          phases: {
            build: {
              commands: [
                // login to ECR first
                '$(aws ecr get-login --region $AWS_DEFAULT_REGION --no-include-email)',
                // if your application needs any build steps, they would be invoked here
  
                // build the image, and tag it with the commit hash
                // (CODEBUILD_RESOLVED_SOURCE_VERSION is a special environment variable available in CodeBuild)
                'docker build -t $REPOSITORY_URI:$CODEBUILD_RESOLVED_SOURCE_VERSION .',
              ],
            },
            post_build: {
              commands: [
                // push the built image into the ECR repository
                'docker push $REPOSITORY_URI:$CODEBUILD_RESOLVED_SOURCE_VERSION',
                // save the declared tag as an environment variable,
                // that is then exported below in the 'exported-variables' section as a CodePipeline Variable
                'export imageTag=$CODEBUILD_RESOLVED_SOURCE_VERSION',
              ],
            },
          },
          env: {
            // save the imageTag environment variable as a CodePipeline Variable
            'exported-variables': [
              'imageTag',
            ],
          },
        }),
        environmentVariables: {
          REPOSITORY_URI: {
            value: appEcrRepo.repositoryUri,
          },
        },
      });
      // needed for `docker push`
      appEcrRepo.grantPullPush(appCodeDockerBuild);
      // create the ContainerImage used for the ECS application Stack
      this.tagParameterContainerImage = new ecs.TagParameterContainerImage(appEcrRepo);
  
      const cdkCodeBuild = new codebuild.PipelineProject(this, 'CdkCodeBuildProject', {
        buildSpec: codebuild.BuildSpec.fromObject({
          version: '0.2',
          phases: {
            install: {
              commands: [
                'npm install',
              ],
            },
            build: {
              commands: [
                // synthesize the CDK code for the ECS application Stack
                'npx cdk synth --verbose',
              ],
            },
          },
          artifacts: {
            // store the entire Cloud Assembly as the output artifact
            'base-directory': 'cdk.out',
            'files': '**/*',
          },
        }),
      });
  
      /* ********** Pipeline part **************** */
  
      const appCodeSourceOutput = new codepipeline.Artifact();
      const cdkCodeSourceOutput = new codepipeline.Artifact();
      const cdkCodeBuildOutput = new codepipeline.Artifact();
      const appCodeBuildAction = new codepipeline_actions.CodeBuildAction({
        actionName: 'AppCodeDockerImageBuildAndPush',
        project: appCodeDockerBuild,
        input: appCodeSourceOutput,
      });

      new codepipeline.Pipeline(this, 'CodePipelineDeployingEcsApplication', {
        artifactBucket: new s3.Bucket(this, 'ArtifactBucket', {
          removalPolicy: cdk.RemovalPolicy.DESTROY,
        }),
        stages: [
          {
            stageName: 'Source',
            actions: [
              // this is the Action that takes the source of your application code
              // BenassiJosef/api-backend-php-infra-cdk
              new codepipeline_actions.GitHubSourceAction({
                  actionName:'AppCodeSource',
                  owner: 'BenassiJosef',
                  repo: 'api-backend-php',
                  oauthToken: cdk.SecretValue.secretsManager('my-github-token'),
                  output: appCodeSourceOutput,
                  branch: 'main',
              }),
              // this is the Action that takes the source of your CDK code
              // (which would probably include this Pipeline code as well)
              // cdkCodeSourceOutputcdkCodeSourceOutput
              // cdkCodeSourceOutput
              new codepipeline_actions.GitHubSourceAction({
                actionName:'cdkCodeSourceOutputcdkCodeSourceOutput',
                owner: 'BenassiJosef',
                repo: 'api-backend-php',
                oauthToken: cdk.SecretValue.secretsManager('github-token'),
                output: cdkCodeSourceOutput,
                branch: 'main',
            }),
            ],
          },
          {
            stageName: 'Build',
            actions: [
              appCodeBuildAction,
              new codepipeline_actions.CodeBuildAction({
                actionName: 'CdkCodeBuildAndSynth',
                project: cdkCodeBuild,
                input: cdkCodeSourceOutput,
                outputs: [cdkCodeBuildOutput],
              }),
            ],
          },
          {
            stageName: 'Deploy',
            actions: [
              new codepipeline_actions.CloudFormationCreateUpdateStackAction({
                actionName: 'CFN_Deploy',
                stackName: 'SampleEcsStackDeployedFromCodePipeline',
                // this name has to be the same name as used below in the CDK code for the application Stack
                templatePath: cdkCodeBuildOutput.atPath('EcsStackDeployedInPipeline.template.json'),
                adminPermissions: true,
                parameterOverrides: {
                  // read the tag pushed to the ECR repository from the CodePipeline Variable saved by the application build step,
                  // and pass it as the CloudFormation Parameter for the tag
                  [this.tagParameterContainerImage.tagParameterName]: appCodeBuildAction.variable('imageTag'),
                },
              }),
            ],
          },
        ],
      });
    }
  }
  