import codebuild = require('@aws-cdk/aws-codebuild');
import ecs = require('@aws-cdk/aws-ecs')
import codepipeline = require('@aws-cdk/aws-codepipeline');
import codepipeline_actions = require('@aws-cdk/aws-codepipeline-actions');
import ecr = require('@aws-cdk/aws-ecr');
import * as cdk from '@aws-cdk/core';

interface ApiBackendPhpInfraCdkStackProps extends cdk.StackProps {
  stageFargateService: ecs.IBaseService
}
export class ApplicationPipeline extends cdk.Stack {
  constructor(scope: cdk.Construct, id: string, props: ApiBackendPhpInfraCdkStackProps) {
    super(scope, id, props);

    // All this pipeline should do is push a new container image to a fargate service in 
    // stage and prod account 
    
    const pipeline = new codepipeline.Pipeline(this, 'Pipeline', {
        pipelineName: 'php-api-stampede-example-pipeline',
        crossAccountKeys: true
    });

      // Source
      const githubAccessToken = cdk.SecretValue.secretsManager('github-token');
      const sourceOutput = new codepipeline.Artifact('SourceArtifact');
      const sourceAction = new codepipeline_actions.GitHubSourceAction({
          actionName: 'GitHubSource',
          owner: 'BenassiJosef',
          repo: 'api-backend-php',
          oauthToken: githubAccessToken,
          output: sourceOutput
      });
      // start create ecr repo 
      const imageRepo  = new ecr.Repository(this, 'EcrRepoECRStampedeTest');
      imageRepo.applyRemovalPolicy(cdk.RemovalPolicy.DESTROY)

      // end ecr rep
      
      const outputSources = new codepipeline.Artifact()
      const outputBuild = new codepipeline.Artifact()

      pipeline.addStage({
        stageName: 'Source',
        actions: [sourceAction],
      });

      //Build 

      pipeline.addStage({
        stageName: 'Build',
        actions: [
          // AWS CodePipeline action to run CodeBuild project
          new codepipeline_actions.CodeBuildAction({
            actionName: 'BuildPhpImageStampedeAction',
            // role: ECRRole,
            project: new codebuild.PipelineProject(this, 'BuildPhpImageStampedePipelineProject', {
              projectName: 'buildPhpImageStampedProjectName',
              environment: {
                buildImage: codebuild.LinuxBuildImage.AMAZON_LINUX_2_2,
                privileged: true
              },
              environmentVariables: {
                'ECR_REPO_URI': {
                  value: `${imageRepo.repositoryUri}`
                }
              },
              buildSpec: codebuild.BuildSpec.fromObject({
                version: "0.2",
                phases: {
                  pre_build: {
                    commands: [
                      'echo Logging in to Amazon ECR...',
                      'aws --version',
                      '$(aws ecr get-login --region eu-west-1 --no-include-email)',
                      'COMMIT_HASH=$(echo $CODEBUILD_RESOLVED_SOURCE_VERSION | cut -c 1-7)',
                      'IMAGE_TAG=${COMMIT_HASH:=latest}',
                      'docker login -u josefbenassi -p dogcatpig100.'
                    ]
                  },
                  build: {
                    commands: [
                      'echo Build started on `date` ',
                      'echo Building the Docker image...',
                      'docker build -t $ECR_REPO_URI:latest .',
                      'docker tag $ECR_REPO_URI:latest $ECR_REPO_URI:$IMAGE_TAG'
                    ]
                  },
                  post_build: {
                    commands: [
                      'echo Build completed on `date`',
                      'echo Pushing the Docker images...',
                      'docker push $ECR_REPO_URI:latest',
                      'docker push $ECR_REPO_URI:$IMAGE_TAG',
                      "printf '[{\"name\":\"%s\",\"imageUri\":\"%s\"}]' stampede-php-test $ECR_REPO_URI:$IMAGE_TAG > imagedefinitions.json",
                      "pwd; ls -al; cat imagedefinitions.json"
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
            input: outputSources,
            outputs: [outputBuild],
          }),
        ],
      })

      pipeline.addStage({
        stageName: 'ManualApproval',
        actions: [
       
          new codepipeline_actions.ManualApprovalAction({
            actionName: 'Approve',
          })
        ],
      })


      pipeline.addStage({
        stageName: 'Deploy-Stage-Image-To-Fargate',
        actions: [
          // AWS CodePipeline action to deploy node app to ecs fargate
          new codepipeline_actions.EcsDeployAction({
            actionName: 'DeployAction',
            service: props.stageFargateService,
            imageFile: new codepipeline.ArtifactPath(outputBuild , `imagedefinitions.json`)
          })
        ],
      })    

  }
}
