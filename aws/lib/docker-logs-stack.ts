import * as cdk from '@aws-cdk/core';
import {Fn, RemovalPolicy} from '@aws-cdk/core';
import {Effect, Group, ManagedPolicy, PolicyStatement, User} from "@aws-cdk/aws-iam";
import {LogGroup, RetentionDays} from "@aws-cdk/aws-logs";

export class DockerLogsStack extends cdk.Stack {
    constructor(scope: cdk.Construct, id: string, props?: cdk.StackProps) {
        super(scope, id, props);

        this.iamPermissions();
        this.createLogGroup('frontend-proxy');
        this.createLogGroup('php');
        this.createLogGroup('jobrunner');
        this.createLogGroup('cache');
        this.createLogGroup('memcached');
        this.createLogGroup('redis');
        this.createLogGroup('thumbor');
        this.createLogGroup('citoid');
        this.createLogGroup('zotero');
        this.createLogGroup('parsoid');
        this.createLogGroup('restbase');
    }

    private createLogGroup(name: string, retention: RetentionDays = RetentionDays.ONE_WEEK) {
        new LogGroup(this, name, {
            logGroupName: `/docker/${name}`,
            removalPolicy: RemovalPolicy.DESTROY,
            retention
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
            resources: [Fn.sub('arn:aws:logs:${AWS::Region}:${AWS::AccountId}:log-group:/docker/*')]
        });
        const putLogEvent = new PolicyStatement({
            effect: Effect.ALLOW,
            actions: [
                'logs:PutLogEvents',
            ],
            resources: [Fn.sub('arn:aws:logs:${AWS::Region}:${AWS::AccountId}:log-group:/docker/*:log-stream:*')]
        });
        return new ManagedPolicy(this, 'docker-awslogs', {
            statements: [createLogStream, putLogEvent],
        });
    }
}
