# variables.tf

variable "target_env" {
  description = "AWS workload account env (e.g. dev, test, prod, sandbox, unclass)"
  default = "dev"
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
  default = "Dev"
}

variable "common_tags" {
  description = "Common tags for created resources"
  default = {
    Application = "pdex.gov.bc.ca"
  }
}

variable "cloudfront" {
  description = "enable or disable the cloudfront distribution creation"
  type        = bool
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
  description = "ARN of the Secrets Manager secret containing RDS credentials"
  type        = string
  default     = "pdex-rds-creds"
}

variable "pdex_rds_envpref_secret_arn" {
  description = "ARN of the environment-prefixed Secrets Manager secret containing RDS credentials"
  type        = string
  default     = "secrets.env-bV4LPz"
}

variable "pdex_opensearch_secret_arn" {
  description = "ARN of the Secrets Manager secret containing OpenSearch master credentials"
  type        = string
  default     = "pdex-opensearch-creds"
}
