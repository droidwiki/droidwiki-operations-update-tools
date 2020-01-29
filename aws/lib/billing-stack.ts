import * as cdk from "@aws-cdk/core";
import {Group, ManagedPolicy, User} from "@aws-cdk/aws-iam";

export class BillingStack extends cdk.Stack {
    constructor(scope: cdk.Construct, id: string, props?: cdk.StackProps) {
        super(scope, id, props);

        new Group(this, 'billing-admins', {
            path: '/billing/',
            managedPolicies: [ManagedPolicy.fromAwsManagedPolicyName('job-function/Billing')]
        });
    }
}
