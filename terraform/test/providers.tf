# Default provider for ca-central-1
provider "aws" {
  region = var.aws_region
}

# Provider for us-east-1 (required for CloudFront WAF)
provider "aws" {
  alias  = "us-east-1"
  region = "us-east-1"
}
