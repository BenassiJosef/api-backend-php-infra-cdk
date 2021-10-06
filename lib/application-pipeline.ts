import * as codepipeline from '@aws-cdk/aws-codepipeline';
import * as codepipeline_actions from '@aws-cdk/aws-codepipeline-actions'
import * as codebuild from "@aws-cdk/aws-codebuild"
import * as ecs from "@aws-cdk/aws-ecs"
import * as iam from "@aws-cdk/aws-iam"
import * as ecr from "@aws-cdk/aws-ecr"
import * as cdk from '@aws-cdk/core';

interface ApplicationPipelineProps extends cdk.StackProps {
  stageFargateService: ecs.IService
}
export class ApplicationPipeline extends cdk.Stack {
  constructor(scope: cdk.Construct, id: string, props: ApplicationPipelineProps) {
    super(scope, id, props);

    // New Ecr Repo 
    const ecrRepo  = new ecr.Repository(this, 'Php-Test-Ecr-Repo');
    ecrRepo.applyRemovalPolicy(cdk.RemovalPolicy.DESTROY)

    // Role policy for ecr build in codebuild 
    const ecrCodeBuildRole = new iam.Role(this, 'EcrCodeBuildRole', {
      assumedBy: new iam.ServicePrincipal('codebuild.amazonaws.com'),
    });

    // allow execution actions on the ecr role created above
    const executionRolePolicy =  new iam.PolicyStatement({
      effect: iam.Effect.ALLOW,
      resources: ['*'],
      actions: [
                "ecr:GetAuthorizationToken",
                "ecr:BatchCheckLayerAvailability",
                "ecr:GetDownloadUrlForLayer",
                "ecr:InitiateLayerUpload",
                "ecr:UploadLayerPart",
                "ecr:CompleteLayerUpload",
                "ecr:PutImage",
                "ecr:BatchGetImage",
                "logs:CreateLogStream",
                "logs:PutLogEvents"
            ],
    });

    ecrCodeBuildRole.addToPrincipalPolicy(executionRolePolicy);

    // pipeline

    const pipeline = new codepipeline.Pipeline(this, 'AppPipelineTest', {
      pipelineName: 'AppPipeline',
      restartExecutionOnUpdate: true
    });

    const outputSources = new codepipeline.Artifact()
    const outputBuild = new codepipeline.Artifact()
  
    // Get source to trigger pipeline
    pipeline.addStage({
      stageName: 'Source',
      actions: [
        new codepipeline_actions.GitHubSourceAction({
          actionName: 'Checkout',
          owner: 'BenassiJosef',
          repo: 'api-backend-php',
          branch:"main",
          oauthToken: cdk.SecretValue.secretsManager('github-token'),
          output: outputSources ,
          trigger: codepipeline_actions.GitHubTrigger.WEBHOOK,
        }),
      ],
    })

    //Build Docker File to obtain container image for fargate serve. store in outputbuild.
    pipeline.addStage({
      stageName: 'Build',
      actions: [
        // AWS CodePipeline action to run CodeBuild project
        new codepipeline_actions.CodeBuildAction({
          actionName: 'BuildPhpApp',
          project: new codebuild.PipelineProject(this, 'PhpPipelineProject', {
            projectName: 'PhpPipeline',
            environment: {
              buildImage: codebuild.LinuxBuildImage.AMAZON_LINUX_2_2,
              privileged: true
            },
            role: ecrCodeBuildRole ,
            environmentVariables: {
              'ECR_REPO_URI': {
                value: `${ecrRepo.repositoryUri}`
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
                    "printf '[{\"name\":\"%s\",\"imageUri\":\"%s\"}]' php-application-test $ECR_REPO_URI:$IMAGE_TAG > imagedefinitions.json",
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
    // pipeline.addStage({
    //   stageName: 'Deploy',
    //   actions: [
    //     // AWS CodePipeline action to deploy node app to ecs fargate
    //     new codepipeline_actions.EcsDeployAction({
    //       actionName: 'DeployAction',
    //       service: props.stageFargateService ,
    //       imageFile: new codepipeline.ArtifactPath(outputBuild , `imagedefinitions.json`)
    //     })
    //   ],
    // })    
  
  }
}
