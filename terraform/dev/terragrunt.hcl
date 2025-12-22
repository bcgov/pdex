terraform {
  source = "./"
}

include {
  path = find_in_parent_folders()
}

generate "dev_tfvars" {
  path              = "dev.auto.tfvars"
  if_exists         = "overwrite"
  disable_signature = true
  contents          = <<-EOF
    cloudfront = true
  EOF
}

# CloudFront cert must be in us-east-1
provider "aws" {
  alias  = "use1"
  region = "us-east-1"
}

# Find an existing ACM cert for dev.pdex.gov.bc.ca in us-east-1
data "aws_acm_certificate" "cloudfront_cert" {
  provider    = aws.use1
  domain      = "dev.pdex.gov.bc.ca"
  statuses    = ["ISSUED"]
  most_recent = true
}