data "aws_eks_cluster" "this" {
  name = aws_eks_cluster.pdex-cluster.name
}

data "aws_eks_cluster_auth" "this" {
  name = aws_eks_cluster.pdex-cluster.name
}

provider "kubernetes" {
  host                   = data.aws_eks_cluster.this.endpoint
  cluster_ca_certificate = base64decode(data.aws_eks_cluster.this.certificate_authority[0].data)
  token                  = data.aws_eks_cluster_auth.this.token
}

provider "helm" {
  kubernetes = {
    host                   = data.aws_eks_cluster.this.endpoint
    cluster_ca_certificate = base64decode(data.aws_eks_cluster.this.certificate_authority[0].data)
    token                  = data.aws_eks_cluster_auth.this.token
  }
}


resource "helm_release" "aws_load_balancer_controller" {
  name       = "aws-load-balancer-controller"
  namespace  = "kube-system"
  repository = "https://aws.github.io/eks-charts"
  chart      = "aws-load-balancer-controller"
  version    = "1.7.2"
  wait       = true
  timeout    = 600

  set = [
    {
      name  = "clusterName"
      value = aws_eks_cluster.pdex-cluster.name
    },
    {
      name  = "region"
      value = var.aws_region
    },
    {
      name  = "vpcId"
      value = data.aws_vpc.main.id
    },
    {
      name  = "serviceAccount.name"
      value = "aws-load-balancer-controller"
    }
  ]

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_iam_role_policy_attachment.alb_attachment
  ]
}

resource "time_sleep" "wait_for_alb_controller_crds" {
  create_duration = "30s"
  depends_on      = [helm_release.aws_load_balancer_controller]
}

# need metrics server for HPA to work
resource "helm_release" "metrics_server" {
  name       = "metrics-server"
  namespace  = "kube-system"
  repository = "https://kubernetes-sigs.github.io/metrics-server/"
  chart      = "metrics-server"
  version    = "3.12.1" # pick a stable version; you can bump later
  wait       = true
  timeout    = 600

  # EKS commonly needs these kubelet flags to avoid metrics not available
  set = [
    {
      name  = "args[0]"
      value = "--kubelet-insecure-tls"
    },
    {
      name  = "args[1]"
      value = "--kubelet-preferred-address-types=InternalIP\\,ExternalIP\\,Hostname"
    }
  ]

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_addon.coredns-addon
  ]
}

# need VPA for vertical pod autoscaling, this is set for Recommendation only mode
# resource "helm_release" "vpa" {
#   name       = "vpa"
#   namespace  = "kube-system"
#   repository = "https://charts.fairwinds.com/stable"
#   chart      = "vpa"
#   version    = "4.5.0"
#   wait       = true
#   timeout    = 600

#   depends_on = [
#     aws_eks_cluster.pdex-cluster,
#     aws_eks_addon.coredns-addon
#   ]
# }

# Cluster Autoscaler Helm Release to see nodes scaling beyond desired count and up to max count
resource "helm_release" "cluster_autoscaler" {
  name       = "cluster-autoscaler"
  namespace  = "kube-system"
  repository = "https://kubernetes.github.io/autoscaler"
  chart      = "cluster-autoscaler"
  version    = "9.37.0"
  wait       = true
  timeout    = 600

  set = [
    {
      name  = "autoDiscovery.clusterName"
      value = aws_eks_cluster.pdex-cluster.name
    },
    {
      name  = "awsRegion"
      value = var.aws_region
    },
    {
      name  = "rbac.serviceAccount.name"
      value = "cluster-autoscaler"
    }
  ]

  depends_on = [
    aws_eks_cluster.pdex-cluster,
    aws_eks_pod_identity_association.cluster_autoscaler
  ]
}