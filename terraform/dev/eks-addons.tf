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

# Install AWS Secrets Manager Provider
resource "kubernetes_manifest" "aws_secrets_provider_sa" {
  manifest = {
    apiVersion = "v1"
    kind       = "ServiceAccount"
    metadata = {
      name      = "csi-secrets-store-provider-aws"
      namespace = "kube-system"
    }
  }

  depends_on = [helm_release.secrets_store_csi_driver]
}

resource "kubernetes_manifest" "aws_secrets_provider_cluster_role" {
  manifest = {
    apiVersion = "rbac.authorization.k8s.io/v1"
    kind       = "ClusterRole"
    metadata = {
      name = "csi-secrets-store-provider-aws-cluster-role"
    }
    rules = [
      {
        apiGroups = [""]
        resources = ["serviceaccounts/token"]
        verbs     = ["create"]
      },
      {
        apiGroups = [""]
        resources = ["serviceaccounts"]
        verbs     = ["get"]
      },
      {
        apiGroups = [""]
        resources = ["pods"]
        verbs     = ["get"]
      },
      {
        apiGroups = [""]
        resources = ["nodes"]
        verbs     = ["get"]
      }
    ]
  }

  depends_on = [helm_release.secrets_store_csi_driver]
}

resource "kubernetes_manifest" "aws_secrets_provider_cluster_role_binding" {
  manifest = {
    apiVersion = "rbac.authorization.k8s.io/v1"
    kind       = "ClusterRoleBinding"
    metadata = {
      name = "csi-secrets-store-provider-aws-cluster-rolebinding"
    }
    roleRef = {
      apiGroup = "rbac.authorization.k8s.io"
      kind     = "ClusterRole"
      name     = "csi-secrets-store-provider-aws-cluster-role"
    }
    subjects = [
      {
        kind      = "ServiceAccount"
        name      = "csi-secrets-store-provider-aws"
        namespace = "kube-system"
      }
    ]
  }

  depends_on = [
    kubernetes_manifest.aws_secrets_provider_sa,
    kubernetes_manifest.aws_secrets_provider_cluster_role
  ]
}

resource "kubernetes_manifest" "aws_secrets_provider_daemonset" {
  manifest = {
    apiVersion = "apps/v1"
    kind       = "DaemonSet"
    metadata = {
      name      = "csi-secrets-store-provider-aws"
      namespace = "kube-system"
      labels = {
        app = "csi-secrets-store-provider-aws"
      }
    }
    spec = {
      updateStrategy = {
        type = "RollingUpdate"
      }
      selector = {
        matchLabels = {
          app = "csi-secrets-store-provider-aws"
        }
      }
      template = {
        metadata = {
          labels = {
            app = "csi-secrets-store-provider-aws"
          }
        }
        spec = {
          serviceAccountName = "csi-secrets-store-provider-aws"
          hostNetwork        = true
          containers = [
            {
              name            = "provider-aws-installer"
              image           = "public.ecr.aws/aws-secrets-manager/secrets-store-csi-driver-provider-aws:1.0.r2-50-g5b4aca1-2023.06.09.21.19"
              imagePullPolicy = "Always"
              args = [
                "--provider-volume=/etc/kubernetes/secrets-store-csi-providers"
              ]
              resources = {
                requests = {
                  cpu    = "50m"
                  memory = "100Mi"
                }
                limits = {
                  cpu    = "50m"
                  memory = "100Mi"
                }
              }
              volumeMounts = [
                {
                  mountPath = "/etc/kubernetes/secrets-store-csi-providers"
                  name      = "providervol"
                },
                {
                  mountPath = "/var/run/secrets/pods.eks.amazonaws.com"
                  name      = "token"
                }
              ]
            }
          ]
          volumes = [
            {
              name = "providervol"
              hostPath = {
                path = "/etc/kubernetes/secrets-store-csi-providers"
              }
            },
            {
              name = "token"
              projected = {
                sources = [
                  {
                    serviceAccountToken = {
                      path              = "serviceaccount/token"
                      expirationSeconds = 86400
                      audience          = "sts.amazonaws.com"
                    }
                  }
                ]
              }
            }
          ]
          nodeSelector = {
            "kubernetes.io/os" = "linux"
          }
        }
      }
    }
  }

  depends_on = [
    kubernetes_manifest.aws_secrets_provider_cluster_role_binding
  ]
}
