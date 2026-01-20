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
}



variable "common_tags" {
  description = "Common tags for created resources"
  default = {
    Application = "pdex.gov.bc.ca"
  }
}



variable "cloudfront" {
  description = "enable or disable the cloudfront distrabution creation"
  type        = bool
}

variable "source_token" {
  type      = string
  sensitive = true
}


