# Without this, Terraform can pull an older Helm provider that doesn’t support that block style
terraform {
  required_providers {
    aws = {
      source = "hashicorp/aws"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = ">= 2.20.0"
    }
    helm = {
      source  = "hashicorp/helm"
      version = ">= 2.10.0"
    }
  }
}
