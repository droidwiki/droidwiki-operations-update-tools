import * as cdk from '@aws-cdk/core';
import {Effect, Group, ManagedPolicy, PolicyStatement, User} from "@aws-cdk/aws-iam";

export class DockerLogsStack extends cdk.Stack {
    constructor(scope: cdk.Construct, id: string, props?: cdk.StackProps) {
        super(scope, id, props);

        this.iamPermissions();
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
        const dockerLogsPolicy = new PolicyStatement({
            effect: Effect.ALLOW,
            actions: [
                'logs:CreateLogStream',
                'logs:PutLogEvents',
            ],
            resources: ['*']
        });
        return new ManagedPolicy(this, 'docker-awslogs', {
            statements: [dockerLogsPolicy],
        });
    }
}
