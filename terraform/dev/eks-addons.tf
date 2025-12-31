# Grant GitHub Actions role access to the EKS cluster
resource "aws_eks_access_entry" "github_actions" {
  cluster_name  = aws_eks_cluster.pdex-cluster.name
  principal_arn = var.github_actions_role_arn
  type          = "STANDARD"
}

resource "aws_eks_access_policy_association" "github_actions_admin" {
  cluster_name  = aws_eks_cluster.pdex-cluster.name
  principal_arn = var.github_actions_role_arn
  policy_arn    = "arn:aws:eks::aws:cluster-access-policy/AmazonEKSClusterAdminPolicy"

  access_scope {
    type = "cluster"
  }

  depends_on = [aws_eks_access_entry.github_actions]
}

# Helm provider for installing Secrets Store CSI Driver
terraform {
  required_providers {
    helm = {
      source  = "hashicorp/helm"
      version = "~> 2.12"
    }
    kubernetes = {
      source  = "hashicorp/kubernetes"
      version = "~> 2.25"
    }
  }
}

provider "helm" {
  kubernetes {
    host                   = aws_eks_cluster.pdex-cluster.endpoint
    cluster_ca_certificate = base64decode(aws_eks_cluster.pdex-cluster.certificate_authority[0].data)
    exec {
      api_version = "client.authentication.k8s.io/v1beta1"
      command     = "aws"
      args = [
        "eks",
        "get-token",
        "--cluster-name",
        aws_eks_cluster.pdex-cluster.name,
        "--region",
        var.aws_region
      ]
    }
  }
}

provider "kubernetes" {
  host                   = aws_eks_cluster.pdex-cluster.endpoint
  cluster_ca_certificate = base64decode(aws_eks_cluster.pdex-cluster.certificate_authority[0].data)
  exec {
    api_version = "client.authentication.k8s.io/v1beta1"
    command     = "aws"
    args = [
      "eks",
      "get-token",
      "--cluster-name",
      aws_eks_cluster.pdex-cluster.name,
      "--region",
      var.aws_region
    ]
  }
}

# Install Secrets Store CSI Driver via Helm
resource "helm_release" "secrets_store_csi_driver" {
  name       = "csi-secrets-store"
  repository = "https://kubernetes-sigs.github.io/secrets-store-csi-driver/charts"
  chart      = "secrets-store-csi-driver"
  namespace  = "kube-system"
  version    = "1.4.6"

  set {
    name  = "syncSecret.enabled"
    value = "true"
  }

  set {
    name  = "enableSecretRotation"
    value = "true"
  }

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_node_group.eks-ng
  ]
}
# Install AWS Provider for Secrets Store CSI Driver via Helm
resource "helm_release" "secrets_store_csi_driver_aws_provider" {
  name       = "secrets-store-csi-driver-provider-aws"
  repository = "https://aws.github.io/secrets-store-csi-driver-provider-aws"
  chart      = "secrets-store-csi-driver-provider-aws"
  namespace  = "kube-system"
  version    = "0.5.0"

  depends_on = [helm_release.secrets_store_csi_driver]
}
