# Import existing AWS resources into Terraform state
# Run this from the terraform/dev directory after running 'terragrunt init'

$ErrorActionPreference = "Continue"
Set-Location $PSScriptRoot

# Security Groups
terraform import aws_security_group.alb_sg sg-0614e4549c9a369f7
terraform import aws_security_group.allow_postgres sg-0f7ca813c3674f8e4
terraform import aws_security_group.allow_redis sg-0dabaaf22d619e8e3
terraform import aws_security_group.allow_tls sg-0975c8b04dd4ff0df
terraform import aws_security_group.allow_nfs sg-01a8ba1622fb3ef65

# Get Target Group ARNs
$CER_TG_ARN = aws elbv2 describe-target-groups --names cer-target-group --query "TargetGroups[0].TargetGroupArn" --output text --region ca-central-1 2>$null
$CDQ_TG_ARN = aws elbv2 describe-target-groups --names cdq-target-group --query "TargetGroups[0].TargetGroupArn" --output text --region ca-central-1 2>$null
$PDEX_TG_ARN = aws elbv2 describe-target-groups --names pdex-target-group --query "TargetGroups[0].TargetGroupArn" --output text --region ca-central-1 2>$null

# Target Groups
if ($CER_TG_ARN) { terraform import aws_alb_target_group.cer $CER_TG_ARN }
if ($CDQ_TG_ARN) { terraform import aws_alb_target_group.cdq $CDQ_TG_ARN }
if ($PDEX_TG_ARN) { terraform import aws_alb_target_group.pdex $PDEX_TG_ARN }

# ALB
$ALB_ARN = aws elbv2 describe-load-balancers --names default --query "LoadBalancers[0].LoadBalancerArn" --output text --region ca-central-1 2>$null
if ($ALB_ARN) { terraform import aws_lb.default_alb $ALB_ARN }

# RDS
terraform import aws_db_subnet_group.data_subnet data-subnet
terraform import aws_rds_cluster.postgres-pdex pdex-postgres-cluster
$RDS_INSTANCE = aws rds describe-db-clusters --db-cluster-identifier pdex-postgres-cluster --query "DBClusters[0].DBClusterMembers[0].DBInstanceIdentifier" --output text --region ca-central-1 2>$null
if ($RDS_INSTANCE) { terraform import aws_rds_cluster_instance.postgres-pdex "pdex-postgres-cluster:$RDS_INSTANCE" }

# ElastiCache
terraform import aws_elasticache_subnet_group.default redis-subnet-group-drupal
terraform import aws_elasticache_replication_group.pdex_redis_rg pdex-rep-group
terraform import 'aws_elasticache_cluster.replica[0]' pdex-rep-group-0

# EFS
$EFS_ID = aws efs describe-file-systems --query "FileSystems[?Name=='pdex-cer-efs'].FileSystemId" --output text --region ca-central-1 2>$null
if ($EFS_ID) {
    terraform import aws_efs_file_system.pdex-cer $EFS_ID
    $MT_IDS = @(aws efs describe-mount-targets --file-system-id $EFS_ID --query "MountTargets[*].MountTargetId" --output text --region ca-central-1 2>$null -split '\s+')
    if ($MT_IDS.Count -ge 1) { terraform import aws_efs_mount_target.data_azA $MT_IDS[0] }
    if ($MT_IDS.Count -ge 2) { terraform import aws_efs_mount_target.data_azB $MT_IDS[1] }
}

# KMS
terraform import aws_kms_key.pdex-kms-key 43934437-94a9-4668-bfbf-b5394622743a

# OpenSearch
terraform import aws_elasticsearch_domain.pdex-jb-cluster pdex-jb-cluster
$ACCOUNT_ID = aws sts get-caller-identity --query Account --output text
terraform import aws_iam_service_linked_role.es "arn:aws:iam::${ACCOUNT_ID}:role/aws-service-role/es.amazonaws.com/AWSServiceRoleForAmazonElasticsearchService"

# IAM Roles
terraform import aws_iam_role.eks-cluster-role eks-cluster-role
terraform import aws_iam_role.efs-csi-role efs-csi-role
terraform import aws_iam_role.eks-ng-role eks-ng-role
terraform import aws_iam_role.cluster_auto_scaler_role cluster_auto_scaler_role
terraform import aws_iam_role.ses_mailer_role ses_mailer_role
terraform import aws_iam_role.alb_role AWSLoadBalancerControllerIAMRole
terraform import aws_iam_policy.alb_policy "arn:aws:iam::${ACCOUNT_ID}:policy/AWSLoadBalancerControllerIAMPolicy"

Write-Host "Import complete! Run 'terragrunt plan' to verify." -ForegroundColor Green
