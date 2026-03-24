# variables.tf

variable "target_env" {
  description = "AWS workload account env (e.g. dev, test, prod, sandbox, unclass)"
  default = "prod"
}

variable "aws_region" {
  description = "The AWS region things are created in"
  default     = "ca-central-1"
}

variable "vpc_id" {
  type = string
  description = "VPC ID to deploy resources into"
}

variable "environment_name" {
  type = string
  description = "Environment name for subnet naming (e.g., Dev, Test, Prod)"
  default = "Prod"
}

variable "common_tags" {
  description = "Common tags for created resources"
  default = {
    Application = "pdex.gov.bc.ca"
  }
}

variable "create_target_group_binding" {
  description = "Set to true to create the TargetGroupBinding (requires EKS cluster and ALB controller to be ready)"
  type        = bool
  default     = false
}

variable "cloudfront" {
  description = "enable or disable the cloudfront distribution creation"
  type        = bool
  default     = true
}

variable "cloudfront_origin_domain" {
  description = "domain name of the app for CloudFront origin"
  type        = string
  default     = ""
}

variable "certificate_arn" {
  description = "ARN of the ACM certificate for ALB HTTPS listener"
  type        = string
}

variable "cloudfront_certificate_arn" {
  description = "ARN of the ACM certificate for CloudFront (must be in us-east-1)"
  type        = string
  default     = ""
}

variable "source_token" {
  description = "Source token for PDEX header validation"
  type        = string
  sensitive   = true
}

variable "github_actions_role_arn" {
  description = "ARN of the GitHub Actions IAM role for EKS access"
  type        = string
}
variable "pdex_rds_secret_arn" {
  description = "Exact ARN of the RDS credentials secret (no wildcard)"
  type        = string
  default     = "arn:aws:secretsmanager:ca-central-1:868987904026:secret:pdex-rds-creds-1zd8ku"
}

variable "pdex_rds_envpref_secret_arn" {
  description = "Exact ARN of the env-prefixed RDS credentials secret (no wildcard)"
  type        = string
  default     = "arn:aws:secretsmanager:ca-central-1:868987904026:secret:secrets.env-YejdSZ-pdex-rds-creds-KrsR0B"
}


variable "pdex_opensearch_secret_arn" {
  description = "ARN of the Secrets Manager secret containing OpenSearch master credentials"
  type        = string
  default     = "arn:aws:secretsmanager:ca-central-1:868987904026:secret:pdex-opensearch-creds"
}

variable "pdex_rds_secret_arn_wildcard" {
  description = "Wildcard ARN for IAM policy to match the RDS creds secret"
  type        = string
  default     = "arn:aws:secretsmanager:ca-central-1:868987904026:secret:pdex-rds-creds-*"
}

variable "pdex_rds_envpref_secret_arn_wildcard" {
  description = "Wildcard ARN for IAM policy to match env-prefixed RDS creds secrets"
  type        = string
  default     = "arn:aws:secretsmanager:ca-central-1:868987904026:secret:secrets.env-YejdSZ-*"
}
