#!/bin/bash
# Import existing AWS resources into Terraform state
# Run this from the terraform/dev directory after running 'terragrunt init'

cd "$(dirname "$0")"

# Security Groups
terraform import aws_security_group.alb_sg sg-0614e4549c9a369f7
terraform import aws_security_group.allow_postgres sg-0f7ca813c3674f8e4
terraform import aws_security_group.allow_redis sg-0dabaaf22d619e8e3
terraform import aws_security_group.allow_tls sg-0975c8b04dd4ff0df
terraform import aws_security_group.allow_nfs sg-01a8ba1622fb3ef65

# Get Target Group ARNs
CER_TG_ARN=$(aws elbv2 describe-target-groups --names cer-target-group --query "TargetGroups[0].TargetGroupArn" --output text --region ca-central-1 2>/dev/null)
CDQ_TG_ARN=$(aws elbv2 describe-target-groups --names cdq-target-group --query "TargetGroups[0].TargetGroupArn" --output text --region ca-central-1 2>/dev/null)
PDEX_TG_ARN=$(aws elbv2 describe-target-groups --names pdex-target-group --query "TargetGroups[0].TargetGroupArn" --output text --region ca-central-1 2>/dev/null)

# Target Groups
if [ ! -z "$CER_TG_ARN" ]; then
  terraform import aws_alb_target_group.cer "$CER_TG_ARN"
fi
if [ ! -z "$CDQ_TG_ARN" ]; then
  terraform import aws_alb_target_group.cdq "$CDQ_TG_ARN"
fi
if [ ! -z "$PDEX_TG_ARN" ]; then
  terraform import aws_alb_target_group.pdex "$PDEX_TG_ARN"
fi

# ALB
ALB_ARN=$(aws elbv2 describe-load-balancers --names default --query "LoadBalancers[0].LoadBalancerArn" --output text --region ca-central-1 2>/dev/null)
if [ ! -z "$ALB_ARN" ]; then
  terraform import aws_lb.default_alb "$ALB_ARN"
fi

# RDS
terraform import aws_db_subnet_group.data_subnet data-subnet
terraform import aws_rds_cluster.postgres-pdex pdex-postgres-cluster
terraform import aws_rds_cluster_instance.postgres-pdex pdex-postgres-cluster:$(aws rds describe-db-clusters --db-cluster-identifier pdex-postgres-cluster --query "DBClusters[0].DBClusterMembers[0].DBInstanceIdentifier" --output text --region ca-central-1 2>/dev/null)

# ElastiCache
terraform import aws_elasticache_subnet_group.default redis-subnet-group-drupal
terraform import aws_elasticache_replication_group.pdex_redis_rg pdex-rep-group
terraform import 'aws_elasticache_cluster.replica[0]' pdex-rep-group-0

# EFS
EFS_ID=$(aws efs describe-file-systems --query "FileSystems[?Name=='pdex-cer-efs'].FileSystemId" --output text --region ca-central-1 2>/dev/null)
if [ ! -z "$EFS_ID" ]; then
  terraform import aws_efs_file_system.pdex-cer "$EFS_ID"
  # Mount targets - need to get IDs
  MT_IDS=$(aws efs describe-mount-targets --file-system-id "$EFS_ID" --query "MountTargets[*].MountTargetId" --output text --region ca-central-1 2>/dev/null)
  MT_ARRAY=($MT_IDS)
  if [ ${#MT_ARRAY[@]} -ge 1 ]; then
    terraform import aws_efs_mount_target.data_azA "${MT_ARRAY[0]}"
  fi
  if [ ${#MT_ARRAY[@]} -ge 2 ]; then
    terraform import aws_efs_mount_target.data_azB "${MT_ARRAY[1]}"
  fi
fi

# KMS
KMS_ID=$(aws kms list-keys --query "Keys[?KeyId=='43934437-94a9-4668-bfbf-b5394622743a'].KeyId" --output text --region ca-central-1 2>/dev/null)
if [ ! -z "$KMS_ID" ]; then
  terraform import aws_kms_key.pdex-kms-key "$KMS_ID"
fi

# OpenSearch
terraform import aws_elasticsearch_domain.pdex-jb-cluster pdex-jb-cluster
terraform import aws_iam_service_linked_role.es "arn:aws:iam::$(aws sts get-caller-identity --query Account --output text):role/aws-service-role/es.amazonaws.com/AWSServiceRoleForAmazonElasticsearchService"

# IAM Roles
terraform import aws_iam_role.eks-cluster-role eks-cluster-role
terraform import aws_iam_role.efs-csi-role efs-csi-role
terraform import aws_iam_role.eks-ng-role eks-ng-role
terraform import aws_iam_role.cluster_auto_scaler_role cluster_auto_scaler_role
terraform import aws_iam_role.ses_mailer_role ses_mailer_role
terraform import aws_iam_role.alb_role AWSLoadBalancerControllerIAMRole
terraform import aws_iam_policy.alb_policy "arn:aws:iam::$(aws sts get-caller-identity --query Account --output text):policy/AWSLoadBalancerControllerIAMPolicy"

echo "Import complete! Run 'terragrunt plan' to verify."
