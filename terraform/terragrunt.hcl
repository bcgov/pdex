locals {
  project          = get_env("LICENSE_PLATE")
  environment      = reverse(split("/", get_terragrunt_dir()))[0]
}
/*
generate "remote_state" {
  path      = "backend.tf"
  if_exists = "overwrite"
  contents  = <<EOF
terraform {
  backend "s3" {
    bucket = "terraform-remote-state-${local.project}-${local.environment}"
    key = "pdex-infra.tfstate"
    region = "ca-central-1"
    encrypt = true
	use_lockfile = true # enable native S3 locking
  }
}
EOF
}
*/
/*
generate "tfvars" {
  path              = "terragrunt.auto.tfvars"
  if_exists         = "overwrite"
  disable_signature = true
  contents          = <<-EOF
#  app_image = "${local.app_image}"
#  app_repo = "${local.app_repo}"
EOF
}*/

generate "provider" {
  path      = "provider.tf"
  if_exists = "overwrite"
  contents  = <<EOF
  provider "aws" {
    region  = var.aws_region
  }
  
  # CloudFront requires certificates in us-east-1
  provider "aws" {
    alias  = "us_east_1"
    region = "us-east-1"
  }
EOF
}

inputs = {
  vpc_name = "${upper(substr(local.environment, 0, 1))}${substr(local.environment, 1, -1)}"
}
