import * as cdk from '@aws-cdk/core';
import {Duration} from '@aws-cdk/core';
import {BlockPublicAccess, Bucket, BucketAccessControl, BucketEncryption, IBucket, StorageClass} from "@aws-cdk/aws-s3";
import {Effect, Group, ManagedPolicy, PolicyStatement, User} from "@aws-cdk/aws-iam";

export class BackupStack extends cdk.Stack {
    constructor(scope: cdk.Construct, id: string, props?: cdk.StackProps) {
        super(scope, id, props);

        this.iamPermissions(this.backupsBucket());
    }

    private backupsBucket() {
        return new Bucket(this, 'backups', {
            bucketName: 'droidwiki-backups',
            encryption: BucketEncryption.S3_MANAGED,
            accessControl: BucketAccessControl.PRIVATE,
            blockPublicAccess: BlockPublicAccess.BLOCK_ALL,
            lifecycleRules: [{
                expiration: Duration.days(7)
            }],
        });
    }

    private iamPermissions(bucket: Bucket) {
        let iamPath = '/backups/';
        const s3SyncGroup = new Group(this, 'backups-ingest', {
            path: iamPath
        });
        new User(this, 'backups-ingest/droidwiki-infra', {
            path: iamPath,
            groups: [s3SyncGroup]
        });
        s3SyncGroup.addManagedPolicy(this.s3SyncPolicy(bucket));
    }

    private s3SyncPolicy(bucket: IBucket) {
        const s3SyncPolicy = new PolicyStatement({
            effect: Effect.ALLOW,
            actions: [
                's3:PutObject',
                's3:GetObject',
                's3:DeleteObject',
                's3:ListBucket',
                's3:GetBucketLocation',
            ],
            resources: [
                bucket.bucketArn + '/*'
            ]
        });
        return new ManagedPolicy(this, 'backups-write-policy', {
            statements: [s3SyncPolicy],
        });
    }
}
