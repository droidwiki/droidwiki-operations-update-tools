import * as cdk from '@aws-cdk/core';
import {RemovalPolicy} from '@aws-cdk/core';
import {Effect, Group, ManagedPolicy, PolicyStatement, User} from "@aws-cdk/aws-iam";
import {LogGroup, RetentionDays} from "@aws-cdk/aws-logs";

export class DockerLogsStack extends cdk.Stack {
    constructor(scope: cdk.Construct, id: string, props?: cdk.StackProps) {
        super(scope, id, props);

        this.iamPermissions();
        new LogGroup(this, 'frontend-proxy', {
            logGroupName: '/docker/frontend-proxy',
            removalPolicy: RemovalPolicy.DESTROY,
            retention: RetentionDays.ONE_WEEK
        });
    }

    private iamPermissions() {
        let iamPath = '/docker-logs/';
        const dockerLogsGroup = new Group(this, 'backups-ingest', {
            path: iamPath
        });
        new User(this, 'docker-logs/droidwiki-infra', {
            path: iamPath,
            groups: [dockerLogsGroup]
        });
        dockerLogsGroup.addManagedPolicy(this.dockerLogsPolicy());
    }

    private dockerLogsPolicy() {
        const createLogStream = new PolicyStatement({
            effect: Effect.ALLOW,
            actions: [
                'logs:CreateLogStream',
            ],
            resources: ['arn:aws:logs:${AWS::Region}:${AWS::AccountId}:log-group:log-group:/docker/*']
        });
        const putLogEvent = new PolicyStatement({
            effect: Effect.ALLOW,
            actions: [
                'logs:PutLogEvents',
            ],
            resources: ['arn:aws:logs:${AWS::Region}:${AWS::AccountId}:log-group:log-group:/docker/*:log-stream:*']
        });
        return new ManagedPolicy(this, 'docker-awslogs', {
            statements: [createLogStream, putLogEvent],
        });
    }
}
