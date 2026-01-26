locals {
  environment      = reverse(split("/", get_terragrunt_dir()))[0]
  project          = "pdex"
}

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
  }
}
EOF
}
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

inputs = {
  # Don't auto-generate vpc_name, let it be passed from environment
}
