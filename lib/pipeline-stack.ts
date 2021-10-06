import * as codepipeline from '@aws-cdk/aws-codepipeline';
import * as codepipeline_actions from '@aws-cdk/aws-codepipeline-actions'
import * as codebuild from "@aws-cdk/aws-codebuild"
import * as ecs from "@aws-cdk/aws-ecs"
import * as ecr from "@aws-cdk/aws-ecr";
import * as s3 from "@aws-cdk/aws-s3"
import * as iam from "@aws-cdk/aws-iam"
import * as cdk from '@aws-cdk/core';

/**
 * This is the Stack containing the CodePipeline definition that deploys an ECS Service.
 */

 export interface PipelineStackProps extends cdk.StackProps {
  readonly ecsStackName:string;
}

 export class PipelineStack extends cdk.Stack {
  public readonly tagParameterContainerImage: ecs.TagParameterContainerImage;

  constructor(scope: cdk.Construct, id: string, props: PipelineStackProps) {
    super(scope, id, props);

    /* ********** ECS part **************** */

    // this is the ECR repository where the built Docker image will be pushed
    const appEcrRepo = new ecr.Repository(this, 'EcsDeployRepository',{
      repositoryName: 'pipeline-php-app-test-repo',
    });


    const EcrRole : iam.IRole = new iam.Role(this, 'EcrBuildRole', {
      assumedBy: new iam.ServicePrincipal('codebuild.amazonaws.com'),
    });

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

    EcrRole.addToPrincipalPolicy(executionRolePolicy);
    
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
    //appEcrRepo.grantPullPush(appCodeDockerBuild);
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
      role: EcrRole
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
              new codepipeline_actions.GitHubSourceAction({
                actionName: 'Checkout-PhpApp',
                owner: 'BenassiJosef',
                repo: 'api-backend-php',
                branch:"main",
                oauthToken: cdk.SecretValue.secretsManager('github-token'),
                output:  appCodeSourceOutput ,
                trigger: codepipeline_actions.GitHubTrigger.WEBHOOK,
              }),
            // this is the Action that takes the source of your CDK code
            // (which would probably include this Pipeline code as well)
            new codepipeline_actions.GitHubSourceAction({
              actionName: 'Checkout-CdkApp',
              owner: 'BenassiJosef',
              repo: 'api-backend-php-infra-cdk',
              branch:"main",
              oauthToken: cdk.SecretValue.secretsManager('github-token'),
              output:  cdkCodeSourceOutput ,
              trigger: codepipeline_actions.GitHubTrigger.WEBHOOK,
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
              role: EcrRole
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
              templatePath: cdkCodeBuildOutput.atPath(`${props.ecsStackName}.template.json`),
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

































// interface ApplicationPipelineProps extends cdk.StackProps {
//   stageFargateService: ecs.IBaseService
// }
// export class ApplicationPipeline extends cdk.Stack {
//   constructor(scope: cdk.Construct, id: string, props: ApplicationPipelineProps) {
//     super(scope, id, props);

//     // New Ecr Repo 
//     const ecrRepo  = new ecr.Repository(this, 'Php-Test-Ecr-Repo');
//     ecrRepo.applyRemovalPolicy(cdk.RemovalPolicy.DESTROY)

//     // Role policy for ecr build in codebuild 
//     const ecrCodeBuildRole = new iam.Role(this, 'EcrCodeBuildRole', {
//       assumedBy: new iam.ServicePrincipal('codebuild.amazonaws.com'),
//     });

//     // allow execution actions on the ecr role created above
//     const executionRolePolicy =  new iam.PolicyStatement({
//       effect: iam.Effect.ALLOW,
//       resources: ['*'],
//       actions: [
//                 "ecr:GetAuthorizationToken",
//                 "ecr:BatchCheckLayerAvailability",
//                 "ecr:GetDownloadUrlForLayer",
//                 "ecr:InitiateLayerUpload",
//                 "ecr:UploadLayerPart",
//                 "ecr:CompleteLayerUpload",
//                 "ecr:PutImage",
//                 "ecr:BatchGetImage",
//                 "logs:CreateLogStream",
//                 "logs:PutLogEvents"
//             ],
//     });

//     ecrCodeBuildRole.addToPrincipalPolicy(executionRolePolicy);

//     // pipeline

//     const pipeline = new codepipeline.Pipeline(this, 'AppPipelineTest', {
//       pipelineName: 'AppPipeline',
//       restartExecutionOnUpdate: true
//     });

//     const outputSources = new codepipeline.Artifact()
//     const outputBuild = new codepipeline.Artifact()
  
//     // Get source to trigger pipeline
//     pipeline.addStage({
//       stageName: 'Source',
//       actions: [
//         new codepipeline_actions.GitHubSourceAction({
//           actionName: 'Checkout',
//           owner: 'BenassiJosef',
//           repo: 'api-backend-php',
//           branch:"main",
//           oauthToken: cdk.SecretValue.secretsManager('github-token'),
//           output: outputSources ,
//           trigger: codepipeline_actions.GitHubTrigger.WEBHOOK,
//         }),
//       ],
//     })

//     //Build Docker File to obtain container image for fargate serve. store in outputbuild.
//     pipeline.addStage({
//       stageName: 'Build',
//       actions: [
//         // AWS CodePipeline action to run CodeBuild project
//         new codepipeline_actions.CodeBuildAction({
//           actionName: 'BuildPhpApp',
//           project: new codebuild.PipelineProject(this, 'PhpPipelineProject', {
//             projectName: 'PhpPipeline',
//             environment: {
//               buildImage: codebuild.LinuxBuildImage.AMAZON_LINUX_2_2,
//               privileged: true
//             },
//             role: ecrCodeBuildRole ,
//             environmentVariables: {
//               'ECR_REPO_URI': {
//                 value: `${ecrRepo.repositoryUri}`
//               }
//             },
//             buildSpec: codebuild.BuildSpec.fromObject({
//               version: "0.2",
//               phases: {
//                 pre_build: {
//                   commands: [
//                     'echo Logging in to Amazon ECR...',
//                     'aws --version',
//                     '$(aws ecr get-login --region eu-west-1 --no-include-email)',
//                     'COMMIT_HASH=$(echo $CODEBUILD_RESOLVED_SOURCE_VERSION | cut -c 1-7)',
//                     'IMAGE_TAG=${COMMIT_HASH:=latest}',
//                     'docker login -u josefbenassi -p dogcatpig100.'
//                   ]
//                 },
//                 build: {
//                   commands: [
//                     'echo Build started on `date` ',
//                     'echo Building the Docker image...',
//                     'docker build -t $ECR_REPO_URI:latest .',
//                     'docker tag $ECR_REPO_URI:latest $ECR_REPO_URI:$IMAGE_TAG'
//                   ]
//                 },
//                 post_build: {
//                   commands: [
//                     'echo Build completed on `date`',
//                     'echo Pushing the Docker images...',
//                     'docker push $ECR_REPO_URI:latest',
//                     'docker push $ECR_REPO_URI:$IMAGE_TAG',
//                     "printf '[{\"name\":\"%s\",\"imageUri\":\"%s\"}]' php-application-test $ECR_REPO_URI:$IMAGE_TAG > imagedefinitions.json",
//                     "pwd; ls -al; cat imagedefinitions.json"
//                   ]
//                 }
//               },
//               artifacts: {
//                 files: [
//                   'imagedefinitions.json'
//                 ]
//               }
//             }),
//           }),
//           input: outputSources,
//           outputs: [outputBuild],
//         }),
//       ],
//     })
//     pipeline.addStage({
//       stageName: 'Deploy',
//       actions: [
//         // AWS CodePipeline action to deploy node app to ecs fargate
//         new codepipeline_actions.EcsDeployAction({
//           actionName: 'DeployAction',
//           service: props.stageFargateService ,
//           imageFile: new codepipeline.ArtifactPath(outputBuild , `imagedefinitions.json`)
//         })
//       ],
//     })    
  
//   }
// }
