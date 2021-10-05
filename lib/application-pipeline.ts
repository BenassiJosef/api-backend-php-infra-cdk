import * as codepipeline from '@aws-cdk/aws-codepipeline';
import * as codepipeline_actions from '@aws-cdk/aws-codepipeline-actions'
import * as codebuild from "@aws-cdk/aws-codebuild"
import * as ecs from "@aws-cdk/aws-ecs"
import * as cdk from '@aws-cdk/core';

interface ApplicationPipelineProps extends cdk.StackProps {
  stageFargateService: ecs.IBaseService
}
export class ApplicationPipeline extends cdk.Stack {
  constructor(scope: cdk.Construct, id: string, props: ApplicationPipelineProps) {
    super(scope, id, props);

    const pipeline = new codepipeline.Pipeline(this, 'AppPipelineTest', {
      pipelineName: 'AppPipeline',
    });

    const sourceArtifact = new codepipeline.Artifact();
    const outputBuild = new codepipeline.Artifact()
  
    pipeline.addStage({
      stageName: 'Source',
      actions: [
        new codepipeline_actions.GitHubSourceAction({
          actionName: 'Checkout',
          owner: 'BenassiJosef',
          repo: 'api-backend-php',
          branch:"main",
          oauthToken: cdk.SecretValue.secretsManager('github-token'),
          output: sourceArtifact ,
          trigger: codepipeline_actions.GitHubTrigger.WEBHOOK,
        }),
      ],
    })

    pipeline.addStage({
      stageName: 'Build',
      actions: [
        // AWS CodePipeline action to run CodeBuild project
        new codepipeline_actions.CodeBuildAction({
          actionName: 'BuildNodeApp',
          // role: ECRRole,
          project: new codebuild.PipelineProject(this, 'BuildWebsite', {
            projectName: 'ecsNodeAppTest',
            environment: {
              buildImage: codebuild.LinuxBuildImage.AMAZON_LINUX_2_2,
              privileged: true
            },
            buildSpec: codebuild.BuildSpec.fromObject({
              version: "0.2",
              phases: {
                pre_build: {
                  commands: [
                    'echo Logging in to Amazon ECR...',
                  ]
                },
                build: {
                  commands: [
                    'echo Build started on `date` ',
                    'echo Building the Docker image...'
                  ]
                },
                post_build: {
                  commands: [
                    'echo Build completed on `date`',
                    'echo Pushing the Docker images...'
                  ]
                }
              },
              artifacts: {
                files: [
                  'imagedefinitions.json'
                ]
              }
            }),
          }),
          input: sourceArtifact,
          outputs: [outputBuild],
        }),
      ],
    })

  
  }
}
