# variables.tf

variable "target_env" {
  description = "AWS workload account env (e.g. dev, test, prod, sandbox, unclass)"
  default = "dev"
}

variable "aws_region" {
  description = "The AWS region things are created in"
  default     = "ca-central-1"
}

variable "vpc_name" {
  type = string
  description = "Name of the VPC to deploy resources into"
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
